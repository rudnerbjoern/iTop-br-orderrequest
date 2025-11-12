<?php

/**
 * iTop Order Request (BANF) - Core model logic for OrderRequest
 *
 * This class adds business logic for the OrderRequest ticket:
 * - automatic dates prefill on creation
 * - attribute flags (read-only / hidden) depending on lifecycle
 * - approval defaulting based on OrderRequestType
 * - roll-up of estimated_total_cost from linked line items
 * - UI guard rails (deny submit if no line items)
 *
 * @copyright   Copyright (C) 2025 Björn Rudner
 * @license     https://www.gnu.org/licenses/agpl-3.0.en.html
 * @version     2025-11-11
 */

namespace BR\Extension\OrderRequest\Model;

use Combodo\iTop\Service\Events\EventData;
use Ticket;
use MetaModel;
use DBObjectSearch;
use DBObjectSet;
use AttributeDate;
use AttributeDateTime;


/**
 * Parent class for the generated OrderRequest class (declared via <php_parent>).
 *
 * Extends iTop Ticket with additional behavior specific to purchase Order Requests.
 */
class _OrderRequest extends Ticket
{

    /**
     * Defines the human-readable reference format for this ticket.
     * iTop will use its internal counter to produce sequential numbers.
     *
     * Example: OR-000123
     *
     * @return string The sprintf-compatible format including the "OR-" prefix.
     */
    public static function GetTicketRefFormat(): string
    {
        return 'OR-%06d';
    }


    /**
     * PrefillCreationForm
     *
     * Called by iTop before rendering the creation form (UI).
     * Here we initialize date fields if they are empty:
     *  - start_date (creation/intent timestamp)
     *  - last_update (technical timestamp, refreshed on write as well)
     *
     * @param array $aContextParam UI context (might contain caller, org, etc.)
     * @return void
     */
    public function PrefillCreationForm(&$aContextParam): void
    {
        if (empty($this->Get('start_date'))) {
            $this->Set('start_date', date(AttributeDateTime::GetInternalFormat()));
        }
        if (empty($this->Get('last_update'))) {
            $this->Set('last_update', date(AttributeDateTime::GetInternalFormat()));
        }
    }

    /**
     * EVENT_DB_SET_INITIAL_ATTRIBUTES_FLAGS
     *
     * Set one-time initial flags when the object is instantiated (before UI display).
     * - ref, start_date, last_update: read-only
     * - estimated_total_cost: hidden initially (it will be displayed later but is always RO)
     *
     * @param EventData $oEventData Event payload (not used here)
     * @return void
     */
    public function OnOrderRequestSetInitialAttributesFlags(EventData $oEventData): void
    {
        $this->ForceInitialAttributeFlags('ref', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('start_date', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('last_update', OPT_ATT_READONLY);
        $this->ForceInitialAttributeFlags('estimated_total_cost', OPT_ATT_HIDDEN);
    }


    /**
     * EVENT_DB_SET_ATTRIBUTES_FLAGS
     *
     * Enforce contextual flags each time attributes flags are computed.
     * - estimated_total_cost must always be read-only (computed)
     *
     * @param EventData $oEventData Event payload (not used here)
     * @return void
     */
    public function OnOrderRequestSetAttributesFlags(EventData $oEventData): void
    {
        $this->ForceAttributeFlags('estimated_total_cost', OPT_ATT_READONLY);
    }

    /**
     * EVENT_DB_LINKS_CHANGED
     *
     * Triggered when the linked sets change (add/update/remove line items).
     * We recompute the estimated_total_cost whenever line items are modified.
     *
     * @param EventData|null $oEventData Event payload or null (depending on origin)
     * @return void
     */
    public function OnOrderRequestLinksChanged(?EventData $oEventData): void
    {
        $this->ComputeEstimatedTotalCost();
    }

    /**
     * EVENT_DB_COMPUTE_VALUES
     *
     * Compute dynamic values before write:
     * - Auto-fill technical_approver_id from OrderRequestType.default_approver_id
     *   * only if request_type_id is set, and either:
     *     - the approver is currently empty, or
     *     - request_type_id has just changed
     *
     * Behavior:
     *   - If the selected type has a default approver, copy it into technical_approver_id
     *   - If type changed and the type has no default approver, clear the field
     *
     * @param EventData $oEventData Event payload (used to detect attribute changes)
     * @return void
     */
    public function OnOrderRequestComputeValues(EventData $oEventData)
    {
        // Skip if no request type selected
        $iTypeId = (int) $this->Get('request_type_id');
        if ($iTypeId <= 0) {
            return;
        }

        // Only set if empty OR type has just changed
        $aChanges = $this->ListChanges();
        $bTypeChanged = array_key_exists('request_type_id', $aChanges);
        $iCurrentApprover = (int) $this->Get('technical_approver_id');
        if (!$bTypeChanged && $iCurrentApprover > 0) {
            return;
        }

        // Read default approver from the selected type
        $oType = MetaModel::GetObject('OrderRequestType', $iTypeId, false);
        if (!$oType) {
            return;
        }
        $iDefault = (int) $oType->Get('default_approver_id');

        if ($iDefault > 0) {
            $this->Set('technical_approver_id', $iDefault);
        } else {
            // If the type provides no default and the type was changed, clear the field
            if ($bTypeChanged) {
                $this->Set('technical_approver_id', null);
            }
        }
    }

    /**
     * EVENT_ENUM_TRANSITIONS
     *
     * Dynamically deny transitions based on current data.
     * - While in 'draft': deny 'ev_submit' if the request has no line items.
     *
     * @param EventData $oEventData Event payload (not used here)
     * @return void
     */
    public function OnOrderRequestEnumTransitions(EventData $oEventData): void
    {
        if ($this->Get('status') == 'draft') {
            if ($this->HideSubmitIfNoItems()) {
                $this->DenyTransition('ev_submit');
            }
        }
    }

    /**
     * EVENT_DB_CHECK_TO_WRITE
     *
     * Server-side pre-write validation.
     * - When applying the 'ev_submit' stimulus, ensure at least one line item exists.
     *   If none is found, register a blocking check issue.
     *
     * @param EventData $oEventData Event payload (used to detect applied stimulus)
     * @return void
     */
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
            $this->AddCheckIssues(\Dict::S('Class:OrderRequest/Error:AtLeastOneLineItemBeforeSubmit'));
        }
    }

