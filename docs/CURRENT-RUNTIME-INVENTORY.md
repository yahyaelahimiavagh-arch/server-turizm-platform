# Current Runtime Inventory

**Inventory date:** 2026-09-16

**Repository authority:** `yahyaelahimiavagh-arch/server-turizm-platform`

**Merged main checkpoint before this documentation closeout:** `beec4f95245c5a1407c7a7cfcc244cb5c6f3ba66`

**Evidence basis:** Production Google Sheets selected-row tests, Production STTI operator/review/approval screenshots, Production Hub visibility screenshots, merged repository/runtime evidence through PR #24, and previously accepted live Server Turizm baselines.

## Classification rules

- `PRODUCTION ACCEPTED`: directly observed in Production and accepted for the stated scope.
- `LIVE AUTHORITATIVE`: confirmed active/aligned with accepted Production evidence.
- `ACCEPTED INTEGRATION`: accepted operator integration, not itself a WordPress plugin.
- `MERGED / REPO+RUNTIME ACCEPTED`: merged code with accepted repository/disposable runtime evidence.
- `LAST KNOWN LIVE`: last documented Production version; reverify before a rollout depending on exact version.
- `PRESERVE / OFF`: intentionally disabled boundary that must remain disabled until a separate gate.
- `LEGACY / NON-ACTIVE SOURCE`: retained for audit/rollback, not an active deployment source.

---

## WordPress plugins

| Repository path | Known version/status | Classification | Current note |
| --- | ---: | --- | --- |
| `wordpress/plugins/program-intelligence` | `0.3.5` | LAST KNOWN LIVE / PRESERVE | Immutable `STP-*`, lifecycle/archive snapshots and identity runtime. |
| `wordpress/plugins/program-publishing-integration` | `0.4.14` | LAST KNOWN LIVE / PRESERVE | Program publication registry boundary. |
| `wordpress/plugins/tour-intelligence` | `1.1.7` | PRODUCTION ACCEPTED | Operator editor + Review Queue + guarded Approval + explicit per-Tour Hub visibility. |
| `wordpress/plugins/direct-sync-foundation` | repo `0.1.4`; accepted live contract | ACCEPTED INTEGRATION | Shared REST gateway used successfully by selected-row Tour CREATE/UPDATE. |
| `wordpress/plugins/hotel-intelligence` | `0.9.11` last known live | LAST KNOWN LIVE / PRESERVE | Canonical `STH-*` Hotel ownership retained. |

---

## Repository progression

```text
STTI v0.6.5   AI Completion Review Contract       MERGED / ACCEPTED
STTI v0.7.0   Review Relations                    MERGED / ACCEPTED
STTI v0.7.1   Canonical Geo                       MERGED / ACCEPTED
STTI v0.8.0   Customer Renderer                   MERGED / ACCEPTED
STTI v0.9.0   First Real Full Tour                MERGED / ACCEPTED
STTI v1.0.0   Controlled Public Tour Pilot        MERGED / CONTROLLED
STTI v1.1.x   Dynamic Culture Tours Hub           MERGED
STTI v1.1.2   Operator-first editor               PRODUCTION ACCEPTED
STTI v1.1.3+  Review Queue                        PRODUCTION ACCEPTED
STTI v1.1.6   Guarded editorial Approval          PRODUCTION ACCEPTED
STTI v1.1.7   Explicit per-Tour Hub visibility    PRODUCTION ACCEPTED
```

---

## Active Google Sheets integrations

### Umrah — independent bound-script stack

| Repository path | Runtime status | Classification |
| --- | --- | --- |
| `integrations/google-sheets/umrah/ST-Umrah-Config.gs` | clean stack config | LIVE AUTHORITATIVE |
| `integrations/google-sheets/umrah/ST-Umrah-Exporter.gs` | canonical-hash-compatible exporter | LIVE AUTHORITATIVE |
| `integrations/google-sheets/umrah/ST-Umrah-Direct-Sync.gs` | independent Umrah Direct Sync | LIVE AUTHORITATIVE |
| `integrations/google-sheets/umrah/ST-Umrah-Menu.gs` | independent Umrah menu | LIVE AUTHORITATIVE |

Hidden identity sheet:

`ST Umrah Sync State`

Preserved accepted state migration:

```text
Migrated state: 36
Invalid/skipped: 0
WordPress writes: 0
```

### Tour — independent bound-script stack

| Repository path | Runtime version/status | Classification |
| --- | --- | --- |
| `integrations/google-sheets/tours/ST-Tour-Generator.gs` | implementation `1.0.2`; producer contract `0.6.2.1` | LIVE AUTHORITATIVE SOURCE |
| `integrations/google-sheets/tours/ST-Tour-Direct-Sync.gs` | Tour Direct Sync `1.0.1` | PRODUCTION ACCEPTED |
| `integrations/google-sheets/tours/ST-Tour-Menu.gs` | independent Tour menu | PRODUCTION ACCEPTED |

