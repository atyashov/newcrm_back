# Outlay document templates

The eight original templates were copied from
`D:/OSPanel/home/newcrm/OldCrm/web/export-documents/`.
They are application resources, not publicly served files.

| File | API document type |
| --- | --- |
| `akt_priema_vozvrata.docx` | `return-act` (legal entities only) |
| `Arenda_IP_fiz_l_1.docx` | `lease-contract` for individuals |
| `dogovor_arendy.docx` | `lease-contract` for legal entities |
| `spetsifikatsia_1.xlsx` | `specification-1` |
| `spetsifikatsia_2.xlsx` | `specification-2` |
| `outlay-contract-template.docx` | `contract` (individual offer contract) |
| `outlay-template.xlsx` | `estimate` |
| `act-template.xlsx` | `service-act` |

Keep these exact filenames when replacing a template. No generated client files
belong in this directory. Generated files are private and streamed to the caller.

IMPORTANT: original templates contain hard-coded issuer details and legacy legal
wording. Review/update those business details before production use. Template
copying is not verification of the legal text, current bank details or authority
to sign. Retain the existing DOCX `${...}` variables; unknown variables result in
422 instead of silently producing an incomplete contract.

XLSX headers/cell layouts follow the original templates. Data starts at row 13,
or row 12 for the service act. Keep those layouts or update the export service
alongside a replacement. Data/footer rows are rebuilt by the service. It uses
saved outlay item titles, prices, quantities and discount-adjusted totals.
Loss/compensation-price columns remain empty: the new Product schema currently
has no corresponding supported data field; zero compensation is not invented.

No original template or generated file should be moved into `public/`.
