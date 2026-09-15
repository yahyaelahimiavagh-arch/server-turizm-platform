# SERVER TURIZM — SEO & PERFORMANCE MASTER PLAN
## 2026-09-15 — DIRECT SYNC PRODUCTION ACCEPTED / CONTROLLED ARCHIVE REPO ACCEPTED

> **AUTHORITATIVE CURRENT MASTER PLAN — 2026-09-15**
>
> Repository acceptance, merge approval, production installation and public/indexation activation are separate gates. No repository result by itself authorizes a live deployment or SEO/public unlock.

---

## 1. AUTHORITATIVE CHECKPOINT

Repository: `yahyaelahimiavagh-arch/server-turizm-platform`

Authoritative branch: `main`

Current merged `main` before PR #16:

`2f6c0bd22b64958e0b08e41bd54179a13fd6086c`

Current open release candidate:

- PR #16 — `Direct Sync v0.1.4 — Controlled Umrah KALDIR archive`
- head: `6f90f9bd7e5dbec222a496e4aa90afc6a422d58f`
- Baseline verification run #61: **PASS**
- Unified Direct Sync runtime run #33: **PASS**
- controlled public/noindex Umrah archive runtime: **PASS**
- replacement ZIP build: **PASS**
- merge: **PENDING explicit owner approval**
- production deployment: **NOT YET AUTHORIZED / NOT YET INSTALLED**

---

## 2. CURRENT PRODUCTION STATE — DIRECT SYNC

Production WordPress Direct Sync plugin:

`Server Turizm Direct Sync Foundation v0.1.3`

Production shared Apps Script client:

`ST-Direct-Sync v0.1.3.2`

Contract:

`ST-DIRECT-SYNC-1.0.0`

Production endpoint:

`/wp-json/server-turizm/v1/direct-sync`

Secrets remain external:

- `ST_DIRECT_SYNC_KEY_ID`
- `ST_DIRECT_SYNC_SECRET`

Apps Script stores endpoint/key/secret in Script Properties. Secret material is never stored in business-data cells or Git.

### Production acceptance evidence

#### Umrah selected-row

Production runtime accepted:

- selected-row precheck returns the existing Stable ID;
- live Program update preserves approval automatically;
- existing public/noindex route/hash refreshes in place;
- updated business data reaches the live card without manual Approve/Prepare;
- follow-up precheck returns `UNCHANGED`;
- no duplicate Program is created.

Accepted production fixture:

`STP-000005 / Program No 223`

#### Transient transport retry

Apps Script v0.1.3.2 is production accepted for one-click transient transport recovery.

Retry contract:

- same request ID;
- byte-identical request body;
- fresh timestamp/nonce/HMAC per attempt;
- safe `stds_processing` polling;
- timeout/DNS/network transient retry;
- no retry of ordinary business/validation errors.

Production one-click selected-row precheck completed successfully after the retry hotfix.

#### Umrah full-batch

`Siteyi Güncelle — Tüm Aktif Umrah` no-change/preflight path is production accepted.

Observed full active set returned `UNCHANGED` for all Programs.

Only `STP-000005` is currently proven eligible for automatic live refresh. Other legacy live Programs correctly report that automatic refresh is unavailable for safety.

Therefore:

- mass no-change/preflight: **ACCEPTED**;
- mass live UPDATE across legacy Programs: **NOT YET ACCEPTED**;
- fail-closed behavior remains required.

#### Tour selected-row

Production Tour Direct Sync selected-row is accepted.

Accepted production fixture:

`STT-000001 / IRN-2026-01 / Büyük İran Turu`

Observed sequence:

```text
Precheck → STT-000001 — UPDATE
Apply    → STT-000001 — UPDATE
Precheck → STT-000001 — UNCHANGED
```

This proves:

- existing Stable ID targeting;
- expected-checksum concurrency;
- successful partial canonical update;
- no duplicate `STT-000002` creation;
- post-write idempotency.

---

## 3. TOUR INTELLIGENCE BASELINE — PRESERVE

Accepted chain:

```text
v0.6.5  AI Completion / Import NO-WRITE        ACCEPTED
v0.7.0  Review Relations                       ACCEPTED
v0.7.1  Canonical Geo Resolver                 ACCEPTED
v0.8.0  Complete Customer Renderer             ACCEPTED
v0.9.0  First Real Full Tour                   ACCEPTED
v1.0.0  Controlled Public Tour Pilot           ACCEPTED
```

Production Tour Intelligence plugin is v1.0.0.

Public Tour controls remain separate from Direct Sync:

- Public Master;
- Route;
- Indexation;
- Sitemap;
- Schema;
- Canonical.

All Tour public/SEO unlocks remain owner-controlled. Direct Sync must not silently enable them.

Accepted pilot identity remains:

