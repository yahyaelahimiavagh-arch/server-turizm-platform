# SERVER TURIZM — SEO & PERFORMANCE MASTER PLAN
## 2026-09-14 — STTI v0.6.5 RUNTIME CLOSEOUT / v0.7.0 REVIEW RELATIONS

> **AUTHORITATIVE CURRENT MASTER PLAN — 2026-09-14**
>
> This file is the execution authority for the current Server Turizm engineering branch. The previous complete Master Plan is preserved byte-for-byte at `docs/archive/MASTER-PLAN-through-2026-09-14-pre-v070.md`. Historical accepted architecture, runtime evidence, rollback rules, and closed checkpoints remain valid unless this file explicitly supersedes them.

---

## 1. AUTHORITATIVE REPOSITORY / RUNTIME CHECKPOINT

Repository: `yahyaelahimiavagh-arch/server-turizm-platform`

Authoritative branch before the current feature branch: `main`

Verified `main` HEAD:

`403fecc9ac2d1fe2eada73ea24e3fda6aefa90b6`

Merge commit message:

`Merge PR #3: Add WordPress runtime and package pipeline`

Underlying tree SHA:

`26e1e7a06b2e28e49a62033c8bfe803abfff1f32`

Authoritative runtime references:

- `docs/CURRENT-RUNTIME-INVENTORY.md`
- `docs/evidence/STTI-v0.6.5-RUNTIME-ACCEPTANCE-2026-09-14.md`
- `.github/workflows/wordpress-runtime-and-package.yml`

### STTI v0.6.5 status

`STTI v0.6.5 — AI Completion Contract` is **CLOSED / RUNTIME ACCEPTED** on WordPress.

Accepted runtime evidence includes, with **NO WRITE**:

1. valid source-bound AI Completion -> `REVIEW` with proposed `CREATE`;
2. tampered `source_document_sha256` -> `INVALID`;
3. incorrect deterministic duration -> `INVALID`;
4. attempted public/indexable completion -> `INVALID`;
5. accepted PARTIAL import -> proposed `CREATE` dry-run only;
6. Tour table unchanged;
7. Audit table unchanged;
8. Stable-ID sequence unchanged.

PR #3 added the disposable WordPress + MariaDB runtime CI and automatic replacement ZIP + SHA256 packaging path. That infrastructure is merged. It does **not** deploy to production.

---

## 2. CURRENT ACTIVE STAGE — STTI v0.7.0 WORDPRESS REVIEW RELATIONS

Current feature branch:

`feature/stti-v0.7.0-review-relations`

STTI v0.7.0 is the next implementation stage. It is **not runtime accepted** until its PR gates pass. It is not authorized for production deployment merely because local/unit/CI tests pass.

### v0.7.0 scope

Private editorial relation modeling only:

- first-class **Route Variant** records;
- **Hotel Options** with explicit option grouping/selection state;
- **Hotel Intelligence relations** by canonical Stable ID only;
- **Transport relations** with explicit relation references;
- per-relation **Human Review** workflow;
- server-owned reviewer identity and timestamp;
- editorial approval gate that fails closed while active relations are unresolved, pending, contradictory, or structurally invalid.

### Canonical relation rules

#### Route Variant

A Route Variant may explicitly reference:

- existing route-stop IDs;
- Hotel relation IDs;
- Transport segment IDs.

Variant role is explicit:

`unknown | primary | alternative`

Zero primary variants is valid. The system must not invent a primary. More than one active primary is contradictory and blocked.

#### Hotel Option / Hotel Intelligence

Hotel option selection is explicit:

`unknown | selected | alternative`

Zero selected options in a group is valid. The system must not choose one automatically. More than one selected option inside one option group is contradictory and blocked.

A Hotel Intelligence link must use the canonical format:

`STH-######`

STTI stores the identity edge and Tour-specific stay/review context only.

Hotel Intelligence remains owner of Hotel facts, including:

- Hotel name;
- star rating;
- gallery/media;
- coordinates/map facts;
- amenities;
- Hotel verification state;
- Hotel public URL.

STTI must not duplicate these facts merely because a Hotel is related to a Tour.

An unresolved source Hotel name is valid private data. It stays unresolved until a real identity is known.

#### Transport relation

Transport keeps source-backed free text where supplied, but optional relation references are explicit:

- `from_stop_ref`;
- `to_stop_ref`;
- `route_variant_ref`.

Free-text origin/destination labels must never be silently matched to canonical stops.

### Human Review states

`pending | confirmed | rejected`

Rules:

