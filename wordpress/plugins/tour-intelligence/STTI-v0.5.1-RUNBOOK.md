# STTI v0.5.1 — Theme-Shell Customer Preview Hotfix

## Why this release exists
v0.5.0 customer preview was rejected in visual QA because it duplicated the site header, exposed a technical-looking standalone shell, and the CTA buttons were not all live links.

v0.5.1 replaces that preview layer only. Canonical Tour data, stable IDs, schema 1.1.0 and the accepted technical renderer are preserved.

## Changes
- Customer Preview now renders inside the live WordPress theme shell using `get_header()` and `get_footer()`.
- The real Server Turizm site header/navigation is therefore used; no duplicated fake header exists in the preview.
- Preview moved from wp-admin standalone rendering to a private front-end query surface protected by logged-in `manage_options` + nonce.
- Unauthorized/expired preview requests fail closed to the theme 404 page.
- `WhatsApp'tan Bilgi Al` is a real link to the same Server Turizm WhatsApp number used by the public site.
- `Programı İncele` is a real in-page anchor.
- Existing theme header/menu/buttons keep their native links/scripts.
- When canonical daily itinerary details are empty, nine empty customer cards are no longer shown. A single neutral `program hazırlanıyor` state is shown instead.
- Hotel/transport/visa/services sections are hidden when no verified facts exist.
- Hero media priority: canonical media first; for this Iran pilot, the existing first-party Server Turizm Iran destination image is used as a verified site-owned fallback.

## Hard locks remain
- Public Master OFF
- Public Tour routes OFF
- Sitemap OFF
- Indexation OFF
- Homepage adapter OFF
- STTI schema output OFF
- No canonical Tour writes from preview
- Candidate frontend writes 0

## Gate H-A — install isolation
After replacing v0.5.0 with v0.5.1, export Full JSON Evidence before opening Customer Preview.
Expected:
- plugin_version `0.5.1`
- mode `private_customer_experience_theme_shell_pilot`
- candidate_count `1`
- stable_id `STT-000001`
- schema `1.1.0`
- checksum unchanged: `d72c07fbec5d97c338e076c0203cacad4f8049101bf85adff5732f1423954348`
- updated_at unchanged: `2026-09-11 09:47:41`
- no audit event after ID 8
- all public locks OFF

## Gate H-B — visual/runtime
Open Customer Preview from Tour Registry.
Expected:
- exact live site header and footer
- private preview bar only as an admin indicator
- working WhatsApp CTA
- working Program anchor
- Iran first-party destination image in hero when canonical media is empty
- route 5 stops
- no invented itinerary/hotel/transport/visa facts

## Gate H-C — access isolation
Copy the private preview URL to Incognito.
Expected: 404 / fail closed. The customer preview must never render to a logged-out visitor.
