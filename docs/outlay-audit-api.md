# Outlay edit audit

## Read

`GET /api/admin/outlays/{outlay}/audit`

Requires API authentication, `OUTLAYS_ACCESS` (or `SUPER_ADMINISTRATOR`) and
access to the outlay's actual subdivision. A supplied subdivision ID cannot
override this check. This endpoint is internal only; the public UUID response
does not contain audit data.

Query parameters:

- `page`: positive integer, default 1.
- `page_size`: integer 1..100, default 20.
- `event`: optional `created`, `form_updated`, `creator_changed`, `status_changed`.
  Omit to get the complete edit journal; use `event=status_changed` for the
  legacy status-installation block. Filtering does not affect the summary.

Response `data`:

```json
{
  "summary": {
    "created": {"user":{"id":1,"login":"admin","firstname":null,"lastname":null},"at":"2026-10-20 10:00:00","source":"user"},
    "last_edited": null,
    "creator_changed": null
  },
  "items": {
    "data": [
      {
        "id":2,"event":"status_changed","source":"user",
        "user":{"id":1,"login":"admin","firstname":null,"lastname":null},
        "from_status":null,"to_status":{"id":1,"title":"New"},
        "from_creator":null,"to_creator":null,
        "at":"2026-10-20 10:00:00"
      }
    ],
    "current_page":1,"last_page":1,"per_page":20,"total":2,"from":1,"to":2
  }
}
```

Standard Laravel pagination also includes navigation URLs. The example above
abbreviates the list to one event. Entries are sorted by `occurred_at DESC,
id DESC`, so equal timestamps have a deterministic order. Times use the same
application timezone and `Y-m-d H:i:s` formatting as other outlay endpoints.

## Event rules

- `created`: the real request user, not the mutable `creator_id` chosen on the
  form. It is written once for a new outlay, independently of its selected
  creator. Creating a copy through the save service gives it a new creation
  event and UUID; no audit events from the original are copied.
- `form_updated`: a meaningful change from the main form, including groups,
  ordered items/images, price, quantity, description and assignments. The
  comparison uses persisted values and excludes technical timestamps and UUID.
  Recreating groups/items with new IDs counts as a structural change; clients
  must retain existing group/item IDs when editing.
- `creator_changed`: every actual change of `creator_id`, with `from_creator`
  and `to_creator` safe user snapshots. Later changes do not overwrite earlier
  events. Also produces `form_updated` for the main form.
- `status_changed`: every actual status change from the main form or quick
  status PATCH. At creation the first status is recorded with `from_status=null`
  and should be shown as an initial status installation.

An unchanged main-form save or repeated identical status PATCH does not create
events. The quick status PATCH records only `status_changed`, not
`form_updated`. Separate stage/comment/work-note/assignment PATCH endpoints and
public-link rotation do not overwrite the main-form last editor. Assignment
changes submitted through the main form do count as form edits.

All writes use the same transaction as the outlay/stock/cash changes. Failed
saves and failed stock reservations leave no audit events. Normal form writes
hold the outlay lock during before/after comparison. The request cannot supply
audit authors, timestamps or events.

## Snapshots and old records

User snapshots contain only `id`, `login`, `firstname`, `lastname`; status
snapshots contain `id`, `title`. User/status deletion or renaming cannot change
these saved historical values. They have no cascading user/status foreign keys.
Deleting the outlay itself deletes its journal.

No historical authors are inferred from `creator_id` or `updated_at`. For old
outlays with no creation event, `summary.created` uses only their known
`created_at`, with `user=null` and `source=unknown`. Missing edit/creator-change
events are null; the historical event list is empty until real new actions.

System audit writes use `source=system` and `user=null`; request actions use
`source=user`. No import/copy endpoint currently exists in the application.
Future import/copy flows must use the transactional save/audit service rather
than raw inserts or copying event rows. Direct database edits are not audited.
This feature is an edit/status journal, not a full log of all operational acts.

## Deploy

```bash
php artisan migrate --path=app/Modules/Admin/Outlays/Migrations/2026_10_20_000002_create_outlay_audit_events.php
```

The module migration directory is already included in `app:migration-deploy`.
`outlay_id` is `INT UNSIGNED`, matching `outlays.id`. The migration creates
`outlay_audit_events` without modifying existing outlays or backfilling events.
It was not run on the application database during implementation.
