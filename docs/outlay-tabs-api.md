# Outlay dashboard tabs API

Implemented: stages 1, 2 and 3 of the 2026-10-17 dashboard task.
Item-processing flags are independent per item, aggregated per current store,
as confirmed by the owner. Existing `stores` entries retain `id` and `title`
and add the nested `stages` counters.

## Setup and compatibility

Apply after the existing outlay schema migrations:

```shell
php artisan migrate --path=app/Modules/Admin/Outlays/Migrations/2026_10_17_000001_add_outlay_dispatch_fields.php
php artisan migrate --path=app/Modules/Admin/OutlayGroups/Migrations/2026_10_17_000002_create_outlay_item_stages.php
```

`app:migration-deploy` already includes the Outlays and OutlayGroups migration
directories, so both new migrations are discovered there too. The first adds two boolean columns (default false),
nullable `return_date_comment`, a subdivision/return-date index, and the
`outlay_assignments` pivot table. Existing outlays keep their data and have no new
assignments. No production migrations or seeders were run during implementation.
The second adds one current stage record per item, with a unique primary key,
six default-false flags, last editor and timestamps. Deleting an item cascades
to its stage row. It is in OutlayGroups so fresh deployment creates items first.

Existing full-form routes are unchanged:

- `POST /api/admin/outlays`: filtered paginated list (not GET).
- `POST /api/admin/outlays/store`: create.
- `GET /api/admin/outlays/{outlay}`: detail.
- `PUT /api/admin/outlays/{outlay}`: full-form update.
- `DELETE /api/admin/outlays/{outlay}`: delete using the existing request contract.

All responses use `{status, errors, data}`. Validation failures return 422 with
`errors` keyed by fields. Access failures return 403 (unauthenticated requests 401).
Paginated collections are under `data.items` with `data`, `current_page`,
`last_page`, `per_page`, `total`, `from`, `to` and standard Laravel links.

Server timezone: **UTC**, as configured in `config/app.php`. Input/output dates
without offsets are interpreted in that timezone; changing the application's
timezone is a separate deployment decision. Datetimes serialize as
`YYYY-MM-DD HH:mm:ss`, financial date filters as `YYYY-MM-DD`.
Both boundary days are included. Financial dates without a time serialize at
00:00:00. Event dates are not replaced by record `created_at`.

## Outlay list and details

Read permission: `OUTLAYS_ACCESS` or `SUPER_ADMINISTRATOR`. All list queries
are restricted to the actor's available subdivisions, including requests without
`subdivision_id`. A supplied subdivision must also be accessible.
Details and mutations check the outlay's actual subdivision.

Additional optional full-form fields:

```json
{
  "self_pickup": true,
  "self_return": false,
  "return_date_comment": "After the event",
  "performer_ids": [10, 11],
  "return_performer_ids": [12],
  "assembler_ids": [13],
  "acceptor_ids": []
}
```

The four arrays are independent, distinct integer user IDs (maximum 500 per
array). Assigned users must be active and explicitly associated with the outlay's
subdivision in `users_subdivisions`. Missing arrays preserve the corresponding
memberships; `[]` clears only that membership group. They do not alter
`responsible_workers` or `creator_id`. Assignments are transactional with the form.
Self-pickup and self-return are independent; all four combinations are valid.
Omitted new flags/comment retain existing values on update.

List/details and successful write responses include:

- ID arrays above and `performers`, `return_performers`, `assemblers`, `acceptors`.
- Each related user includes `id`, `login`, `firstname`, `lastname` (no passwords or tokens).
- `creator` with the same display fields, and the existing `status` object.
- `date_comment` for the order and `return_date_comment` for the return.
- Existing fields, including dates, address, title and list `stores`, remain present.

Lists eager-load users/statuses and batch-load stores. The number of database
queries does not grow with the number of outlays on the page.

### Filters and sorting

Existing list filters remain available. New filters:

| Field | Semantics |
| --- | --- |
| `id` | Exact outlay ID |
| `self_pickup`, `self_return` | Independent booleans; false/0 are meaningful |
| `return_date_from`, `return_date_to` | Independent inclusive return-date boundaries |
| `status_ids` | At least one selected status |
| `performer_ids`, `return_performer_ids`, `assembler_ids`, `acceptor_ids` | Any selected user within each group |
| `store_ids` | At least one item whose current product belongs to a selected store |
| `return_date_comment` | Substring match |

Different filters are ANDed; values within one array are ORed. Empty arrays do
not restrict results. Combining a nonempty `status_id` with `status_ids`, even
`[]`, returns 422. Relation filters use EXISTS, so they do not multiply outlays
or pagination totals. Null return dates do not match a return-date boundary.
Legacy `return_date` remains an inclusive upper bound for compatibility.

