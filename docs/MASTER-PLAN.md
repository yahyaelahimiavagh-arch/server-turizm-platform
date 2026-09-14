# SERVER TURIZM — SEO & PERFORMANCE MASTER PLAN
## 2026-09-14 — STTI v1.0.0 ACCEPTED / UNIFIED GOOGLE SHEETS DIRECT SYNC ACTIVE

> **AUTHORITATIVE CURRENT MASTER PLAN — 2026-09-14**
>
> Historical detail remains preserved in the repository archive. Repository acceptance never authorizes production deployment, secret configuration or public/indexation activation by itself.

---

## 1. AUTHORITATIVE CHECKPOINT

Repository: `yahyaelahimiavagh-arch/server-turizm-platform`

Authoritative branch: `main`

Verified `main` HEAD after owner-approved PR #8 merge:

`9074d6a4911a09e55f7012259b0935e9c952798e`

Merge title:

`STTI v1.0.0 — Controlled Public Tour Pilot`

### Accepted Tour Intelligence baseline

`STTI v1.0.0 — Controlled Public Tour Pilot` is **MERGED / DISPOSABLE WORDPRESS RUNTIME ACCEPTED**.

Accepted chain:

```text
v0.6.5  AI Completion / Import NO-WRITE        ACCEPTED
v0.7.0  Review Relations                       ACCEPTED
v0.7.1  Canonical Geo Resolver                 ACCEPTED
v0.8.0  Complete Customer Renderer             ACCEPTED
v0.9.0  First Real Full Tour                   ACCEPTED
v1.0.0  Controlled Public Tour Pilot           ACCEPTED
```

Accepted v1.0 facts:
- exact allowlist pilot only: `STT-000001`;
- exact route only: `/turlar/buyuk-iran-kultur-turu/`;
- Public Master / Route / Indexation / Canonical / Schema / Sitemap are separate controls;
- every public/SEO gate defaults OFF;
- Master OFF collapses every child gate immediately;
- no wildcard Tour route and no mass URL generation;
- no rewrite flush is required;
- existing private renderer/source-truth rules remain preserved;
- v0.6.5 through v0.9 regressions and v1.0 runtime passed;
- replacement package gate passed;
- no production deployment occurred.

`docs/CURRENT-RUNTIME-INVENTORY.md` remains authoritative for what is actually installed on production. Repository Tour acceptance is newer than the currently inventoried live Tour runtime.

---

## 2. CURRENT ACTIVE STAGE — UNIFIED GOOGLE SHEETS DIRECT SYNC

Goal: one permanent authenticated Google Sheets → WordPress synchronization foundation shared by Umrah Program Intelligence and Tour Intelligence.

Contract:

`ST-DIRECT-SYNC-1.0.0`

WordPress candidate plugin:

`wordpress/plugins/direct-sync-foundation/`

Shared Apps Script client:

`integrations/google-sheets/shared/ST-Direct-Sync.gs`

Operator menu:

`integrations/google-sheets/shared/ST-Direct-Sync-Menu.gs`

Existing source producers are preserved rather than replaced:
- Umrah: `ST_TDE_Exporter.gs`;
- Tours: `STTI-v0.6.2.1-Sheet-to-Partial-JSON.gs`.

Target operating path:

```text
Google Sheet
→ Siteyi Güncelle
→ signed authenticated request
→ replay / idempotency guard
→ identity + expected-checksum validation
→ Umrah or Tour adapter
→ validate-before-write
→ CREATE / UPDATE / UNCHANGED / ARCHIVE / CONFLICT / ERROR
→ audit evidence
→ Stable ID + current checksum returned to Sheet
```

---

## 3. SHARED AUTHENTICATION / REPLAY CONTRACT

Secrets remain external to the repository.

WordPress reads:
- `ST_DIRECT_SYNC_SECRET`;
- `ST_DIRECT_SYNC_KEY_ID`.

Apps Script stores endpoint/key/secret in Script Properties, never business-data cells.

Every request requires:
- timestamp;
- nonce;
- key ID;
- HMAC-SHA256 signature over the exact raw-body SHA-256.

Timestamp tolerance: 300 seconds.

