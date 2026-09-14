# STTI v0.8.0 — Complete Customer Renderer Contract

Status: implementation candidate. Private only. No production/public authorization.

## Contract
`STTI-CUSTOMER-RENDERER-1.0.0`

The renderer is a read-only projection of canonical Tour data. It MUST:
- read the canonical STTI payload directly;
- consume v0.7 reviewed Route Variants, Hotel Options and Transport relations;
- consume v0.7.1 source-backed, human-confirmed canonical geo only;
- read Hotel Intelligence facts by `STH-######` identity at render time without copying hotel facts into STTI;
- exclude pending/rejected relations from customer HTML;
- never infer a primary route when reviewed variants exist but no confirmed primary exists;
- preserve legacy base-route rendering only when no route variants exist at all;
- preserve unknown/missing facts as absent or neutral private-review notices;
- perform zero canonical writes;
- keep public route, indexation, sitemap, schema, canonical and homepage gates OFF.

## Route selection
If exactly one confirmed `role=primary` variant exists, it is the active customer route. Confirmed alternatives may be displayed as alternatives but are never auto-selected. If variants exist and no confirmed primary exists, no active route is inferred.

## Hotel projection
Only confirmed relations in active scope are rendered. Hotel Intelligence mode resolves identity with `stti_v070_hotel_link_state()` and reads display facts live from the Hotel Intelligence entity. Those facts are never persisted into the Tour payload.

## Map projection
Only active-route stops with `resolved + confirmed` geo records become map points. Missing/pending/rejected geo is omitted and explicitly counted unresolved. The browser performs no geocoding and uses no coordinate cache authority.

## Security / publication
The surface remains `manage_options + nonce`, noindex, non-sitemap, no STTI schema/canonical exposure, and has no publication authority.
