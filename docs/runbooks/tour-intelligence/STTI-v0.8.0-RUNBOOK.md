# STTI v0.8.0 — Complete Customer Renderer Runbook

Scope: disposable/private validation only. This runbook does not authorize production deployment or public exposure.

## Runtime sequence
1. Run repository baseline verification.
2. Build the replacement Tour Intelligence ZIP and SHA256.
3. Install a disposable latest WordPress with MariaDB.
4. Activate the Tour Intelligence package.
5. Run the accepted legacy runtime smoke (`wp_runtime_smoke.php`).
6. Run `wp_runtime_customer_renderer_v080.php` in the same disposable WordPress.
7. Confirm the renderer uses exactly one reviewed primary Route Variant when present and never guesses a primary.
8. Confirm pending/rejected Hotel and Transport relations are excluded from the renderer projection.
9. Confirm Hotel Intelligence facts are read live by Stable ID and are not copied into the Tour payload.
10. Confirm the map receives only canonical confirmed geo and contains no browser geocoder/cache authority.
11. Confirm renderer analysis leaves Tour rows, audit rows, Stable-ID sequence and source payload unchanged.
12. Confirm Tour public route/indexation/sitemap/schema/canonical/homepage gates remain OFF.

## Failure policy
Any failed gate stops acceptance. Fix the smallest failing surface, rerun the relevant local/static test, then use the single PR CI flow for final evidence. Do not weaken accepted v0.6.5, v0.7.0 or v0.7.1 gates to make v0.8 pass.

## Merge / deployment policy
- no automatic merge;
- merge requires explicit owner approval;
- repository acceptance is not production deployment;
- no live-site install or public route activation is part of v0.8.0 acceptance.
