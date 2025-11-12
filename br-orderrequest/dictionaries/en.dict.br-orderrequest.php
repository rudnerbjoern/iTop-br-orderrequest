<?php

/**
 * English dictionary for br-orderrequest
 *
 * Covers:
 * - Menus & Dashboard labels
 * - Classes: OrderRequest, OrderRequestLineItem, OrderRequestType
 * - Attributes, statuses, stimuli
 * - Validation messages & warnings used in PHP
 *
 * Notes:
 * - Some optional keys are present for future use (eg. cost_center, expected_delivery_date).
 *   Remove them if you don't plan to add the attributes.
 *
 * @copyright   Copyright (C) 2025 Björn Rudner
 * @license     https://www.gnu.org/licenses/agpl-3.0.en.html
 * @version     2025-11-12
 */

// ─────────────────────────────────────────────────────────────────────────────
// Menus & Dashboard
// ─────────────────────────────────────────────────────────────────────────────
/** @disregard P1009 Undefined type Dict */
Dict::Add('EN US', 'English', 'English', array(
    'Menu:OrderRequest' => 'Order Requests',
    'Menu:OrderRequest+' => 'Create, track and approve internal purchase (BANF) requests.',
    'Menu:OrderRequestSpace' => 'Order Requests',
    'Menu:OrderRequestSpace+' => 'Overview and quick access',
    'Menu:OrderRequestSpace:Header' => 'Overview',
));

// ─────────────────────────────────────────────────────────────────────────────
// Class: OrderRequestType
// ─────────────────────────────────────────────────────────────────────────────
/** @disregard P1009 Undefined type Dict */
Dict::Add('EN US', 'English', 'English', array(
    'Class:OrderRequestType' => 'Order Request Type',
    'Class:OrderRequestType/Plural' => 'Order Request Types',
    'Class:OrderRequestType+' => 'Configurable request types (eg. Server, Network, Firewall) with a default approver.',

    'Class:OrderRequestType/Attribute:org_id'   => 'Organization',
    'Class:OrderRequestType/Attribute:org_id+'  => 'Owning organization.',
    'Class:OrderRequestType/Attribute:org_name' => 'Organization name',
    'Class:OrderRequestType/Attribute:org_name+' => 'Readable organization name.',

    'Class:OrderRequestType/Attribute:name'     => 'Name',
    'Class:OrderRequestType/Attribute:name+'    => 'Display name of the request type.',
    'Class:OrderRequestType/Attribute:code'     => 'Code',
    'Class:OrderRequestType/Attribute:code+'    => 'Short unique code for this type (per organization).',

    'Class:OrderRequestType/Attribute:status'   => 'Status',
    'Class:OrderRequestType/Attribute:status+'  => 'Whether the type can be selected on new requests.',
    'Class:OrderRequestType/Attribute:status/Value:active'   => 'Active',
    'Class:OrderRequestType/Attribute:status/Value:active+'  => 'Type is selectable on Order Requests.',
    'Class:OrderRequestType/Attribute:status/Value:inactive' => 'Inactive',
    'Class:OrderRequestType/Attribute:status/Value:inactive+' => 'Type cannot be selected on new Order Requests.',

    'Class:OrderRequestType/Attribute:description'   => 'Description',
    'Class:OrderRequestType/Attribute:description+'  => 'Optional functional/technical description for the type.',

    'Class:OrderRequestType/Attribute:default_approver_id'   => 'Default technical approver',
    'Class:OrderRequestType/Attribute:default_approver_id+'  => 'Person automatically suggested as technical approver.',
    'Class:OrderRequestType/Attribute:default_approver_name' => 'Default technical approver (name)',
    'Class:OrderRequestType/Attribute:default_approver_name+' => 'Readable name of the default technical approver.',

    'Class:OrderRequestType/Attribute:requires_budget_owner_approval'   => 'Requires budget owner approval',
    'Class:OrderRequestType/Attribute:requires_budget_owner_approval+'  => 'If set to "Yes", an additional budget owner approval is required.',
    'Class:OrderRequestType/Attribute:requires_budget_owner_approval/Value:yes' => 'yes',
    'Class:OrderRequestType/Attribute:requires_budget_owner_approval/Value:no'  => 'no',

    'Class:OrderRequestType/Attribute:budget_approver_id'    => 'Budget approver',
    'Class:OrderRequestType/Attribute:budget_approver_id+'   => 'Person responsible for budget approval.',
    'Class:OrderRequestType/Attribute:budget_approver_name'  => 'Budget approver (name)',
    'Class:OrderRequestType/Attribute:budget_approver_name+' => 'Readable name of the budget approver.',

    'Class:OrderRequestType/Error:BudgetApproverRequired' => 'A budget approver is required when "Requires budget owner approval" is set to Yes.',
));

