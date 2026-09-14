# STPI Foundation Architecture

## Single-source flow

Google Sheets remains the operational entry surface. The WordPress editor is a second controlled entry surface. Neither produces public HTML directly. Both emit the same ST-TDE Program Batch JSON.

1. Enter or edit facts.
2. Export canonical JSON.
3. Validate structure and business rules.
4. Dry-run CREATE / UPDATE / UNCHANGED / CONFLICT.
5. Human review of dates, prices, hotels and publication intent.
6. Commit into a private normalized Program Candidate with a Server-owned Stable Program ID.
7. Review/approve the private candidate with an auditable human action.
8. Produce public pages, cards, price tables, archive, SEO and media exports from that entity in later gates.

## Identity

- `program_id` is immutable and uses `STP-000000`.
- `program_code` is an operator-facing display/sales label; duplicates such as `Özel` are allowed.
- Hotel relationships use `STH-000000` only.
- A hotel name copied from legacy Sheets is migration evidence, not identity.

## Lifecycle axes

The system does not overload one status field:

- Editorial: draft, needs_review, approved, published, archived.
- Schedule: scheduled, tentative, postponed, rescheduled, cancelled.
- Availability: open, limited, sold_out, on_request, closed.
- Temporal state is computed later: upcoming, in_progress, completed, undated.

Completion never means deletion. A completed public program leaves active sales views and enters the internal archive with a historical snapshot.

## Projection rule

Cards, program pages, live price tables, seasonal price guides, PDF brochures, Instagram assets, WhatsApp text, SEO metadata and machine-readable feeds are projections of approved Program data. They are not editable copies.

## Hotel boundary

STPI resolves a Hotel DTO read-only from Hotel Intelligence v0.9.11. The initial DTO includes stable ID, official display name, city, verified star rating, routable public URL and the first four display images. Program-specific facts such as dates, nights, meal plan, room allocation and negotiated price remain in STPI.

When a hotel has not been selected, the program may carry an explicit unresolved label such as `4 ve 5 yıldızlı oteller - isimler açıklanmadı`. This is allowed for draft or interest campaigns and must never be presented as a resolved Hotel entity.

## Archive rule

- Partial exports may create or update only the records present.
- A missing row in a partial export has no lifecycle effect.
- A missing row in a full snapshot may create an Archive Candidate after review; it never archives automatically.
- Approved archive actions capture the dates, prices, hotel names/URLs/images and the source payload hash.
- v0.3.2 preserves private archive snapshots, restore, audit history and JSON export; it separates pure Temporal from composite Effective state in admin UI and keeps the entire STPI runtime off public requests. Clone-as-new-departure and visual revision comparison remain later gates.
