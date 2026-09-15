# SERVER TURIZM — SEO & PERFORMANCE MASTER PLAN
## 2026-09-15 — TOUR HUB v1.1 REPO/RUNTIME ACCEPTED — MERGE PENDING

> **AUTHORITATIVE CURRENT MASTER PLAN — 2026-09-15**
>
> Repository acceptance, Production runtime acceptance, Merge approval, WordPress plugin deployment, and public/indexation activation remain separate gates. Never infer one from another.

---

## 1. AUTHORITATIVE CHECKPOINT

Repository: `yahyaelahimiavagh-arch/server-turizm-platform`

Authoritative branch: `main`

Current merged `main`:

`3a906f037512ce971fc37c5516c0b8a2f5cf4e84`

That merge contains PR #17 / separated Umrah + Tour Apps Script runtimes.

Current release candidate:

- branch: `feature/tour-hub-v1.1`
- PR: `#18 — Tour Hub v1.1 — dynamic /kultur-turlari/ + future Tour pipeline`
- repository/static gates: **PASS**
- disposable WordPress + MariaDB Tour Hub runtime: **PASS**
- existing STTI v1.0 public-pilot regression: **PASS**
- Baseline verification: **PASS**
- Unified Direct Sync runtime: **PASS**
- Tour Intelligence replacement ZIP build: **PASS**
- merge: **PENDING explicit owner approval**
- Production WordPress installation / Hub activation: **NOT AUTHORIZED by repository acceptance**

No individual Tour public/indexation/SEO gate is opened by this checkpoint.

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

Production WordPress Direct Sync version was not re-verified in the separated-Sheets/Tour-Hub checkpoints. Do not infer v0.1.4 live deployment without explicit version evidence or replacement + runtime test.

---

## 3. GOOGLE SHEETS ARCHITECTURE — HARD-SEPARATED

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

Stable identity: `STP-######`.

Independent Umrah hidden state:

`ST Umrah Sync State`

Identity key:

`document_ref + worksheet + source_row`

Stored values: Stable ID, expected canonical checksum, updated timestamp.

### Migration evidence

2026-09-15 one-time migration:

```text
Migrated Umrah state: 36
Invalid/skipped state rows: 0
WordPress WRITE: none
```

Accepted selected-row fixture:

`STP-000005 / Program No 223`

Observed:

```text
Precheck → STP-000005 — UNCHANGED
Apply    → STP-000005 — UNCHANGED
```

---

## 5. UMRAH SOURCE CONTRACT

Visible `Home` remains authoritative.

Removal/archive control is exactly:

`AD = Programı Kaldır`

No new `KALDIR` business column is part of the accepted contract.

Rules:

- checked rows leave active export;
- historical checked rows with no accepted `STP-* + checksum` are Sheet-only closed;
- they are never CREATEd merely to archive them;
- linked rows may enter controlled archive after server validation and operator confirmation;
- archive is never hard delete.

Mass legacy live UPDATE remains a separate future gate.

---

## 6. TOUR IDENTITY / STATE

Stable identity: `STT-######`.

Tour technical identity fields are header-driven:

- `STTI Stable ID`
- `Expected Checksum`

Rules:

- both must exist together and be adjacent;
- if absent they are appended after the last business column;
- technical columns are hidden;
- business columns such as `Vize` are never overwritten;
- existing Tour targeting requires Stable ID + checksum.

Generator implementation is `1.0.2`; payload `producer.version` remains `0.6.2.1` to preserve accepted canonical payload hashes.

Accepted fixture:

`STT-000001 / IRN-2026-01 / Büyük İran Turu`

Observed:

```text
Precheck → STT-000001 — UNCHANGED
Apply    → STT-000001 — UNCHANGED
```

---

## 7. TOUR HUB v1.1 — DYNAMIC `/kultur-turlari/`

Contract:

`STTI-TOUR-HUB-1.1.0`

Repository release candidate bumps Tour Intelligence to `1.1.0` while preserving the accepted v1.0 controlled single-Tour public pilot as an independent baseline.

### Existing page is preserved as route/SEO owner

Tour Hub does **not** create a new route and does not use rewrite rules.

When Hub Master is explicitly enabled, it replaces only the content area of the existing WordPress page:

`/kultur-turlari/`

The existing WordPress page continues to own:

- URL/permalink;
- theme header/footer;
- page-level SEO metadata;
- canonical/indexation behavior;
- rollback content.

Hub option:

`stti_v110_hub_master`

Default: **OFF**.

OFF = legacy/current WPBakery page remains authoritative immediately.

### Canonical eligibility

Hub reads directly from canonical Tour Intelligence rows. There is no separate Hub Tour database.

A Tour appears only when:

- `editorial = approved`;
- not cancelled/archived;
- exact end date is not in the past;
- temporal state is not `past`.

Sorting:

1. dated future/upcoming Tours by nearest start date;
2. approved undated Tours afterward.

Undated approved Tours show `Tarih yakında`.

`sold_out` remains visible with `Kontenjan dolu`; it is not silently deleted.

### Future Tour pipeline

```text
Google Sheet Tour row
→ Direct Sync precheck/apply
→ canonical STT-* record
→ human review
→ editorial approved
→ automatic Tour Hub eligibility
```