Technical identity fields:

- `STTI Stable ID`
- `Expected Checksum`

They are header-driven, created together if absent and hidden from normal operator use. Fixed Z/AA targeting is retired.

---

## Tour Sheet Production acceptance

Controlled fixture:

```text
Local ID: ID-642D23A3
Stable ID: STT-000002
Title: Iran Test Turu Update Test
```

Accepted CREATE sequence:

```text
Local validation  → Target NEW
Precheck          → STT-000002 — CREATE
Apply             → STT-000002 — CREATE
Second precheck   → STT-000002 — UNCHANGED
```

Accepted UPDATE sequence:

```text
Local validation  → Target STT-000002
Precheck          → STT-000002 — UPDATE
Apply             → STT-000002 — UPDATE
Second precheck   → STT-000002 — UNCHANGED
```

Result:

```text
Selected-row CREATE path      PRODUCTION ACCEPTED
Selected-row UPDATE path      PRODUCTION ACCEPTED
Stable ID continuity          PASS
Hidden checksum continuity    PASS
Post-write idempotency        PASS / UNCHANGED
Duplicate loop                NOT OBSERVED
Bulk Tour sync                NOT AUTHORIZED / NOT TESTED
```

Transport note:

- intermittent DNS/latency was observed during earlier Production tests;
- bounded retry recovered successful calls;
- one validate attempt exceeded Apps Script maximum execution time;
- canonical identity/idempotency remained correct;
- after uncertain transport outcome, validate/status first and do not blindly repeat Apply.

---

## Direct Sync contract

Contract:

`ST-DIRECT-SYNC-1.0.0`

Production endpoint:

`https://www.serverturizm.com.tr/wp-json/server-turizm/v1/direct-sync`

Server configuration:

- `ST_DIRECT_SYNC_KEY_ID`
- `ST_DIRECT_SYNC_SECRET`

Tour Apps Script Properties:

- `ST_DIRECT_SYNC_ENDPOINT`
- `ST_DIRECT_SYNC_KEY_ID`
- `ST_DIRECT_SYNC_SECRET`

Secrets remain external to Git and Sheet cells.

Accepted transport properties:

- bounded retry;
- stable request ID/body across transport retries;
- fresh timestamp/nonce/HMAC per transport attempt;
- safe replay/poll handling;
- no business failure retried into an unintended write.

---

## STTI operator workflow — Production accepted

### Operator-first editor

Production record:

`STT-000002`

Accepted behavior:

- common operational fields visible in simple mode;
- technical fields available via Advanced mode;
- Stable ID immutable;
- no-change save returned `Private candidate unchanged. Public output remains OFF.`;
- no duplicate Tour was created.

### Review Queue

Production accepted summary cards:

- Source;
- Route;
- Geo;
- Hotels;
- Transport;
- Dates;
- Price;
- Day-by-day Program.

For `STT-000002`:

```text
Initial blockers      13
After Hotel review     7
After Transport review 3
After Geo review       0
```

Unsupported placeholder Hotel/transport facts were not invented; unresolved test-only relations were explicitly reviewed/rejected.

### Guarded editorial Approval

Production accepted transition:

```text
STT-000002
Editorial: NEEDS_REVIEW → APPROVED
Hard blocker: 0
```

Approval preserved:

```text
Public route      OFF
Hub visibility   OFF
Indexation        OFF
Sitemap           OFF
Homepage          OFF
Hub Master        OFF
```

Approval is idempotent and is not a publication action.

---

## STTI v1.1.7 explicit per-Tour Hub visibility

### Reason for the gate

Production QA showed that the older Hub eligibility logic counted an editorially approved record immediately even though Hub visibility was still shown as OFF.

That was unsafe because:

`STT-000002 / Iran Test Turu Update Test`

is a **test fixture**, and there is currently **no real Iran Tour for sale**.

### New invariant

A Tour is Hub-renderable only when:

```text
editorial = approved
AND not cancelled/archived
AND not past
AND publication.hub_visible = true
```

Missing `hub_visible` defaults to false.

### Production evidence after installing v1.1.7

Observed Tour Hub admin state:

```text
STT-000002                     APPROVED
Hub görünürlüğü                KAPALI
Hub'da gösterilecek tur        0
Dynamic Tour Hub aktif         unchecked / OFF
```

This is the accepted Production state.

Do not press `Hub’da Göster` for `STT-000002`.

---

## Culture Tours Hub

Existing WordPress page:

`/kultur-turlari/`

Hub Master option:

`stti_v110_hub_master`

Current Production state:

`OFF`

Observed while OFF:

- existing WordPress `/kultur-turlari/` page remains unchanged;
- STTI dynamic cards do not replace content;
- Hub-visible count is currently `0`;
- rollback path is preserved.

### Gate hierarchy

```text
Editorial APPROVED
    ↓ separate gate
Hub visibility AÇIK
    ↓ separate gate
Hub Master AÇIK
    ↓ still separate
Individual Tour public route / indexation / sitemap / schema
```

No lower gate implies the next one.

---

## Current Production test fixture

| Field | State |
| --- | --- |
| Stable ID | `STT-000002` |
| Title | `Iran Test Turu Update Test` |
| Purpose | TEST / PRIVATE FIXTURE |
| Editorial | `APPROVED` |
| Hard blockers | `0` |
| Hub visibility | `KAPALI` |
| Public route | `OFF` |
| Indexation | `OFF` |
| Sitemap | `OFF` |
| Homepage | `OFF` |
| Hub Master | `OFF` |
| Public Culture Hub appearance | NONE |

This record must not be presented as a real Server Turizm Iran product.

---

## SEO / publication locks

Preserve:

- `/umre-1/` accepted commercial Hub baseline;
- Program detail indexation OFF;
- Program sitemap OFF;
- protected Program fixtures `STP-000036` / `STP-000037`;
- Tour Hub visibility explicit per Tour;
- Tour Hub Master OFF until a separate owner-approved real-Tour rollout;
- individual Tour public/indexation/sitemap/schema/canonical/homepage gates independently controlled;
- editorial approval does not imply publication;
- no mass Search Console submission merely because Direct Sync or Approval succeeds.

---

## Current outcomes

| Gate | Result |
| --- | --- |
| Umrah separated selected-row no-change | PRODUCTION ACCEPTED |
| Tour selected-row CREATE | PRODUCTION ACCEPTED |
| Tour selected-row UPDATE | PRODUCTION ACCEPTED |
| Tour post-write idempotency | PRODUCTION ACCEPTED / `UNCHANGED` |
| Operator-first Tour editor | PRODUCTION ACCEPTED |
| Review Queue | PRODUCTION ACCEPTED |
| Guarded Editorial Approval | PRODUCTION ACCEPTED |
| STTI v1.1.7 explicit Hub visibility | PRODUCTION ACCEPTED |
| `STT-000002` editorial | `APPROVED` / TEST FIXTURE |
| `STT-000002` Hub visibility | OFF |
| Current Hub-visible Tours | 0 |
| Tour Hub Master | OFF |
| `/kultur-turlari/` while Master OFF | PRESERVED |
| Program mass indexation/sitemap | OFF |
| Tour mass public/SEO gates | OFF / SEPARATE |

---

## Next runtime checkpoint

Wait for a **real Culture Tour**.

Expected sequence:

```text
real Sheet row
→ Direct Sync validate/apply
→ canonical STT-* record
→ post-write UNCHANGED proof
→ source-backed Review Queue completion
→ guarded Editorial Approval
→ explicit Hub visibility AÇIK for that real Tour only
→ verify Hub-visible count
→ explicit owner gate for Hub Master
→ desktop/mobile QA + rollback proof
```

Until then:

```text
STT-000002 Hub visibility = OFF
Hub-visible Tours = 0
Hub Master = OFF
/kultur-turlari/ = existing WordPress page
```

---

## Retired/non-active Apps Script paths

The following remain non-active legacy/operator paths and must not be copied into the active Production Sheet stack:

- `integrations/google-sheets/shared/ST-Direct-Sync.gs`
- `integrations/google-sheets/shared/ST-Direct-Sync-Menu.gs`
- `integrations/google-sheets/shared/ST-Direct-Sync-Pilot.gs`
- `integrations/google-sheets/shared/ST-Direct-Sync-Archive.gs`
- `integrations/google-sheets/umrah/ST_TDE_Exporter.gs`
- `integrations/google-sheets/tours/STTI-v0.6.2.1-Sheet-to-Partial-JSON.gs`

---

## Runtime safety rules

Stop before mutation if any of these appears unexpectedly:

- CREATE for an existing Tour;
- wrong Stable ID;
- CONFLICT / INVALID / ERROR;
- unexpected checksum replacement;
- duplicate canonical Tour;
- unsupported facts being invented;
- test fixture becoming Hub-visible;
- Hub Master toggling unexpectedly;
- public/indexation/schema/sitemap/homepage state changing unexpectedly;
- uncertain transport outcome after timeout.

No bulk mutation or mass public/SEO action is authorized by the current acceptance.
