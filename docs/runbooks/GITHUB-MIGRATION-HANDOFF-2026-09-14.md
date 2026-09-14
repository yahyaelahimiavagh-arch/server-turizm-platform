# Server Turizm — GitHub Migration / Work Mode Handoff
**Date:** 2026-09-14  
**Target:** one private canonical source repository for the Server Turizm platform.

## 1. Goal
Create one authoritative private GitHub repository so future work starts from source control, not from scattered ZIPs/chat attachments.

Recommended repository:

`yahyaelahimiavagh-arch/server-turizm-platform`

Visibility: **PRIVATE**

## 2. Current authoritative project direction
- Weekly SEO gate is closed for 2026-09-14.
- `/umre-1/` is the dynamic live Umrah Hub.
- Program routes remain `PUBLIC_NOINDEX`; Program indexation/sitemap/bulk submission remain OFF.
- Program Intelligence runtime: **v0.3.5**
- Program Publishing Integration runtime: **v0.4.14**
- Programs 219 / 220 / 221 were archived with **0 deleted**.
- Stable-ID incident repaired: **22 entities repaired / 0 deleted**.
- Publishing Registry refresh: **21 routes PASS**.
- Active import identity dry-run: **33 unchanged / 0 create / 0 update / 0 conflicts**.
- `STP-000038` = Zekerya, identity corrected.
- Hotel Intelligence accepted golden baseline: **v0.9.10**
- Tour Intelligence accepted Sheet → Partial JSON contract: **v0.6.2.1**
- Next Tour branch: **v0.6.5 AI Completion Contract**
- Direct Google Sheets sync for Umrah is intentionally deferred until Tour operating model is stable; then build one shared Umrah + Tours sync.
- Porto direct directory 5xx was fixed at server level so `/wp-content/themes/porto` now returns clean 404.
- Exact legacy SEO redirects were added for 5 Umrah URLs → `/umre-1/` and one Hac URL → `/hac/`.

## 3. Canonical repository structure

```text
server-turizm-platform/
├─ README.md
├─ CHANGELOG.md
├─ SECURITY.md
├─ .gitignore
├─ docs/
│  ├─ MASTER-PLAN.md
│  ├─ architecture/
│  ├─ runbooks/
│  ├─ seo/
│  ├─ incidents/
│  ├─ decisions/
│  └─ evidence/
├─ wordpress/
│  ├─ plugins/
│  │  ├─ hotel-intelligence/
│  │  ├─ program-intelligence/
│  │  ├─ program-publishing-integration/
│  │  ├─ tour-intelligence/
│  │  ├─ homepage-intelligence/
│  │  └─ header-footer/
│  ├─ mu-plugins/
│  └─ theme-overrides/
├─ integrations/
│  └─ google-sheets/
│     ├─ umrah/
│     └─ tours/
├─ schemas/
├─ examples/
├─ tests/
├─ scripts/
└─ releases/
```

## 4. Files that MUST NOT be committed
Do not commit:
- `.env`
- API keys / tokens / passwords
- `wp-config.php` with secrets
- database dumps containing customer/admin/private data
- passport/customer/lead data
- WordPress `uploads/` media archive
- cache/log/session files
- full WordPress core
- paid/proprietary Porto theme source unless the license explicitly allows repository storage
- licensed third-party plugin source unless permitted
- server backup archives containing secrets
- raw production exports containing sensitive personal data

## 5. Backups to collect from the LIVE server before migration

### A. REQUIRED — send to the Work session
Create one ZIP from cPanel containing only the current custom source:

```text
server-turizm-custom-source-2026-09-14.zip

wp-content/
├─ plugins/
│  ├─ [every active custom Server Turizm plugin directory]
│  └─ [any other custom plugin written for this project]
├─ mu-plugins/
│  └─ [entire directory]
└─ themes/
   └─ [Porto CHILD theme only, if one exists]
```

Do **not** include WordPress core, uploads, cache, Porto parent theme, or third-party plugins in this ZIP.

Also include a copy of root:

`.htaccess`

This is required because the Porto directory 5xx guard is currently implemented there.

### B. REQUIRED — Google Sheets / Apps Script source
Export or copy all current Apps Script source files for:
- Umrah Sheet exporter/generator
- Tour Sheet exporter/generator
- any helper/library script used by either sheet

