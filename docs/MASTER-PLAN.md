# SERVER TURIZM — SEO & PERFORMANCE MASTER PLAN
## 2026-09-14 EOD — DIRECT SYNC v0.1.2 PRODUCTION SELECTED-ROW ACCEPTED / PUBLIC PROPAGATION INCIDENT OPEN

> **AUTHORITATIVE EOD HANDOFF — 2026-09-14**
>
> This document records the exact repository and Production state reached tonight. Repository acceptance, plugin installation and a successful private/canonical sync do **not** by themselves authorize mass sync, archive, Tour live sync, public/indexation changes or automatic republishing.

---

## 1. AUTHORITATIVE CHECKPOINT

Repository: `yahyaelahimiavagh-arch/server-turizm-platform`

Authoritative branch before this documentation PR: `main`

Verified `main` HEAD after owner-approved PR #11 merge:

`c87f81a43b4ff60f32c3ebd21d8f7ea288c82c47`

Merge title:

`Direct Sync v0.1.2 — Fix Umrah sidecar source-hash drift`

Accepted merge chain today:

```text
PR #8   STTI v1.0.0 Controlled Public Tour Pilot
PR #9   Unified Google Sheets Direct Sync — Umrah + Tours
PR #10  Direct Sync v0.1.1 Production Rollout Hardening
PR #11  Direct Sync v0.1.2 Umrah sidecar source-hash drift fix
```

Relevant merge commits:

```text
PR #9   9b8e9fbcb0e25486d65667ef01a9d75898e3fced
PR #10  340c3bb185fd516733a2f610dcecebc4da79df21
PR #11  c87f81a43b4ff60f32c3ebd21d8f7ea288c82c47
```

---

## 2. ACCEPTED TOUR INTELLIGENCE BASELINE

`STTI v1.0.0 — Controlled Public Tour Pilot` remains **MERGED / DISPOSABLE WORDPRESS RUNTIME ACCEPTED**.

Accepted chain:

```text
v0.6.5  AI Completion / Import NO-WRITE        ACCEPTED
v0.7.0  Review Relations                       ACCEPTED
v0.7.1  Canonical Geo Resolver                 ACCEPTED
v0.8.0  Complete Customer Renderer             ACCEPTED
v0.9.0  First Real Full Tour                   ACCEPTED
v1.0.0  Controlled Public Tour Pilot           ACCEPTED
```

v1.0 invariants remain unchanged:
- exact allowlist pilot only: `STT-000001`;
- exact route only: `/turlar/buyuk-iran-kultur-turu/`;
- Public Master / Route / Indexation / Canonical / Schema / Sitemap are independent controls;
- every public/SEO gate defaults OFF;
- Master OFF collapses all child gates;
- no wildcard Tour route;
- no mass Tour URL generation;
- no automatic sitemap/indexation/schema/canonical exposure;
- no rewrite flush required;
- no public gate may be enabled merely because repository/runtime tests pass.

Production Tour Intelligence was upgraded tonight from the previously inventoried `0.6.5` to **v1.0.0** and remained active. No Tour public/SEO gate was intentionally enabled during this rollout.

---

## 3. UNIFIED DIRECT SYNC — REPOSITORY STATE

Contract:

`ST-DIRECT-SYNC-1.0.0`

Current accepted plugin release:

`Server Turizm Direct Sync Foundation v0.1.2`

WordPress plugin:

`wordpress/plugins/direct-sync-foundation/`

Shared Apps Script components:

```text
integrations/google-sheets/shared/ST-Direct-Sync.gs
integrations/google-sheets/shared/ST-Direct-Sync-Menu.gs
integrations/google-sheets/shared/ST-Direct-Sync-Pilot.gs
```

Existing source producers remain preserved:
- Umrah: `ST_TDE_Exporter.gs`;
- Tours: `STTI-v0.6.2.1-Sheet-to-Partial-JSON.gs`.

