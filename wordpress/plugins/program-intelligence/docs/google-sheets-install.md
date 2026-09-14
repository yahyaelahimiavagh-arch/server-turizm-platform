# Google Sheets Shadow Exporter — Safe Installation

This integration is additive. It must not replace or edit the legacy HTML/reservation generator.

## WordPress side

1. Upgrade the existing Server Turizm Program Intelligence plugin to v0.3.0 or later.
2. Open **Program Intelligence → Hotel Directory**.
3. Confirm `Hotels = 9`, `Directory state = READY` and `Writes = 0`.
4. Copy the Hotel Directory JSON.

## Google Sheets side

1. Open **Extensions → Apps Script** for the existing Umrah spreadsheet.
2. Add a new script file named `ST_TDE_Exporter`.
3. Paste the complete contents of `integrations/google-sheets/ST_TDE_Exporter.gs`.
4. In the existing `onOpen()` function add exactly one call immediately before its closing brace:

```javascript
stTdeOnOpen_();
```

5. Save and reload the spreadsheet. The old menu remains; a separate **ST-TDE** menu appears.

## Pilot run

1. Use **ST-TDE → Import Hotel Directory** and paste the copied WordPress JSON.
2. Use **ST-TDE → Hotel Mapping Report**. Every known hotel should resolve to one Stable `STH-*` ID.
3. Use **ST-TDE → Validate Active Programs**. On the supplied snapshot, 48 source rows minus 13 rows marked `Programı Kaldır` equals 35 exported candidates.
4. Export Program 219 first, then upload the JSON only to **Program Intelligence → JSON Dry Run**.
5. Do not enable a public renderer, import candidates, modify `/umre-1/`, or request indexing during this gate.

## Failure behavior

- Missing or ambiguous hotel names remain explicit warnings; the exporter never guesses.
- A missing G/J date becomes a tentative interest campaign instead of an exact departure.
- H and I transition slots retain their source slot in segment evidence.
- `Programı Kaldır` only excludes a row from the partial batch; it never deletes or archives anything.
- Hotel names, images, maps and URLs remain owned by Hotel Intelligence.
