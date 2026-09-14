# STTI v0.5.2 — Theme Integration Layout Fix

## Scope
Private Customer Preview layout-only hotfix over the runtime-safe v0.5.1 branch.

### Fixes
- Full-bleed viewport surface; no theme-container white side gutters around Tour Experience bands.
- Dynamic live-header overlap measurement; customer content must begin after the actual theme header instead of rendering underneath it.
- Glass/blur compatibility for the live Server Turizm header while this private preview is active.
- Private technical badge moved out of the header/hero stack into a compact floating admin-only marker.
- Live `get_header()` / `get_footer()` remain the shell.
- Canonical STTI candidate data is not migrated or rewritten.

## Hard locks preserved
- Public Master OFF
- Public Renderer OFF
- Public Routes OFF
- Sitemap OFF
- Indexation OFF
- Homepage Adapter OFF
- STTI schema output OFF
- STTI canonical/robots writes OFF
- Candidate frontend writes 0

## Gate A — Installation isolation
After replacing v0.5.1 with v0.5.2, export Full JSON Evidence before opening Customer Preview.
Expected: `STT-000001`, schema 1.1.0, candidate count 1, checksum and updated_at unchanged, no new audit event.

## Gate B — Desktop visual integration
Open Customer Preview and verify:
1. No white side gutters caused by the theme parent container.
2. Hero begins below the live header; no hidden title/controls under navigation.
3. Header is glass/blurred navy and remains visually above the hero.
4. Header/footer are still the live theme shell.
5. WhatsApp CTA and Program CTA work.

## Gate C — Read-only evidence
Export Full JSON Evidence after viewing preview. Check checksum, updated_at and audit remain unchanged.

## Fail closed
If the public can open the nonce-protected preview, or any candidate/audit data changes just by viewing, stop immediately.
