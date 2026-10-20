# Outlay composition API

POST /api/admin/outlays/store creates an outlay.
PUT /api/admin/outlays/{id} updates an outlay.
GET /api/admin/outlays/{id} returns its groups, group_name, items and images.
DELETE /api/admin/outlays/{id} removes the outlay and its composition.
The existing POST /api/admin/outlays list endpoint is unchanged.

All endpoints require authentication and the corresponding OUTLAYS permission.
The list endpoint accepts an optional subdivision_id: omit it to receive the complete
paginated list, or pass it to filter by subdivision. Create, update and delete require
subdivision_id. For update and delete it must match the saved outlay subdivision.
The authenticated user must have access to the subdivision used by a write operation.

## Request example

```json
{
  "title": "Event",
  "execution_date": "2026-10-01 10:00:00",
  "return_date": "2026-10-02 18:00:00",
  "subdivision_id": 1,
  "creator_id": 1,
  "responsible_workers": [1],
  "status_id": 1,
  "client_id": 1,
  "partner_entity": "individual",
  "percent_discount": "10.00",
  "groups": [
    {
      "outlay_group_name_id": 1,
      "items": [
        {
          "product_id": 1,
          "price": "350.00",
          "quantity": "100",
          "description": "Ten rows",
          "images": ["/images/chair.jpg"]
        }
      ]
    }
  ]
}
```

Use actual database IDs. The client_id/partner_entity pair is preserved as supplied;
this stage validates a positive client ID and entity string, not the polymorphic client lookup.

## Update contract

Send the complete groups/items composition on every save. Include id for existing
groups and items; omit id for new ones. Omitted rows are deleted. groups: [] clears
the composition; items: [] clears a group. Array order becomes position.
Items are matched by their own id, allowing repeated product_id in a group.
An item ID cannot move between groups; create a new item in the destination group.
Changing the outlay subdivision is rejected.

On a new item, omitted description/images default to catalog values.
On an existing item, omitted description/images are preserved; images: [] removes
all images. Changing product_id refreshes catalog snapshots unless overrides are sent.
Images in a request are path/URL strings; responses contain image records with id,
path and position. This API stores references only; upload files through the existing
file module. Product titles are snapshots; group names are current dictionary names.

## Amounts

Prices have up to two decimal places; quantities up to three. Line gross amounts
are rounded half up to cents. discount_amount, when non-null, takes precedence over
percent_discount (including an explicit zero). If neither is supplied, discount is zero.
The fixed discount cannot exceed the subtotal. Discounts are allocated across lines
with cumulative rounding, so the sum of line total_cost values equals outlay.total_cost.
Send the desired discount on every save. Client-supplied total_cost is ignored.
The maximum subtotal is 9,999,999,999.99.

Payment fields are stored only; prepayment remains the boolean defined by the current
schema. When prepayment is true, prepayment_amount, prepayment_date and
prepayment_method_id are required. The amount cannot exceed total_cost.
No cashbox effects, reservations, stock checks, tasks, notifications or
document generation are performed.

## Schema rollout

Run migrations in dependency order:
1. OutlayGroupNames migrations (if not already applied).
2. Outlays migrations (if not already applied).
3. OutlayGroups migrations.

The new normalization migration upgrades the existing schema, removes JSON composition
columns and adds groups/items/images with foreign keys. It refuses to run when legacy
outlay_groups rows or non-null outlays.product_groups exist: migrate those contents
explicitly before running it. Do not clear production data to bypass this check.

The down migration removes the normalized composition tables and restores empty JSON
columns; it does not convert saved composition back to JSON. Decimal money columns
and the corrected outlay cascade are intentionally retained.