Repository/runtime acceptance includes:
- HMAC-SHA256 body binding;
- timestamp skew rejection;
- nonce replay protection;
- request-ID idempotency;
- changed-body reuse conflict;
- immutable Stable IDs;
- expected-checksum concurrency;
- validate-before-write;
- CREATE / UPDATE / UNCHANGED / ARCHIVE / CONFLICT / ERROR semantics;
- archive-never-delete;
- disposable WordPress + MariaDB runtime;
- replacement ZIP + SHA256 package evidence.

---

## 4. PRODUCTION INSTALLATION STATE — 2026-09-14 EOD

Production changes intentionally completed tonight:

```text
Server Turizm Tour Intelligence          0.6.5 → 1.0.0    ACTIVE
Server Turizm Direct Sync Foundation     new → 0.1.2      ACTIVE
Server Turizm Program Intelligence       remains 0.3.5    ACTIVE
Program Publishing Integration           remains 0.4.14   ACTIVE
Hotel Intelligence                       remains 0.9.11   ACTIVE
```

Direct Sync credentials are configured externally in `wp-config.php`:
- `ST_DIRECT_SYNC_KEY_ID` configured;
- `ST_DIRECT_SYNC_SECRET` configured;
- secret value is not committed and must never be copied into Git, Sheet cells or chat evidence.

Apps Script configuration is stored in Script Properties:
- Direct Sync endpoint;
- matching Key ID;
- matching secret.

The installable `Server Turizm Sync` menu is active in the Umrah Google Sheet.

No mass sync was authorized.

---

## 5. DIRECT SYNC v0.1.1 INCIDENT — CLOSED BY v0.1.2

During the first controlled Production pilot on existing Umrah record:

```text
Sheet Program No: 223
Stable ID:        STP-000005
```

Initial no-change sequence on `v0.1.1`:

```text
PRECHECK   → UNCHANGED
APPLY      → UNCHANGED
PRECHECK   → UPDATE   ← unexpected
```

Root cause:
- first response stored `STP-000005` + checksum in the hidden sidecar;
- the next request placed server-assigned `program_id` into the source Program payload;
- Program Intelligence payload hashing included that field;
- unchanged source content therefore hashed differently after sidecar bootstrap.

No canonical business-data mutation occurred in the observed first `UNCHANGED` apply.

### v0.1.2 fix

Direct Sync now:
- normalizes Google Sheets source content with `program_id = null` before source-content validation/planning/hashing/import;
- uses sidecar Stable ID only as identity/concurrency control;
- requires Stable ID and source-planned entity to agree;
- preserves checksum enforcement;
- fails closed on Stable-ID/source mismatch.

Runtime regression reproduced the exact incident sequence and passed.

---

## 6. PRODUCTION SELECTED-ROW UMRAH EVIDENCE — ACCEPTED SCOPE

After Production upgrade to `Direct Sync v0.1.2`, the existing record `Program 223 / STP-000005` passed the following live sequence:

### No-change / idempotency

```text
PRECHECK    STP-000005 — UNCHANGED
APPLY       STP-000005 — UNCHANGED
PRECHECK    STP-000005 — UNCHANGED
```

### Controlled real UPDATE

A real phone-number change was made in the Sheet.

Observed sequence:

```text
PRECHECK    STP-000005 — UPDATE
APPLY       STP-000005 — UPDATE
PRECHECK    STP-000005 — UNCHANGED
```

This proves, for the selected-row existing-Umrah path:
- Stable ID remains fixed;
- duplicate was not created;
- no-change is idempotent;
- a real source change is detected as UPDATE;
- apply updates the same canonical entity;
- after apply, the same source becomes UNCHANGED.

### Accepted wording

`Selected-row Umrah Direct Sync — existing-record canonical sync path` is **PRODUCTION RUNTIME PROVEN for idempotency + controlled UPDATE**.

Do **not** generalize this acceptance to:
- full active Umrah batch sync;
- live CREATE;
- live ARCHIVE;
- Tour Direct Sync on Production;
- automatic public republishing;
- public/indexation/SEO changes.

---

## 7. OPEN PRODUCTION INCIDENT — PROGRAM 223 DISAPPEARED FROM /umre-1/

After the real phone-number UPDATE, `Program 223` disappeared from the public `/umre-1/` Hub.

This is **not deletion** and is **not Stable-ID loss**.

