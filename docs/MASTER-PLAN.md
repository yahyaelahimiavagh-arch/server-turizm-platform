# SERVER TURIZM — SEO & PERFORMANCE MASTER PLAN
## 2026-09-15 — SEPARATED GOOGLE SHEETS RUNTIME ACCEPTED

> **AUTHORITATIVE CURRENT MASTER PLAN — 2026-09-15**
>
> Repository acceptance, Production runtime acceptance, Merge approval, WordPress plugin deployment, and public/indexation activation remain separate gates. Never infer one from another.

---

## 1. AUTHORITATIVE CHECKPOINT

Repository: `yahyaelahimiavagh-arch/server-turizm-platform`

Authoritative branch: `main`

Current merged `main`:

`6f3bc51b36e1cc62a4543f90c3ff668a9ca54e10`

That merge contains PR #16 / Direct Sync v0.1.4 repository/runtime work.

Current documentation/source reconciliation branch:

`feature/separate-umrah-tour-apps-scripts`

Purpose of the current checkpoint:

- make the Google Sheets Apps Script architecture match the accepted Production operator reality;
- eliminate the active shared Umrah+Tour Apps Script client/menu;
- keep Umrah and Tour as independent bound-script stacks;
- preserve the same WordPress Direct Sync contract and endpoint;
- preserve accepted canonical payload hashes and Stable IDs;
- record the Production no-change runtime evidence from 2026-09-15.

No public/indexation/SEO gate is changed by this checkpoint.

---

## 2. DIRECT SYNC SERVER CONTRACT — PRESERVE

Contract:

`ST-DIRECT-SYNC-1.0.0`

WordPress endpoint:

`/wp-json/server-turizm/v1/direct-sync`

Server adapters remain:

- `umrah`
- `tour`

Modes remain:

- `validate` — no-write preflight;
- `apply` — mutation only after accepted checks.

Secrets remain external:

- `ST_DIRECT_SYNC_KEY_ID`
- `ST_DIRECT_SYNC_SECRET`

Google Apps Script stores endpoint/key/secret in Script Properties only.

### WordPress plugin deployment status

Repository `main` contains Direct Sync Foundation v0.1.4 after PR #16 merge.

Production WordPress plugin version was **not re-verified in the separated-Sheets checkpoint**. The last explicit production-version evidence before this checkpoint was v0.1.3. Therefore do **not** claim Production WordPress v0.1.4 until separately verified or replaced and tested.

---

## 3. GOOGLE SHEETS ARCHITECTURE — NOW HARD-SEPARATED

The previous active architecture used shared Apps Script files for both Umrah and Tour. That architecture is retired from the active source tree by this checkpoint.

### Umrah bound Apps Script — only Umrah code

Active source files:

- `integrations/google-sheets/umrah/ST-Umrah-Config.gs`
- `integrations/google-sheets/umrah/ST-Umrah-Exporter.gs`
- `integrations/google-sheets/umrah/ST-Umrah-Direct-Sync.gs`
- `integrations/google-sheets/umrah/ST-Umrah-Menu.gs`

No Tour function/menu is permitted in these files.

Accepted operator menu:

```text
Ön Kontrol — Seçili Umrah
Siteyi Güncelle — Seçili Umrah
Siteyi Güncelle — Tüm Aktif Umrah
Programı Kaldır → Arşivle
Aktif Programları Kontrol Et
Seçili Program JSON
Tüm Aktif Programlar JSON
Hotel Directory Yükle
Direct Sync Ayarları
```

### Tour bound Apps Script — only Tour code

Active source files:

- `integrations/google-sheets/tours/ST-Tour-Generator.gs`
- `integrations/google-sheets/tours/ST-Tour-Direct-Sync.gs`
- `integrations/google-sheets/tours/ST-Tour-Menu.gs`

No Umrah function/menu is permitted in these files.

Accepted operator menu:

```text
Ön Kontrol — Seçili Tur
Siteyi Güncelle — Seçili Tur
Seçili Turu Arşivle
Seçili Satırı STTI Kaydına Bağla
Seçili Satır Bağını Temizle
Teknik Stable ID/Checksum Hazırla / Gizle
Local Satır Kontrolü
Partial JSON Göster
Partial JSON İndir
Direct Sync Ayarları
```

---

## 4. UMRAH IDENTITY / STATE

Stable identity:

`STP-######`

The independent Umrah stack uses hidden Sheet state:

`ST Umrah Sync State`

Identity key:

`document_ref + worksheet + source_row`

Stored values:

- Stable ID;
- expected canonical checksum;
- updated timestamp.

### Migration evidence

On 2026-09-15, the one-time migration from legacy `ST Direct Sync State` completed:

```text
Migrated Umrah state: 36
Invalid/skipped state rows: 0
WordPress WRITE: none
```

This migration was Sheet-side state reconciliation only and is not part of the steady-state runtime package.

---

## 5. UMRAH SOURCE CONTRACT

The existing visible `Home` sheet stays authoritative for Umrah operator data.

The removal/archive control is exactly:

`AD = Programı Kaldır`

No new `KALDIR` business column is part of the accepted Umrah contract.

Important behavior:

- active/exportable rows are normal sync candidates;
- `Programı Kaldır` rows are excluded from active export;
- if a checked historical row predates Direct Sync and has no accepted `STP-* + checksum`, it is treated as **Sheet-only closed**;
- a historical Sheet-only row is never CREATEd merely so it can immediately be archived;
- a checked row with accepted Stable ID/checksum may enter the controlled archive flow;
- archive is never hard delete.

### Selected-row Production evidence

Accepted fixture:

`STP-000005 / Program No 223`

Observed after separated-stack migration/fix:

```text
Precheck → STP-000005 — UNCHANGED
Apply    → STP-000005 — UNCHANGED
```

This proves the separated Umrah Apps Script preserves the accepted canonical hash and exact existing Stable ID on the tested no-change path.

### Full active Umrah

Previously accepted no-change/preflight remains preserved.

Mass legacy live UPDATE remains a separate future gate and must continue to fail closed when protected Programs are not individually auto-refresh eligible.

---

## 6. TOUR IDENTITY / STATE

Stable identity:

`STT-######`

The Tour generator no longer assumes that `Z/AA` are free technical columns.

Accepted technical behavior:

- discover `STTI Stable ID` and `Expected Checksum` by header if already present;
- both must exist together and be adjacent;
- if absent, append both after the current last used business column;
- hide the technical columns;
- never overwrite business columns such as `Vize`;
- exact existing Tour targeting still requires Stable ID + checksum.

The generator runtime version is `1.0.2`, while the payload `producer.version` deliberately remains `0.6.2.1` so an implementation-only refactor does not change canonical Tour payload hashes.

### Selected-row Production evidence

Accepted fixture:

`STT-000001 / IRN-2026-01 / Büyük İran Turu`

Observed after separated-stack installation:

```text
Precheck → STT-000001 — UNCHANGED
Apply    → STT-000001 — UNCHANGED
```

This proves the separated Tour Apps Script preserves the existing accepted payload/checksum and targets `STT-000001` without duplicate creation.

Publication remains private/off unless separately authorized through Tour public controls.

---

## 7. TRANSPORT RETRY — PRESERVE IN BOTH CLIENTS

Both independent clients preserve the accepted retry contract:

- max 3 attempts;
- one request ID per business request;
- byte-identical JSON body across transport retries;
- fresh timestamp/nonce/HMAC each attempt;
- timeout/DNS/network transient retry;
- `409 stds_processing` polling with the same request ID/body;
- ordinary business/validation failures are not retried as transport errors;
- recovered transport retry is surfaced to the operator.

The Umrah and Tour clients implement this independently; there is no shared Apps Script runtime dependency.