// ─────────────────────────────────────────────────────────────────────────────
// Class: OrderRequest (Ticket descendant)
// ─────────────────────────────────────────────────────────────────────────────
/** @disregard P1009 Undefined type Dict */
Dict::Add('EN US', 'English', 'English', array(
    'OrderRequest:approval' => 'Approval',
    'OrderRequest:costs'    => 'Costs & procurement',

    'Class:OrderRequest' => 'Order Request',
    'Class:OrderRequest/Plural' => 'Order Requests',
    'Class:OrderRequest+' => 'Internal demand / BANF / procurement request handled as a specialized ticket.',

    'Class:OrderRequest/Attribute:status' => 'Status',
    'Class:OrderRequest/Attribute:status+' => 'Lifecycle status of the Order Request.',
    'Class:OrderRequest/Attribute:status/Value:draft'            => 'Draft',
    'Class:OrderRequest/Attribute:status/Value:draft+'           => 'Information is being collected.',
    'Class:OrderRequest/Attribute:status/Value:submitted'        => 'Submitted',
    'Class:OrderRequest/Attribute:status/Value:submitted+'       => 'Submitted for review.',
    'Class:OrderRequest/Attribute:status/Value:in_review'        => 'In review',
    'Class:OrderRequest/Attribute:status/Value:in_review+'       => 'Under internal review.',
    'Class:OrderRequest/Attribute:status/Value:waiting_approval' => 'Waiting for approval',
    'Class:OrderRequest/Attribute:status/Value:waiting_approval+' => 'Awaiting technical approval.',
    'Class:OrderRequest/Attribute:status/Value:waiting_budget_approval' => 'Waiting budget approval',
    'Class:OrderRequest/Attribute:status/Value:approved'         => 'Approved',
    'Class:OrderRequest/Attribute:status/Value:approved+'        => 'Approved by the technical approver.',
    'Class:OrderRequest/Attribute:status/Value:rejected'         => 'Rejected',
    'Class:OrderRequest/Attribute:status/Value:rejected+'        => 'Rejected by the approver.',
    'Class:OrderRequest/Attribute:status/Value:procurement'      => 'Procurement',
    'Class:OrderRequest/Attribute:status/Value:procurement+'     => 'Handed over to purchasing.',
    'Class:OrderRequest/Attribute:status/Value:closed'           => 'Closed',
    'Class:OrderRequest/Attribute:status/Value:closed+'          => 'Completed and closed.',

    'Class:OrderRequest/Attribute:request_type_id'   => 'Request type',
    'Class:OrderRequest/Attribute:request_type_id+'  => 'Type/category of the order (e.g., Server, Network, Firewall).',
    'Class:OrderRequest/Attribute:request_type_name' => 'Request type name',
    'Class:OrderRequest/Attribute:request_type_name+' => 'Readable name of the request type.',

    'Class:OrderRequest/Attribute:description' => 'Business justification',
    'Class:OrderRequest/Attribute:description+' => 'Why is this needed? Compliance, audit, business impact...',

    'Class:OrderRequest/Attribute:expected_delivery_date' => 'Expected delivery date',
    'Class:OrderRequest/Attribute:expected_delivery_date+' => 'Desired delivery/ready date.',

    'Class:OrderRequest/Attribute:estimated_total_cost'  => 'Estimated total cost',
    'Class:OrderRequest/Attribute:estimated_total_cost+' => 'Sum of estimated line totals (auto-computed).',

    'Class:OrderRequest/Attribute:technical_approver_id'   => 'Technical approver',
    'Class:OrderRequest/Attribute:technical_approver_id+'  => 'Person responsible for the technical approval.',
    'Class:OrderRequest/Attribute:technical_approver_name' => 'Technical approver name',
    'Class:OrderRequest/Attribute:technical_approver_name+' => 'Readable name of the technical approver.',

    'Class:OrderRequest/Attribute:approved_by_id'   => 'Approved by / Rejected by',
    'Class:OrderRequest/Attribute:approved_by_id+'  => 'Person who approved or rejected the request.',
    'Class:OrderRequest/Attribute:approved_by_name' => 'Approved by (name)',
    'Class:OrderRequest/Attribute:approved_by_name+' => 'Readable name of the approver/rejector.',

    'Class:OrderRequest/Attribute:approval_comment'       => 'Approval comment',
    'Class:OrderRequest/Attribute:approval_comment+'      => 'Decision rationale or constraints given by approver.',
    'Class:OrderRequest/Attribute:approval_request_date'  => 'Approval requested on',
    'Class:OrderRequest/Attribute:approval_request_date+' => 'Timestamp when approval was requested.',
    'Class:OrderRequest/Attribute:approval_date'          => 'Approval decision on',
    'Class:OrderRequest/Attribute:approval_date+'         => 'Timestamp of approve/reject decision.',

    'Class:OrderRequest/Attribute:budget_approver_id' => 'Budget approver',
    'Class:OrderRequest/Attribute:budget_approver_name' => 'Budget approver (name)',
    'Class:OrderRequest/Attribute:budget_approval_request_date' => 'Budget approval requested on',
    'Class:OrderRequest/Attribute:budget_approved_by_id' => 'Budget approved/rejected by',
    'Class:OrderRequest/Attribute:budget_approved_by_name' => 'Budget approver (name)',
    'Class:OrderRequest/Attribute:budget_approval_comment' => 'Budget approval comment',
    'Class:OrderRequest/Attribute:budget_approval_date' => 'Budget approval decision on',

    'Class:OrderRequest/Attribute:procurement_reference'   => 'Procurement reference',
    'Class:OrderRequest/Attribute:procurement_reference+'  => 'Reference used by purchasing (e.g., PO number, request ID).',

    'Class:OrderRequest/Attribute:line_items'       => 'Line items',
    'Class:OrderRequest/Attribute:line_items+'      => 'Requested items/services grouped as line items.',

    'Class:OrderRequest/Attribute:parent_request_id'  => 'Related request',
    'Class:OrderRequest/Attribute:parent_request_id+' => 'Related user request (if any).',
    'Class:OrderRequest/Attribute:parent_request_ref' => 'Related request ref',
    'Class:OrderRequest/Attribute:parent_incident_id' => 'Related incident',
    'Class:OrderRequest/Attribute:parent_incident_ref' => 'Related incident ref',
    'Class:OrderRequest/Attribute:parent_problem_id'  => 'Related problem',
    'Class:OrderRequest/Attribute:parent_problem_ref' => 'Related problem ref',
    'Class:OrderRequest/Attribute:parent_change_id'   => 'Related change',
    'Class:OrderRequest/Attribute:parent_change_ref'  => 'Related change ref',
    'Class:OrderRequest/Attribute:related_request_list' => 'Child requests',
    'Class:OrderRequest/Attribute:related_request_list+' => 'User requests referencing this order request.',

    'Class:OrderRequest/Stimulus:ev_submit'           => 'Submit',
    'Class:OrderRequest/Stimulus:ev_review'           => 'Start review',
    'Class:OrderRequest/Stimulus:ev_request_approval' => 'Request approval',
    'Class:OrderRequest/Stimulus:ev_approve'          => 'Approve',
    'Class:OrderRequest/Stimulus:ev_reject'           => 'Reject',
    'Class:OrderRequest/Stimulus:ev_procure'          => 'Send to procurement',
    'Class:OrderRequest/Stimulus:ev_close'            => 'Close',
    'Class:OrderRequest/Stimulus:ev_request_budget_approval' => 'Request budget approval',
    'Class:OrderRequest/Stimulus:ev_budget_approve' => 'Approve budget',
    'Class:OrderRequest/Stimulus:ev_budget_reject' => 'Reject budget',

    // Validation messages (used in PHP)
    'Class:OrderRequest/Error:AtLeastOneLineItemBeforeSubmit' => 'Please add at least one line item before submitting.',
    'Class:OrderRequest/Error:BudgetApproverRequired' => 'Please select a budget approver before requesting budget approval.',
));