Confirmed cause:
1. Program Intelligence import/update semantics intentionally set updated candidates to `workflow.editorial = needs_review`;
2. Publishing Integration requires an `approved + scheduled + current/upcoming` Program for its public renderer/Hub eligibility;
3. the changed canonical payload also invalidates the previously pinned publishing hash until the route is reviewed/prepared again.

Therefore the Direct Sync UPDATE correctly changed canonical data but exposed a production workflow gap: a live/public Program can be silently removed from Hub visibility when canonical UPDATE demotes it to review.

### Current incident state at EOD

- `STP-000005` still exists;
- canonical UPDATE completed;
- subsequent Direct Sync precheck is `UNCHANGED`;
- Program 223 is currently absent from `/umre-1/` pending explicit review/approval + route prepare refresh;
- no mass recovery or Step 2 public opening has been executed tonight.

### Safety classification

**OPEN / RECOVERY PENDING — stop additional Direct Sync applies until Program 223 is restored and v0.1.3 workflow hardening is designed.**

---

## 8. FIRST TASK TOMORROW — RESTORE PROGRAM 223 SAFELY

Before any further Sheet sync:

1. Open WordPress → `Program Intelligence → Yayın & Entegrasyon`.
2. Select **only** `STP-000005 / Program 223`.
3. Confirm the canonical approval + final-route preparation acknowledgement.
4. Run only:

```text
1 · TÜM UYGUNLARI APPROVE + PREPARE ET
```

5. Do **not** run Step 2 `PUBLIC / NOINDEX AÇ` as part of this recovery unless separately justified by the existing route state.
6. Refresh `/umre-1/`.
7. Confirm Program 223 is visible again.
8. Confirm the updated phone number is the one used by Program 223.
9. Confirm neighboring Programs such as 222/224 are unchanged.
10. Capture runtime evidence before continuing development.

If the Step 1 preflight returns BLOCKED/error, stop and diagnose; do not use broader recovery actions casually.

---

## 9. NEXT ENGINEERING CHECKPOINT — DIRECT SYNC v0.1.3 PUBLIC-IMPACT SAFETY

The next branch must solve the workflow gap revealed tonight.

Non-negotiable behavior:
- Direct Sync must never silently make an already-live/public Program disappear from the Hub;
- Direct Sync must not blindly preserve `approved` after arbitrary business-data changes merely to keep a card visible;
- human-review boundaries remain real;
- public/indexation controls remain separate;
- no automatic SEO/indexation activation.

Preferred safety direction for design review:

```text
PRECHECK detects whether target currently participates in a live/public registry
→ returns explicit PUBLIC_REVIEW_REQUIRED / public-impact warning
→ APPLY refuses or requires a dedicated coordinated workflow
→ canonical UPDATE + human review + route hash refresh are treated as one controlled operator sequence
→ no silent Hub disappearance
```

Alternative transactional designs may be considered, but any accepted design must preserve human approval semantics and provide fast rollback.

Required regression case for v0.1.3:
1. public/current approved Program exists;
2. Sheet source changes;
3. validate clearly reports UPDATE + public impact;
4. unsafe direct apply cannot silently demote/remove public visibility;
5. explicit reviewed path updates canonical data;
6. route hash is refreshed only after approval;
7. Hub retains or intentionally pauses the card with visible operator intent, never accidental disappearance;
8. no other Program/public gate changes.

---

## 10. CURRENT SAFETY LOCKS

Until the open incident is closed:

**DO NOT RUN:**

```text
Siteyi Güncelle — Tüm Aktif Umrah
Seçili Turu Arşivle
Tour Direct Sync apply on Production
bulk archive
mass publication recovery
Tour Public/Indexation/Schema/Sitemap/Canonical gates
```

Selected-row Umrah **precheck** may be used for diagnosis, but additional APPLY should wait until 223 recovery and v0.1.3 safety decision.

---

## 11. IDENTITY + CONCURRENCY CONTRACT — PRESERVE

### Umrah

Stable identity: `STP-######`.

Technical sidecar:

`ST Direct Sync State`

Key:

`adapter + document_ref + worksheet + source_row`