- legacy relation rows without a review state become `pending`, never `confirmed`;
- client-supplied `reviewed_by` and `reviewed_at` are not trusted;
- WordPress stamps review transitions from the authenticated reviewer and server time;
- returning a relation to `pending` clears prior decision metadata;
- rejected rows may remain as private evidence when unreferenced;
- active Route Variants cannot reference rejected Hotel/Transport relations;
- active Transport cannot reference a rejected Route Variant;
- editorial `approved` is blocked until the active relation graph is ready.

### Truth policy

Unknown is a valid state. v0.7.0 MUST NOT infer or fabricate:

- a primary Route Variant;
- Route Variant membership from route text;
- a Hotel Intelligence Stable ID from a Hotel name;
- a selected Hotel Option;
- Transport stop/variant refs from free-text labels;
- Hotel facts owned by Hotel Intelligence;
- public/indexable readiness from Human Review completion.

---

## 3. PUBLIC / SEO LOCKS — HARD OFF

No v0.7.0 work authorizes public Tour exposure.

Remain OFF:

```text
Tour Public Master                 OFF
Tour public routes                 OFF
Tour indexation                    OFF
Tour sitemap inclusion             OFF
Tour schema output                 OFF
Tour canonical/robots output       OFF / UNTOUCHED
Tour homepage adapter              OFF
Automatic publication              OFF
Mass Tour URL generation           OFF
```

Private preview may remain admin-only/noindex and read canonical private data. A successful private renderer, Human Review, or CI run is never permission to publish.

No direct live-site change belongs to this stage.

---

## 4. v0.6.5 NON-REGRESSION CONTRACT

v0.7.0 must preserve the accepted v0.6.5 behavior.

Mandatory regression gates:

```text
Valid completion                         REVIEW / NO WRITE
Tampered source hash                     INVALID / NO WRITE
Wrong deterministic duration             INVALID / NO WRITE
Public/indexable request                 INVALID / NO WRITE
Accepted PARTIAL import                  CREATE proposal / NO WRITE
Tour table                               unchanged during dry-run
Audit table                              unchanged during dry-run
Stable-ID sequence                       unchanged during dry-run
```

The v0.6.5 AI Completion contract remains source-bound and human-review-only. v0.7.0 does not convert AI output into an automatic canonical write path.

---

## 5. v0.7.0 RELEASE / ACCEPTANCE GATES

Before v0.7.0 can be proposed for merge:

1. local relation contract tests PASS;
2. PHP syntax/lint PASS;
3. JavaScript syntax check PASS;
4. v0.6.5 static regression tests PASS;
5. disposable WordPress + MariaDB core runtime smoke PASS;
6. disposable WordPress + MariaDB v0.7 relation runtime PASS;
7. Hotel identity resolver proves identity-only linking with no Hotel-fact duplication;
8. Human Review approval gate fails closed for pending/unresolved relations;
9. relation review analysis proves NO WRITE;
10. Tour public/SEO locks remain OFF;
11. replacement ZIP + SHA256 is generated by CI;
12. no production deployment occurs;
13. final PR review is completed;
14. **merge requires explicit owner approval**.

Local contract status at the feature-branch preparation checkpoint:

```text
v0.7 relation assertions             23 / 23 PASS
PHP syntax                            PASS
review-relations.js syntax           PASS
```

This local evidence is candidate evidence only; it is not Runtime Acceptance.

---

## 6. EXECUTION ORDER TO FINISH TOUR INTELLIGENCE

Do not reopen completed SEO/Umrah design branches without regression evidence.

Current execution order:

```text
1. STTI v0.7.0 — WordPress Review Relations
2. STTI v0.7.1 — Canonical Geo Resolver
3. STTI v0.8.0 — Complete Customer Renderer
4. STTI v0.9.0 — First Real Full Tour
5. STTI v1.0 — Controlled Public Tour Pilot
6. Unified Google Sheets Direct Sync — Umrah + Tours
```

### v0.7.1 — Canonical Geo Resolver

Goals:

- reviewed coordinates;
- worldwide route support;
- no guessed coordinates;
- remove browser localStorage as a canonical dependency;
- unresolved geo stays unresolved.

### v0.8.0 — Complete Customer Renderer

Goals:

- consume complete canonical Tour data;
- variant-aware presentation;
- Hotel-option-aware presentation;
- preserve missing facts without fabricated filler;
- still private until public gates are separately approved.

### v0.9.0 — First Real Full Tour

Use one source-complete real Tour as the end-to-end production candidate.

Required:

- source evidence;
- canonical relations;
- reviewed Hotel links/options;
- reviewed Transport;
- reviewed geo;
- full renderer QA;
- audit evidence;
- no accidental public/SEO exposure.

### v1.0 — Controlled Public Tour Pilot

Only after explicit owner approval:

- Public Master Lock exists and defaults OFF;
- one intentional Tour route only;
- no mass route generation;
- schema/indexation/sitemap/canonical gates approved separately;
- fast rollback available.

---

## 7. UMRAH / PROGRAM INTELLIGENCE — PRESERVE

The Umrah canonical operating system is treated as complete for its current runtime scope. Do not redesign it merely because Tour development continues.

Preserve accepted Program/Umrah rules:

- Program facts -> Program Intelligence;
- Hotel facts -> Hotel Intelligence;
- Stable IDs are immutable identity;
- source row position is provenance, not identity;
- removed Programs archive, they are not normally deleted;
- `/umre-1/` remains the dynamic commercial Hub;
- Program detail indexation remains governed separately;
- protected fixtures remain protected;
- no duplicate business facts between Program and Hotel ownership.

The remaining operational Umrah task is not a new Umrah data model. It is connection to the future shared Direct Sync layer.

---

## 8. GOOGLE SHEETS — FINAL SHARED OPERATING MODEL

Do not build two independent direct-sync stacks.

The final target is one shared foundation for both Umrah and Tours:

```text
Google Sheet
→ operator action: Siteyi Güncelle
→ authenticated request
→ identity/checksum validation
→ domain adapter (Umrah or Tour)
→ validate before write
→ create / update / archive / unchanged / error
→ audit evidence
→ concise operator result
```

Required shared properties:

- HMAC-signed or equivalently authenticated requests;
- timestamp/nonce replay protection;
- idempotency keys;
- immutable Stable IDs (`STP-*`, `STT-*`);
- expected-checksum conflict protection;
- transactional validate-before-write;
- per-record error handling;
- one bad row must not cause a destructive global fail-close;
- archive-never-delete semantics where lifecycle applies;
- auditable result evidence;
- no automatic public/indexable exposure outside explicit publication rules;
- JSON import/export remains available as fallback/recovery transport.

Current Tour Sheet v0.6.2.1 is an accepted PARTIAL JSON producer with hidden Stable ID/checksum targeting. It is **not** direct WordPress sync and performs no WordPress write.

Umrah and Tour adapters are connected to the shared direct-sync foundation only after the Tour ingestion/review model is stable.

---

## 9. SEO OPERATING STATE

The 2026-09-14 weekly SEO gate is CLOSED / PASS for the purpose of resuming Tour engineering.

Preserve the current non-blocking backlog:

1. classify `Crawled - currently not indexed` URLs by type;
2. verify the single robots-blocked URL is intentional;
3. later improve CTR/intent alignment for `/umre-vizesi-nasil-alinir/`;
4. monitor `umre fiyatları 2027` / `2027 umre fiyatları`;
5. monitor Hotel query growth;
6. do not mass-request indexing or mass-validate exclusions.

Known accepted URL-hygiene closeout:

- historical `/wp-content/themes/porto` 5xx -> fixed to 404 behavior;
- classified intentional legacy 404s preserved;
- six exact legacy redirects accepted;
- no wildcard `/tour/*` redirect introduced.

---

## 10. REPOSITORY OPERATING METHOD

From this checkpoint:

- perform development, analysis, linting, and deterministic tests locally as far as practical;
- do not make a GitHub request for each tiny step;
- do not repeatedly poll Actions;
- prepare a coherent release candidate first;
- use one feature branch per coherent release;
- use one PR when the release candidate is ready;
- perform one final GitHub/CI review after the workflows finish;
- do not merge automatically;
- do not modify the live site from this engineering branch;
- merge requires explicit owner approval.

---

## 11. CURRENT PROJECT PROGRESS — PLANNING ESTIMATE

These are planning estimates, not automated telemetry.

```text
Site-wide SEO / Intelligence platform        ~82%
Tour Intelligence                             ~74% before v0.7 merge/acceptance
Umrah canonical system                       100% for current system scope
Umrah including direct Sheet operations       ~88%
Tour Sheet operating pipeline                 ~55%
Unified direct Sheets → WordPress foundation  ~25%
```

The remaining path is intentionally concentrated:

`v0.7.0 → Geo → Renderer → Real Tour → Controlled Pilot → Unified Sheet Connector`

Do not inflate completion percentages merely because code exists; only accepted runtime gates should materially advance them.

---

## 12. NEXT CHECKPOINT

Immediate target:

> Finish `STTI v0.7.0 — WordPress Review Relations` on the feature branch, run baseline + WordPress/MariaDB CI exactly once through the release PR, inspect final evidence, and stop before Merge until the owner explicitly approves it.

After v0.7.0 is accepted, continue directly to Canonical Geo Resolver without reopening completed branches.