Persistent Direct Sync request ledger enforces:
- unique `request_id`;
- unique nonce hash;
- same request ID + same completed body → cached idempotent response;
- same request ID + different body → conflict;
- nonce replay → conflict.

No secret value is committed to Git.

---

## 4. IMMUTABLE IDENTITY + OPTIMISTIC CONCURRENCY

### Umrah

Stable identity remains `STP-######` and is allocated only by Program Intelligence.

Existing Program updates require Sheet-side expected checksum equal to stored canonical `_stpi_payload_hash`.

The visible legacy Home sheet is not redesigned. Direct Sync stores technical Umrah state in hidden sidecar:

`ST Direct Sync State`

Key:

`adapter + document_ref + worksheet + source_row`

Values include current `STP-*` and expected canonical checksum.

### Tours

Stable identity remains `STT-######` and is allocated only by Tour Intelligence.

Existing Tour updates require the accepted Z/AA controls:
- Z = Stable ID;
- AA = Expected Checksum.

Checksum mismatch fails closed before mutation.

---

## 5. WRITE / ARCHIVE POLICY

Direct Sync supports per-record:

```text
CREATE
UPDATE
UNCHANGED
ARCHIVE
CONFLICT
ERROR
```

`validate` performs preflight only.

`apply` performs only the accepted private/canonical mutation for that subsystem.

### Archive is never delete

Umrah:
- explicit source removal intent;
- accepted Program Intelligence lifecycle transition;
- immutable archive snapshot;
- `STP-*` entity retained.

Tours:
- `STT-*` entity retained;
- editorial state becomes archived;
- availability closes;
- audit evidence preserved.

No Direct Sync delete path is authorized.

---

## 6. PUBLICATION SAFETY — HARD SEPARATION

Direct Sync is operating-data synchronization, not publication authorization.

It must never enable or request:
- Tour Public Master;
- public Tour route;
- Tour indexation;
- Tour sitemap;
- Tour schema;
- Tour canonical exposure;
- Tour homepage exposure;
- automatic Program publication/indexation.

Tour Direct Sync writes publication state private/off. The accepted v1.0 release overlay remains a separate owner-controlled layer.

Program publication remains controlled by Program Intelligence / Publishing Integration lifecycle.

---

## 7. GOOGLE SHEETS OPERATOR EXPERIENCE

Existing generators remain usable for JSON fallback/recovery.

The shared installable menu adds:

```text
🔄 Server Turizm Sync
  Siteyi Güncelle — Umrah
  Siteyi Güncelle — Seçili Tur
  Seçili Turu Arşivle
  Direct Sync Ayarları
```

The menu is installed with a separate trigger and does not replace existing `onOpen()` functions.

Successful sync writes returned Stable ID/current checksum state back only to the accepted technical storage:
- Tour Z/AA;
- Umrah hidden Direct Sync State sidecar.

---

## 8. DIRECT SYNC ACCEPTANCE GATES

Before merge:
1. all Direct Sync PHP syntax PASS;
2. shared Apps Script syntax PASS;
3. static security contract PASS;
4. baseline repository regressions remain PASS;
5. Direct Sync endpoint is one shared route, not two permanent stacks;
6. secret is external and no credential-shaped value is committed;
7. HMAC body binding PASS;
8. timestamp skew rejection PASS;
9. nonce replay protection PASS;
10. request ID idempotency PASS;
11. changed-body reuse conflict PASS;
12. Tour CREATE PASS;
13. Tour idempotent replay PASS;
14. Tour expected-checksum CONFLICT PASS;
15. Tour UPDATE PASS;
16. Tour ARCHIVE-without-delete PASS;
17. Umrah CREATE PASS;
18. Umrah expected-checksum CONFLICT PASS;
19. Umrah UPDATE PASS;
20. Umrah explicit removal ARCHIVE-without-delete PASS;
21. immutable `STT-*` / `STP-*` identity preserved;
22. per-record result/error evidence PASS;
23. Public Master remains unchanged;
24. disposable Tour/audit/sequences restored after runtime;
25. disposable Program/audit/sequences restored after runtime;
26. Direct Sync request ledger restored after runtime;
27. replacement Direct Sync ZIP + SHA256 generated;
28. no production deployment or secret configuration;
29. Merge requires explicit owner approval.