    /**
     * EVENT_DB_BEFORE_WRITE
     *
     * Last-minute updates before persisting the object:
     * - If line_items linked set changed, recompute estimated_total_cost
     * - Initialize start_date on the first write if empty
     * - Always refresh last_update to 'now'
     *
     * Note: This complements PrefillCreationForm (UI side) with server-side safety.
     *
     * @param EventData $oEventData Event payload (uses 'is_new' boolean flag)
     * @return void
     */
    public function OnOrderRequestBeforeWrite(EventData $oEventData): void
    {
        // If the LinkedSet 'line_items' changed, recalculate totals
        $aChanges = $this->ListChanges();
        if (array_key_exists('line_items', $aChanges)) {
            $this->ComputeEstimatedTotalCost();
        }

        // Keep date fields consistent
        $sNow = date(AttributeDateTime::GetInternalFormat());

        // Set creation date on initial write if not already set
        if ($oEventData->Get('is_new') === true && empty($this->Get('start_date'))) {
            $this->Set('start_date',  $sNow);
        }

        // Always track the last technical update timestamp
        $this->Set('last_update', $sNow);
    }

    /**
     * Helper: whether submit should be hidden/denied due to missing line items.
     *
     * @return bool True if there are no line items yet, false otherwise.
     */
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

    /**
     * Compute and update the roll-up field 'estimated_total_cost' from all linked line items.
     *
     * Behavior:
     * - If a line item's total_price_estimated is empty, compute it on the fly from quantity × unit_price_estimated.
     * - Sum all line items and store the result as the request's estimated_total_cost.
     *
     * Notes:
     * - This does not persist automatically; it runs within iTop's write pipeline or link change events.
     * - The field is enforced read-only by flags; users cannot edit it manually.
     *
     * @return void
     */
    public function ComputeEstimatedTotalCost(): void
    {
        $fSum = 0.0;

        /** @var DBObjectSet $oSet Linked set of OrderRequestLineItem */
        $oSet = $this->Get('line_items');
        while ($oItem = $oSet->Fetch()) {
            $qty   = (int)($oItem->Get('quantity') ?: 0);
            $unit  = (float)($oItem->Get('unit_price_estimated') ?: 0);
            $total = $oItem->Get('total_price_estimated');

            // Fallback calculation if the child didn't compute its own total (e.g. transient UI state)
            if ($total === null || $total === '') {
                $total = $qty * $unit;
            }
            $fSum += (float)$total;
        }

        $this->Set('estimated_total_cost', $fSum);
    }
}
