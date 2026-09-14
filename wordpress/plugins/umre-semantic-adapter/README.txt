Server Turizm Umre Semantic Adapter v0.33.0

Baseline:
- Built from v0.31.0 (responsive Program main images + accepted semantic/SEO work).
- v0.32.0 main-image lazy-loading experiment is NOT included.

v0.33.0 candidate change:
- On /umre-1/ only, late-dequeues a narrow allowlist of WooCommerce/YITH/Porto shop assets that a runtime DOM audit proved have no matching UI on the Umre page.

Styles removed on /umre-1/:
- yith_wcas_frontend
- porto-theme-shop

Scripts removed on /umre-1/:
- wc-add-to-cart
- vc_woocommerce-add-to-cart-js
- woocommerce
- yith_autocomplete
- porto-woocommerce-theme

Intentionally left untouched for this first candidate:
- jquery-blockui
- js-cookie
- jquery-cookie
These are generic dependency handles and require a separate proof before removal.

No generator, Google Sheet, Apps Script, Program Card CSS, card layout, hotel gallery behavior, schema, redirects, H1 logic, Header/Footer code, or image URLs are changed by this candidate.
