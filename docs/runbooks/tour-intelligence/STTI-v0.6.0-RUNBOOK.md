# STTI v0.6.0 — JSON Import Validate + Dry Run Gate

Scope: STTI-TOUR-IMPORT-1.0.0 paste/upload, server-side validation, normalization and dry-run classification only.

## Hard locks
- No import commit / canonical candidate write
- No audit write
- No stable ID sequence increment
- Public routes OFF
- Sitemap OFF
- Indexation OFF
- Homepage adapter OFF
- Schema output OFF
- Canonical/robots changes OFF

## Runtime test
1. Replace current STTI plugin with this ZIP.
2. Export full JSON evidence before any dry run.
3. Open Tour Intelligence → Import / Export.
4. Paste `contracts/STTI-TOUR-IMPORT-1.0.0-PARTIAL.example.json` or upload a JSON file.
5. Click Validate + Dry Run. Expected for the Iran example on a new-target contract: CREATE with proposed Stable ID, NO WRITE.
6. Export full JSON evidence again. Candidate checksum/updated_at/audit and next sequence must remain unchanged.
