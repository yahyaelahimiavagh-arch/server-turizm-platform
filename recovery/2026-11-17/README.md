# Server Turizm — recovery snapshot (2026-11-17)

This is a **non-deploying recovery branch**. Do not merge or install its files without checking against the actual production versions.

## Product contract
- Google Sheets Home: program editing, dates, hotel selection, order, explicit archive checkbox.
- WordPress Program Intelligence: stable program IDs and controlled approval/archive.
- WordPress Hotel Intelligence 0.9.11: hotel metadata, photos and canonical hotel entities; hotel data should remain WordPress-authoritative.
- Original Program Publishing Integration renderer retains the old gold/navy program cards, galleries, reservation buttons and shortcodes. No replacement HTML/CSS.
- Hotel names should be resolved through stable STH IDs; a confirmed hotel must not stay in the provisional/unknown-hotel state.
- Safe archives require identity match, repeated confirmation and cache invalidation. No silent mass deletion on missing source rows.
- No credentials, authorization headers, or real customer data should be checked in.

## Versions assembled in conversation
| Component | Version | Status |
| --- | --- | --- |
| Auto Sync WordPress plugin | 2.9 | ZIP package generated; production deployment not verified in the conversation |
| Google Apps Script ST2 one-file | 2.9 | Source preserved in this branch; script properties/secrets intentionally absent |
| Program Intelligence | 0.3.8 | Earlier install screenshot; source package generated |
| Program Publishing Integration | 0.4.23 | Earlier install screenshot; source package generated |
| Hotel Intelligence | 0.9.11 | Source already exists in main |
| Direct Sync Foundation | 0.1.6 | Dependency; original was accidentally deleted once; verify active plugin before operations |

## Timeline
- 2.2: guarded sync and stable IDs
- 2.3: automatic recovery checkpoint
- 2.4: queue, reconciliation and archive safety
- 2.5: restore original public renderer — **never replace** `[stppi_umre_programs]`
- 2.6: R1/R2 approve with provisional hotel details
- 2.7: fast archive with 60s confirmation, repeated valid snapshot and cache clearing
- 2.8: manual confirmation of R2 / Jiwar Al-Madina Hotel connection
- 2.9: hotel directory to sheet and stable ID lookup; adds Hotels sheet columns G/H and updates dropdown choices

## Known data examples
- R1 = STP-000050. Accommodation areas, not hotel entities.
- R2 = STP-000051. Jiwar Al-Madina Hotel = STH-000023; Makkah Dar Al Ghufran. Hotel is confirmed by operator, should link to real entity.
- R3 = STP-000052; R4 = STP-000053.
- R501–R509 intentionally archived by operator.
- Kahire4 = STP-000014 archived through sheet archive intent.
- Other program deletions must be verified for identity before archive.
- Google Sheet tabs: Home, Hotels, ST Hotel Directory, ST Direct Sync State, ST Umrah Sync State.
- Hotels tab currently has A=Hotel Name, B=Harita, C=Resim, D=Konum, E=Logo, F=UUID; G/H are intended for canonical WordPress hotel ID / public URL.
- Home hotel dropdowns were static ONE_OF_LIST; future engine should refresh safely, preserving region placeholder values.

## Restore / debug runbook
1. Confirm current production plugin versions; screenshots and generated archives are **not** proof of deployment.
2. Pause database updates before replacing plugin files.
3. Verify direct-sync auth dependency remains installed and active.
4. Review queue/stats separately: `RUNNING` means enabled, not stuck; `complete:false` or pending archives require investigation.
5. Keep original public shortcode and CSS/renderer.
6. Check hotel stable IDs, aliases, city and verified workflow status.
7. Check WordPress post state and gallery, Google Sheets Home/Hotels rows, archive flags, WP-Cron and LiteSpeed cache.
8. Keep secrets in Google Script Properties and WordPress configuration; never commit values.

## Source availability notice
The current GitHub main initially had Program Intelligence 0.3.5 and Publishing Integration 0.4.14, not the newer production candidate files. The WordPress ZIP source packages are preserved as a **separate conversation recovery archive** but are not assumed present in GitHub until a corresponding committed artifact/source path is verified. The latest Apps Script source and debugging notes are tracked here.
