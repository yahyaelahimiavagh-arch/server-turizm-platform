SERVER TURIZM PORTO CHILD v1.0.3
=====================================

Purpose
-------
This child theme is intentionally minimal.

It DOES:
- inherit Porto as the parent theme;
- preserve parent theme_mods on first activation;
- override footer.php;
- remove only the call that renders Porto's legacy widget footer:
  get_template_part( 'footer/footer' );

It DOES NOT:
- edit Porto core;
- remove wp_footer();
- remove the existing floating WhatsApp or telephone code from footer.php;
- change Program Intelligence, Hotel Intelligence, Program Publishing,
  indexation locks, sitemap locks, or canonical data;
- add or alter the new Server Turizm global footer.

Expected result after activation
--------------------------------
Legacy footer content sourced from Footer Widget 1–4 / Footer Bottom Widget
should stop rendering, including:
- info@serverturizm.com.tr
- 2019 copyright text
- Ela Design credit

The accepted Server Turizm footer/runtime layer should remain.

Rollback
--------
Appearance > Themes > activate Porto again.

Runtime QA required immediately after activation
------------------------------------------------
1. Homepage
2. /umre-1/
3. One Hotel page
4. One Program detail page
5. /letisim/
6. Mobile view
7. View Source: confirm legacy footer strings are absent.


v1.0.3
------
Adds a narrowly-scoped width-integrity hotfix for #stSiteFooter:
- width: 100% instead of a 100vw/full-bleed runtime width
- removes the negative horizontal margin
- prevents the browser scrollbar width from creating horizontal page scroll

Expected mobile/runtime result:
- footer width == document.documentElement.clientWidth
- footer left == 0
- footer right == clientWidth
- window.scrollX remains 0 after a horizontal scroll attempt


v1.0.3
------
Explicitly enqueues the child theme stylesheet at wp_enqueue_scripts priority 999.
Reason: v1.0.1 hotfix CSS was present in style.css, but on this Porto installation
the child stylesheet was not being applied in runtime.


v1.0.3
------
Architecture cleanup:
- removes the temporary #stSiteFooter width hotfix from the child theme;
- child theme now owns only Porto legacy-footer retirement + theme_mod preservation;
- canonical Server Turizm footer styling/content remains owned by the Header & Footer plugin.
