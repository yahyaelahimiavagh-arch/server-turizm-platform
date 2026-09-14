SERVER TURIZM — SEO-PERF-UMRE-2C-C2
Version 0.1.0

Candidate
---------
Conditional Porto plugins.css Unload

Evidence
--------
Chrome Coverage after meaningful page interaction showed:
- Porto plugins.css
- Total bytes: 112,585
- Unused bytes: 112,585
- Unused: 100%

Scope
-----
- Only /umre-1/
- Only WordPress style handle: porto-plugins
- Expected resource: /wp-content/themes/porto/css/plugins.css
- No JS changes
- No global changes
- No deregistration

IMPORTANT
---------
Keep the already accepted C1 plugin active during this C2 test.
C2 is an incremental test on top of the accepted C1 baseline.

Verification
------------
Run in browser console:

({
  c1_revslider_css_present: !!document.getElementById('rs-plugin-settings-css'),
  c2_porto_plugins_css_present: !!document.getElementById('porto-plugins-css'),
  h1_count: document.querySelectorAll('h1').length
})

Expected:
- c1_revslider_css_present: false
- c2_porto_plugins_css_present: false
- h1_count: 1

Rollback
--------
Deactivate/delete this C2 plugin. Leave accepted C1 active.
