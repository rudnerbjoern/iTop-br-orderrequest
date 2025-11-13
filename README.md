# iTop-br-orderrequest

[![License](https://img.shields.io/github/license/rudnerbjoern/iTop-br-orderrequest)](https://github.com/rudnerbjoern/iTop-br-orderrequest/blob/main/LICENSE) [![Contributions welcome](https://img.shields.io/badge/contributions-welcome-brightgreen.svg?logo=git&style=flat)](https://github.com/rudnerbjoern/iTop-br-orderrequest/issues)

An iTop extension to manage **Order Requests** (German: **BANF – Bedarfsanforderung**) directly in iTop.
Submit purchase requests, route them through approval, and hand over to procurement - all as a specialized ticket with line items.

---

## Features

- **Order Request ticket** (`OrderRequest`, extends iTop `Ticket`)
  - Typed requests via `OrderRequestType` (per organization, `status = active/inactive`)
  - Technical approver auto-defaulted from type
  - Approval tracking: `approval_request_date`, `approved_by_id`, `approval_date`, `approval_comment`
  - Procurement hand-off with `procurement_reference`
  - Class branding (icon + colors)
  - Status badges with colors and icons
- **Line items** (`OrderRequestLineItem`)
  - Autonumbered `line_number` (1..n) per order
  - Commercial UoM enum: `EA`, `PT`, `HUR`, `LIC`, `SET`, `MON`, `ANN`
  - Calculated `total_price_estimated = quantity × unit_price_estimated` (read-only)
  - Defensive validations (positive qty, non-negative price, UoM required)
  - **Mutability rules**
    - Edits are allowed only while parent is in `draft`
    - Deletion is allowed when the parent is in `draft`, `closed`, or `rejected`
- **Functional CI linking**
  - Link line items to CIs through `lnkOrderRequestLineItemToFunctionalCI`
  - **Add/edit FunctionalCIs in any lifecycle state** (also supports reservations for future CIs)
  - Simple, workflow-agnostic relation (no extra transitions)
- **Totals & consistency**
  - `estimated_total_cost` on `OrderRequest` computed from line items
  - When changing `org_id`, an incompatible `request_type_id` is cleared
  - `request_type_id` is filtered to the same organization (and active types)
- **Menus & dashboard**
  - Dedicated Order Request menu and dashboard badges (`OrderRequest` / `OrderRequestType`)
- **Localization & Icons**
  - Dictionaries in **English** and **German**
  - Matching **SVG** icons for all classes (teal theme)

## Data Model

1. **OrderRequest** (extends `Ticket`)
   - `request_type_id → OrderRequestType` (filtered to same `org_id`, `status='active'`)
   - Technical approval fields:  `technical_approver_id`, `approval_request_date`, `approved_by_id`, `approval_date`, `approval_comment`
   - Budget approval fields: `budget_approver_id`, `budget_approval_request_date`, `budget_approved_by_id`, `budget_approval_date`, `budget_approval_comment`
   - Procurement: `procurement_reference`
   - Roll-ups: `estimated_total_cost` (decimal, computed)
   - Relations: optional links to `Incident` / `UserRequest` / `Problem` / `Change`
   - Linked set: `line_items` (`OrderRequestLineItem`)
2. **OrderRequestLineItem**
   - `order_request_id` (FK to `OrderRequest`)
   - `line_number` (auto 1..n per order), `name`, `vendor_sku`, `description`
   - `quantity` (integer, >0), `uom` (enum: `EA`, `PT`, `HUR`, `LIC`, `SET`, `MON`, `ANN`)
   - `unit_price_estimated`, `total_price_estimated` (RO, computed)
   - Linked set: `functionalcis_list` via link class
3. **lnkOrderRequestLineItemToFunctionalCI**
   - Link table between `OrderRequestLineItem` and `FunctionalCI`
   - Uniqueness on (`order_request_line_item_id`, `functionalci_id`)
4. **OrderRequestType** (typology per org)
   - `org_id`, `code`, `name`, `status (active/inactive)`
   - `default_approver_id`
   - `requires_budget_owner_approval` (boolean)
   - `budget_approver_id` (optional in general; required if `requires_budget_owner_approval = yes`)
   - `description`

## Lifecycle

`OrderRequest.status` values (with UI styling and icons):

- `draft` → **new** (`fas fa-plus-circle`)
- `submitted` → **active** (`fas fa-paper-plane`)
- `in_review` → **active** (`fas fa-search`)
- `waiting_approval` → **waiting** (`fas fa-hourglass-half`)
- `waiting_budget_approval` → waiting (`fas fa-coins`) - only used when the type requires budget approval
- `approved` → **success** (`fas fa-check-circle`)
- `procurement` → **active** (`fas fa-shopping-cart`)
- `rejected` → **failure** (`fas fa-times-circle`)
- `closed` → **frozen** (`fas fa-flag-checkered`)

Transitions (user stimuli): submit → review → request approval → approve/reject → procure → close.
On `approve` the approver and date are recorded; `request approval` sets `approval_request_date`.

## Stimuli / Transitions

- `ev_submit` → `submitted`
- `ev_review` → `in_review`
- `ev_request_approval` → sets `approval_request_date`, moves to `waiting_approval`
- If **no budget approval required**:
  - `ev_approve` → sets technical approver & date, moves to approved
- If **budget approval required**:
  - From `waiting_approval`: `ev_request_budget_approval` → sets `approval_date` and `budget_approval_request_date`, moves to `waiting_budget_approval`
  - In `waiting_budget_approval`:
    - `ev_budget_approve` → sets budget approver & date, moves to approved
    - `ev_budget_reject` → sets rejector & date, moves to rejected
- `ev_procure` → `procurement` (prompts for `procurement_reference`)
- `ev_close` → `closed`

### Mutability note

- Line items: editable only in `draft`.
- Deletion allowed when parent is `draft`, `rejected`, or `closed` (can be done from the line item’s detail page even if the LinkedSet widget is read-only).

### State diagram

 ```mermaid
stateDiagram-v2
    [*] --> draft

    draft --> submitted : ev_submit
    draft --> rejected  : ev_reject

    submitted --> in_review : ev_review
    in_review --> waiting_approval : ev_request_approval

    waiting_approval --> approved : ev_approve\n(no budget required)
    waiting_approval --> waiting_budget_approval : ev_request_budget_approval\n(budget required)
    waiting_approval --> rejected : ev_reject

    waiting_budget_approval --> approved : ev_budget_approve
    waiting_budget_approval --> rejected : ev_budget_reject

    approved --> procurement : ev_procure
    procurement --> closed   : ev_close

    rejected --> [*]
    closed   --> [*]

    note right of waiting_approval
      Path selection:
      - If OrderRequestType.requires_budget_owner_approval = no → ev_approve
      - If yes → ev_request_budget_approval → waiting_budget_approval
    end note
 ```

## Usage

1. **Create types** (`OrderRequestType`)
   - Per organization, define `code`, `name`, set `status=active`
   - Optionally set a `default_approver_id` (auto-filled on requests)
   - If budget approval is required, set `requires_budget_owner_approval = yes` and assign `budget_approver_id`
2. **Create an order request** (`OrderRequest`)
   - Select **Organization** and **Type** (list is constrained)
   - Add **line items** (quantity, UoM, prices)
   - `estimated_total_cost` is computed automatically
3. **Approval workflow**
   - Move to `in_review` → `waiting_approval`
   - On **approve**, approver & date are set; comments required
   - On **reject**, requester and date are recorded
   - Depending on the type:
     - Direct technical approval (`ev_approve`) or
     - Budget approval path (`ev_request_budget_approval` → `waiting_budget_approval` → `ev_budget_approve` / `ev_budget_reject`)
4. **Procurement**
   - Provide `procurement_reference` and move to `procurement`
   - Close when complete
5. **Functional CIs**
   - Link FunctionalCIs to line items at any time via `functionalcis_list`

> Once the request leaves `draft`, **line items** become **read-only** and **not deletable**.
> Once the request is in `closed` or `rejected`, **line items** become **deletable**.

## iTop Compatibility

The extension was tested on iTop 3.2.2

## Contributing

Issues and PRs are welcome.

Please keep close to iTop conventions and include:

- Valid [itop_design.xsd XML](https://rudnerbjoern.github.io/iTop-schema/) (iTop 3.2)
- English dictionary updates (German appreciated)
- A short test scenario (when relevant)

## License

This project is licensed under the **GNU Affero General Public License v3.0 (AGPL-3.0)**.
See the [LICENSE](LICENSE) file or <https://www.gnu.org/licenses/agpl-3.0.en.html>.

© 2025 Björn Rudner
