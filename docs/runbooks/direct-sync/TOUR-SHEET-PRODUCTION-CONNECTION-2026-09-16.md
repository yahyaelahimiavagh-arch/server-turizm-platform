# Tour Sheet → Production Direct Sync — Connection Runbook

**Checkpoint:** 2026-09-16

**Scope:** Reconnect the separated Tour Google Apps Script stack to Server Turizm WordPress safely after the scripts have been copied into the operator Sheet.

This runbook proves connectivity with a controlled Tour fixture first. It does **not** authorize bulk Tour writes, public-route activation, indexation, sitemap, schema, canonical exposure or Tour Hub activation.

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
2. verify its exact Production version when the next rollout depends on version-specific behavior;
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

## 6. Controlled CREATE connection proof — ACCEPTED

Production fixture:

```text
Iran Test Turu
Local ID: ID-642D23A3
```

Initial local result:

```text
SATIR UYGUN
Target: NEW
```

Validate-only result:

```text
STT-000002 — CREATE
```

Apply result:

```text
STT-000002 — CREATE
```

Post-create validate:

```text
STT-000002 — UNCHANGED
```

Acceptance consequence:

- canonical `STT-000002` was allocated once;
- hidden Stable ID/checksum state was persisted;
- second validation did not propose duplicate CREATE;
- no public/indexation gate was opened.

## 7. Controlled UPDATE proof — ACCEPTED

Intentional safe test edit:

```text
Tur Adı
Iran Test Turu
→ Iran Test Turu Update Test
```

Identity fields and dates were not manually changed.

Local validation:

```text
Target: STT-000002
```

Remote validate-only:

```text
STT-000002 — UPDATE
```

Controlled Apply:

```text
STT-000002 — UPDATE
```

Post-update validate:

```text
STT-000002 — UNCHANGED
```

Acceptance consequence:

- same immutable `STT-000002` identity was preserved;
- updated checksum returned to the hidden Sheet state;
- repeated validation did not loop into another UPDATE;
- controlled CREATE and UPDATE selected-row paths are both accepted.

## 8. Transport behavior observed

Intermittent Google Apps Script → Server Turizm DNS/latency behavior occurred during Production testing.

Observed:

- transient DNS failures;
- successful bounded retry recovery on second/third attempts;
- one validate attempt exceeded Apps Script maximum execution time;
- later validate completed correctly as `UNCHANGED`.

Operational rule:

**Do not repeat Apply merely because a response is delayed or an execution-time limit is reached.**

First re-run validate/status and confirm canonical state before any additional mutation.

## 9. Accepted current operator scope

```text
Tour selected-row local validation     ACCEPTED
Tour selected-row CREATE               ACCEPTED
Tour selected-row UPDATE               ACCEPTED
Post-write idempotency                 ACCEPTED / UNCHANGED
Stable ID continuity                   ACCEPTED
Hidden checksum continuity             ACCEPTED
Bulk Tour mutation                     NOT AUTHORIZED / NOT TESTED
```

## 10. HARD STOP matrix for future rows

Do **not** run Apply when a row believed to already exist unexpectedly returns:

```text
CREATE
CONFLICT
INVALID
ERROR
```

Stop when:

- the returned Stable ID is not the intended `STT-*`;
- a linked row has blank/incorrect technical identity;
- credentials are uncertain;
- an unrelated public/indexation state changes;
- a response delay leaves mutation outcome uncertain — validate first, do not blindly Apply again.

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

## 12. Next ordered checkpoint

The Sheet connection stage is closed for controlled selected-row CREATE + UPDATE.

Next sequence:

```text
1. Merge documentation/evidence PR after green CI
2. Verify/install Tour Intelligence v1.1 in Production with Hub Master OFF
3. Prove existing /kultur-turlari/ remains unchanged while Hub Master OFF
4. Inspect canonical eligible Tour state
5. Only then consider a separately approved Hub activation pilot
```
