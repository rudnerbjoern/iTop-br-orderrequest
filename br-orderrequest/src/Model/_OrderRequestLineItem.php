<?php

/**
 * iTop Order Request (BANF) - Line item model logic
 *
 * This class contains the business logic for individual order request line items:
 * - Prefill of sequential line_number (1..n) within the same OrderRequest
 * - Computation of total_price_estimated (quantity × unit_price_estimated)
 * - Attribute flags (read-only) and immutability when parent OrderRequest is not in 'draft'
 * - Server-side validations (qty > 0, price >= 0, UoM required) and duplicate warnings
 * - Guard against edits and deletions once the parent OrderRequest has left 'draft'
 *
 * The generated class OrderRequestLineItem will extend this class (via <php_parent>).
 *
 * @copyright   Copyright (C) 2025 Björn Rudner
 * @license     https://www.gnu.org/licenses/agpl-3.0.en.html
 * @version     2025-11-11
 */

namespace BR\Extension\OrderRequest\Model;

use Combodo\iTop\Service\Events\EventData;
use cmdbAbstractObject;
use MetaModel;
use DBObjectSearch;
use DBObjectSet;
use DBObject;

/**
 * Parent class for the generated OrderRequestLineItem model.
 *
 * The XML binds the generated class to this parent using:
 * <php_parent><name>BR\Extension\OrderRequest\Model\_OrderRequestLineItem</name>...</php_parent>
 */
class _OrderRequestLineItem extends cmdbAbstractObject
{

    /**
     * Check if the parent OrderRequest is editable.
     *
     * Contract:
     * - When creating a new line item from a LinkedSet, the parent is usually provided
     *   by the UI context. If the parent cannot be resolved here, we *allow* edits,
     *   because the subsequent write will fail anyway if no parent is set.
     * - If a parent exists, only return true if its status is 'draft'.
     *
     * @return bool True if edits are allowed, false if the parent is not in 'draft'.
     */
    private function isParentEditable(): bool
    {
        $iOrderId = (int)($this->Get('order_request_id') ?: 0);
        if ($iOrderId <= 0) {
            // During creation via LinkedSet, the parent gets injected later by the UI.
            // Be permissive here; persistence will still validate the presence of a parent.
            return true;
        }

        /** @var \DBObject|null $oParent */
        $oParent = \MetaModel::GetObject('OrderRequest', $iOrderId, false);
        if (!$oParent) {
            // Defensive: if we cannot load the parent, do not hard-fail in flags.
            return true;
        }
        return ((string)$oParent->Get('status') === 'draft');
    }

    /**
     * Resolve the parent OrderRequest ID from the object and UI context.
     *
     * Lookup order:
     *  1) The object's own 'order_request_id' (already set?)
     *  2) Direct context parameter 'order_request_id'
     *  3) LinkedSet context via 'source_obj' (finalclass == 'OrderRequest')
     *
     * @param array $aContextParam UI context passed by iTop
     * @return int The order_request_id or 0 if not resolvable
     */
    private function resolveOrderIdFromContext(array &$aContextParam): int
    {
        // 1) From the object itself
        $iOrderId = (int)($this->Get('order_request_id') ?: 0);
        if ($iOrderId > 0) {
            return $iOrderId;
        }

        // 2) From a direct context parameter
        if (isset($aContextParam['order_request_id'])) {
            $iOrderId = (int)$aContextParam['order_request_id'];
            if ($iOrderId > 0) {
                return $iOrderId;
            }
        }

        // 3) From LinkedSet context: source_obj = OrderRequest
        if (isset($aContextParam['source_obj']) && is_object($aContextParam['source_obj'])) {
            // Avoid hard coupling: only check if it looks like a DBObject
            if ($aContextParam['source_obj'] instanceof \DBObject) {
                $sFinal = (string)$aContextParam['source_obj']->Get('finalclass');
                if ($sFinal === 'OrderRequest') {
                    $iOrderId = (int)$aContextParam['source_obj']->GetKey();
                    if ($iOrderId > 0) {
                        return $iOrderId;
                    }
                }
            }
        }

        return 0;
    }