Order-date aliases are retained: `date_start_from`/`date_start_to`,
`dateStartFrom`/`dateStartTo`, `dateStart`/`dateEnd`.

Pagination: `page`, `page_size` (default 20, maximum 100); `per_page` and `pageSize`
are retained aliases. Sorting uses `sort=field` or `sort=-field`.
Allowed fields: `id`, `total_cost` and the scalar fillable Outlay columns:
`title`, `execution_date`, `return_date`, `date_comment`, `subdivision_id`,
`creator_id`, `status_id`, `client_type_id`, `client_id`, `partner_entity`,
`source_id`, `subject_id`, `is_rent`, `recipient_name`, `recipient_phone`, `address`,
`percent_discount`, `discount_amount`, `is_paid`, `date_of_paid`, `payment_method_id`,
`prepayment`, `prepayment_amount`, `prepayment_date`, `prepayment_method_id`,
`payment_cash_box_id`, `prepayment_cash_box_id`, `route_link`, `self_pickup`,
`self_return`, `return_date_comment`. ID is the stable tie-breaker.
Default order remains execution date, then ID ascending.

### Tab requests

Examples of POST bodies sent to `/api/admin/outlays`:

```json
{"subdivision_id":5,"date_start_from":"2026-10-17","date_start_to":"2026-10-17","sort":"execution_date","page_size":100}
```

```json
{"subdivision_id":5,"return_date_from":"2026-10-17","return_date_to":"2026-10-17","sort":"return_date","page_size":100}
```

These are distribution order/return tables. Ordinary distribution includes
self-pickups/returns, matching legacy. Add `self_pickup:true` to the order request
or `self_return:true` to the return request for the self-service tab.
The frontend should choose today's distribution day, independently of its monthly
financial period; the server does not silently add a date constraint.
The returns tab uses a return-date range and may combine staff/status/store filters.
The text tab can load all pages for both date dimensions and merge the events;
returns do not depend on the order date being in the selected period.

## Quick mutations

Permission: `OUTLAYS_MANAGE` or super, plus access to the actual outlay subdivision.
These endpoints do not require the rest of the full form or `subdivision_id`.

```http
PATCH /api/admin/outlays/123/status
```
```json
{"status_id":2}
```

Runs WarehouseService in a transaction. Repeating a status produces no duplicate
movements/reservations. Insufficient stock rolls back the status change.
Products, prices, payments and assignment groups are not rewritten.

```http
PATCH /api/admin/outlays/123/assignments
```
```json
{"performer_ids":[10,11],"acceptor_ids":[]}
```

At least one assignment array is required. Other groups are preserved, including
when the provided array is empty. Returns updated `data.item` with assignments,
creator and status. Both quick mutations lock the outlay row before writing.

## Receipts

```http
GET /api/admin/outlays/receipts?subdivision_id=5&date_from=2026-10-01&date_to=2026-10-31&page_size=20
```

Read permission: `OUTLAYS_ACCESS` or super. The static route precedes detail binding.
Returns one current row per positive active prepayment/final payment. For a 1000
outlay with a 300 prepayment, final payment is 700, not 1000. A fully covering
prepayment creates no zero final-payment row.

Filters: `subdivision_id`, `date_from`, `date_to` (payment event date), `type`
(`outlay_prepayment`/`outlay_payment`), `cash_box_id`, `payment_method_id`,
`outlay_id`, `outlay_title` (substring), `execution_date_from`, `execution_date_to`.
Each date boundary can be used independently.
Sort fields: `occurred_at`, `amount`, `outlay_id`, `execution_date` and descending
variants. Default: `-occurred_at`, with stable source/type tie-breakers.
Pagination uses the same three page-size names as outlay lists.

Example `data` (paginator metadata omitted here):

```json
{
  "items": {"data": [{
    "key":"outlay:123:outlay_prepayment",
    "type":"outlay_prepayment", "direction":"income",
    "source_type":"outlay", "source_id":123, "source_title":"Event",
    "outlay_id":123, "outlay_title":"Event", "subdivision_id":5,
    "execution_date":"2026-11-10 12:00:00", "occurred_at":"2026-10-01 10:00:00",
    "amount":"300.00", "signed_amount":"300.00",
    "cash_box":{"id":1,"title":"Cash"}, "payment_method":{"id":1,"title":"Cash"}
  }]},
  "totals":{"amount":"1000.00"}
}
```

