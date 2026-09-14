# STTI v0.6.5 Runtime Acceptance — 2026-09-14

## Status

`CLOSED / RUNTIME ACCEPTED`

The merged v0.6.5 replacement package was installed on the current WordPress runtime. The following administrator-observed checks passed while the private candidate count remained `1` and all Tour public/SEO release locks remained OFF.

| Check | Observed result |
| --- | --- |
| Plugin/runtime version | `0.6.5` |
| Valid AI completion envelope | `REVIEW`, proposed `STT-000002`, `NO WRITE` |
| Tampered source-document hash | `INVALID`, canonical source hash mismatch, `NO WRITE` |
| Incorrect deterministic duration (`99`) | `INVALID`, expected `9`, `NO WRITE` |
| Injected `indexable=true` | `INVALID`, publication/indexation field rejected and exact claim missing, `NO WRITE` |
| Accepted PARTIAL import regression | `CREATE`, proposed `STT-000002`, Mode `PARTIAL`, `NO WRITE` |

The warning that price basis is unspecified was expected and preserved as `UNKNOWN`; it did not block the dry run.

## Preserved locks

- Public routes: OFF
- Sitemap: OFF
- Indexation: OFF
- Homepage adapter: OFF
- AI completion canonical/audit/sequence writes: absent
- JSON import commit: locked

The administrator screenshots used for runtime verification are intentionally not committed because they expose private WordPress administration and server context.

## Next branch

`STTI v0.7.0 — WordPress Review Relations`
