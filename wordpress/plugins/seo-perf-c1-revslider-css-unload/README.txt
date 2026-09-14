SERVER TURIZM — SEO-PERF-UMRE-2C-C1
Version 0.1.1

Purpose
-------
Remove Slider Revolution rs6.css ONLY from:
https://www.serverturizm.com.tr/umre-1/

Why v0.1.1?
-----------
v0.1.0 dequeue did not remove the final link tag in production.
v0.1.1 keeps the dequeue attempt and adds a final WordPress
style_loader_tag output guard for handle:
rs-plugin-settings

Scope
-----
- Only /umre-1/
- Only rs-plugin-settings
- No JS changes
- No global changes
- No deregistration
- Easy rollback: deactivate plugin

Verification
------------
Run in browser console:
document.getElementById('rs-plugin-settings-css')

Expected result:
null
