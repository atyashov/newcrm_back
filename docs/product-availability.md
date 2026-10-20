# Product availability for outlays

POST /api/admin/products/availability calculates availability for up to 1000 unique
products in one request. Authentication, access to the subdivision, and one of
PRODUCTS_ACCESS, WAREHOUSE_ACCESS, OUTLAYS_ACCESS, OUTLAYS_MANAGE or SUPER_ADMINISTRATOR are required.

## Request

```json
{
  "subdivision_id": 5,
  "product_ids": [12, 34],
  "starts_at": "2026-10-10 10:00:00",
  "ends_at": "2026-10-11 18:00:00",
  "outlay_id": 123
}
```

starts_at corresponds to execution_date; ends_at corresponds to return_date.
Use full date/time strings in the application's timezone. ends_at must be at least
starts_at; it may be null or omitted for a selection containing only sales, services
or unlimited products. Limited rental products require ends_at.

Omit outlay_id when creating an outlay. For editing, it must identify an existing
outlay in the supplied subdivision. All products must belong to that subdivision
through their categories. Invalid or foreign IDs produce HTTP 422; subdivision
access is checked by the existing middleware (HTTP 403).

## Response

```json
{
  "status": true,
  "errors": {},
  "data": {
    "items": [
      {
        "product_id": 12,
        "is_rent": true,
        "unlimited": false,
        "is_service": false,
        "total_quantity": "100.000",
        "occupied_quantity": "25.000",
        "available_quantity": "75.000"
      },
      {
        "product_id": 34,
        "is_rent": false,
        "unlimited": false,
        "is_service": true,
        "total_quantity": "0.000",
        "occupied_quantity": "0.000",
        "available_quantity": null
      }
    ]
  }
}
```

Quantities are decimal strings with three fractional digits. Items follow the order
of product_ids. available_quantity = null means the quantity is unrestricted.

total_quantity is the current stock quantity before the edited outlay's own
consumption. Its net consumption is added back from the stock movement journal,
including any previous reversals. Other outlays' completed sales are already
deducted from stock; they are not counted as temporary reservations.

occupied_quantity is the maximum simultaneously reserved/issued quantity during
the requested period, excluding outlay_id. Returned and cancelled rentals do not
count. Separate non-overlapping rentals are not summed into one reservation.
Periods include both boundaries: a return and a new rental at the same instant
overlap. available_quantity is clamped to zero when stock is insufficient.

If a stock row has not been initialized yet, products.quantity is used as the
initial quantity, matching the save operation. Reading availability does not
create stock rows or reservations.

## Frontend integration

- Load saved groups and prices immediately; fetch availability separately.
- Send unique product IDs; sum the requested quantity across all groups locally.
- Compare that sum directly with available_quantity. Do not subtract the saved
  outlay's quantity again when outlay_id was supplied.
- Refresh availability when products or dates change, and ignore stale responses.
- Keep saved prices; this endpoint does not return catalog prices.
- Availability is a preview. Saving revalidates stock inside the transaction;
  another outlay may reserve or consume products between preview and save.

The calculation uses three queries without outlay_id and five with it, independent
of the number of selected products (excluding authentication/request validation).
Rental validation at save uses the same peak occupancy calculation. Sales reconcile
all line changes per stock before checking the resulting balance.

GET /api/admin/products/availability accepts product_id,
subdivision_id, starts_at, ends_at and optional outlay_id. It delegates to this
calculation and returns data.quantity, including null for unrestricted products.
