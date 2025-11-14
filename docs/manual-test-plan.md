# Manual Test Plan — iTop-br-orderrequest

**Module:** br-orderrequest (iTop-br-orderrequest)
**Version:** 0.0.1
**Date:** 2025-11-13
**Target iTop:** 3.2.x (tested on 3.2.2)
**Author:** Björn Rudner

---

## Scope

Manual validation of:

- `OrderRequest` lifecycle (including secondary budget approval)
- Line items creation/editing, roll-up to `estimated_total_cost`
- Defaulting logic from `OrderRequestType`
- Policy controls (warn/enforce/off) with minimal configuration churn
- FunctionalCI links on line items (no workflow restrictions)
- Read-only and visibility flags per state
- Deletion constraints for line items (allowed in `draft`, `rejected`, `closed`)

---

## Configuration Under Test (baseline)

- `policy_mode`: `warn`
- `approval_restrict_to_assigned_approver`: `true`
- `approval_forbid_self_approval`: `true`
- `budget_auto_threshold`: `0` (disabled)

> Only a **single temporary switch** is used later for “enforce-mode & threshold” tests, then reverted.

---

## Roles & Personas

Create (or ensure availability of) the following Persons in **Organization: `ACME`**:

- **Requester** — `alice.requester@acme.example`
- **Technical Approver** — `tom.tech@acme.example`
- **Budget Approver** — `bob.budget@acme.example`
- **Service Desk Agent** (optional for status moves) — `sam.agent@acme.example`

Give them typical profiles so they can log in / act (e.g., portal user for requester, console users for approvers).

---

## Baseline Test Data

### OrderRequestType (ACME)

1) **Type A (No Budget)**
   - `code`: `STD-NO-BUDGET`
   - `name`: `Standard (No Budget)`
   - `status`: `active`
   - `requires_budget_owner_approval`: `no`
   - `default_approver_id`: _Tom Tech_
2) **Type B (With Budget)**
   - `code`: `STD-BUDGET`
   - `name`: `Standard (Budget)`
   - `status`: `active`
   - `requires_budget_owner_approval`: `yes`
   - `budget_approver_id`: _Bob Budget_
   - `default_approver_id`: _Tom Tech_ (optional but convenient)

> Keep at least one `FunctionalCI` in ACME (e.g., `Laptop-INV-001`) to use in link tests.

---

## Conventions

- **Navigation labels** may vary slightly by theme; use the standard Console.
- When asked to _“Act as …”_, log in as that Persona (or impersonate).
- Expected results are marked with **Expected:**.

---

## Test Cases

### A. Create OrderRequest, add line items, compute totals (no budget)

1. **Act as Requester**
   `Create > OrderRequest`
   - **Organization**: `ACME`
   - **Type**: `Standard (No Budget)` (Type A)
   - **Title**: `Laptop procurement`
   - **Description**: `Two developer laptops`
   - Save (stay in **draft**)

2. Add **Line Item #1**
   - **Name**: `Laptop Pro 14"`
   - **Quantity**: `2`
   - **UoM**: `EA`
   - **Unit price estimated**: `1200`
   - **Total price estimated** auto-computes to `2400.00`
   **Expected:** `OrderRequest.estimated_total_cost` updates to `2400.00`.

3. Add **Line Item #2**
   - **Name**: `Docking Station`
   - **Quantity**: `2`
   - **UoM**: `EA`
   - **Unit price estimated**: `150`
   - **Total** auto-computes to `300.00`
   **Expected:** `estimated_total_cost = 2400.00 + 300.00 = 2700.00`.

4. Confirm **technical approver** is defaulted to _Tom Tech_ from Type A.
   **Expected:** `technical_approver_id = Tom Tech`.

---

### B. Submit denied with no line items (guard rail)

1. **Create new OrderRequest** (Type A) titled `Empty request`.
2. Try **Submit** from `draft`.
**Expected:** Blocking message _“Please add at least one line item before submitting.”_

---

### C. Change request type & defaults