- `STT-000001`;
- exact public pilot path `/turlar/buyuk-iran-kultur-turu/` when separately enabled;
- no wildcard route generation;
- no mass URL generation.

---

## 4. IMMUTABLE IDENTITY + CONCURRENCY

### Umrah

Stable identity: `STP-######`.

Technical state is stored in hidden Sheet sidecar:

`ST Direct Sync State`

Identity key:

`adapter + document_ref + worksheet + source_row`

Stored values include Stable ID and expected canonical checksum.

### Tours

Stable identity: `STT-######`.

Existing Tour targeting uses technical columns:

- `Z = STTI Stable ID`
- `AA = Expected Checksum`

A checksum mismatch must fail closed before mutation.

Names, titles and row numbers alone are never allowed to silently rotate Stable IDs.

---

## 5. WRITE / ARCHIVE POLICY

Direct Sync record outcomes:

```text
CREATE
UPDATE
UNCHANGED
ARCHIVE
CONFLICT
ERROR
```

`validate` is no-write preflight.

`apply` performs only the explicitly accepted subsystem mutation.

Archive is never hard delete.

Stable IDs, audit history and archive evidence remain retained.

---

## 6. DIRECT SYNC v0.1.4 — CONTROLLED `KALDIR` ARCHIVE

Current repository/runtime candidate: **ACCEPTED, PENDING MERGE + PRODUCTION QA**.

Operator requirement:

A checked Sheet header named one of:

- `KALDIR`
- `Programı Kaldır`
- `Program Kaldır`

means explicit archive intent for that exact existing Umrah Program.

The physical spreadsheet column letter is not authoritative; discovery is header-driven.

Production target menu after deployment:

`Server Turizm Sync → KALDIR → İşaretli Umrahları Arşivle`

Required sequence:

1. discover checked rows;
2. resolve exact `STP-*` + expected checksum from Direct Sync sidecar;
3. no-write validate;
4. fail closed on conflict/error;
5. show exact Stable IDs;
6. require explicit YES confirmation;
7. apply controlled archive;
8. write returned archived checksum back to sidecar.

### public/noindex Program

For an existing `public_noindex` Program, successful controlled archive:

- keeps the same `STP-*`;
- transitions canonical editorial lifecycle to `archived`;
- closes availability;
- keeps immutable Program Intelligence archive snapshot evidence;
- stores source-removal intent inside that immutable archive snapshot;
- consumes/clears the active removal-intent marker after archive;
- demotes the existing Publishing registry route from `public_noindex` to `prepared`;
- preserves registry identity/slug history;
- does not change Program Public Master;
- does not change `/umre-1/` Hub bridge/master;
- does not change Hotel relation global gate;
- reports public exposure change truthfully;
- rolls back canonical/registry state if postconditions fail.

### indexable Program

Automatic archive remains fail-closed:

`INDEXABLE_PROGRAM_ARCHIVE_REQUIRES_SEO_REVIEW`

Indexable URL removal requires a separate SEO/redirect/archive decision.

### Repository acceptance evidence

At PR #16 final accepted head:

- baseline regressions: PASS;
- Direct Sync static/security guards: PASS;
- Apps Script syntax: PASS;
- PHP syntax: PASS;
- Unified Direct Sync runtime: PASS;
- live Umrah auto-refresh runtime: PASS;
- controlled public/noindex archive runtime: PASS;
- explicit confirmation requirement: PASS;
- indexable fail-closed: PASS;
- route demotion to `prepared`: PASS;
- immutable removal-intent archive snapshot evidence: PASS;
- global public gate preservation: PASS;
- post-archive idempotent retry: PASS;
- replacement ZIP build: PASS.

No production v0.1.4 acceptance may be claimed until owner-approved merge, live plugin replacement, Apps Script archive module install and a controlled production test are completed.

---

## 7. GOOGLE SHEETS OPERATOR SURFACES

### Umrah

Existing visible `Home` operator sheet remains preserved.

Shared sync menu includes:

```text
Ön Kontrol — Seçili Umrah (WP yazma yok)
Pilot Güncelle — Seçili Umrah
Siteyi Güncelle — Tüm Aktif Umrah
KALDIR → İşaretli Umrahları Arşivle   [v0.1.4 candidate]
```

### Tours

Tour generator remains:

`STTI-v0.6.2.1-Sheet-to-Partial-JSON.gs`

Required helper functions include:

- technical Z/AA columns;
- explicit existing-record linking;
- Partial JSON fallback/export.

Shared sync menu includes:

```text
Ön Kontrol — Seçili Tur (WP yazma yok)
Siteyi Güncelle — Seçili Tur
Seçili Turu Arşivle
```

Tour selected-row Production runtime is accepted.

---

## 8. ANNUAL UMRAH SHEET ROLLOVER

