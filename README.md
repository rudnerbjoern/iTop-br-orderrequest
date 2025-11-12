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
  - **Lockdown:** once the parent `OrderRequest` leaves `draft`, line items are read-only and not deletable
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
   - Approval fields: `technical_approver_id`, `approval_request_date`, `approved_by_id`, `approval_date`, `approval_comment`
   - Procurement: `procurement_reference`
   - Roll-ups: `estimated_total_cost` (decimal, computed)
   - Relations: optional links to `Incident` / `UserRequest` / `Problem` / `Change`

2. **OrderRequestLineItem** (stand-alone)
   - `order_request_id` (FK to `OrderRequest`)
   - `line_number` (auto 1..n per order)
   - `name`, `vendor_sku`, `description`
   - `quantity` (integer, >0), `uom` (enum: `EA`, `PT`, `HUR`, `LIC`, `SET`, `MON`, `ANN`)
   - `unit_price_estimated`, `total_price_estimated` (RO, computed)

3. **OrderRequestType** (typology per org)
   - `org_id`, `code`, `name`, `status (active/inactive)`
   - `default_approver_id`
   - `requires_budget_owner_approval` (boolean)
   - `description`

## Lifecycle

`OrderRequest.status` values (with UI styling and icons):

- `draft` → **new** (`fas fa-plus-circle`)
- `submitted` → **active** (`fas fa-paper-plane`)
- `in_review` → **active** (`fas fa-search`)
- `waiting_approval` → **waiting** (`fas fa-hourglass-half`)
- `approved` → **success** (`fas fa-check-circle`)
- `rejected` → **failure** (`fas fa-times-circle`)
- `procurement` → **active** (`fas fa-shopping-cart`)
- `closed` → **frozen** (`fas fa-flag-checkered`)

Transitions (user stimuli): submit → review → request approval → approve/reject → procure → close.
On `approve` the approver and date are recorded; `request approval` sets `approval_request_date`.

## Usage

1. **Create types** (`OrderRequestType`)
   - Per organization, define `code`, `name`, set `status=active`
   - Optionally set a `default_approver_id` (auto-filled on requests)
2. **Create an order request** (`OrderRequest`)
   - Select **Organization** and **Type** (list is constrained)
   - Add **line items** (quantity, UoM, prices)
   - `estimated_total_cost` is computed automatically
3. **Approval workflow**
   - Move to `in_review` → `waiting_approval`
   - On **approve**, approver & date are set; comments required
   - On **reject**, requester and date are recorded
4. **Procurement**
   - Provide `procurement_reference` and move to `procurement`
   - Close when complete

> Once the request leaves **draft**, **line items** become **read-only** and **not deletable**.

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