---

## 8. CONTROLLED UMRAH ARCHIVE

Repository/server candidate v0.1.4 remains accepted by disposable runtime evidence.

Automatic server archive constraints remain:

- exact Stable ID + expected checksum;
- explicit controlled-archive approval;
- same Stable ID retained;
- canonical moves to archived/closed lifecycle;
- public/noindex route may be demoted to `prepared` under accepted postconditions;
- indexable Program archive remains fail-closed for separate SEO review;
- global Program Public Master / Hub bridge are preserved;
- rollback exists if postconditions fail.

**Production controlled-archive acceptance is still PENDING.**

The 2026-09-15 Production screenshots in this checkpoint prove selected-row no-change Umrah/Tour synchronization only. They do not prove a live archive occurred.

---

## 9. ANNUAL UMRAH SHEET ROLLOVER

Use one Google Spreadsheet per operating year.

Examples:

- `Server Turizm Umre Programlari 2026 Control Panel`
- `Server Turizm Umre Programlari 2027 Control Panel`

Do not erase/reuse old annual `Home` rows for a new year's Programs.

New-year procedure:

1. create/copy a new annual workbook when next-year Programs begin entry;
2. keep the primary tab name `Home` unless deliberately redesigned;
3. install only the accepted **Umrah** Apps Script stack;
4. ensure the new workbook starts with a clean `ST Umrah Sync State` identity namespace;
5. configure Direct Sync Script Properties once if they are not already present in the new bound project;
6. first genuinely new Program must prove:

```text
Precheck → CREATE
Apply    → CREATE
Precheck → same STP-* / UNCHANGED
```

Cross-year Programs are not duplicated just because the calendar year changes.

---

## 10. PUBLICATION / SEO HARD SEPARATION

Direct Sync synchronizes operating/canonical data; it does not grant SEO/publication authority.

It must never silently enable:

- Tour Public Master;
- Tour public route;
- Tour indexation;
- sitemap;
- schema;
- canonical exposure;
- mass Program indexation.

Hotel Intelligence remains canonical owner of Hotel facts (`STH-######`).

---

## 11. CURRENT ACCEPTANCE REGISTER

```text
Umrah separated Apps Script selected-row precheck/apply      PRODUCTION ACCEPTED
Tour separated Apps Script selected-row precheck/apply       PRODUCTION ACCEPTED
Umrah legacy state migration (36/36 valid)                   ACCEPTED ONE-TIME MIGRATION
Tour dynamic technical-column safety                         PRODUCTION OBSERVED / ACCEPTED
Transport retry contract in both separated clients           PRESERVED / STATIC-GATED
Umrah full active no-change/preflight                        PRODUCTION ACCEPTED (prior evidence)
Umrah mass legacy live UPDATE                                OPEN / separate future gate
Direct Sync v0.1.4 server controlled archive                 REPO/RUNTIME ACCEPTED
Controlled archive Production live test                      PENDING
Tour public/indexation gates                                 SEPARATE / OWNER-CONTROLLED
```

---

## 12. REPOSITORY OPERATING METHOD

- one coherent feature branch per checkpoint;
- static/lint gates before merge;
- disposable WordPress + MariaDB server runtime evidence;
- Apps Script syntax/static separation gates;
- no direct Production mutation from repository work;
- no automatic Merge;
- Merge requires explicit owner approval;
- WordPress Production deployment requires separate explicit owner approval;
- never claim acceptance without runtime evidence.

---

## 13. NEXT CHECKPOINT

The current repository checkpoint is **source-of-truth reconciliation for the already-tested separated Apps Script Production surfaces**.

After CI is green, stop at explicit owner Merge approval.

Do not bundle any of the following into this merge:

- a Production WordPress v0.1.4 claim without verification;
- a controlled archive live acceptance claim;
- mass legacy live Umrah UPDATE;
- Tour public/indexation activation.
