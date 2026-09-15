# Tour Hub v1.1 — Dynamic `/kultur-turlari/`

## Status

Repository candidate only until CI/runtime acceptance and explicit owner Merge approval.

Production activation is a separate explicit owner gate.

Contract: `STTI-TOUR-HUB-1.1.0`

## Goal

Replace only the **content area** of the existing WordPress page:

`/kultur-turlari/`

with a dynamic list sourced directly from Tour Intelligence canonical `STT-*` records.

The existing WordPress page continues to own:

- the route itself;
- theme header/footer;
- page-level SEO metadata/canonical/indexation behavior;
- rollback content (the current WPBakery/static page).

Tour Hub does **not** create a new route and does not generate rewrite rules.

## Hard boundaries

Tour Hub must not automatically change:

- Tour Public Master;
- individual Tour public route gates;
- Tour indexation;
- sitemap;
- schema;
- canonical;
- Google Sheets canonical data;
- Tour editorial approval.

`stti_v110_hub_master` defaults to `OFF`.

When OFF, the old `/kultur-turlari/` page content remains untouched and visible.

## Eligibility

A canonical Tour can appear on the Hub only when:

1. `editorial = approved`;
2. it is not cancelled/archived;
3. its exact end date has not passed;
4. its temporal state is not `past`.

Dated upcoming Tours sort first by nearest start date.

Approved undated Tours remain allowed after dated Tours and render as `Tarih yakında`.

A `sold_out` Tour remains visible and is labelled `Kontenjan dolu`; it is not silently removed.

## Card data

Cards read directly from canonical Tour payloads:

- public title;
- destination/country;
- route summary or stop list;
- date;
- duration;
- price;
- availability;
- optional canonical media URL when present.

No separate Hub database or duplicated Tour facts are created.

If canonical media is absent, the card uses a branded typographic fallback rather than inventing an image.

## Detail CTA

If the accepted v1.0 single-Tour public route is separately enabled for the same Stable ID, the card may link to that public Tour detail page.

Otherwise the card CTA falls back to `/iletisim/`.

Tour Hub itself never enables a Tour detail route.

## Future Tour pipeline

Operator flow:

```text
Google Sheet Tour row
  → Direct Sync precheck
  → CREATE / UPDATE
  → canonical STT-* record
  → human review
  → editorial approved
  → automatically eligible for Tour Hub
```

Therefore adding a future Tour does not require manually rebuilding `/kultur-turlari/`.

Expired Tours fall out of the future/upcoming Hub automatically by date/lifecycle truth and remain retained canonically; no hard delete is used.

## Activation

After repository acceptance and plugin installation:

1. keep Hub Master OFF;
2. verify `/kultur-turlari/` still shows the current legacy/static page;
3. approve at least one real Tour intended for the Hub;
4. open `Tour Intelligence → Tour Hub`;
5. confirm eligible count and card facts;
6. enable `Dynamic Tour Hub aktif`;
7. verify desktop/mobile rendering;
8. verify page URL and SEO metadata remain unchanged;
9. verify individual Tour public/indexation gates remain unchanged.

## Fast rollback

Uncheck `Dynamic Tour Hub aktif`.

The existing WordPress page content immediately becomes authoritative again. No permalink flush, route migration or data restoration is required.

## Acceptance gate

Repository acceptance requires:

- static v1.1 contract PASS;
- previous v1.0 public pilot regressions PASS;
- disposable WordPress + MariaDB runtime PASS;
- Hub Master default OFF;
- approved future Tour included;
- past Tour excluded;
- `needs_review` Tour excluded;
- approved undated Tour retained after dated Tour;
- sold-out label preserved;
- existing v1.0 public gate options unchanged;
- Tour Intelligence replacement ZIP built and verified.

Production acceptance requires a separate owner-approved installation and live `/kultur-turlari/` runtime check.
