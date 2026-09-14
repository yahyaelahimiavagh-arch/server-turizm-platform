# STTI v0.4.0 — Private Renderer Runtime Gate

## Accepted baseline
- v0.3.0 Structured Authoring Core: CLOSED / RUNTIME ACCEPTED.
- Candidate: `STT-000001`.
- Schema: `STTI-TOUR-1.1.0`.
- Accepted canonical checksum before v0.4 install:
  `d72c07fbec5d97c338e076c0203cacad4f8049101bf85adff5732f1423954348`
- Public Master and all public release locks remain OFF.

## Install
1. WordPress → Plugins → Add New → Upload Plugin.
2. Upload the v0.4.0 REPLACE ZIP.
3. Choose **Replace current version**.
4. Open Tour Intelligence.
5. Verify badge `STTI · PRIVATE RENDERER PILOT v0.4.0`.

## Gate PR-A — Install / no mutation
Immediately download `Tüm JSON Evidence İndir` before editing any Tour.
Expected:
- plugin_version `0.4.0`
- schema_version `1.1.0`
- mode `private_renderer_pilot`
- candidate_count `1`
- stable_id `STT-000001`
- checksum unchanged: `d72c07...54348`
- no new candidate audit event caused by installation
- frontend_writes `0`
- all public release locks `false`
- `private_renderer.status = pilot_active`

## Gate PR-B — Canonical direct-read preview
Tour Registry → `STT-000001` → **Private Preview**.
Expected UI:
- `Büyük İran Turu`
- route: Tahran → Kaşan → İsfahan → Yezd → Şiraz
- exact dates: 16.10.2026–24.10.2026
- duration: 9 Gün / 8 Gece
- primary price: 899 EUR
- 5 route stops
- 9 itinerary days
- 0 hotels
- 0 transport
- 1 structured price
- included/excluded empty
- visa UNKNOWN
- no invented itinerary facts
- canonical checksum shown
- render fingerprint shown

## Gate PR-C — Preview no-write
Open/refresh Private Preview twice. Do not save.
Download full evidence again.
Expected:
- candidate_count unchanged
- checksum unchanged
- updated_at unchanged
- audit log count unchanged
- no `renderer_*` write event
- renderer evidence fingerprint stable

## Gate PR-D — Access / exposure isolation
Expected architecture:
- preview URL exists only under `wp-admin/admin.php?...view=preview`
- `manage_options` required
- no `init` rewrite rule
- no frontend template hook
- no public `/kultur-turlari/...` route
- no sitemap entry
- no schema output
- no canonical/robots frontend change

## Acceptance
If PR-A/B/C/D pass:
`Server Turizm Tour Intelligence v0.4.0 — Private Renderer Pilot` → **CLOSED / RUNTIME ACCEPTED**.

Next branch: renderer completeness / relation resolution and then Controlled Public Rendering design. Public release does not turn on automatically.
