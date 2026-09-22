# STCA — Server Turizm Conversational Assistant

## Status

`v1.0.0 — REPOSITORY CANDIDATE / PRODUCTION MASTER OFF`

STCA is the conversational channel layer for Server Turizm. It must not become a new owner of Program, Hotel or Tour facts.

## Canonical ownership

- Umrah facts → Program Intelligence (`STP-*`)
- Hotel facts → Hotel Intelligence (`STH-*`)
- Tour facts → Tour Intelligence (`STT-*`)
- Instagram transport → STCA

## Hard locks

1. STCA never changes Program/Tour/Hotel records.
2. STCA never changes public routes, indexation, sitemap, schema, canonical or homepage exposure.
3. Instagram master defaults OFF.
4. Meta secrets remain outside Git.
5. Unsupported/missing facts are never invented.
6. Tour records are customer-visible to STCA only when `publication.hub_visible === true` and lifecycle gates pass.
7. Missing Tour `hub_visible` means false.
8. Cancelled/archived/completed/closed Umrah records do not appear as current offers.
9. Visa/Hajj answers are conservative and hand off when personal/current confirmation is required.

## Runtime path

```text
Instagram user DM
  → Meta webhook
  → signature verification
  → dedupe
  → deterministic intent/slot parsing
  → canonical read-only adapters
  → deterministic response composition
  → Meta Send API
  → privacy-aware operational log
```

No generative model is required for v1. The deterministic core is intentional: dates, prices, hotels and availability stay source-bound. A future language model may be added only as a constrained paraphrase/intent layer around canonical facts.

## Accepted contact fallback

- Umrah: https://www.serverturizm.com.tr/umre-1/
- Phone: 0212 621 05 00
- Mobile / WhatsApp: +90 532 267 65 49

## Installation gate

Do not switch `STCA_INSTAGRAM_ENABLED` to true until:

- plugin package CI passes;
- WordPress runtime test passes;
- admin Safe Simulator passes representative Turkish customer prompts;
- Meta app/webhook is verified;
- one controlled Instagram DM round-trip is confirmed.
