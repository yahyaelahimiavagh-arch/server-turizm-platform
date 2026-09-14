# STTI v0.6.5 — AI Completion Contract Runbook

## Release boundary

This is a private review-gate release. It adds no AI provider connection, import commit, public route, sitemap, indexation, schema output, canonical/robots change or homepage placement.

The accepted v0.6.0 JSON import dry run remains available. The new v0.6.5 workbench validates `STTI-AI-COMPLETION-1.0.0` envelopes and always returns `REVIEW`, `INVALID` or the underlying fail-closed `CONFLICT` result with `NO WRITE`.

## Pre-deployment

1. Confirm CI passes PHP lint, baseline verification and `test_v065_ai_completion.py`.
2. Download the currently active Tour Intelligence plugin as the rollback package.
3. Export all STTI evidence JSON from the existing read-only export.
4. Record the current plugin version and candidate-row count.
5. Do not change the Tour public/indexation/sitemap/schema/canonical/homepage locks.

## Deployment candidate check

1. Install the v0.6.5 plugin package in the controlled WordPress environment.
2. Open **Tour Intelligence → Import / Export**.
3. Confirm the original `STTI-TOUR-IMPORT-1.0.0` dry-run workbench remains present.
4. Confirm **AI Completion Review** shows `ACTIVE · REVIEW ONLY`.
5. Paste `contracts/STTI-AI-COMPLETION-1.0.0.example.json` into the new workbench.
6. Run **Validate Completion + Review Dry Run**.
7. Expect `REVIEW`, nested candidate classification `CREATE` or `UPDATE`, and a visible `NO WRITE` badge.
8. Confirm candidate-row count, audit-row count and next Stable-ID sequence did not change.

## Fail-closed checks

Repeat with disposable copies of the example:

- change one character in `source_document` without updating its hash: expect `INVALID`;
- change `candidate_document.target`: expect `INVALID`;
- add a meaningful value without an exact claim: expect `INVALID`;
- set a duration inconsistent with exact dates: expect `INVALID`;
- add `indexable`, `sitemap`, `publication` or another public control below `tour`: expect `INVALID`;
- set review status to anything except `pending`: expect `INVALID`;
- for an existing target, use a stale checksum: expect fail-closed `CONFLICT`.

## Runtime acceptance

Close the gate only when:

- valid example returns review-only evidence;
- every fail-closed test above passes;
- no Tour, audit or sequence write occurs;
- the accepted private technical and customer previews show no regression;
- all public/SEO locks remain OFF.

Until those checks are recorded, v0.6.5 is a release candidate and the runtime inventory remains v0.6.0.

## Rollback

Restore the captured v0.6.0 plugin package. No data rollback should be required because the v0.6.5 completion path performs no writes. Re-check the original JSON import dry run and both private preview surfaces.