Prefer `.gs` / `.js` / `.txt` files.  
Do **not** include deployment secrets, HMAC secrets, tokens, or credentials.

### C. REQUIRED — authoritative planning docs
Attach/use:
- latest authoritative Master Plan:
  `SERVER-TURIZM-SEO-MASTER-PLAN-2026-09-14-SEO-WEEKLY-GATE-CLOSED-TOURS-RESUME.md`
- latest Tour Intelligence docs / runbooks
- latest accepted Hotel Intelligence Master Plan
- latest Program/Publishing incident/recovery runbooks if available

### D. STRONGLY RECOMMENDED — rollback backup, keep OUTSIDE GitHub
Before any migration, create:
1. full cPanel `public_html` backup ZIP;
2. MySQL database export (`.sql.gz`);
3. optional full cPanel account backup.

Keep these locally / secure cloud storage.  
They are disaster-recovery backups and should **not** be committed to GitHub.

Do not send the production DB unless we later need it for a specific migration/debug task.

## 6. Runtime/source reconciliation gate
Before calling GitHub canonical, Work must compare:
1. live server custom plugin source;
2. latest accepted ZIPs/docs in ChatGPT Library;
3. current WordPress runtime version labels.

For every module classify:

`LIVE AUTHORITATIVE`  
`ACCEPTED HISTORICAL`  
`REJECTED / EXPERIMENTAL`  
`LEGACY / RETIRED`

No “highest version number wins” assumption.

## 7. Git workflow
Use:
- `main` = accepted runtime baseline only
- new branch for every implementation
- PR before merge
- no direct experimental changes to `main`
- tagged releases for accepted runtime checkpoints

Suggested first tag after reconciliation:

`server-turizm-baseline-2026-09-14`

## 8. First GitHub migration sequence
1. Create private repo `server-turizm-platform`.
2. Add `.gitignore`, README, SECURITY, repository structure.
3. Import current LIVE custom source.
4. Add authoritative Master Plan.
5. Import current Apps Script source.
6. Add accepted runbooks/evidence.
7. Run secret scan.
8. Compare live versions against documented accepted versions.
9. Produce `docs/CURRENT-RUNTIME-INVENTORY.md`.
10. Commit baseline on migration branch.
11. Open PR: `Establish authoritative Server Turizm baseline`.
12. Review diff.
13. Merge only after no secrets, no proprietary third-party code, no missing current custom modules.
14. Tag accepted baseline.
15. Resume Tour Intelligence v0.6.5 from a new feature branch.

## 9. Non-negotiable project rules to preserve
- Stable IDs are identity; row position / slug / name is not identity.
- No automatic destructive delete for Programs.
- Removed Programs archive with history preserved.
- Program detail indexation remains OFF until explicit SEO gate.
- Hotel data belongs only to Hotel Intelligence.
- Program→Hotel relation is stored in Program data; Hotel→Program is derived.
- No duplicated business-data sources.
- `/umre-1/` remains the current Umrah commercial hub.
- Direct Google Sheets production sync comes after Tour workflow is stabilized.
- Production-changing flows must be transactional / fail closed.
- Do not use one invalid record to globally take healthy production offline.

## 10. Work Mode continuation instruction
In a new **Work** chat, attach:
1. this handoff file;
2. the latest authoritative Master Plan;
3. `server-turizm-custom-source-2026-09-14.zip`;
4. Apps Script backup;
5. any current custom plugin ZIPs that are not present in the server-source ZIP.

Then instruct:

> Continue Server Turizm as a repository migration and reconciliation task. GitHub is connected. First create/verify a PRIVATE `server-turizm-platform` repository under my GitHub account. Treat the live-server custom source backup as the runtime truth, but reconcile it against the attached authoritative Master Plan and accepted historical artifacts before declaring any module canonical. Do not upload secrets, production database data, customer/passport data, WordPress uploads, full WordPress core, Porto parent-theme source, or licensed third-party plugin source. Build a clean monorepo, produce CURRENT-RUNTIME-INVENTORY.md, establish the 2026-09-14 baseline through a branch + PR, and only after the baseline is accepted resume Tour Intelligence v0.6.5.
