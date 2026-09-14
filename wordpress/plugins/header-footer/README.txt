Server Turizm Header & Footer v1.1.7

Candidate change:
- Homepage only: starts semantic output buffering at WordPress `get_header`.
- Converts only Porto's hidden legacy logo wrapper from H1 to DIV when it has:
  - classes `logo` and `logo-transition`
  - a rel="home" anchor
  - an image inside
- Preserves the visible custom Server Turizm header/footer, logo image, ALT, classes and hero H1.
- No CSS or JavaScript changes.

Runtime acceptance test:
On homepage Console run:
[...document.querySelectorAll('h1')].map(h => ({text:h.textContent.trim(), class:h.className, html:h.outerHTML}))
Expected: only the visible `.hero-title` H1 remains.


1.1.9
- Fix Porto homepage legacy logo H1 semantic cleanup after runtime tracing showed that `porto_logo` filter receives class="logo" before `logo-transition` is appended later.
- Safety remains scoped to H1 + logo class + rel=home + img; only wrapper tag changes to DIV.


1.1.10
- Performance candidate: adds fetchpriority="high" only to the visible custom Server Turizm header brand logo.
- Based on PageSpeed LCP diagnostics: the logo is discoverable in initial HTML and is not lazy-loaded, but lacked high fetch priority.
- No preload, CSS, JavaScript, image source, dimensions, layout, or other image behavior changes.
- Keeps the accepted v1.1.9 Porto legacy H1 cleanup unchanged.

Runtime acceptance checks:
1. document.querySelector('.st-brand-logo img')?.outerHTML
   Expected: fetchpriority="high" present.
2. document.querySelectorAll('h1')
   Expected: only the real page H1; no Porto logo H1 regression.
3. Visual header/mobile smoke test.


v1.1.11
-------
- Moves the canonical footer width fix into this Header & Footer plugin, which actually owns #stSiteFooter.
- Replaces the old 100vw + negative-margin full-bleed rule with width: 100%.
- Adds the second valid Server Turizm phone: +90 212 621 06 00.
- Keeps canonical primary email: server@serverturizm.com.tr.
- Adds discreet footer signature: ElahiMiavagh © 2026 -> https://elahimiavagh.com/
  using rel="nofollow noopener noreferrer".
- Porto legacy footer retirement remains the child theme's responsibility.


v1.1.12
-------
- Keeps the footer credit in its intended brand casing:
  ElahiMiavagh © 2026
- Overrides inherited uppercase transformation for .st-footer-credit.
- Slightly reduces letter spacing for a quieter signature appearance.
