# Outlay documents and public viewing API

## Legacy source and templates

Reviewed `OldCrm/modules/admin/controllers/OutlayController.php` (export/info),
`OldCrm/controllers/OutlayController.php` (public show),
`OldCrm/services/DocumentService.php`, and the corresponding outlay views.
The eight DOCX/XLSX source templates exist and have already been copied into
`resources/document-templates/outlays/`; no manual copy is required now.
See that directory's README for exact filenames and layout constraints.

This project remains a backend API. The new Pub module returns JSON for the
separate frontend's public page; it does not add a customer-facing Blade screen.
`resources/views/documents/outlay.blade.php` is exclusively a PDF input template,
not an application page. No public signing, SMS verification or document-approval
state machine was added from legacy; these are separate features.

## Runtime and migrations

New Composer dependencies: PHPWord, PhpSpreadsheet and Dompdf. composer.json and
composer.lock were updated; no Git commit was made.
Office generation requires PHP `zip` and spreadsheet generation also requires `gd`.
Enable them in the PHP runtime serving the API, not just CLI. Existing Passport
also requires `sodium`. Installation/tests here used per-command extension flags;
the machine's php.ini was not modified.

Apply the new migrations (production DB was not modified during implementation):

```shell
php artisan migrate --path=app/Modules/Admin/Clients/Migrations/2026_10_19_000001_add_ogrn_to_legal_entities.php
php artisan migrate --path=app/Modules/Admin/Outlays/Migrations/2026_10_19_000001_add_public_uuid_to_outlays.php
```

Both existing module directories are already in `app:migration-deploy`.
The first adds optional `legal_entities.ogrn` (string, 13/15 digits via request
validation), needed by the existing legal-entity lease contract template.
The second adds unique nullable `outlays.public_uuid`, backfills all existing
rows in batches with random UUIDv4 values, and leaves business values/timestamps
untouched. New model-created outlays always generate their own UUIDv4.
`public_uuid` is not fillable or an editable full-form field.

Review legacy client identifier columns: some existing migrations store passport
and bank identifiers as integers. Exporting does not repair already-lost leading
zeros or truncated bank details. Verify those source records before issuing a
legal document; broad client-schema normalization is outside this change.

## Authenticated info

```http
GET /api/admin/outlays/{outlay}/info
```

Requires `OUTLAYS_ACCESS` or super and access to the outlay's actual subdivision.
Returns standard `{status, errors, data}` with:

- `data.item`: operational outlay with subdivision, ordered groups/items/images,
  stages, creator/status, the four staff assignments and payment metadata.
- `data.counterparty`: name/contact name/phone, or null if the client no longer exists.
- `data.documents`: supported export types and authenticated download URLs.
- `data.public_api_url`: public JSON URL for this outlay's UUID.

Info is the counterpart of the staff-facing legacy info page; it is NOT public.
Existing quick status/assignment/item-stage mutation routes remain separate.

## Document generation/downloads

```http
GET /api/admin/outlays/{outlay}/documents
GET /api/admin/outlays/{outlay}/documents/{type}
```

The first returns `data.items: [{type,title,format,url}]`. The second generates
the document and returns an attachment, not JSON or a public file URL.
Both require `OUTLAYS_ACCESS` or super plus subdivision access.
The generation route is throttled at 20 requests/minute per authenticated user.

Types:

| type | Format | Legacy mapping |
| --- | --- | --- |
| `return-act` | DOCX | exportToWord type 1; legal entities only |
| `lease-contract` | DOCX | exportToWord type 2; individual/legal template selected automatically |
| `specification-1` | XLSX | exportToWord type 3 |
| `specification-2` | XLSX | exportToWord type 4 |
| `contract` | DOCX | DocumentService offer contract; individuals only |
| `estimate` | XLSX | DocumentService estimate |
| `service-act` | XLSX | DocumentService act of services |
| `estimate-pdf` | PDF | Printable single-outlay composition |