    /**
     * Compute the next line number for a given OrderRequest:
     *  - 1 if no lines exist yet
     *  - MAX(line_number) + 1 otherwise
     *
     * Implementation note:
     * - Uses OQL iteration to determine the max value. If performance becomes a concern,
     *   this can be replaced with a direct SQL MAX() query via CMDBSource.
     *
     * @param int $iOrderId The parent OrderRequest ID
     * @return int The next sequential line number
     */
    private function getNextLineNumber(int $iOrderId): int
    {
        if ($iOrderId <= 0) {
            return 1;
        }

        // OQL fallback (simple and portable across DB engines supported by iTop)
        $oSearch = DBObjectSearch::FromOQL('SELECT OrderRequestLineItem WHERE order_request_id = :id');
        $oSet    = new DBObjectSet($oSearch, [], ['id' => $iOrderId]);

        $iMax = 0;
        while ($oItem = $oSet->Fetch()) {
            $i = (int)($oItem->Get('line_number') ?: 0);
            if ($i > $iMax) {
                $iMax = $i;
            }
        }
        return ($iMax > 0) ? $iMax + 1 : 1;
    }


    public function ComputeReceiptRollups(): void
    {
        $ordered = (int) ($this->Get('quantity') ?: 0);
        $sum = 0;

        /** @var \DBObjectSet $oSet */
        $oSet = $this->Get('receipts_list');
        while ($oRec = $oSet->Fetch()) {
            $sum += (int) ($oRec->Get('quantity') ?: 0);
        }

        $open = max(0, $ordered - $sum);

        $status = 'none';
        if ($sum === 0) {
            $status = 'none';
        } elseif ($sum < $ordered) {
            $status = 'partial';
        } elseif ($sum == $ordered) {
            $status = 'complete';
        } else {
            $status = 'over';
        }

        $this->Set('quantity_received_total', $sum);
        $this->Set('quantity_open', $open);
        $this->Set('receipt_status', $status);
    }

    /**
     * PrefillCreationForm
     *
     * Called by iTop prior to rendering the creation form (UI).
     * If 'line_number' is empty, compute the next sequential value for the same parent OrderRequest.
     *
     * @param array $aContextParam UI context passed by iTop
     * @return void
     */
    public function PrefillCreationForm(&$aContextParam): void
    {
        // Only prefill when the field is empty
        $iLine = (int)($this->Get('line_number') ?: 0);
        if ($iLine > 0) {
            return;
        }

        $iOrderId = $this->resolveOrderIdFromContext($aContextParam);
        $this->Set('line_number', $this->getNextLineNumber($iOrderId));
    }

    /**
     * EVENT_DB_COMPUTE_VALUES
     *
     * Compute transient values before write:
     * - total_price_estimated = ROUND(quantity × unit_price_estimated, 2)
     *
     * @param EventData $oEventData Event payload (not used here)
     * @return void
     */
    public function OnLineItemComputeValues(EventData $oEventData): void
    {
        $qty  = (float)($this->Get('quantity') ?: 0);
        $unit = (float)($this->Get('unit_price_estimated') ?: 0);
        $this->Set('total_price_estimated', round($qty * $unit, 2));
    }