Runtime evidence must use disposable WordPress + MariaDB.

---

## 9. ACCEPTED TOUR BASELINES — PRESERVE

### v1.0 Controlled Public Pilot
Exact one-Tour allowlist, six independent gates default OFF, fast Master rollback and no mass URL generation.

### v0.9 First Real Full Tour
`STT-000001 / IRN-2026-01 / Büyük İran Turu`; current source dates `2027-01-26 → 2027-02-12`; source route `Tahran → Kaşan → İsfahan → Yezd → Şiraz`; unsupported facts remain unknown.

### v0.8 Complete Customer Renderer
Canonical direct read, exact confirmed-primary selection, no route guessing, reviewed relations only, Hotel Intelligence live reads, canonical geo only, zero renderer writes.

### v0.7.1 Canonical Geo
Exact stop IDs, explicit source-backed coordinates, Human Review and no browser geocoder/localStorage authority.

### v0.7 Review Relations
Route Variants, Hotel Options, Transport refs, review states and fail-closed unresolved/contradictory relations.

### v0.6.5 AI Completion / Import
REVIEW/NO-WRITE semantics, source hash/duration/publication validation and dry-run DB/audit/sequence invariants.

---

## 10. UMRAH / PROGRAM INTELLIGENCE — PRESERVE

Umrah canonical system remains complete for current canonical scope.

Direct Sync must reuse, not bypass:
- immutable `STP-*` allocation;
- validation;
- Hotel Intelligence references;
- archive-never-delete lifecycle;
- audit evidence;
- protected fixtures;
- private review semantics;
- Publishing Integration boundaries.

Existing JSON exporter/importer remains fallback and recovery evidence.

---

## 11. HOTEL INTELLIGENCE — PRESERVE OWNERSHIP

Hotel facts remain owned by Hotel Intelligence.

Neither Tour Direct Sync nor Umrah Direct Sync may duplicate canonical Hotel facts merely to simplify Sheet synchronization.

Stable Hotel references remain `STH-######`.

---

## 12. SEO OPERATING STATE

Weekly SEO work remains non-blocking for this engineering branch.

Backlog:
- classify crawled-currently-not-indexed URLs;
- verify intentional robots-blocked URL;
- later improve `/umre-vizesi-nasil-alinir/` CTR;
- monitor `umre fiyatları 2027` / `2027 umre fiyatları`;
- monitor Hotel query growth;
- no mass request-indexing/validation.

Direct Sync acceptance does not authorize indexation changes.

---

## 13. REPOSITORY OPERATING METHOD

- one coherent feature branch;
- static/lint gates before runtime;
- disposable runtime evidence;
- dedicated Direct Sync workflow;
- existing accepted regression workflows preserved;
- one release PR;
- no repeated Actions polling;
- no direct live-site edits;
- no automatic Merge;
- explicit owner approval required for Merge.

---

## 14. PROJECT PROGRESS — PLANNING ESTIMATE

```text
Site-wide SEO / Intelligence platform          ~91%
Tour Intelligence repository/runtime design     ~98% after accepted v1.0
Umrah canonical system                         100% current canonical scope
Umrah including direct Sheet operations         ~88% before Direct Sync acceptance
Tour Sheet operating pipeline                   ~55% before Direct Sync acceptance
Unified Direct Sync foundation                  ACTIVE CANDIDATE — not accepted yet
```

Do not call the operating pipeline 100% until the shared Direct Sync runtime/CI is accepted.

After Direct Sync repository/runtime acceptance:
- the planned Tour Intelligence architecture can be considered repository-complete for current scope;
- Umrah + Tour Sheet→WordPress operating architecture can be considered repository-complete for current scope;
- production installation/configuration/live-sync QA remains a separate operational deployment stage.

---

## 15. NEXT CHECKPOINT

> Finish `Unified Google Sheets Direct Sync — Umrah + Tours`, run baseline + dedicated disposable WordPress/MariaDB runtime + replacement package gates, inspect final CI once, and stop before Merge until explicit owner approval. Do not configure production secrets, install the plugin live, run a live sync or enable any public/SEO gate in this repository branch.
