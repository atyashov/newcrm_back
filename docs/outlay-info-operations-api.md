# Outlay info operational updates

All endpoints require API authentication, `OUTLAYS_MANAGE` (or
`SUPER_ADMINISTRATOR`) and access to the outlay's actual subdivision. Item IDs
must belong to that outlay; otherwise the response is 403. These endpoints do
not require a client-supplied subdivision ID.

## Item comments

`PATCH /api/admin/outlays/{outlay}/items/{item}/comments`

```json
{
  "assembly_comment": "Collect carefully",
  "retrieve_comment": null
}
```

Both fields are optional nullable strings, maximum 10000 characters per field.
At least one recognized field must be present. Omitted fields remain unchanged;
empty strings/null clear the corresponding comment. The server's normal text
trimming middleware applies.

Response: `data.item` is the outlay item. Its `comments` object contains
`assembly_comment`, `retrieve_comment`, and `editors`:

```json
{
  "assembly_comment": "Collect carefully",
  "retrieve_comment": null,
  "editors": {
    "assembly_comment": {
      "user": {"id": 7, "login": "worker", "firstname": null, "lastname": null},
      "updated_at": "2026-10-20 10:00:00"
    },
    "retrieve_comment": {"user": null, "updated_at": null}
  }
}
```

## Outlay work notes

`PATCH /api/admin/outlays/{outlay}/work-notes`

Accepted fields:

- `additional_upload_comment`
- `retriever_comment`
- `damage_loss_comment`

The same partial update, clearing, length and minimum-one-field rules apply.
Response: `data.item` is the outlay, with `work_notes` containing these three
fields and the per-field `editors` object, in the format above.

## Stage editors

`PATCH /api/admin/outlays/{outlay}/items/{item}/stages` keeps its existing input
contract: one or more boolean flags `assembled`, `uploaded`, `retrieved`,
`checked`, `lost`, `defective`.

`data.item.stages.editors` now contains an entry for each flag, with `user` and
`updated_at`. Both setting and unsetting a flag record its editor. The existing
`updated_by_id`, `updated_by`, `updated_at` and `created_at` remain compatible.
An initial false-to-false request does not create a stage record or invent an
editor.

## Shared behavior

- `GET /api/admin/outlays/{outlay}/info` includes `work_notes`, item `comments`
  and stage `editors`. Regular detailed administrative responses include them
  as well.
- Public `GET /api/pub/outlays/{uuid}` excludes all three operational objects.
- Authors and timestamps are server-owned. Repeating an unchanged value does
  not change authors or timestamps. Updating one field leaves all others alone.
- Editor data is limited to ID, login, first name and last name. If an author
  is deleted, the user becomes null but the timestamp remains.
- No-op clearing of a never-written null field does not create metadata.
- Existing stage values and their common legacy editor are preserved by the
  migration; unknown per-flag authors/times remain null.
- Updates are transactional, locking the outlay and (for comments/stages) the
  owned item. No stock, rentals, payments, prices, quantities or totals are
  recalculated. Lost/defective flags do not trigger automatic stock changes.
- Normal composition saves do not overwrite operational texts. Deleting a
  position deletes its comments; deleting an outlay deletes its work notes.
- This records the latest editor of each field, not a full event history.

## Migration

```bash
php artisan migrate --path=app/Modules/Admin/OutlayGroups/Migrations/2026_10_20_000001_add_outlay_work_notes_and_item_comments.php
```

The `OutlayGroups/Migrations` directory is already registered in
`app:migration-deploy`. Run after the existing outlay composition and stage
migrations. The migration adds nullable per-flag metadata and two dedicated
tables, `outlay_item_comments` and `outlay_work_notes`. It does not backfill
unknown authors. No application database migration was run while implementing
this change.

`outlay_work_notes.outlay_id` uses `INT UNSIGNED`, matching `outlays.id`.
The migration supports retry after partial MySQL DDL: existing objects/data are
kept, an unbound legacy BIGINT outlay reference is corrected, and missing foreign
keys are added. If the original attempt failed, rerun this migration directly;
do not roll back a previous successful batch or delete these tables manually.