1. Open **Laptop procurement** (from A).
2. Change **Type** to `Standard (Budget)` (Type B).
   **Expected:**
   - `budget_approver_id` defaults to _Bob Budget_.
   - (If you had a manual technical approver before, it stays unless type changed and previous was empty — your baseline has default Tom.)
3. Change **Type** back to `Standard (No Budget)` (Type A).
   **Expected:** Budget-related fields (`budget_approver_id`, budget dates/comments) are cleared by compute logic on type change.

---

### D. Approval without budget (Type A)

1. Keep **Type A** on **Laptop procurement**.
2. **Transitions:** `draft → submitted → in_review → waiting_approval`.
3. **Act as Tom Tech**, click **Approve**.
   - Provide **Approval comment**: `OK for procurement`
   **Expected:**
   - Status moves to `approved`.
   - `approved_by_id = Tom Tech`, `approval_date` set.
   - `procurement_reference` still empty.

4. From **approved**, click **Send to procurement**.
   - Prompted for **procurement_reference**: enter `PO-2025-0001`.
   **Expected:**
   - Status `procurement`, `procurement_reference = PO-2025-0001`.

---

### E. Budget path required (Type B)

1. Create **OrderRequest** `Firewall license renewal` with **Type B**.
2. Add one line item:
   - **Name**: `Firewall License`
   - **Quantity**: `12`
   - **UoM**: `MON`
   - **Unit price estimated**: `100`
   **Expected:** `total = 1200.00`, `estimated_total_cost = 1200.00`.
3. Move to `waiting_approval`.
4. **Act as Tom Tech**:
   - **Approve** should be **denied** (UI hides or transition denied) because type requires budget.
   - Use **Request budget approval** instead (must provide comment).
   **Expected:** Status `waiting_budget_approval`, `budget_approval_request_date` set.
5. **Act as Bob Budget**:
   - Click **Budget Approve**, comment `Within budget`.
   **Expected:** Status `approved`, budget approver & date set.

---

### F. Policy (warn mode): only assigned approver / self-approval

> Baseline is `policy_mode = warn`.

1. **Only assigned approver (warn)**
   - On a Type A ticket at `waiting_approval`, **act as someone NOT Tom Tech** (e.g., Requester).
   - Click **Approve**.
   **Expected:** Warning shown (non-blocking) about _OnlyAssignedApprover_, approval still goes through.

2. **Self-approval (warn)**
   - On a Type A ticket at `waiting_approval`, **act as Requester** (caller = current user).
   - Click **Approve**.
   **Expected:** Warning (non-blocking) about _SelfApprovalForbidden_, approval still goes through.

---

### G. FunctionalCI links on line items (no restrictions)

1. Open any **OrderRequestLineItem** (e.g., `Laptop Pro 14"`).
2. In **Functional CIs** linked set: **Add** → pick `Laptop-INV-001` (or any CI).
**Expected:** Link row appears.
3. Change the parent order status to various states (`draft`, `approved`, `procurement`, `closed`), and add/edit/remove CI links.
**Expected:** Always allowed; no lifecycle restrictions.

---

### H. Line item deletion rules

