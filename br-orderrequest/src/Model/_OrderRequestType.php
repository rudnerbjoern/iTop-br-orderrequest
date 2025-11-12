<?php

/**
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


class _OrderRequestType extends cmdbAbstractObject
{

    public function OnOrderRequestTypeSetAttributesFlags(EventData $oEventData): void
    {
        \IssueLog::Info(print_r($oEventData, true));

        // Wenn Budget-Approval benötigt wird -> Feld als "mandatory" markieren (UI)
        $bReq = in_array((string)$this->Get('requires_budget_owner_approval'), ['1', 'yes', 'true'], true);
        if ($bReq) {
            $this->ForceAttributeFlags('budget_approver_id', OPT_ATT_MANDATORY);
        }
    }

    public function OnOrderRequestTypeCheckToWrite(EventData $oEventData): void
    {
        \IssueLog::Info(print_r($oEventData, true));
        // Serverseitige Validierung: wenn "yes" -> budget_approver_id muss gesetzt sein
        $bReq = in_array((string)$this->Get('requires_budget_owner_approval'), ['1', 'yes', 'true'], true);
        $iBudget = (int)($this->Get('budget_approver_id') ?: 0);
        if ($bReq && $iBudget <= 0) {
            $this->AddCheckIssue(\Dict::S('Class:OrderRequestType/Error:BudgetApproverRequired'));
        }
    }
}
