# STTI v0.2.0 — Private Store Runtime Gate

## Gate A — Upgrade safety
- WordPress çalışıyor.
- STTI v0.2.0 görünüyor.
- Public Master HARD OFF.
- Public routes / Sitemap / Indexation / Homepage adapter OFF.

## Gate B — Controlled CREATE
Create/Edit Tour içinde minimum geçerli candidate gir:
- Public Başlık: Büyük İran Turu
- Dil: tr-TR
- Primary Country: İran
- Route: Tahran → Kaşan → İsfahan → Yezd → Şiraz
- Date Mode: exact
- Date Precision: exact
- Start: 2026-10-16
- End: 2026-10-24
- Duration: 9 days / 8 nights
- Price Type: exact
- Amount: 899
- Currency: EUR
- Source Type: brochure
- Source Ref: iran-brosur-2026
- Source Completeness: SOURCE COMPLETE
- Editorial: needs_review
- Schedule: scheduled
- Availability: open
- Temporal: upcoming

Kaydet.
Beklenen:
- STT-000001 atanır.
- Registry'de 1 private candidate.
- Frontend output yok.
- Audit: candidate_created.

## Gate C — Idempotency
Aynı candidate'i değiştirmeden tekrar kaydet.
Beklenen:
- Yeni STT ID yok.
- Aynı `STT-000001`.
- Audit: candidate_unchanged.

## Gate D — Controlled UPDATE
Fiyatı `899 → 900` yap ve kaydet.
Beklenen:
- Aynı Stable ID.
- Registry row sayısı aynı.
- Audit: candidate_updated.

Sonra `900 → 899` geri döndür ve kaydet.
Beklenen:
- Yine aynı Stable ID.
- Audit yeni update kaydı.

## Gate E — Fail-closed validation
Yeni candidate açıp Public Başlık boş bırakmayı dene.
Beklenen: save BLOCK.

Exact date seçip tarihleri boş bırakmayı dene.
Beklenen: save BLOCK.

From/Exact fiyat seçip amount boş bırakmayı dene.
Beklenen: save BLOCK.

## Gate F — Public isolation
- `/kultur-turlari/` değişmemeli.
- Homepage Culture cards değişmemeli.
- WordPress sitemap'te yeni Tour URL olmamalı.
- Frontend'de STT candidate görünmemeli.

## Acceptance
Tüm gate'ler PASS ise:
`STTI v0.2.0 PRIVATE STORE = RUNTIME ACCEPTED`

Sonraki branch:
`STTI v0.3.0 — Private Renderer Pilot`.