// ─────────────────────────────────────────────────────────────────────────────
// Class: OrderRequestLineItem
// ─────────────────────────────────────────────────────────────────────────────
/** @disregard P1009 Undefined type Dict */
Dict::Add('EN US', 'English', 'English', array(
    'OrderRequestLineItem:base'        => 'Basics',
    'OrderRequestLineItem:qtyprice'    => 'Quantity & pricing',
    'OrderRequestLineItem:description' => 'Description',

    'Class:OrderRequestLineItem' => 'Order Request Line Item',
    'Class:OrderRequestLineItem+' => 'Single line of an internal order request (BANF) used by IT procurement.',

    'Class:OrderRequestLineItem/Attribute:name'          => 'Name',
    'Class:OrderRequestLineItem/Attribute:name+'         => 'Short designation of the item/service.',

    'Class:OrderRequestLineItem/Attribute:order_request_id'   => 'Order request',
    'Class:OrderRequestLineItem/Attribute:order_request_id+'  => 'Parent order request (BANF).',
    'Class:OrderRequestLineItem/Attribute:order_request_ref'  => 'Order request ref',
    'Class:OrderRequestLineItem/Attribute:order_request_ref+' => 'Readable reference of the parent order request.',

    'Class:OrderRequestLineItem/Attribute:line_number'   => 'Line no.',
    'Class:OrderRequestLineItem/Attribute:line_number+'  => 'Sequential position within the order request.',

    'Class:OrderRequestLineItem/Attribute:vendor_sku'    => 'Vendor SKU / article no.',
    'Class:OrderRequestLineItem/Attribute:vendor_sku+'   => 'Supplier-specific article number (optional).',

    'Class:OrderRequestLineItem/Attribute:description'   => 'Description',
    'Class:OrderRequestLineItem/Attribute:description+'  => 'Optional details for procurement / IT.',

    'Class:OrderRequestLineItem/Attribute:quantity'      => 'Quantity',
    'Class:OrderRequestLineItem/Attribute:quantity+'     => 'Requested quantity.',

    'Class:OrderRequestLineItem/Attribute:uom'           => 'Unit of measure',
    'Class:OrderRequestLineItem/Attribute:uom+'          => 'Commercial unit of measure for the line item.',
    'Class:OrderRequestLineItem/Attribute:uom/Value:EA'  => 'each',
    'Class:OrderRequestLineItem/Attribute:uom/Value:PT'  => 'person-day',
    'Class:OrderRequestLineItem/Attribute:uom/Value:HUR' => 'hour',
    'Class:OrderRequestLineItem/Attribute:uom/Value:LIC' => 'license',
    'Class:OrderRequestLineItem/Attribute:uom/Value:SET' => 'set',
    'Class:OrderRequestLineItem/Attribute:uom/Value:MON' => 'month',
    'Class:OrderRequestLineItem/Attribute:uom/Value:ANN' => 'year',

    'Class:OrderRequestLineItem/Attribute:unit_price_estimated'  => 'Estimated unit price',
    'Class:OrderRequestLineItem/Attribute:unit_price_estimated+' => 'Estimated price per unit.',
    'Class:OrderRequestLineItem/Attribute:total_price_estimated'  => 'Estimated total',
    'Class:OrderRequestLineItem/Attribute:total_price_estimated+' => 'Auto-computed as quantity × estimated unit price.',

    'Class:OrderRequestLineItem/Attribute:functionalcis_list' => 'Linked CIs',

    // Validation messages (used in PHP)
    'Class:OrderRequestLineItem/Error:ParentNotEditable'      => 'This line item cannot be modified because the related Order Request is no longer in "draft" status.',
    'Class:OrderRequestLineItem/Error:QtyMustBePositive'      => 'Quantity must be greater than 0.',
    'Class:OrderRequestLineItem/Error:UnitPriceNegative'      => 'Estimated unit price cannot be negative.',
    'Class:OrderRequestLineItem/Error:UomRequired'            => 'Unit of measure is required.',
    'Class:OrderRequestLineItem/Warning:DuplicateNameUom'     => 'There is already a line with the same name and unit.',
));

// ─────────────────────────────────────────────────────────────────────────────
// Class: lnkOrderRequestLineItemToFunctionalCI
// ─────────────────────────────────────────────────────────────────────────────
/** @disregard P1009 Undefined type Dict */
Dict::Add('EN US', 'English', 'English', array(
    'Class:lnkOrderRequestLineItemToFunctionalCI' => 'Line Item / Functional CI',
    'Class:lnkOrderRequestLineItemToFunctionalCI+' => 'Links an Order Request line item to a Functional CI.',
    'Class:lnkOrderRequestLineItemToFunctionalCI/Attribute:order_request_line_item_id' => 'Line item',
    'Class:lnkOrderRequestLineItemToFunctionalCI/Attribute:order_request_line_item_name' => 'Line item name',
    'Class:lnkOrderRequestLineItemToFunctionalCI/Attribute:order_request_ref' => 'Order request',
    'Class:lnkOrderRequestLineItemToFunctionalCI/Attribute:functionalci_id' => 'Functional CI',
    'Class:lnkOrderRequestLineItemToFunctionalCI/Attribute:functionalci_name' => 'Functional CI name',
    'Class:lnkOrderRequestLineItemToFunctionalCI/Attribute:comment' => 'Comment',


));
