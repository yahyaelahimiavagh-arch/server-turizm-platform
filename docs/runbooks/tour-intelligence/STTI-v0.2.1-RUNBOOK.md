# STTI v0.2.1 — Private Store Quality Hotfix Runbook

1. Mevcut v0.2.0 üzerine Replace Current Version ile kurun.
2. Tour Intelligence → Create / Edit Tour açın.
3. Exact dates test:
   - Start: `16/10/2026`
   - End: `24/10/2026`
   - Expected: Days=9, Nights=8, Temporal=Upcoming (11.09.2026 itibarıyla).
4. Aynı tarih alanında:
   - manuel yazma çalışmalı;
   - takvim ikonu çalışmalı;
   - çift tıklama takvimi açmalı.
5. İran candidate fiyatı source'a göre `899 EUR` olarak girilmeli.
6. Poster kişi başı demiyorsa Price Basis = `Unknown / source not specified` tutulmalı.
7. Source Type = Brochure; Source Completeness = SOURCE COMPLETE; Editorial = Needs review.
8. JSON Önizleme ile canonical payload kontrol edilmeden Private Kaydet yapılmamalı.
9. Public locks her zaman OFF kalmalı.
