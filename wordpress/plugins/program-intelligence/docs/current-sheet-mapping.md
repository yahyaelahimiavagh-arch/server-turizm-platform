# Current Google Sheet to ST-TDE Mapping

The existing `Home` sheet remains unchanged during the Shadow pilot.

| Current column | Current meaning | Contract target | Migration rule |
|---|---|---|---|
| A | No | provenance.source_row | Evidence only |
| B | Program No | program_code | Never used as stable identity |
| C | Lüks/Eko | tier | Normalize to controlled vocabulary |
| D | Başlık | title | Required |
| E | Sayaç Hedef | schedule.start_date | Compare with G; conflict blocks approval |
| F | Kalan gün | computed | Never imported |
| G | Gidiş | schedule.start_date + departure segment | Preserve explicit role |
| H | Ara Geçiş 1 | segments[] | Do not compact missing dates |
| I | Ara Geçiş 2 | segments[] | Do not compact missing dates |
| J | Dönüş | schedule.end_date + return segment | Required for scheduled departure |
| K | Gece/Gündüz | schedule duration + destination nights | Parse then compare with dates |
| L | Başka ülke/şehir | destinations[] | Normalize country/city |
| M/P/S | Hotel names | stays[].unresolved_hotel_name | Resolve once to STH-* and stop copying names |
| N/Q/T | Hotel images | removed | Hotel Intelligence supplies media |
| O/R/U | Hotel map | removed | Hotel Intelligence supplies map/location |
| V/W/X | 2/3/4-person prices | pricing.entries[] | Numeric amount + currency + per-person unit |
| Y | Child prices | pricing.child_rules[] | Parse into age bands; multiline text cannot publish directly |
| Z | Important notes | inclusions/exclusions/notes | Operator mapping required |
| AA | Phone | contact | Normalize E.164 where possible |
| AB | Capacity | capacity.total | Optional |
| AC | Dolu | workflow.availability | true becomes sold_out |
| AD | Programı Kaldır | archive candidate | Never delete directly |
| AE/AF | Meal plan flags | stays[].meal_plan | Normalize FB/HB/BB/custom |
| AG/AH | Rooms | stay room/allotment extension | Later contract extension after source examples |
| AI | Main image | media.hero_image_url | Requires rights/source note |
| AJ | Logos | media/output template | Prefer controlled brand asset IDs later |

## Required new Sheet columns after pilot

- Stable Program ID
- Editorial Status
- Schedule Status
- Availability Status
- Publication Mode
- Date Precision
- Verified At
- Verified By
- Source Note

The current `Hotels` tab remains read-only migration evidence. v0.2.0 creates a separate `ST Hotel Directory` cache tab only after an operator pastes the read-only directory JSON exported by WordPress. It contains Stable Hotel ID, canonical names/aliases, city, status, public URL, primary image and synchronization timestamps. The exporter performs exact normalized alias matching only; ambiguous and unknown names remain unresolved warnings.
