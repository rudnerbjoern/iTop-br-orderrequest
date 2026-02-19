<?php

/**
 * OrderRequestType – UI flags & server-side validation
 *
 * - Makes budget_approver_id mandatory in the UI when requires_budget_owner_approval is truthy.
 * - Enforces the same rule server-side during write (blocking check issue).
 *
 * Notes:
 * - "Mandatory" via flags only affects the UI; always keep the server-side check.
 * - Truthy for requires_budget_owner_approval is normalized across common representations ('1','yes','true').
 *
 * @copyright   Copyright (C) 2025-2026 Björn Rudner
 * @license     https://www.gnu.org/licenses/agpl-3.0.en.html
 * @version     2026-01-16
 */

namespace BR\Extension\OrderRequest\Model;

use Combodo\iTop\Service\Events\EventData;
use cmdbAbstractObject;
use UserRights;

class _OrderRequestType extends cmdbAbstractObject
{

    /**
     * PrefillCreationForm
     *
     * Called by iTop before rendering the creation form (UI).
     *
     * @param array $aContextParam UI context (might contain caller, org, etc.)
     * @return void
     */
    public function PrefillCreationForm(&$aContextParam): void
    {
        // Keep the standard iTop behavior first (important for compatibility)
        parent::PrefillCreationForm($aContextParam);

        // Get the currently authenticated iTop user object (may be null in some contexts)
        $oUser = UserRights::GetUserObject();
        if ($oUser !== null) {
            // Assign the user's organization to the new record
            $this->Set('org_id', (int) $oUser->Get('org_id'));
        }
    }

    /**
     * EVENT_DB_SET_ATTRIBUTES_FLAGS
     *
     * UI-only rule: if "requires_budget_owner_approval" is truthy, mark budget_approver_id as mandatory.
     * Otherwise, explicitly remove any forced mandatory flag to avoid stale UI state.
     *
     * @param EventData $oEventData Event payload (not used)
     * @return void
     */
    public function OnOrderRequestTypeSetAttributesFlags(EventData $oEventData): void
    {
        // Debug only: uncomment while diagnosing flag computation
        // \IssueLog::Info(__METHOD__.' '.print_r($oEventData, true));

        $bReq = in_array((string)$this->Get('requires_budget_owner_approval'), ['1', 'yes', 'true'], true);

        if ($bReq) {
            // Make it mandatory in the UI when budget approval is required
            $this->AddAttributeFlags('budget_approver_id', OPT_ATT_MANDATORY);
        } else {
            // Ensure we drop any previously forced mandatory flag when not required
            $this->AddAttributeFlags('budget_approver_id', 0);
        }
    }

    /**
     * EVENT_DB_CHECK_TO_WRITE
     *
     * Server-side enforcement: if "requires_budget_owner_approval" is truthy,
     * then budget_approver_id MUST be provided. Emits a blocking check issue.
     *
     * @param EventData $oEventData Event payload (not used)
     * @return void
     */
    public function OnOrderRequestTypeCheckToWrite(EventData $oEventData): void
    {
        // Debug only: uncomment while diagnosing validations
        // \IssueLog::Info(__METHOD__.' '.print_r($oEventData, true));

        $bReq = in_array((string)$this->Get('requires_budget_owner_approval'), ['1', 'yes', 'true'], true);
        $iBudget = (int)($this->Get('budget_approver_id') ?: 0);

        if ($bReq && $iBudget <= 0) {
            // Use a translated, user-facing message key from your dictionary
            $this->AddCheckIssue(\Dict::S('Class:OrderRequestType/Error:BudgetApproverRequired'));
        }
    }
}
