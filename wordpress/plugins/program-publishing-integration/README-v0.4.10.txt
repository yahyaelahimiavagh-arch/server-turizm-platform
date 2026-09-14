Server Turizm Program Publishing Integration v0.4.10
Current Route / Chronological Hub / Date-only Countdown Hotfix

Fixes:
- /umre-1/ current list includes UPCOMING + IN_PROGRESS Programs; COMPLETED stays out.
- Current/in-progress routes may remain public/noindex until completion.
- Hub ordering is deterministic by start_date ASC, then source_row, then Stable ID.
- destinations / segments / stays are rendered by explicit sequence.
- Date-only schedules no longer invent 00:00 departure precision. Upcoming cards show calendar-day remaining; hours/minutes display em dash. In-progress cards show PROGRAM BAŞLADI.
- Existing runtime gates are preserved on v0.4.9 -> v0.4.10 replacement.
- Admin shows an explicit CURRENT ROUTE BASELINE ONAR gate if current/upcoming routes are still PREPARED from the v0.4.9 recovery.
- Fixtures STP-000036/37 remain blocked. Indexation/sitemap locks unchanged.

Expected order on 2026-09-12 for the first current Programs from the provided Sheet export:
219 (2026-09-10)
220 (2026-09-10)
221 (2026-09-10)
ZEKERYA KURT HOCA UMRE PROGRAMI (2026-09-13)
then 2026-09-24 Programs, etc.

IMPORTANT SOURCE QA:
The provided ZEKERYA KURT HOCA row contains phone/WhatsApp +915302015284. Verify/correct the Google Sheet source before the next import if this is unintended.