Use one Google Spreadsheet per operating year.

Examples:

- `Server Turizm Umre Programlari 2026 Control Panel`
- `Server Turizm Umre Programlari 2027 Control Panel`
- `Server Turizm Umre Programlari 2028 Control Panel`

Do not erase and reuse the old annual `Home` rows for new Programs.

Primary worksheet name remains exactly:

`Home`

until ST-TDE is deliberately changed and retested.

### New-year procedure

1. create the new annual Spreadsheet when next-year Programs begin operational entry; waiting until 1 January is not required;
2. preserve the accepted visible schema and `Home` tab;
3. install/copy accepted Apps Script modules;
4. if the file was duplicated, clear/delete copied hidden `ST Direct Sync State` before first sync;
5. run `Direct Sync Ayarları` once in the new bound Apps Script project;
6. enter only genuinely new Programs;
7. first genuinely new Program must pass:

```text
Precheck → CREATE
Apply    → CREATE
Precheck → same STP-* / UNCHANGED
```

The old annual file remains historical/source evidence and continues to own any Program that originated there until completion/archive.

Cross-year Programs are not duplicated merely because the calendar year changes.

Example: a Program departing 28 Dec 2026 and returning 6 Jan 2027 remains owned by the 2026 source row.

The website itself has no global "change year" operation. Programs from multiple years may coexist; lifecycle/date controls determine active/upcoming/archived state.

Authoritative runbook:

`docs/runbooks/direct-sync/UMRAH-YEAR-ROLLOVER.md`

---

## 9. PUBLICATION / SEO HARD SEPARATION

Direct Sync is operating-data synchronization, not publication authorization.

It must never silently enable:

- Tour Public Master;
- Tour public route;
- Tour indexation;
- Tour sitemap;
- Tour schema;
- Tour canonical exposure;
- automatic mass Program indexation.

Controlled Umrah archive may close the exact already-existing `public_noindex` route only under its dedicated validate + confirmation contract. It does not unlock any public gate.

---

## 10. HOTEL INTELLIGENCE — PRESERVE OWNERSHIP

Hotel Intelligence remains canonical owner of Hotel facts.

Neither Umrah nor Tour Direct Sync may duplicate Hotel canonical facts merely to simplify Sheet sync.

Stable Hotel identity remains `STH-######`.

---

## 11. CURRENT REMAINING ENGINEERING GATES

Current scope is nearly closed.

Remaining gates:

1. explicit owner approval to merge PR #16;
2. verify `main` once after merge;
3. separately obtain owner approval for Production v0.1.4 replacement;
4. install Direct Sync v0.1.4 WordPress replacement ZIP;
5. add `ST-Direct-Sync-Archive.gs` and current menu file to the Umrah Apps Script project;
6. keep current v0.1.3.2 transport client behavior;
7. run one controlled production archive test only on an explicitly approved disposable/finished Program;
8. verify same `STP-*` remains, canonical becomes archived, public/noindex route closes to prepared and global gates remain unchanged;
9. run archive action/precheck again and require `UNCHANGED`;
10. documentation-only final closeout after production evidence.

Mass UPDATE of legacy live Umrah Programs remains a separate future gate and must not be accidentally bundled into archive acceptance.

---

## 12. PROJECT PROGRESS — CURRENT ESTIMATE

```text
Site-wide SEO / Intelligence platform              ~96%
Tour Intelligence current repository scope          100%
Tour selected-row Sheet→WordPress Production path   100%
Umrah selected-row Sheet→WordPress Production path  100%
Transport timeout/DNS retry Production path          100%
Umrah mass no-change/preflight                       100%
Umrah mass legacy live UPDATE gate                   OPEN / separate future work
Controlled KALDIR archive repository/runtime         100% accepted pre-merge
Controlled KALDIR archive Production acceptance      PENDING
Annual Umrah rollover operating design               100% documented
```

Do not call `KALDIR` Production Accepted until live evidence exists.

---

## 13. REPOSITORY OPERATING METHOD

- one coherent feature branch per checkpoint;
- static/lint gates before runtime;
- disposable WordPress + MariaDB runtime evidence;
- dedicated Direct Sync workflow;
- accepted regression workflows preserved;
- no repeated Actions polling;
- no direct production mutation from repository work;
- no automatic Merge;
- Merge requires explicit owner approval;
- production deployment requires separate explicit owner approval;
- never claim acceptance without runtime evidence.

---

## 14. NEXT CHECKPOINT

> PR #16 is repository/runtime accepted at head `6f90f9bd7e5dbec222a496e4aa90afc6a422d58f`. Stop only at the explicit owner Merge gate. After owner-approved merge, verify `main` once. Production v0.1.4 deployment remains a separate explicit approval gate. Do not enable any indexable/SEO route as part of archive rollout.
