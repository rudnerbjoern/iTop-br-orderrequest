<?php

/**
 * @copyright   Copyright (C) 2025-2026 Björn Rudner
 * @license     https://www.gnu.org/licenses/agpl-3.0.en.html
 * @version     2026-01-16
 */

namespace BR\Extension\OrderRequest\Model;

use Combodo\iTop\Service\Events\EventData;
use DBObjectSearch;
use DBObjectSet;
use MetaModel;
use cmdbAbstractObject;

/**
 * Goods receipt entry for a single OrderRequestLineItem (partial delivery).
 */
class _OrderReceiptEntry extends cmdbAbstractObject
{

    /**
     * Resolve parent OrderRequest and return its status.
     * @return array{line:\DBObject|null, order:\DBObject|null, status:string}
     */
    private function GetParentOrderAndStatus(): array
    {
        $oLine = null;
        $oOrder = null;
        $sStatus = '';
        $iLineId = (int)($this->Get('order_request_line_item_id') ?: 0);
        if ($iLineId > 0) {
            $oLine = \MetaModel::GetObject('OrderRequestLineItem', $iLineId, /*must_exist*/ false);
            if ($oLine) {
                $iOrderId = (int)$oLine->Get('order_request_id');
                if ($iOrderId > 0) {
                    $oOrder = \MetaModel::GetObject('OrderRequest', $iOrderId, /*must_exist*/ false);
                    if ($oOrder) {
                        $sStatus = (string)$oOrder->Get('status');
                    }
                }
            }
        }
        return ['line' => $oLine, 'order' => $oOrder, 'status' => $sStatus];
    }


    /**
     * Optional UI defaulting (wird von iTop beim Erstellen im UI aufgerufen):
     * - receipt_date: now
     * - received_by_id: current person
     */
    public function PrefillCreationForm(&$aContextParam): void
    {
        if (empty($this->Get('receipt_date'))) {
            $this->Set('receipt_date', date(\AttributeDateTime::GetInternalFormat()));
        }
        if (empty($this->Get('received_by_id'))) {
            try {
                $iMe = (int) \UserRights::GetContactId();
                if ($iMe > 0) {
                    $this->Set('received_by_id', $iMe);
                }
            } catch (\Throwable $e) { /* no-op */
            }
        }
    }

    /**
     * Flags: FK nach der Anlage nicht mehr ändern, Kleinkram RO machen wenn sinnvoll.
     */
    public function OnReceiptSetAttributesFlags(EventData $oEventData): void
    {
        if ((int)$this->GetKey() > 0) {
            $this->ForceAttributeFlags('order_request_line_item_id', OPT_ATT_READONLY);
        }
    }

    public function OnReceiptCheckToWrite(EventData $oEventData): void
    {
        // --- Parent status guard ---
        $ctx = $this->GetParentOrderAndStatus();
        if (!$ctx['order'] || $ctx['status'] !== 'receiving') {
            $this->AddCheckIssue(\Dict::S('Class:OrderReceiptEntry/Error:ParentNotReceiving'));
            return;
        }

        // receipt_date required (sicher, auch bei CSV/REST)
        $sDate = (string)($this->Get('receipt_date') ?? '');
        if ($sDate === '') {
            $this->AddCheckIssue(\Dict::S('Class:OrderReceiptEntry/Error:ReceiptDateRequired'));
        }

        // qty > 0
        $qty = (int)($this->Get('quantity') ?: 0);
        if ($qty <= 0) {
            $this->AddCheckIssue(\Dict::S('Class:OrderReceiptEntry/Error:QuantityMustBePositive'));
        }

        // LineItem vorhanden?
        $iLiId = (int)($this->Get('order_request_line_item_id') ?? 0);
        if ($iLiId <= 0) {
            $this->AddCheckIssue(\Dict::S('Class:OrderReceiptEntry/Error:LineItemMissing'));
            return;
        }

        $oLI = \MetaModel::GetObject('OrderRequestLineItem', $iLiId, false);
        if (!$oLI) {
            $this->AddCheckIssue(\Dict::S('Class:OrderReceiptEntry/Error:LineItemMissing'));
            return;
        }
        $iOrdered = (int)($oLI->Get('quantity') ?? 0);

        // Summe anderer Receipts
        $oSearch = \DBObjectSearch::FromOQL('SELECT OrderReceiptEntry WHERE order_request_line_item_id = :li AND id != :me');
        $oSet    = new \DBObjectSet($oSearch, [], [
            'li' => $iLiId,
            'me' => (int)$this->GetKey(),
        ]);

        $iAlready = 0;
        while ($oR = $oSet->Fetch()) {
            $iAlready += (int)($oR->Get('quantity') ?? 0);
        }

        // Überlieferung blockieren (oder als Warning ausspielen – aktuell: blockend)
        if (($iAlready + $qty) > $iOrdered) {
            $this->AddCheckIssue(\Dict::S('Class:OrderReceiptEntry/Error:OverReceive'));
        }
    }

