# STTI v0.6.2.1 — Sheet Link + Partial JSON Generator

## What changed

Visible operator columns stay exactly the same.

Two technical columns are used after the visible sheet:

- `Z` — `STTI Stable ID`
- `AA` — `Expected Checksum`

The Apps Script automatically hides both columns.

### Existing tour

Both values must be present.

Example for the Iran pilot:

- Stable ID: `STT-000001`
- Expected Checksum: `d72c07fbec5d97c338e076c0203cacad4f8049101bf85adff5732f1423954348`

The generated JSON should target the existing record.

### New tour

Leave both cells blank.

The generated JSON has:

```json
"target": {
  "stable_id": null,
  "expected_checksum_sha256": null
}
```

and WordPress Dry Run should classify it as CREATE.

## Google Sheets installation

1. Open the current Tour Google Sheet.
2. `Extensions -> Apps Script`.
3. Replace the old `Code.gs` with `STTI-v0.6.2.1-Sheet-to-Partial-JSON.gs`.
4. Save.
5. Return to the Sheet and refresh.
6. Use `STTI -> Teknik kolonları hazırla / gizle`.
7. For an existing tour, select its row and use:
   `STTI -> Seçili satırı mevcut STTI kaydına bağla`.
8. For the Iran pilot, enter:
   - `STT-000001`
   - `d72c07fbec5d97c338e076c0203cacad4f8049101bf85adff5732f1423954348`
9. With the Iran row still selected:
   `STTI -> Partial JSON indir`.

## Expected Iran JSON

The target must be:

```json
"target": {
  "stable_id": "STT-000001",
  "expected_checksum_sha256": "d72c07fbec5d97c338e076c0203cacad4f8049101bf85adff5732f1423954348"
}
```

Route stops keep `label`, and a `city` field is added only when that exact stop also exists in the visible `Şehirler` column.

## Safety

This generator does not write to WordPress.

It does not request:
- public route
- indexation
- sitemap
- schema
- canonical changes

The two hidden Sheet fields are control metadata only.