Files are generated independently: requesting an estimate does not implicitly
create, approve, sign or persist a contract. Word documents validate required
counterparty fields. Errors use 422 with `errors.client.FIELD` keys (or `client_id`
for missing/unsupported counterparties and type restrictions). Missing templates
or required PHP extensions return 503. Unknown types return 404.

Group order and item position follow the saved composition. Exported names/prices
are snapshots, not current catalog values. XLSX unit net prices are derived from
the stored line total divided by quantity using decimal arithmetic; the stored
line total and outlay total remain authoritative. Text cells are explicitly
strings, preventing spreadsheet formula injection. Word substitutions are XML
escaped. Original template legal text/issuer fields are not rewritten by the API.

Generated files are stored under `storage/app/private/outlay-documents/` with
random filenames, then downloaded with no-store headers and scheduled by the
response for deletion after sending. Exceptions remove partial output. Keep
this directory private; configure normal private-temp cleanup for interrupted
workers. The maximum is 5000 positions per single export.

### Period PDF

```http
GET /api/admin/outlays/documents/period-pdf?date_from=2026-10-01&date_to=2026-10-31&subdivision_id=5
```

The subdivision filter is optional, but the entire selection is always restricted
to accessible subdivisions. Date boundaries are inclusive by execution date,
using the server's UTC timezone. Pagination does not affect this report.
At most 100 outlays and 5000 positions; reduce the period when the limit is exceeded
(422). Throttle: 10 requests/minute. PDF uses escaped text and local fonts;
remote resource loading, embedded PHP and JavaScript are disabled.

## Public customer data

```http
GET /api/pub/outlays/{uuid}
```

Registered in `app/Modules/Pub/Outlays/Routes/api.php` without `auth` or subdivision
middleware. No token/login is required. Numeric IDs and invalid/nonexistent UUIDs
return 404. Rate limit: 60 requests/minute per IP. There is no public list/search.

Returns `data.item` with an explicit allowlist: number/title, execution/return
dates and comments, address/recipient contact, self-service/rental flags,
discount/total/prepayment/payment-state information, public subdivision branding,
ordered group names and item snapshots with prices, quantities, descriptions,
totals and image paths. It does not return clients' passport/bank records, client
IDs, staff accounts/assignments, internal stages, cash boxes, stock journals,
status internals or document-generation routes.

The UUID is a bearer capability, not authentication or identity verification.
Anyone receiving it can read the allowlisted customer data, including recipient
contact/address. Treat links as confidential. The frontend must sanitize any
HTML item descriptions, avoid logging UUID links, set its own no-referrer/noindex
policy, and use HTTPS. API headers include `private, no-store`, `noindex, nofollow`
and `Referrer-Policy: no-referrer`; they do not set headers on the separate frontend.

## Invalidate/reissue a shared link

```http
POST /api/admin/outlays/{outlay}/public-link/rotate
```

Requires `OUTLAYS_MANAGE` or super plus actual subdivision access. No user-supplied
UUID is accepted; the server generates a new UUID under a row lock. Returns
`data.public_uuid` and `data.public_api_url`. Old public links immediately return
404. Deleting an outlay also invalidates its public link. Existing full-form edits
do not rotate links or let a caller choose a UUID.

## Verification and environment notes

Feature coverage includes guest/public access, redacted payloads, unknown UUIDs,
link rotation, unauthorized document/info access, UUID backfill, missing client
data, generating each DOCX/XLSX type and PDF from saved composition, XML validity,
placeholder replacement, snapshot prices and authenticated attachments.

Run Office tests directly so child PHP processes do not lose extension flags:

```shell
php -d extension=zip -d extension=gd -d extension=sodium vendor/phpunit/phpunit/phpunit --filter=OutlayCompositionTest
```

The generated sample PDF was rendered and visually checked. DOCX/XLSX were reopened
and structurally/data-checked; full Word/Excel page rendering could not be completed
because LibreOffice is absent on this host. Review print layout after editing templates.

Composer audit currently reports advisories in existing project dependencies;
none were reported for newly added document packages and their new dependencies.
Updating unrelated framework/auth packages is a separate task.