    /**
     * After write: refresh rollups on the parent line item (total/open/status).
     */
    public function OnReceiptAfterWrite(EventData $e): void
    {
        $iLineId = (int) ($this->Get('order_request_line_item_id') ?: 0);
        if ($iLineId > 0) {
            $oLine = \MetaModel::GetObject('OrderRequestLineItem', $iLineId, false);
            if ($oLine) {
                if (method_exists($oLine, 'ComputeReceiptRollups')) {
                    $oLine->ComputeReceiptRollups();
                    $oLine->DBUpdate();
                }
            }
        }
    }

    /**
     * EVENT_DB_ABOUT_TO_DELETE
     *
     * Block deletion if the parent OrderRequest is not in 'receiving'
     * and recompute the rollups (quantity_received_total / quantity_open / receipt_status)
     * on the parent line item as if this entry was already gone.
     */
    public function OnReceiptBeforeDelete(EventData $oEventData): void
    {
        // Parent context (OrderRequestLineItem + OrderRequest + status)
        $ctx = $this->GetParentOrderAndStatus();
        $oLine   = $ctx['line'];
        $oOrder  = $ctx['order'];
        $sStatus = $ctx['status'];

        // 1) Strict: only allow delete while parent OrderRequest is in 'receiving'
        if (!$oOrder || $sStatus !== 'receiving') {
            $this->AddDeleteIssue(\Dict::S('Class:OrderReceiptEntry/Error:ParentNotReceiving'));
            return;
        }

        // 2) Recompute rollups on the parent line item *without* this entry
        if (!$oLine) {
            return;
        }

        $iLiId    = (int) ($this->Get('order_request_line_item_id') ?: 0);
        $iOrdered = (int) ($oLine->Get('quantity') ?: 0);

        if ($iLiId <= 0 || $iOrdered < 0) {
            return;
        }

        // Sum all other receipts for this line item (exclude current id)
        $oSearch = DBObjectSearch::FromOQL('SELECT OrderReceiptEntry WHERE order_request_line_item_id = :li AND id != :me');
        $oSet = new DBObjectSet($oSearch, [], [
            'li' => $iLiId,
            'me' => (int) $this->GetKey(),
        ]);

        $iSum = 0;
        while ($oR = $oSet->Fetch()) {
            $iSum += (int) ($oR->Get('quantity') ?: 0);
        }

        $iOpen   = max(0, $iOrdered - $iSum);
        $sNewRec = 'none';
        if ($iSum === 0) {
            $sNewRec = 'none';
        } elseif ($iSum < $iOrdered) {
            $sNewRec = 'partial';
        } elseif ($iSum === $iOrdered) {
            $sNewRec = 'complete';
        } else {
            $sNewRec = 'over';
        }

        $oLine->Set('quantity_received_total', $iSum);
        $oLine->Set('quantity_open', $iOpen);
        $oLine->Set('receipt_status', $sNewRec);
        $oLine->DBUpdate();
    }
}
