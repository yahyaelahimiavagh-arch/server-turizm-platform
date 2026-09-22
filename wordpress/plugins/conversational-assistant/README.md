# Server Turizm Conversational Assistant (STCA)

STCA is a fail-closed, read-only conversational layer for Server Turizm. It connects Instagram Professional Account messaging to the same canonical data already owned by Program Intelligence (`STP-*`), Hotel Intelligence (`STH-*`) and Tour Intelligence (`STT-*`).

## v1.0.0 scope

- Official Instagram webhook verification + signed webhook processing.
- Official Instagram Send API text replies.
- Graph API default: `v26.0`.
- Turkish-first deterministic intent parsing with Persian and English support.
- Canonical Umrah lookup by month, tier, room occupancy, date, hotel and pricing.
- Tour lookup only when the Tour is explicitly Hub-visible. Private/test fixtures never qualify.
- Human handoff/contact, Hajj and visa safe responses.
- Message deduplication and privacy-aware operational logs.
- Admin health panel and Safe Simulator.
- No publication/indexation/sitemap/schema/canonical/homepage mutations.
- No secrets in Git.

## Required plugins / data owners

STCA reads data only; it does not replace these owners:

- Program Intelligence
- Hotel Intelligence
- Tour Intelligence

If one source is unavailable, STCA fails closed for that source and falls back to contact/site guidance.

## wp-config.php

Keep credentials outside Git:

```php
define('STCA_INSTAGRAM_ENABLED', false); // keep false until final Meta verification

define('STCA_META_GRAPH_VERSION', 'v26.0');
define('STCA_META_VERIFY_TOKEN', 'generate-a-long-random-token');
define('STCA_META_APP_SECRET', 'meta-app-secret');
define('STCA_IG_ACCESS_TOKEN', 'instagram-user-access-token');
define('STCA_IG_ACCOUNT_ID', 'instagram-professional-account-id');
```

## Webhook

`/wp-json/server-turizm/v1/instagram/webhook`

The GET verification endpoint works while the master remains OFF. Incoming POST events are signature-verified; replies remain disabled until `STCA_INSTAGRAM_ENABLED` is explicitly set to `true`.

## Safety invariants

- Reads canonical entities; no writes to Program/Hotel/Tour Intelligence.
- Never invents dates, prices, hotels, availability or visa requirements.
- Old/completed/cancelled/closed Umrah programs are excluded.
- Tour requires explicit `publication.hub_visible = true` plus customer-safe lifecycle.
- A missing Tour `hub_visible` value is treated as false.
- Meta credentials are never stored in WordPress options or Git.
- Duplicate Instagram message IDs are suppressed.
