<?php

/**
 * @copyright   Copyright (C) 2025 Björn Rudner
 * @license     https://www.gnu.org/licenses/agpl-3.0.en.html
 * @version     2025-11-11
 */

namespace BR\Extension\OrderRequest\Model;

use BR\Extension\OrderRequest\Util\IsmsUtils;
use Combodo\iTop\Service\Events\EventData;
use Ticket;
use MetaModel;
use DBObjectSearch;
use DBObjectSet;
use Dict;
use ItopCounter;
use AttributeDate;
use AttributeDateTime;
use Combodo\iTop\Application\WebPage\WebPage;


class _OrderRequest extends Ticket
{
    public static function GetTicketRefFormat(): string
    {
        return 'OR-%06d';
    }

    public function PrefillCreationForm(&$aContextParam): void
    {
        if (empty($this->Get('start_date'))) {
            $this->Set('start_date', date(AttributeDateTime::GetInternalFormat()));
        }
        if (empty($this->Get('last_update'))) {
            $this->Set('last_update', date(AttributeDateTime::GetInternalFormat()));
        }
    }

    public function OnOrderRequestSetInitialAttributesFlags(EventData $oEventData): void
    {
        $this->ForceInitialAttributeFlags('ref', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('start_date', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('last_update', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('estimated_total_cost', OPT_ATT_HIDDEN);
    }

    public function OnOrderRequestSetAttributesFlags(EventData $oEventData): void
    {
        $this->ForceAttributeFlags('estimated_total_cost', OPT_ATT_READONLY);
    }

    public function OnOrderRequestLinksChanged(?EventData $oEventData): void
    {
        $this->ComputeEstimatedTotalCost();
    }

    public function OnOrderRequestComputeValues(EventData $oEventData)
    {
        // Wenn kein Typ gesetzt ist, nichts tun
        $iTypeId = (int) $this->Get('request_type_id');
        if ($iTypeId <= 0) {
            return;
        }

        // Nur setzen, wenn leer ODER Typ soeben geändert wurde
        $aChanges = $this->ListChanges();
        $bTypeChanged = array_key_exists('request_type_id', $aChanges);
        $iCurrentApprover = (int) $this->Get('technical_approver_id');
        if (!$bTypeChanged && $iCurrentApprover > 0) {
            return;
        }

        // Default-Genehmiger aus dem Typ lesen
        $oType = MetaModel::GetObject('OrderRequestType', $iTypeId, false);
        if (!$oType) {
            return;
        }
        $iDefault = (int) $oType->Get('default_approver_id');

        if ($iDefault > 0) {
            $this->Set('technical_approver_id', $iDefault);
        } else {
            // Wenn der Typ keinen Default hat und der Typ gerade geändert wurde: Feld leeren
            if ($bTypeChanged) {
                $this->Set('technical_approver_id', null);
            }
        }
    }

    public function OnOrderRequestEnumTransitions(EventData $oEventData): void
    {
        /*  if ($this->Get('status') !== 'draft') {
            return;
        }

        $iId = (int) $this->GetKey();
        $iCount = 0;
        if ($iId > 0) {
            $oSearch = DBObjectSearch::FromOQL('SELECT OrderRequestLineItem WHERE order_request_id = :id');
            $oSet = new DBObjectSet($oSearch, array(), array('id' => $iId));
            $iCount = $oSet->Count();
        }

        if ($iCount < 1) {
            $this->DenyTransition('ev_submit');
        }*/
        if ($this->Get('status') == 'draft') {
            if ($this->HideSubmitIfNoItems()) {
                $this->DenyTransition('ev_submit');
            }
        }
    }

    public function OnOrderRequestCheckToWrite(EventData $oEventData)
    {
        $sStimulus = (string) $oEventData->Get('stimulus_applied');
        if ($sStimulus !== 'ev_submit') {
            return;
        }

        $iId = (int) $this->GetKey();
        $oSearch = DBObjectSearch::FromOQL('SELECT OrderRequestLineItem WHERE order_request_id = :id');
        $oSet = new DBObjectSet($oSearch, array(), array('id' => $iId));
        $iCount = $oSet->Count();

        if ($iCount < 1) {
            $this->AddCheckIssues(Dict::S('Class:OrderRequest/Error:AtLeastOneLineItemBeforeSubmit'));
        }
    }

    public function OnOrderRequestBeforeWrite(EventData $oEventData): void
    {
        // Nur wenn die LinkedSet-Liste geändert wurde, neu summieren
        $aChanges = $this->ListChanges();
        if (array_key_exists('line_items', $aChanges)) {
            $this->ComputeEstimatedTotalCost();
        }

        $this->Set('last_update', date(AttributeDateTime::GetInternalFormat()));
    }

    public function HideSubmitIfNoItems(): bool
    {
        $iId = (int) $this->GetKey();
        $iCount = 0;
        if ($iId > 0) {
            $oSearch = DBObjectSearch::FromOQL('SELECT OrderRequestLineItem WHERE order_request_id = :id');
            $oSet = new DBObjectSet($oSearch, array(), array('id' => $iId));
            $iCount = $oSet->Count();
        }
        return ($iCount < 1);
    }

    public function ComputeEstimatedTotalCost(): void
    {
        $fSum = 0.0;
        /** @var DBObjectSet $oSet */
        $oSet = $this->Get('line_items');
        while ($oItem = $oSet->Fetch()) {
            $qty   = (int)($oItem->Get('quantity') ?: 0);
            $unit  = (float)($oItem->Get('unit_price_estimated') ?: 0);
            $total = $oItem->Get('total_price_estimated');
            if ($total === null || $total === '') {
                $total = $qty * $unit;
            }
            $fSum += (float)$total;
        }
        $this->Set('estimated_total_cost', $fSum);
    }
}