`totals.amount` covers the full filtered selection, not only the displayed page.
The key remains stable across amount/date/cash-box changes for the same document
and payment type. Current document edits, payment removal and deletion immediately
affect rows; journal reversals are not separate receipts.

## Operations

```http
GET /api/admin/cash-boxes/operations?subdivision_id=5&date_from=2026-10-01&date_to=2026-10-31
```

Permission: existing `CAASHBOX_ACCESS` (spelling retained) or super. All source
queries are scoped to available subdivisions before union/pagination. Cash boxes
remain global: access to a cash box does not expose other subdivisions' documents.

Current document types: `outlay_prepayment`, `outlay_payment`, `expense`,
`borrowing_sent`, `borrowing_received`. Outgoing amounts have direction `expense`,
incoming amounts `income`. `amount` is always positive; `signed_amount` is negative
for outgoing rows. A transfer's sender/receiver each belongs to its own subdivision.
When both subdivisions are accessible, both operations appear and net to zero.

Filters: `subdivision_id`, `date_from`, `date_to`, `type`, `direction`, `cash_box_id`,
`source_type` (`outlay`/`expense`/`borrowing`), `source_id`, `source_title` (substring),
`execution_date_from`, `execution_date_to`. Order-date filters exclude non-outlay
operations. `payment_method_id`, `outlay_id`, `outlay_title` are also supported.
An expense's display title comes from its expense group; a borrowing uses its title.

Sort fields: `occurred_at`, `amount`, `type`, plus `outlay_id` and `execution_date`;
descending variants and page-size aliases are supported. All sources are combined
before sorting/pagination. `source_type`, `source_id`, `type` break ties stably.
Rows use the receipt structure above; non-outlay rows have null outlay fields.

`data.totals` contains `income`, `expense` (positive decimal strings), `net` (signed),
computed with exactly the same filters over all pages. Empty selections yield zero.
Initial cash balances are not operations. The existing
`/{cashBox}/transactions` route remains the separate delta/reversal audit journal.

## Shared financial calculations

`DocumentCashFlow` produces current document events for receipts, operations and
`CashBoxesSummaryService`. Decimal amounts are converted to integer cents in SQL;
PHP formatting/summing does not use floating-point arithmetic. The summary keeps
its previous response shape, global initial balance semantics and forecast fields.
Payment method metadata uses the document's saved method, falling back to the
cash box method for legacy documents without one.

For the same subdivision/date range and no extra filters:

- Receipt `totals.amount` equals summary `totals.outlay_income`.
- Operation `totals.net` equals summary `totals.net_change`.
- Operation income = outlay income + borrowing received.
- Operation expense = expenses + borrowing sent.

Operations/receipts require three SQL queries (totals, pagination count, page with
joined cash-box/method metadata), independent of the number of rows.

## Item-processing stages

```http
PATCH /api/admin/outlays/123/items/456/stages
```
```json
{"assembled":true,"uploaded":false,"defective":true}
```

Requires `OUTLAYS_MANAGE` or super and access to the actual outlay subdivision.
The item must belong to that outlay; a foreign/missing item ID returns 403.
Flags: `assembled`, `uploaded`, `retrieved`, `checked`, `lost`, `defective`.
At least one flag is required, each a non-null boolean (0/1 also accepted).
Only supplied flags change; false clears a flag. There are no ordered-transition
requirements, bulk endpoints or per-item comments in this scope.

The response contains the updated position in `data.item`, its `images` and
`stages`. Detail/full-save responses also include `groups[].items[].stages`.
`stages.updated_by_id`, `stages.updated_by` (safe user display fields) and
`stages.updated_at` record the last actual change. This is current state, not a
complete history of every toggle. Repeating an identical patch does not create
a row or change the existing timestamp again; the first write initializes a row.
Before the first write, flags are false and the editor/time are null.
Updates lock the outlay and item and write transactionally.

These flags do not call WarehouseService or alter quantity, reservations,
cash movements, item prices or descriptions. Stable item IDs retain their flags
through ordinary full-form saves; deleting/recreating items deletes their state.

Each list `stores` entry adds:

```json
{
  "id":1,
  "title":"Main",
  "stages":{"total":10,"assembled":10,"uploaded":4,"retrieved":0,"checked":0,"lost":0,"defective":1}
}
```

`total` counts item rows, not quantities or unique product IDs. Repeated products
in separate positions count separately. Stage counters count rows where that flag
is true. For the first four flags, 0 is not done, between 0 and total is partial,
and equal to total is done. `lost` and `defective` indicate issues when above zero.
Products without a store are not included in the store aggregates. Aggregation
uses the product's current store, matching existing store-list behavior, and one
grouped query for the entire page. Missing stage rows count as false, never ready.
