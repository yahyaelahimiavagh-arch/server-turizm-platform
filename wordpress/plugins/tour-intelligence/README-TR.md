# Server Turizm Tour Intelligence

## v0.6.5 — AI Completion Contract

`STTI-AI-COMPLETION-1.0.0`, Sheet kaynaklı PARTIAL JSON ile AI tarafından önerilen FULL/REVIEW adayını aynı denetlenebilir zarf içinde bağlar. Kaynak hash'i, immutable target/policy/sources, exact claim listesi, eksik bilgi listesi ve zorunlu `pending` insan incelemesi doğrulanır.

Bu sürüm AI servisine bağlanmaz ve completion yolundan canonical/audit/sequence write yapmaz. Public route, sitemap, indexation, schema, canonical/robots ve homepage kilitleri OFF kalır. Ayrıntılar için `contracts/STTI-v0.6.5-AI-COMPLETION-CONTRACT.md` ve `STTI-v0.6.5-RUNBOOK.md` dosyalarına bakın.

## v0.5.6 Notu
Hero artwork doğrudan yukarı bleed edilir; Porto header yapısı değiştirilmez. Route görsel dili v0.5.4’e geri alınmıştır.

# Server Turizm Tour Intelligence v0.5.3

## Private Customer Experience — Global Route Map Pilot

v0.5.3, runtime-accepted v0.4 technical renderer ve v0.3 structured authoring baseline'ını korur.

Yeni private customer preview özellikleri:
- Canlı WordPress tema header/footer
- Full-bleed layout
- Büyük ve renk kodlu tur özet kartları
- Dünya çapında çalışan interaktif rota haritası
- Leaflet + OpenStreetMap
- Canonical route stop'lardan otomatik geocoding
- Sıralı rota çizgisi ve numaralı şehir marker'ları
- Auto fit bounds
- Browser localStorage coordinate cache pilot
- Alt tarafta premium yatay route timeline
- Unresolved lokasyonlarda `never invent`

## Güvenlik

HARD OFF:
- Public Renderer
- Public Routes
- Sitemap
- Indexation
- Homepage Adapter
- STTI schema output
- Canonical/robots writes

Customer Preview admin-only + nonce + noindex olarak kalır.
Canonical Tour verisine preview üzerinden write yapılmaz.


## v0.5.4
- Header/hero seam fix: overlap spacing is applied to hero content instead of painting a white root spacer.
- Worldwide Leaflet route remains data-driven.
- Route path is drawn segment-by-segment from origin to destination.
- Markers and the lower route timeline reveal progressively.
- `prefers-reduced-motion` receives an instant final route.
- No public route, sitemap, indexation, schema or canonical write is enabled.
