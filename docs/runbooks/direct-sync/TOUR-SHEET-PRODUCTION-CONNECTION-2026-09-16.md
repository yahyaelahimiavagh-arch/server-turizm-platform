# Tour Sheet → Production Direct Sync — Connection Runbook

**Checkpoint:** 2026-09-16

**Scope:** Reconnect the separated Tour Google Apps Script stack to Server Turizm WordPress safely after the scripts have been copied into the operator Sheet.

This runbook proves connectivity with a known existing Tour first. It does **not** authorize bulk Tour writes, public-route activation, indexation, sitemap, schema, canonical exposure or Tour Hub activation.

## 1. Accepted Tour Apps Script files

The bound Google Apps Script project must contain the current Tour-only stack:

- `ST-Tour-Generator.gs`
- `ST-Tour-Direct-Sync.gs`
- `ST-Tour-Menu.gs`

Do not mix the active Tour project with Umrah runtime files or retired shared Direct Sync files.

## 2. Install the operator menu

Run once from Apps Script:

```javascript
stTourInstall()
```

Approve Google authorization if requested, then reload the spreadsheet.

Expected menu:

`🧭 Server Turizm Tours`

Expected actions include:

- `Ön Kontrol — Seçili Tur`
- `Siteyi Güncelle — Seçili Tur`
- `Seçili Turu Arşivle`
- `Teknik Stable ID/Checksum Hazırla / Gizle`
- `Local Satır Kontrolü`
- `Direct Sync Ayarları`

## 3. Verify WordPress before configuring the Sheet

Required REST endpoint:

`https://www.serverturizm.com.tr/wp-json/server-turizm/v1/direct-sync`

Contract:

`ST-DIRECT-SYNC-1.0.0`

Before any Apply action:

1. verify `Server Turizm Direct Sync` / Direct Sync Foundation is active in Production;
2. verify its exact Production version;
3. verify server-side HMAC configuration exists;
4. do not assume repository version `0.1.4` is live until Production proves it.

Server configuration names:

- `ST_DIRECT_SYNC_KEY_ID`
- `ST_DIRECT_SYNC_SECRET`

The secret must never be committed to Git or stored in a normal Sheet cell.

## 4. Configure Apps Script Properties

From the Tour menu choose:

`Direct Sync Ayarları`

Enter:

### Endpoint

`https://www.serverturizm.com.tr/wp-json/server-turizm/v1/direct-sync`

### Key ID

Use the exact same Key ID configured on the WordPress server.

### HMAC Secret

Use the exact same secret configured on the WordPress server.

The Tour client stores these as Script Properties:

- `ST_DIRECT_SYNC_ENDPOINT`
- `ST_DIRECT_SYNC_KEY_ID`
- `ST_DIRECT_SYNC_SECRET`

## 5. Prepare technical identity columns

Use:

`Teknik Stable ID/Checksum Hazırla / Gizle`

The accepted columns are discovered by header:

- `STTI Stable ID`
- `Expected Checksum`

They are technical state and should remain hidden from normal operator use.

Do not force fixed Z/AA positions and never overwrite a business column such as `Vize`.

## 6. First connection proof — validate only

Use one known existing Tour row. Preferred accepted fixture when its source row is still the same record:

`STT-000001 / IRN-2026-01 / Büyük İran Turu`

Select exactly that Tour row and run:

`Ön Kontrol — Seçili Tur`

### Expected no-change result

```text
STT-000001 — UNCHANGED
```

This is the safest proof that:

- endpoint connectivity works;
- HMAC credentials match;
- the server recognizes the intended Stable ID;
- the current payload/checksum agrees with canonical state;
- validation makes no canonical write.

## 7. HARD STOP matrix

Do **not** run Apply when the first no-change proof unexpectedly returns:

```text
CREATE
UPDATE
CONFLICT
INVALID
ERROR
```

Also stop if:

- the returned Stable ID is not the intended `STT-*`;
- the row believed to be linked has blank/incorrect technical identity;
- the Production Direct Sync plugin version cannot be established;
- credentials were copied from an uncertain source;
- an unrelated public/indexation state changes.

Unexpected `CREATE` is especially important: do not create a duplicate canonical Tour merely to prove the connection.

## 8. No-change Apply proof

Only after the expected validation result, run:

`Siteyi Güncelle — Seçili Tur`

For an unchanged accepted fixture, expected result remains:

```text
STT-000001 — UNCHANGED
```

Then run `Ön Kontrol — Seçili Tur` again.

Expected:

```text
STT-000001 — UNCHANGED
```

This proves the no-change path is idempotent.

## 9. Acceptance checklist

```text
Tour-only Apps Script stack                PASS
stTourInstall authorization                PASS
Tour operator menu                         PASS
Production REST gateway                    VERIFIED
Production Direct Sync version             VERIFIED
Script Properties                          CONFIGURED
Known existing row validate                UNCHANGED
Known existing row apply                   UNCHANGED
Second validate                            UNCHANGED
Duplicate STT row                          NONE
Unexpected public/indexation change        NONE
```

## 10. Only after connection acceptance

The next independent gate may test one intentional, small Tour business-data edit.

Required sequence:

```text
intentional Sheet edit
→ validate
→ expected UPDATE
→ inspect Stable ID
→ operator confirmation
→ apply
→ verify canonical record
→ validate again
→ UNCHANGED
```

Do not start with a bulk operation.

## 11. Publication / SEO boundary

Direct Sync success does not authorize:

- Tour Public Master;
- individual public route;
- indexation;
- sitemap;
- schema;
- canonical exposure;
- homepage exposure;
- Tour Hub Master.

Those remain separately controlled.

## 12. Tour Hub relationship

Repository Tour Hub v1.1 is merged, but Production installation/activation must be verified separately.

Future intended flow is:

```text
Tour Sheet
→ Direct Sync
→ canonical STT-*
→ human review / editorial approval
→ Hub eligibility
```

Hub Master remains a separate explicit Production gate and should stay OFF during initial Sheet connection verification.
