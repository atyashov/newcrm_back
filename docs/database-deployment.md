# Database deployment

## Commands

Run all standard and module migrations in one Laravel migration batch:

```bash
php artisan app:migration-deploy
```

Apply migrations and run only the required seeders, including when there are no
pending migrations:

```bash
php artisan app:migration-deploy --seed
```

Run only required seeders:

```bash
php artisan db:seed
```

Production deployments may add `--force`. Back up the database before deployment.
No migrations or seeders were run on the application's database while preparing
this change. Automated fresh/repeat/rollback checks use isolated in-memory SQLite,
not the application's MySQL database.

## Migration coverage and order

`MigrationDeploy::MIGRATION_PATHS` explicitly includes `database/migrations`
(cache, jobs, sessions, Passport OAuth) and every module migration directory.
Laravel receives all paths together and orders migrations by filename. This
respects cross-module dependencies instead of running a newer module alteration
before an older dependency in another module. One migration failure aborts the
command and prevents the seed stage.

`DeploymentSetupTest` compares all migration files against the configured paths;
a new unregistered module directory makes this test fail. It also checks a
fresh deployment, repeat deployment/seeding, full rollback and redeployment.

Existing applied migrations are not rerun. The normalization of legacy outlay
JSON deliberately stops if legacy compositions exist; this requires a separate
data conversion, not deleting populated tables. Existing partially failed MySQL
DDL likewise needs the relevant migration's recovery procedure.

## Required seeders, in order

1. `PermissionsSeeder`: missing permission aliases, preserving IDs/titles of
   existing permissions and all role/menu links.
2. `CreateAdminUser`: bootstrap the `admin` account and the superadministrator
   role/permission links by login/alias, preserving an existing account/password.
3. `CreateClientTypes`: existing initial client-type entry, only if missing.
4. `SubdivisionsSeeder`: missing initial subdivisions, without deleting existing
   subdivisions or overwriting descriptions, logos and settings.
5. `MenusSeeder`: missing menu entries, without clearing the menu table,
   resetting auto-increment or replacing custom existing entries/positions.
6. `UsersSubdivisionsMenusSeeder`: user/subdivision navigation permission links.
7. `MenuPermissionSeeder`: superadministrator menu links, without detaching
   existing links.
8. `PaymentMethodsAndCashBoxesSeeder`: the 26 payment-method/cashbox entries,
   preserving existing balances, settings and accounting records.

The sequence is wrapped in a database transaction. Repeated calls do not
recreate the same records or reset working data. Old manually created duplicates
are not automatically deleted, since other records may reference their IDs.

Not included in `DatabaseSeeder`:

- `CreateIndividual`: random demo counterparty data.
- `RolePermissionSeeder`: empty placeholder.
- `App\Modules\Admin\Subdivisions\Seeds\SubdivisionsSeed`: older duplicate
  subdivision definitions.
- The Laravel starter factory test user has been removed from `DatabaseSeeder`.

## Administrator bootstrap

`CreateAdminUser` is included in the general seed sequence immediately after
`PermissionsSeeder`. It can also be run separately:

```bash
php artisan db:seed --class=CreateAdminUser
```

For a new account configure `CRM_ADMIN_LOGIN`, `CRM_ADMIN_EMAIL`,
`CRM_ADMIN_PASSWORD` in the environment first. The password must have at least
6 characters. No default password is supplied. `CRM_ADMIN_LOGIN` defaults to
`admin` if omitted. For an existing matching login, the command only ensures its
superadministrator role/permission bindings; it preserves the password, profile,
IDs and other role assignments. Other users do not receive this role. An existing
role is reused strictly by the `SUPER_ADMINISTRATOR` alias; arbitrary first roles
or fixed IDs are never used. The empty `RolePermissionSeeder` is unnecessary.

On a fresh database the general seed command requires the environment values
above. If they are missing/invalid, it fails and rolls back the seed transaction;
it does not create an account with a known fallback password. For an existing
`admin`, email/password environment values are not required and are not applied.

## Rollback

```bash
php artisan app:migration-deploy --revert=1
php artisan app:migration-deploy --revert=all
```

`N` now means a count of individual migrations, not module directories.
`all` delegates to Laravel's migration reset across the full configured path
list. These are destructive rollback commands, not routine deployment steps;
they cannot be combined with `--seed`.

`--pretend` delegates to Laravel's SQL preview and cannot be combined with
`--seed`. Never use rollback to recover an unrelated partially failed migration.