Therefore future Tour cards no longer require rebuilding the `/kultur-turlari/` page manually.

Expired Tours disappear from the future/upcoming Hub by date/lifecycle truth while remaining canonically retained.

### Detail route boundary

Tour Hub never enables individual Tour public routes.

If the separately controlled v1.0 public route is already enabled for the same Stable ID, its card may link to that route. Otherwise the CTA falls back to `/iletisim/`.

### Repository/runtime acceptance evidence

At PR #18 candidate:

- Hub Master default OFF: PASS;
- no route takeover / no rewrite: PASS;
- no Hub SEO/canonical/sitemap ownership: PASS;
- approved future Tour included: PASS;
- past Tour excluded: PASS;
- `needs_review` Tour excluded: PASS;
- approved undated Tour retained after dated Tours: PASS;
- sold-out state retained: PASS;
- existing v1.0 public options unchanged: PASS;
- static v1.1 contract: PASS;
- disposable WordPress + MariaDB runtime: PASS;
- existing v1.0 public pilot regression: PASS;
- replacement Tour Intelligence ZIP: PASS.

**Production Hub acceptance is still PENDING.** Repository acceptance does not authorize replacing the live page.

Runbook:

`docs/runbooks/tour-intelligence/TOUR-HUB-V1.1.md`

---

## 8. TRANSPORT RETRY — PRESERVE IN BOTH CLIENTS

Both independent clients preserve:

- max 3 attempts;
- one request ID per business request;
- byte-identical JSON body across retries;
- fresh timestamp/nonce/HMAC each attempt;
- timeout/DNS/network retry;
- `409 stds_processing` polling;
- ordinary validation/business failures are not retried as transport failures.

---

## 9. CONTROLLED UMRAH ARCHIVE

Repository/server candidate v0.1.4 remains accepted by disposable runtime evidence.

Production controlled-archive acceptance is still **PENDING**. No live archive acceptance is inferred from selected-row no-change tests.

---

## 10. ANNUAL UMRAH SHEET ROLLOVER

Use one Google Spreadsheet per operating year.

New annual workbook rules:

1. keep `Home` unless deliberately redesigned;
2. install only the accepted Umrah Apps Script stack;
3. start with clean yearly `ST Umrah Sync State`;
4. configure Direct Sync Script Properties once if missing;
5. first genuinely new Program must prove `CREATE → CREATE apply → same STP-* / UNCHANGED`.

Cross-year Programs are not duplicated merely because the calendar year changes.

---

## 11. PUBLICATION / SEO HARD SEPARATION

Direct Sync and Tour Hub must never silently enable:

- Tour Public Master;
- individual Tour public route;
- Tour indexation;
- sitemap;
- schema;
- canonical exposure;
- mass Program indexation.

Hotel Intelligence remains canonical owner of Hotel facts (`STH-######`).

---

## 12. CURRENT ACCEPTANCE REGISTER

```text
Umrah separated Apps Script selected-row precheck/apply      PRODUCTION ACCEPTED
Tour separated Apps Script selected-row precheck/apply       PRODUCTION ACCEPTED
Umrah legacy state migration (36/36 valid)                   ACCEPTED ONE-TIME MIGRATION
Tour dynamic technical-column safety                         PRODUCTION OBSERVED / ACCEPTED
Tour Hub v1.1 repository/static/runtime                      ACCEPTED — PR #18 MERGE PENDING
Tour Hub v1.1 Production installation + live activation      PENDING / separate owner gate
Tour future-card pipeline                                    REPO/RUNTIME ACCEPTED; live pending
Transport retry contract in both separated clients           PRESERVED / STATIC-GATED
Umrah full active no-change/preflight                        PRODUCTION ACCEPTED (prior evidence)
Umrah mass legacy live UPDATE                                OPEN / separate future gate
Direct Sync v0.1.4 server controlled archive                 REPO/RUNTIME ACCEPTED
Controlled archive Production live test                      PENDING
Tour individual public/indexation gates                      SEPARATE / OWNER-CONTROLLED
```

---

## 13. REPOSITORY OPERATING METHOD

- one coherent feature branch per checkpoint;
- static/lint gates before merge;
- disposable WordPress + MariaDB runtime evidence;
- no direct Production mutation from repository work;
- no automatic Merge;
- Merge requires explicit owner approval;
- WordPress Production deployment/Hub activation requires separate explicit owner approval;
- never claim acceptance without runtime evidence.

---

## 14. NEXT CHECKPOINT

PR #18 is the current Tour Hub repository/runtime candidate.

After final documentation commit and green final-head CI:

1. stop at explicit owner Merge approval;
2. after Merge verify `main` once;
3. Production installation of Tour Intelligence v1.1.0 requires a separate explicit owner approval;
4. install replacement ZIP with Hub Master still OFF;
5. verify `/kultur-turlari/` still renders the old page while OFF;
6. approve intended real Tour(s) separately;
7. inspect eligible Hub cards;
8. only then enable Hub Master with explicit owner approval;
9. verify desktop/mobile page, page-level SEO continuity, and unchanged individual Tour public gates;
10. add future Tours through Sheet → Direct Sync → human approval, not manual WPBakery card editing.
