# STTI v0.7.0 Review Relations Contract

This contract defines relation semantics only; it does not change the v0.6.5 AI Completion no-write contract.

## Invariants

1. Unknown facts remain empty, `unknown`, `pending`, or `unresolved`.
2. Human review is explicit; legacy rows default to `pending`.
3. Route Variants reference canonical relation IDs and never invent membership.
4. Hotel Intelligence links use only `STH-######` IDs and resolve by identity. No Hotel Intelligence facts are duplicated into STTI.
5. Unresolved source hotel names are preserved as unresolved relations.
6. Transport stop/variant refs are explicit; free-text origin/destination labels are not auto-matched.
7. Rejected relations may remain as private evidence but cannot be referenced by active variants.
8. Editorial approval is blocked while active relation review is incomplete or unresolved.
9. Reviewer identity and review timestamp are server-owned audit metadata; client values cannot forge them.
10. Hotel option groups may remain unselected, but contradictory multiple `selected` rows are blocked. At most one active Route Variant may be `primary`; zero is valid.
11. Relation review never enables public routes, indexation, sitemap, schema, canonical/robots output, or homepage integration.
12. v0.6.5 AI Completion and import dry-runs keep their NO WRITE behavior.
