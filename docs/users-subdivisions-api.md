# Users and subdivisions API

Implemented against `crm-frontend/docs/users-subdivisions-api.md`.

## Lists

`GET /api/admin/users` and `GET /api/admin/subdivisions` return the standard
`data.items` Laravel paginator. Parameters: `page`, `page_size` (default 20,
maximum 100), `sort` (field, or `-field` for descending order).

User substring filters and sorting fields: `firstname`, `lastname`, `login`,
`email`, `phone`. `status` is an exact `0`/`1` filter and is also sortable.
Subdivision substring filters and sorting fields: `title`, `amo_ids`,
`sms_sender_name`, `site_link`. Both lists also support sorting by `id`.
Non-super users see only their accessible subdivisions in the subdivision list.

`GET /api/admin/users/form` retains an unpaginated `data.items` array of all
active users for employee selectors. It requires `USERS_ACCESS` or super access.

## User writes and details

POST/PUT accepts `firstname`, `lastname`, `login`, `email`, `phone`, `role_id`,
optional `status`, and optional `subdivision_ids` (distinct existing integer IDs).
Creating requires `password` and `password_confirmation`. PUT can include the same
fields to replace the password; omission, null or an empty password preserves its hash.
Passwords require at least 6 characters; numeric-only passwords are accepted. With bcrypt they must not exceed 72 bytes
(not characters); other hash drivers accept up to 255 characters.
An explicit password reset revokes all Passport access/refresh tokens for the target
user, not for other users. Omitting the password does not revoke tokens.
Cookie-based web sessions are not invalidated by this API operation.

Password fields are sent with the other required user fields to
`POST /api/admin/users` or `PUT /api/admin/users/{id}`:

```json
{
  "password": "a-new-long-password",
  "password_confirmation": "a-new-long-password"
}
```

These are admin operations, not a self-service password change endpoint. The
target user's old password is not required. Do not grant `USER_MANAGE` to an
ordinary employee merely to let them change their own password. A self-service
flow should be separate and verify the current password or a reset token.
Resetting one's own password also revokes the current API token; the client must
return to login. The API does not currently require an admin re-authentication/MFA
step or send password-change notifications. It does not add an audit event for
password resets. Deploy over HTTPS and redact both password fields in any request
logging/APM infrastructure.
New users default to active; existing users retain their status when it is omitted.

GET list/details and POST/PUT responses include `roles`, `role_id`, `rolename`,
`subdivisions`, and `subdivision_ids`. Roles and subdivisions expose `id` and `title`.
Laravel pivot metadata may also be present. User/role/subdivision writes are atomic.
Omitted `subdivision_ids` preserves memberships; `[]` explicitly clears them.

User writes require `USER_MANAGE`; read endpoints require `USERS_ACCESS`.
The legacy `USERS_MANAGE` alias no longer grants write access. Run `PermissionsSeeder`
and explicitly assign the new permission to the intended roles; grants are not copied.
Super access bypasses both. DELETE is a soft deactivation (`status = 0`).

Non-super user managers may assign only accessible subdivisions. They may assign
roles containing only permissions they already have, unless they have `ROLES_MANAGE`.
Only super users may assign roles named `SUPER_ADMINISTRATOR` or containing that
permission. The same restrictions apply to the existing roles/subdivisions of a
user being edited, preventing takeover of more privileged accounts.

## Subdivision writes

Writes require `SUBDIVISIONS_MANAGE`. Read endpoints require `SUBDIVISIONS_ACCESS`.
Details/update/delete also check access to the selected subdivision.
Title is required; other documented fields are nullable and length-validated.
`site_link` is persisted as a string, without requiring a URL scheme.
`amo_ids` accepts comma-separated numeric IDs.

`logo` must be a relative, existing image path on the `images` disk used by the
file manager. Absolute paths, URLs and traversal are rejected; null clears it.

`default_product_group_id_for_amo` references `outlay_group_names`, not
`outlay_groups`. A group from another accessible subdivision is allowed, matching
the legacy global selector. Super users can select any existing group.

Descriptions are stored as HTML and returned as JSON, not rendered by this API.
Clients must sanitize HTML before inserting it into a page; JSON transport is
not an HTML sanitizer.

## Menu setup

Run `php artisan db:seed --class=UsersSubdivisionsMenusSeeder` to add/link only
the users/subdivisions menu entries and their existing ACCESS/MANAGE permissions.
This seeder preserves other entries and permission links and can be repeated.
It links their parent, when present, so the frontend can display the group.
Do not use the destructive `MenusSeeder` to add just these two entries.

The navigation API requires `type = admin` and a `permission_menu` link to a
permission held by the current user's role. Super users also require that link;
adding a row to `menus` alone does not make it visible. `MenuPermissionSeeder`
links all current menus to the super permission, not to every ordinary role.

No production migrations or seeders were executed for this implementation.