1. Create a fresh Type A ticket **“Monitors”** with one line item `Monitor 27"` and submit to each state to test:
   - **draft**: delete the line item.
     **Expected:** Allowed.
   - Re-add the line item, move to **rejected**: delete the item.
     **Expected:** Allowed.
   - Re-add, move to **closed**: delete the item.
     **Expected:** Allowed.
   - Re-add, try in **submitted**/**in_review**/**waiting_approval**/**waiting_budget_approval**/**approved**/**procurement**: delete.
     **Expected:** **Blocked** with message _ParentNotEditable_.

> This reflects code that permits delete in `draft`, `rejected`, `closed`; denies otherwise.

---

### I. Roll-up & rounding

1. On any ticket in **draft**, create 3 line items:
   - `A`: qty `1`, unit `0.1` → total `0.10`
   - `B`: qty `3`, unit `33.333` → total `99.999` (rounded to `100.00` by compute)
   - `C`: qty `2`, unit `10` → total `20.00`
**Expected:** `estimated_total_cost = 0.10 + 100.00 + 20.00 = 120.10` (rounded to 2 decimals).

---

### J. Filtering and type activity

1. Change **OrderRequestType (Type A)** to `inactive`.
2. Try to create new `OrderRequest` in `ACME`.
**Expected:** Type A is **not** offered; Type B (active) shows.
3. Set Type A back to `active`.

---

### K. Read-only flags by state

Pick a ticket and inspect attribute behavior:

- **draft**: `estimated_total_cost` is RO; system fields (`ref`, `start_date`, `last_update`) RO; approval fields hidden.
- **submitted/in_review**: many base fields become RO; linked set of line items becomes RO.
- **waiting_approval**: approval comment editable; `approved_by_id` hidden until action; dates RO/hidden per state.
- **waiting_budget_approval**: technical approval fields RO; budget comment editable.
- **approved/procurement/closed**: approval fields RO; `procurement_reference` RO after procurement; system dates RO.

**Expected:** Flags match the lifecycle XML.

---

### L. Policy (enforce mode) & threshold — **single temporary config change**

> Temporarily set:
>
> - `policy_mode = enforce`
> - `budget_auto_threshold = 1000`
> Keep:
> - `approval_restrict_to_assigned_approver = true`
> - `approval_forbid_self_approval = true`

1. **Threshold triggers budget flow**
   - Create Type A ticket with `estimated_total_cost >= 1000` (e.g., 2×1200).
   - Move to `waiting_approval`.
   - **Act as Tom Tech** and click **Approve**.
   **Expected:** **Blocked** (policy issue) instructing to use budget flow (because total ≥ threshold).

2. **Only assigned approver (enforce)**
   - Same state, **act as NOT Tom Tech** and click **Approve**.
   **Expected:** **Blocked** (only assigned approver can approve).

3. **Self-approval (enforce)**
   - Same state, **act as Requester** and click **Approve**.
   **Expected:** **Blocked** (self-approval forbidden).

4. **Budget approval restricted to budget approver**
   - Move to `waiting_budget_approval`.
   - **Act as NOT Bob Budget** and click **Budget Approve**.
   **Expected:** **Blocked** (only assigned budget approver can budget-approve).

> **Revert config** to baseline after these tests:
> `policy_mode = warn`, `budget_auto_threshold = 0`.

---

## Reset / Cleanup

- Revert module parameters to baseline (see above).
- Optionally delete test tickets and line items.
- Set all test `OrderRequestType` statuses back to `active`.

---

## Known Limitations (current version)

- No trigger/email actions shipped (iTop handles Org change cascade automatically).
- No OQL filter/validation on `lnkOrderRequestLineItemToFunctionalCI` (by design, to keep it simple).
- Policy messages rely on dictionary entries; ensure EN/DE keys exist for all policy texts.

---

## Result Recording Template

Use this table (copy per run):

| Test ID | Name                         | Result (Pass/Fail) | Notes / Evidence |
| ------- | ---------------------------- | ------------------ | ---------------- |
| A       | Create, line items, totals   |                    |                  |
| B       | Submit guard (no items)      |                    |                  |
| C       | Type change & defaults       |                    |                  |
| D       | Approval without budget      |                    |                  |
| E       | Budget path required         |                    |                  |
| F1      | Warn: Only assigned approver |                    |                  |
| F2      | Warn: Self-approval          |                    |                  |
| G       | FunctionalCI links           |                    |                  |
| H       | Line item deletion rules     |                    |                  |
| I       | Roll-up & rounding           |                    |                  |
| J       | Type filtering by status     |                    |                  |
| K       | Read-only flags by state     |                    |                  |
| L1      | Enforce: threshold           |                    |                  |
| L2      | Enforce: only assigned       |                    |                  |
| L3      | Enforce: self-approval       |                    |                  |
| L4      | Enforce: budget approver     |                    |                  |

---

## File Location

Save this file as:
