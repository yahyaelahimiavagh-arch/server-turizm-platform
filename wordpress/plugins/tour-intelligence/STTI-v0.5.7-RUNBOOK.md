# STTI v0.5.7 — DOM-Measured Header Seam Fix

- Uses the actual live Server Turizm header selector `#stSiteHeader`.
- Runtime evidence: visible header top 30px; STTI hero top 66px; white Porto `#main` starts at 46px and `.main-content` contributes 20px top padding.
- The hero artwork/overlay bleeds upward dynamically by the measured distance to the live header top.
- Header/navigation geometry is not moved.
- v0.5.4 route visual skin and segment animation are preserved.
- Public routes, sitemap, indexation, schema output, canonical writes remain OFF.