Values include:
- Stable Program ID;
- expected canonical checksum.

Sidecar identity data must never change Google Sheets source-content hashing.

### Tours

Stable identity: `STT-######`.

Controls:
- Z = Stable ID;
- AA = Expected Checksum.

Checksum mismatch fails closed.

---

## 12. ARCHIVE POLICY — PRESERVE

Archive is never delete.

Umrah:
- explicit source-removal intent;
- lifecycle transition;
- archive snapshot;
- Stable ID retained.

Tours:
- entity retained;
- editorial archived;
- availability closed;
- audit evidence retained.

No live archive acceptance has been granted from tonight's Production session.

---

## 13. PUBLICATION SAFETY — HARD SEPARATION

Direct Sync synchronizes canonical operating data; it is not publication authorization.

It must never directly enable:
- Tour Public Master;
- Tour public route;
- Tour indexation;
- Tour sitemap;
- Tour schema;
- Tour canonical exposure;
- Tour homepage exposure;
- automatic Program indexation.

Program publication remains controlled by Program Intelligence / Publishing Integration lifecycle and review boundaries.

The incident tonight proves that canonical-update and publication lifecycle must be coordinated explicitly rather than assumed independent in operator UX.

---

## 14. HOTEL INTELLIGENCE — PRESERVE OWNERSHIP

Hotel facts remain owned by Hotel Intelligence.

Neither Umrah Direct Sync nor Tour Direct Sync may duplicate canonical Hotel data for convenience.

Stable Hotel identity remains `STH-######`.

---

## 15. SEO OPERATING STATE

No SEO/indexation expansion was authorized tonight.

Backlog remains:
- classify crawled-currently-not-indexed URLs;
- verify intentional robots-blocked URL;
- improve `/umre-vizesi-nasil-alinir/` CTR later;
- monitor `umre fiyatları 2027` / `2027 umre fiyatları`;
- monitor Hotel query growth;
- no mass request-indexing/validation.

Direct Sync production acceptance does not authorize indexation changes.

---

## 16. PROJECT PROGRESS — EOD PLANNING ESTIMATE

```text
Site-wide SEO / Intelligence platform                     ~93%
Tour Intelligence repository/runtime design                ~98%
Tour Intelligence Production install                       DONE, public gates still controlled
Umrah canonical system                                    100% current canonical scope
Unified Direct Sync repository/runtime foundation         100% current foundation scope
Selected-row Umrah existing-record Production path         ~90% — canonical sync proven, public-impact workflow open
Full Active Umrah sync                                      NOT ACCEPTED
Live Umrah CREATE                                            NOT ACCEPTED
Live Umrah ARCHIVE                                           NOT ACCEPTED
Tour Direct Sync Production                                  NOT ACCEPTED
Public propagation after canonical UPDATE                    OPEN INCIDENT / v0.1.3 REQUIRED
```

Do not call the full operating pipeline 100% until the public-impact workflow, full-batch safety and remaining live paths are explicitly accepted.

---

## 17. REPOSITORY OPERATING METHOD

- use a dedicated branch for meaningful changes;
- static/lint gates before runtime;
- disposable runtime evidence before claims;
- dedicated workflows preserved;
- no repeated Actions polling;
- no automatic Merge;
- explicit owner approval required before Merge;
- no production write merely because CI is green;
- Production incident recovery is separate from repository acceptance;
- never claim accepted without runtime evidence.

---

## 18. TOMORROW START HERE

> **STOP STATE:** no more Production sync tonight.
>
> **First:** restore `STP-000005 / Program 223` through explicit Review/Approve + Step 1 Prepare only, then verify `/umre-1/` and the updated phone number.
>
> **Second:** open a new `Direct Sync v0.1.3` safety branch for public-impact detection / coordinated review workflow. Do not solve the incident by auto-approving arbitrary changes.
>
> **Third:** after v0.1.3 runtime acceptance and owner merge approval, repeat one controlled live UPDATE and verify that canonical data changes without an accidental Hub disappearance.
>
> **Only after that:** consider a second existing Umrah record, full-active Umrah safety design, Tour Direct Sync Production pilot, and archive tests.
