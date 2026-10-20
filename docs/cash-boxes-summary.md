# Cash boxes summary

`GET /api/admin/cash-boxes/summary`

Requires `CAASHBOX_ACCESS` (the existing alias) or `SUPER_ADMINISTRATOR`,
and access to the requested subdivision.

Required query parameters:

- `subdivision_id`
- `date_start_from`: `YYYY-MM-DD`, inclusive
- `date_start_to`: `YYYY-MM-DD`, inclusive

Date aliases match the outlay list: `dateStartFrom` / `dateStartTo`,
or `dateStart` / `dateEnd`. Canonical snake_case parameters take precedence.
Pagination, sorting and other outlay filters do not affect this summary.

Example:

```text
/api/admin/cash-boxes/summary?subdivision_id=5&date_start_from=2026-10-01&date_start_to=2026-10-31
```

The standard response envelope contains `data.subdivision_id`, the normalized
dates, `data.items` (all cash boxes ordered by `sort_order`, then `id`) and
`data.totals`. There is no pagination. Each cash box includes `id`, `title`,
`payment_method_id`, `is_dashboard`, `cash_box_opening_balance` and these metrics:

| Field | Meaning |
| --- | --- |
| `opening_balance` | Subdivision income minus expenses plus received minus sent transfers before the first day |
| `outlay_income` | Prepayments and final payments received during the period |
| `expenses` | Expenses during the period, positive amount |
| `borrowing_received` | Transfers received by the subdivision during the period |
| `borrowing_sent` | Transfers sent by the subdivision during the period, positive amount |
| `net_change` | Income minus expenses plus received minus sent transfers |
| `closing_balance` | Opening balance plus net change |

`totals` contains sums of the same metrics across all returned cash boxes and:

- `outlays_total`: total cost of subdivision outlays whose execution date falls in the period.
- `outlays_forecast`: `outlays_total`, minus payments received before the period for
  those outlays, plus payments received during the period for outlays outside it.
- `forecast_closing_balance`: opening balance plus outlays forecast, minus expenses,
  plus received transfers, minus sent transfers.

All money values are decimal strings with two fractional digits. Empty periods and
cash boxes return zero amounts. Prepayments and final payments may target different
cash boxes; the final payment is total cost minus prepayment, not the full total.

The global initial cash box balance is NOT allocated to a subdivision. It is exposed
as `cash_box_opening_balance`, but is not included in the subdivision's opening or
closing balances. Do not add this global value to every subdivision's totals.
`cash_boxes.balance` is also global and is not used for this historical report.

Calculations use current outlays, expenses and borrowings, not raw journal delta rows.
Repeated saves do not double-count; edited dates and amounts are reflected immediately,
and deleted documents no longer contribute. This is a current-document report,
not an immutable historical audit report.

Unlike the old view, the API returns all cash boxes, including zero-activity ones,
with `is_dashboard` for frontend display selection. Totals cover all returned boxes.
There is no physical cash-box subdivision restriction: attribution comes from each
document, and from the sender/receiver subdivision for transfers. An internal transfer
within the same subdivision contributes to both transfer metrics and nets to zero.

The service runs three SQL queries regardless of the number of cash boxes/outlays:
grouped document events, cash box metadata, and total cost of period outlays.
No schema changes are required.