    /**
     * EVENT_DB_SET_INITIAL_ATTRIBUTES_FLAGS
     *
     * Initial (one-time) attribute flags:
     * - Make total_price_estimated read-only from the start (it's a computed field).
     *
     * @param EventData $oEventData Event payload (not used here)
     * @return void
     */
    public function OnLineItemSetInitialAttributesFlags(EventData $oEventData): void
    {
        $this->ForceInitialAttributeFlags('total_price_estimated', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('quantity_received_total', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('quantity_open', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('receipt_status', OPT_ATT_READONLY);
    }

    /**
     * EVENT_DB_SET_ATTRIBUTES_FLAGS
     *
     * Contextual attribute flags each time the UI recomputes flags:
     * - Keep total_price_estimated read-only
     * - Once a line exists (id > 0), lock its parent link (order_request_id)
     * - If the parent OrderRequest is not 'draft', make *all* editable attributes read-only
     *
     * @param EventData $oEventData Event payload (not used here)
     * @return void
     */
    public function OnLineItemSetAttributesFlags(EventData $oEventData): void
    {
        $this->ForceAttributeFlags('total_price_estimated', OPT_ATT_READONLY);
        $this->ForceAttributeFlags('quantity_received_total', OPT_ATT_READONLY);
        $this->ForceAttributeFlags('quantity_open', OPT_ATT_READONLY);
        $this->ForceAttributeFlags('receipt_status', OPT_ATT_READONLY);

        if ((int)$this->GetKey() > 0) {
            $this->ForceAttributeFlags('order_request_id', OPT_ATT_READONLY);
        }

        // Lock everything if the parent OrderRequest is not in 'draft'
        if (!$this->isParentEditable()) {
            foreach (
                [
                    'name',
                    'vendor_sku',
                    'quantity',
                    'uom',
                    'unit_price_estimated',
                    'description',
                    'line_number',
                    'order_request_id',
                ] as $sAtt
            ) {
                $this->ForceAttributeFlags($sAtt, OPT_ATT_READONLY);
            }
            // Wichtig: 'functionalcis_list' und 'receipts_list' NICHT sperren
        }
    }


    /**
     * EVENT_DB_CHECK_TO_WRITE
     *
     * Server-side validations before write:
     * - Parent must be editable ('draft'); otherwise block the change
     * - quantity must be > 0
     * - unit_price_estimated must be >= 0 (if provided)
     * - uom is required (non-empty)
     * - duplicate warning: same parent + same name + same uom (different id)
     *
     * Notes:
     * - Duplicate check adds a *warning* (non-blocking), everything else is blocking.
     *
     * @param EventData $oEventData Event payload (not used here)
     * @return void
     */
    public function OnLineItemCheckToWrite(EventData $oEventData): void
    {

        // Welche Attribute wurden wirklich geändert?
        $aChanged = array_keys($this->ListChanges());

        // Felder, die NUR im Draft geändert werden dürfen (kommerziell/relevant)
        $aCore = [
            'name',
            'vendor_sku',
            'quantity',
            'uom',
            'unit_price_estimated',
            'total_price_estimated',
            'description',
            'line_number',
            'order_request_id',
        ];

        // Felder, die AUCH NACH Draft geändert werden dürfen (unbedenklich)
        // -> füge hier deine Receiving-Felder hinzu, falls du welche hast
        $aSafeOutsideDraft = [
            'functionalcis_list',
            'receipts_list',
            'quantity_received_total',
            'quantity_open',
            'receipt_status',
        ];

        $bCoreChange = (count(array_intersect($aChanged, $aCore)) > 0);
        $bOnlySafe   = (count(array_diff($aChanged, $aSafeOutsideDraft)) === 0);

        // Parent status guard
        if (!$this->isParentEditable() && $bCoreChange) {
            $this->AddCheckIssue(\Dict::S('Class:OrderRequestLineItem/Error:ParentNotEditable'));
            return;
        }

        // Validierungen nur durchführen, wenn Core-Felder geändert wurden ODER beim Anlegen
        $bIsNew = (bool) $oEventData->Get('is_new');
        if ($bCoreChange || $bIsNew) {
            // qty > 0
            $qty = $this->Get('quantity');
            if ($qty === null || $qty === '' || (float)$qty <= 0) {
                $this->AddCheckIssue(\Dict::S('Class:OrderRequestLineItem/Error:QtyMustBePositive'));
            }

            // unit price >= 0
            $price = $this->Get('unit_price_estimated');
            if ($price !== null && $price !== '' && (float)$price < 0) {
                $this->AddCheckIssue(\Dict::S('Class:OrderRequestLineItem/Error:UnitPriceNegative'));
            }

            // UoM required
            $uom = (string)$this->Get('uom');
            if ($uom === '') {
                $this->AddCheckIssue(\Dict::S('Class:OrderRequestLineItem/Error:UomRequired'));
            }

            // Duplicate warning (gleiches Name+UoM in gleicher Order, andere ID)
            $iOrderId = (int)$this->Get('order_request_id');
            if ($iOrderId > 0 && $uom !== '') {
                $oSearch = \DBObjectSearch::FromOQL(
                    'SELECT OrderRequestLineItem WHERE order_request_id = :id AND name = :name AND uom = :uom AND id != :me'
                );
                $oSet = new \DBObjectSet($oSearch, [], [
                    'id'   => $iOrderId,
                    'name' => (string)$this->Get('name'),
                    'uom'  => $uom,
                    'me'   => (int)$this->GetKey(),
                ]);
                if ($oSet->Count() > 0) {
                    $this->AddCheckWarning(\Dict::S('Class:OrderRequestLineItem/Warning:DuplicateNameUom'));
                }
            }
        }

        // Wenn NUR "sichere" Felder geändert wurden (z. B. functionalcis_list oder Receiving-Felder),
        // dann hier bewusst KEINE Blockade – durchfallen lassen.
    }

    /**
     * EVENT_DB_CHECK_TO_DELETE
     *
     * Prevent deletion of a line item if the parent OrderRequest is not in 'draft'.
     *
     * @param EventData $oEventData Event payload (not used here)
     * @return void
     */
    public function OnLineItemCheckToDelete(EventData $oEventData): void
    {
        // Resolve parent
        $iOrderId = (int)($this->Get('order_request_id') ?: 0);
        if ($iOrderId <= 0) {
            // Kein Parent aufgelöst -> nicht blockieren (UI/REST wird ohnehin scheitern, wenn es keinen Parent gibt)
            return;
        }

        /** @var \DBObject|null $oParent */
        $oParent = \MetaModel::GetObject('OrderRequest', $iOrderId, false);
        if (!$oParent) {
            // Parent nicht ladbar -> defensiv NICHT blockieren
            return;
        }

        $sStatus = (string)$oParent->Get('status');

        // Löschen in 'draft', 'closed' **und** 'rejected' erlauben
        if (!in_array($sStatus, ['draft', 'closed', 'rejected'], true)) {
            $this->AddDeleteIssue(\Dict::S('Class:OrderRequestLineItem/Error:ParentNotEditable'));
        }
    }

    /**
     * EVENT_DB_BEFORE_WRITE
     *
     * Safety net for non-UI writes (CSV/REST):
     * - If line_number is empty, set it to 1 or MAX+1 within the same parent OrderRequest.
     *   (The UI typically calls PrefillCreationForm; this covers programmatic writes.)
     *
     * @param EventData $oEventData Event payload (not used here)
     * @return void
     */
    public function OnLineItemBeforeWrite(EventData $oEventData): void
    {
        // Ensure line_number is set on first write
        $iLine = (int)($this->Get('line_number') ?: 0);
        if ($iLine <= 0) {
            $iOrderId = (int)($this->Get('order_request_id') ?: 0);
            $this->Set('line_number', $this->getNextLineNumber($iOrderId));
        }

        // Always recompute receiving rollups before persist
        if (method_exists($this, 'ComputeReceiptRollups')) {
            $this->ComputeReceiptRollups();
        }
    }

    public function OnLineItemLinksChanged(?EventData $oEventData): void
    {
        // Recompute after add/update/remove of linked receipts
        $this->ComputeReceiptRollups();
    }
}
