# H9F — Admin Recovery + Canonical Geography + Taxonomy Filtering

Version: **0.9.6**

Scope: admin/data-quality bugfix only. Publication Lock remains unchanged and locked by default.

## Fixes

1. Native WordPress Trash for `sthi_hotel` is reachable again, and Hotel Grid exposes a Trash button with count.
2. Country/City fields now use a canonical geography registry:
   - all ISO country names are available as suggestions,
   - common TR/EN/FA/AR aliases for pilot cities are normalized,
   - `مکه / مكة / Mekke / Mecca` → `Makkah`,
   - `مدینه / المدينة / Medine / Medina` → `Madinah`,
   - known city can infer country when Country is blank,
   - canonical Country/City are appended to hierarchical Destinations.
3. Makkah/Madinah Hub detection now consumes canonical city identity, including Persian/Arabic aliases.
4. Hotel Grid now supports Destination and Collection filters. Native taxonomy count links preserve their filter when redirected into the custom Hotel Grid instead of showing all Hotels.

## Safety

- Stable Hotel IDs unchanged.
- Public route architecture unchanged.
- Publication Lock unchanged.
- `/umre-1/` untouched.
- Header/Footer untouched.
- Existing Hotel content/media unchanged.
