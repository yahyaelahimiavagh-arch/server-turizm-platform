/**
 * Server Turizm ST-TDE Google Sheets Exporter v0.2.1
 *
 * INSTALL WITHOUT REPLACING THE LEGACY GENERATOR:
 * 1) Add a new Apps Script file named ST_TDE_Exporter.
 * 2) Paste this entire file into it.
 * 3) Add this single line inside the existing onOpen(), immediately before its closing brace:
 *      stTdeOnOpen_();
 *
 * The module reads Home and writes only to:
 * - a dedicated "ST Hotel Directory" sheet when the operator imports directory JSON;
 * - a new JSON file in Google Drive when the operator explicitly exports.
 * It never edits Home, never generates public HTML and never archives a program.
 */

var ST_TDE = Object.freeze({
  VERSION: '0.2.1',
  SCHEMA_VERSION: '1.0.0',
  HOME_SHEET: 'Home',
  HOTEL_SHEET: 'ST Hotel Directory',
  MAX_COLUMNS: 36,
  TIMEZONE: 'Europe/Istanbul'
});

function stTdeOnOpen_() {
  SpreadsheetApp.getUi()
    .createMenu('🧠 ST-TDE')
    .addItem('بررسی برنامه‌های فعال', 'stTdeValidateActivePrograms')
    .addItem('خروجی JSON برنامه انتخاب‌شده', 'stTdeExportSelectedProgramJson')
    .addItem('خروجی JSON همه برنامه‌های فعال', 'stTdeExportActiveProgramsJson')
    .addItem('خروجی JSON برنامه‌های Programı Kaldır', 'stTdeExportRemovalManifestJson')
    .addSeparator()
    .addItem('واردکردن Hotel Directory', 'stTdeImportHotelDirectory')
    .addItem('گزارش اتصال هتل‌ها', 'stTdeHotelMappingReport')
    .addSeparator()
    .addItem('راهنمای نسخه Shadow', 'stTdeShowHelp')
    .addToUi();
}

function stTdeShowHelp() {
  SpreadsheetApp.getUi().alert(
    'ST-TDE Shadow v' + ST_TDE.VERSION,
    'این ماژول شیت Home را تغییر نمی‌دهد. Active JSON برای Candidate Import است. Programı Kaldır JSON یک Removal Manifest جداگانه است و فقط در WordPress → Program Intelligence → Removal Review با تأیید صریح مدیر می‌تواند Archive ایجاد کند؛ هیچ Delete انجام نمی‌شود.',
    SpreadsheetApp.getUi().ButtonSet.OK
  );
}

function stTdeImportHotelDirectory() {
  var html = HtmlService.createHtmlOutput(
    '<div style="font-family:Tahoma,sans-serif;padding:16px;direction:rtl">' +
      '<h3>واردکردن Hotel Directory JSON</h3>' +
      '<p>JSON کپی‌شده از WordPress → Program Intelligence → Hotel Directory را وارد کنید.</p>' +
      '<textarea id="st-json" style="width:100%;height:260px;direction:ltr;font-family:monospace"></textarea>' +
      '<div id="st-status" style="margin:12px 0"></div>' +
      '<button style="padding:10px 18px" onclick="save()">بررسی و ذخیره</button>' +
      '<script>function save(){var s=document.getElementById("st-status");s.textContent="در حال بررسی...";' +
      'google.script.run.withSuccessHandler(function(r){s.textContent=r;setTimeout(function(){google.script.host.close()},1200)})' +
      '.withFailureHandler(function(e){s.textContent="خطا: "+e.message})' +
      '.stTdeSaveHotelDirectoryJson(document.getElementById("st-json").value)}</script>' +
    '</div>'
  ).setWidth(720).setHeight(470);
  SpreadsheetApp.getUi().showModalDialog(html, 'ST-TDE Hotel Directory');
}

function stTdeSaveHotelDirectoryJson(raw) {
  if (!raw || raw.length > 1500000) throw new Error('JSON خالی یا بیش از حد بزرگ است.');
  var payload;
  try { payload = JSON.parse(raw); } catch (error) { throw new Error('JSON معتبر نیست: ' + error.message); }
  if (!payload || payload.schema_version !== '1.0.0' || !Array.isArray(payload.hotels)) {
    throw new Error('Hotel Directory Contract 1.0.0 مورد انتظار است.');
  }
  if (payload.ready !== true) throw new Error('Hotel Directory در WordPress وضعیت READY ندارد.');

  var seen = {};
  var rows = payload.hotels.map(function(hotel) {
    var id = String(hotel.hotel_id || '').trim().toUpperCase();
    if (!/^STH-\d{6}$/.test(id)) throw new Error('Hotel ID نامعتبر: ' + id);
    if (seen[id]) throw new Error('Hotel ID تکراری: ' + id);
    seen[id] = true;
    return [
      id,
      String(hotel.display_name || ''),
      String(hotel.official_name || ''),
      Array.isArray(hotel.aliases) ? hotel.aliases.join(' | ') : '',
      String(hotel.city || ''),
      String(hotel.verification_status || ''),
      String(hotel.workflow_status || ''),
      String(hotel.public_url || ''),
      String(hotel.primary_image_url || ''),
      String(hotel.modified_at || ''),
      String(payload.generated_at || '')
    ];
  });

  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(ST_TDE.HOTEL_SHEET) || ss.insertSheet(ST_TDE.HOTEL_SHEET);
  sheet.clearContents();
  var headers = [['Stable Hotel ID', 'Display Name', 'Official Name', 'Aliases', 'City', 'Verification', 'Workflow', 'Public URL', 'Primary Image', 'Hotel Modified At', 'Directory Generated At']];
  sheet.getRange(1, 1, 1, headers[0].length).setValues(headers).setFontWeight('bold').setBackground('#071B4D').setFontColor('#FFFFFF');
  if (rows.length) sheet.getRange(2, 1, rows.length, headers[0].length).setValues(rows);
  sheet.setFrozenRows(1);
  sheet.autoResizeColumns(1, 7);
  sheet.setColumnWidth(4, 280);
  sheet.setColumnWidth(8, 300);
  sheet.setColumnWidth(9, 300);
  return rows.length + ' هتل با Stable ID وارد شد.';
}

function stTdeValidateActivePrograms() {
  var built = stTdeBuildActiveBatch_();
  stTdeShowValidation_(built);
}

function stTdeExportActiveProgramsJson() {
  var built = stTdeBuildActiveBatch_();
  if (built.report.errors.length) { stTdeShowValidation_(built); return; }
  stTdeSaveBatchToDrive_(built.batch, built.report, 'active-programs');
}

function stTdeExportRemovalManifestJson() {
  var built = stTdeBuildRemovalManifest_();
  if (built.errors.length) {
    SpreadsheetApp.getUi().alert('Removal Manifest BLOCKED', built.errors.join('\n'), SpreadsheetApp.getUi().ButtonSet.OK);
    return;
  }
  if (!built.manifest.removals.length) {
    SpreadsheetApp.getUi().alert('هیچ ردیف Programı Kaldır پیدا نشد.');
    return;
  }
  stTdeSaveRemovalManifestToDrive_(built.manifest);
}

function stTdeExportSelectedProgramJson() {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  if (sheet.getName() !== ST_TDE.HOME_SHEET) {
    SpreadsheetApp.getUi().alert('ابتدا یک ردیف از شیت Home را انتخاب کنید.');
    return;
  }
  var headerRow = stTdeFindHeaderRow_(sheet);
  var rowNumber = sheet.getActiveCell().getRow();
  if (rowNumber <= headerRow) {
    SpreadsheetApp.getUi().alert('یک ردیف برنامه را انتخاب کنید.');
    return;
  }
  var values = sheet.getRange(rowNumber, 1, 1, ST_TDE.MAX_COLUMNS).getValues()[0];
  var displays = sheet.getRange(rowNumber, 1, 1, ST_TDE.MAX_COLUMNS).getDisplayValues()[0];
  var hotelIndex = stTdeLoadHotelIndex_();
  var issues = {errors: [], warnings: []};
  var program = stTdeBuildProgram_(values, displays, rowNumber, hotelIndex, issues);
  if (!program) {
    SpreadsheetApp.getUi().alert('این ردیف خالی یا با Programı Kaldır علامت‌گذاری شده است.');
    return;
  }
  var batch = stTdeBatchEnvelope_([program], 'partial');
  var report = stTdeValidateBatch_(batch, issues);
  if (report.errors.length) { stTdeShowValidation_({batch: batch, report: report}); return; }
  stTdeSaveBatchToDrive_(batch, report, 'program-' + stTdeFileSafe_(program.program_code));
}

function stTdeHotelMappingReport() {
  var built = stTdeBuildActiveBatch_();
  var rows = built.batch.programs;
  var total = 0, resolved = 0, unresolved = [];
  rows.forEach(function(program) {
    program.stays.forEach(function(stay) {
      total++;
      if (stay.hotel_id) resolved++;
      else unresolved.push(program.program_code + ': ' + (stay.unresolved_hotel_name || stay.destination));
    });
  });
  var message = 'اتصال موفق: ' + resolved + ' از ' + total + '\n\n';
  message += unresolved.length ? 'حل‌نشده:\n- ' + unresolved.join('\n- ') : 'تمام هتل‌ها با STH-* متصل هستند.';
  SpreadsheetApp.getUi().alert('گزارش Hotel Mapping', message.slice(0, 9000), SpreadsheetApp.getUi().ButtonSet.OK);
}

function stTdeBuildActiveBatch_() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(ST_TDE.HOME_SHEET);
  if (!sheet) throw new Error('شیت Home پیدا نشد.');
  var headerRow = stTdeFindHeaderRow_(sheet);
  var lastRow = sheet.getLastRow();
  if (lastRow <= headerRow) throw new Error('هیچ برنامه‌ای در Home پیدا نشد.');

  var count = lastRow - headerRow;
  var values = sheet.getRange(headerRow + 1, 1, count, ST_TDE.MAX_COLUMNS).getValues();
  var displays = sheet.getRange(headerRow + 1, 1, count, ST_TDE.MAX_COLUMNS).getDisplayValues();
  var hotelIndex = stTdeLoadHotelIndex_();
  var issues = {errors: [], warnings: []};
  var programs = [];

  values.forEach(function(row, index) {
    var program = stTdeBuildProgram_(row, displays[index], headerRow + 1 + index, hotelIndex, issues);
    if (program) programs.push(program);
  });
  var batch = stTdeBatchEnvelope_(programs, 'partial');
  return {batch: batch, report: stTdeValidateBatch_(batch, issues)};
}

function stTdeBuildRemovalManifest_() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(ST_TDE.HOME_SHEET);
  if (!sheet) throw new Error('شیت Home پیدا نشد.');
  var headerRow = stTdeFindHeaderRow_(sheet);
  var lastRow = sheet.getLastRow();
  var removals = [], errors = [];
  if (lastRow > headerRow) {
    var count = lastRow - headerRow;
    var values = sheet.getRange(headerRow + 1, 1, count, ST_TDE.MAX_COLUMNS).getValues();
    var displays = sheet.getRange(headerRow + 1, 1, count, ST_TDE.MAX_COLUMNS).getDisplayValues();
    values.forEach(function(row, index) {
      if (!stTdeTruthy_(row[29])) return;
      var rowNumber = headerRow + 1 + index;
      var code = stTdeText_(row[1]);
      if (!code) { errors.push('ردیف ' + rowNumber + ': Program No خالی است.'); return; }
      removals.push({
        program_code: code,
        title: stTdeText_(row[3]),
        source_ref: 'Home row ' + rowNumber,
        source_row: rowNumber,
        reason: 'programi_kaldir',
        start_date: stTdeIsoDate_(row[6]),
        end_date: stTdeIsoDate_(row[9])
      });
    });
  }
  return {
    errors: errors,
    manifest: {
      schema: 'st-tde-removal-manifest/v1',
      export_id: 'STR-' + Utilities.formatDate(new Date(), ST_TDE.TIMEZONE, 'yyyyMMdd-HHmmss'),
      generated_at: new Date().toISOString(),
      source: {type: 'google_sheets', document_ref: ss.getId(), worksheet: ST_TDE.HOME_SHEET, timezone: ST_TDE.TIMEZONE},
      removals: removals
    }
  };
}

function stTdeBatchEnvelope_(programs, mode) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  return {
    schema_version: ST_TDE.SCHEMA_VERSION,
    export_id: 'STX-' + Utilities.formatDate(new Date(), ST_TDE.TIMEZONE, 'yyyyMMdd-HHmmss'),
    generated_at: new Date().toISOString(),
    source: {
      type: 'google_sheets',
      mode: mode || 'partial',
      document_ref: ss.getId(),
      worksheet: ST_TDE.HOME_SHEET,
      timezone: ST_TDE.TIMEZONE
    },
    programs: programs
  };
}

function stTdeBuildProgram_(row, display, rowNumber, hotelIndex, issues) {
  var code = stTdeText_(row[1]);
  if (!code) return null;
  if (stTdeTruthy_(row[29])) return null;

  var title = stTdeText_(row[3]);
  var start = stTdeIsoDate_(row[6]);
  var transition1 = stTdeIsoDate_(row[7]);
  var transition2 = stTdeIsoDate_(row[8]);
  var end = stTdeIsoDate_(row[9]);
  var durationAndRoute = stTdeRawText_(display[10] || row[10]);
  var duration = stTdeParseDuration_(durationAndRoute, start, end);
  var destinations = stTdeParseDestinations_(durationAndRoute);
  var exact = !!(start && end);
  var scope = 'row-' + rowNumber;
  var counterTarget = stTdeIsoDate_(row[4]);

  if (!title) issues.errors.push(stTdeIssue_(scope, 'title', 'Başlık خالی است.'));
  if (counterTarget && start && counterTarget !== start) issues.errors.push(stTdeIssue_(scope, 'schedule.start_date', 'Sayaç Hedef ile Gidiş tarihi farklı. Sayaç Hedef: ' + counterTarget + ' / Gidiş: ' + start));
  if (start && end && end < start) issues.errors.push(stTdeIssue_(scope, 'schedule.end_date', 'تاریخ بازگشت قبل از رفت است.'));
  if (!start || !end) issues.warnings.push(stTdeIssue_(scope, 'schedule', 'تاریخ دقیق رفت/برگشت کامل نیست.', 'warning'));
  if (!destinations.length) issues.errors.push(stTdeIssue_(scope, 'destinations', 'مقصد و تعداد شب از ستون K خوانده نشد.'));

  var eventDates = [start];
  if (transition1) eventDates.push(transition1);
  if (transition2) eventDates.push(transition2);
  eventDates.push(end);
  var allEventDates = eventDates.filter(function(value) { return !!value; });
  if (destinations.length && allEventDates.length !== destinations.length + 1) {
    issues.warnings.push(stTdeIssue_(scope, 'segments', 'تعداد تاریخ‌ها با تعداد مقصدها هم‌خوان نیست؛ نقش تاریخ‌ها جابه‌جا نشد.', 'warning'));
  }

  var segments = [];
  if (start && destinations.length) {
    segments.push({sequence: 1, type: 'departure', origin: null, destination: destinations[0].city, depart_at: stTdeDateTime_(start), arrive_at: null, transport_ref: null, notes: null});
  }
  var transfers = [
    {date: transition1, source_slot: 'H'},
    {date: transition2, source_slot: 'I'}
  ].filter(function(item) { return !!item.date; });
  transfers.forEach(function(item, index) {
    segments.push({sequence: segments.length + 1, type: 'transfer', origin: destinations[index] ? destinations[index].city : null, destination: destinations[index + 1] ? destinations[index + 1].city : null, depart_at: stTdeDateTime_(item.date), arrive_at: null, transport_ref: null, notes: 'Source date slot ' + item.source_slot});
  });
  if (end) segments.push({sequence: segments.length + 1, type: 'return', origin: destinations.length ? destinations[destinations.length - 1].city : null, destination: null, depart_at: stTdeDateTime_(end), arrive_at: null, transport_ref: null, notes: null});

  var stays = [];
  destinations.forEach(function(destination, index) {
    var legacyName = stTdeHotelNameForDestination_(destination.city, row);
    var hotelMatch = stTdeResolveHotel_(legacyName, hotelIndex);
    if (legacyName && hotelMatch.status !== 'resolved') {
      issues.warnings.push(stTdeIssue_(scope, 'stays.' + index + '.hotel_id', 'هتل حل‌نشده یا مبهم: ' + legacyName, 'warning'));
    }
    var checkIn = allEventDates.length === destinations.length + 1 ? allEventDates[index] : null;
    var checkOut = allEventDates.length === destinations.length + 1 ? allEventDates[index + 1] : null;
    stays.push({
      sequence: index + 1,
      destination: destination.city,
      hotel_id: hotelMatch.status === 'resolved' ? hotelMatch.hotel_id : null,
      unresolved_hotel_name: hotelMatch.status === 'resolved' ? null : (legacyName || null),
      check_in: checkIn,
      check_out: checkOut,
      nights: destination.nights,
      meal_plan: stTdeMealPlan_(destination.city, row),
      room_notes: stTdeRoomNotes_(destination.city, row)
    });
  });

  var priceEntries = [];
  [['double', 21, '2 kişilik oda'], ['triple', 22, '3 kişilik oda'], ['quad', 23, '4 kişilik oda']].forEach(function(spec) {
    var amount = stTdeNumber_(row[spec[1]], display[spec[1]]);
    if (amount !== null) priceEntries.push({occupancy: spec[0], amount: amount, unit: 'per_person', label: spec[2]});
  });
  if (!priceEntries.length) issues.warnings.push(stTdeIssue_(scope, 'pricing.entries', 'قیمت ساختاریافته‌ای پیدا نشد.', 'warning'));

  var childParsed = stTdeParseChildRules_(stTdeRawText_(display[24] || row[24]));
  var importantNotes = stTdeLines_(stTdeRawText_(row[25]));
  var hero = stTdeUrlOrNull_(stTdeText_(row[34]));
  var phone = stTdePhone_(stTdeText_(row[26]));

  return {
    program_id: null,
    program_code: code,
    service_type: 'umrah',
    publication_mode: exact ? 'scheduled_departure' : 'interest_campaign',
    title: title,
    locale: 'tr_TR',
    tier: stTdeTier_(row[2]),
    workflow: {editorial: 'needs_review', schedule: exact ? 'scheduled' : 'tentative', availability: stTdeTruthy_(row[28]) ? 'sold_out' : 'open', featured: false, priority: 100},
    schedule: {date_precision: exact ? 'exact' : 'unknown', start_date: start, end_date: end, date_label: exact ? null : stTdeText_(display[4]), timezone: ST_TDE.TIMEZONE, duration_days: duration.days, duration_nights: duration.nights},
    destinations: destinations,
    segments: segments,
    itinerary_days: [],
    stays: stays,
    transport: [],
    pricing: {currency: 'USD', price_type: priceEntries.length ? 'fixed' : 'on_request', entries: priceEntries, child_rules: childParsed.rules, surcharges: [], valid_from: null, valid_until: null, disclaimer: null},
    capacity: {total: stTdeIntegerOrNull_(row[27]), remaining: null, show_exact_remaining: false},
    inclusions: importantNotes,
    exclusions: [],
    notes: childParsed.notes,
    contact: {phone: phone, whatsapp: phone, cta_mode: stTdeTruthy_(row[28]) ? 'closed' : (exact ? 'reserve' : 'pre_register')},
    media: {hero_image_url: hero, gallery: [], media_rights_note: hero ? 'Legacy Sheet media; rights/source approval required.' : null, design_template: 'umrah-program'},
    seo: {seo_title: null, meta_description: null, primary_topic: title ? title.toLocaleLowerCase('tr-TR') : null, index_policy: 'noindex'},
    provenance: {source_type: 'google_sheets', source_ref: 'Home row ' + rowNumber, verified_at: null, verified_by: null, source_row: rowNumber, notes: 'Shadow export; operator verification required before approval.'}
  };
}

function stTdeValidateBatch_(batch, seedIssues) {
  var errors = (seedIssues && seedIssues.errors ? seedIssues.errors : []).slice();
  var warnings = (seedIssues && seedIssues.warnings ? seedIssues.warnings : []).slice();
  var seenIds = {}, programRows = [];
  if (!batch.programs.length) errors.push(stTdeIssue_('batch', 'programs', 'هیچ برنامه فعالی پیدا نشد.'));
  batch.programs.forEach(function(program, index) {
    var scope = 'row-' + program.provenance.source_row;
    if (program.program_id) {
      if (seenIds[program.program_id]) errors.push(stTdeIssue_(scope, 'program_id', 'Stable Program ID تکراری است.'));
      seenIds[program.program_id] = true;
    }
    var unresolved = program.stays.filter(function(stay) { return !stay.hotel_id; }).length;
    var score = 0;
    if (program.program_code && program.title) score += 10;
    if (program.schedule.start_date && program.schedule.end_date) score += 20; else if (program.schedule.date_precision) score += 8;
    if (program.destinations.length && program.segments.length) score += 20;
    if (program.pricing.entries.length || program.pricing.price_type === 'on_request') score += 15;
    if (program.stays.length) score += 8;
    if (program.stays.length && unresolved === 0) score += 7;
    if (program.inclusions.length || program.exclusions.length) score += 10;
    if (program.transport.length) score += 10;
    if (program.media.hero_image_url) score += 5;
    if (program.seo.primary_topic) score += 5;
    var rowErrors = errors.filter(function(issue) { return issue.scope === scope; }).length;
    programRows.push({code: program.program_code, title: program.title, score: Math.min(100, score), gate: rowErrors ? 'BLOCKED' : (score >= 90 ? 'READY' : 'REVIEW'), unresolved_hotels: unresolved});
  });
  return {program_count: batch.programs.length, errors: errors, warnings: warnings, programs: programRows};
}

function stTdeShowValidation_(built) {
  var report = built.report;
  var rows = report.programs.map(function(row) {
    return '<tr><td>' + stTdeEscapeHtml_(row.code) + '</td><td>' + stTdeEscapeHtml_(row.title) + '</td><td>' + row.score + '</td><td>' + row.gate + '</td><td>' + row.unresolved_hotels + '</td></tr>';
  }).join('');
  var issues = report.errors.concat(report.warnings).map(function(issue) {
    return '<li><code>' + stTdeEscapeHtml_(issue.scope + ' / ' + issue.field) + '</code> ' + stTdeEscapeHtml_(issue.message) + '</li>';
  }).join('');
  var html = '<div style="font-family:Tahoma,sans-serif;padding:14px;direction:rtl">' +
    '<h2>ST-TDE Shadow Validation</h2><p>برنامه‌ها: <b>' + report.program_count + '</b> | خطا: <b>' + report.errors.length + '</b> | هشدار: <b>' + report.warnings.length + '</b></p>' +
    '<table style="width:100%;border-collapse:collapse" border="1" cellpadding="7"><tr><th>کد</th><th>عنوان</th><th>امتیاز</th><th>Gate</th><th>هتل حل‌نشده</th></tr>' + rows + '</table>' +
    (issues ? '<h3>موارد نیازمند بررسی</h3><ul>' + issues + '</ul>' : '<p style="color:green">خطا یا هشدار وجود ندارد.</p>') +
    '<p><b>هیچ تغییری در WordPress یا شیت Home ایجاد نشد.</b></p></div>';
  SpreadsheetApp.getUi().showModalDialog(HtmlService.createHtmlOutput(html).setWidth(900).setHeight(620), 'ST-TDE Validation');
}

function stTdeSaveBatchToDrive_(batch, report, label) {
  var filename = 'st-tde-' + label + '-' + Utilities.formatDate(new Date(), ST_TDE.TIMEZONE, 'yyyyMMdd-HHmmss') + '.json';
  var file = DriveApp.createFile(Utilities.newBlob(JSON.stringify(batch, null, 2), 'application/json', filename));
  var html = '<div style="font-family:Tahoma,sans-serif;padding:18px;direction:rtl"><h2>خروجی JSON آماده است</h2>' +
    '<p>برنامه‌ها: <b>' + report.program_count + '</b> | هشدارها: <b>' + report.warnings.length + '</b></p>' +
    '<p><a href="' + stTdeEscapeHtml_(file.getUrl()) + '" target="_blank">بازکردن فایل در Google Drive</a></p>' +
    '<p>این فایل را فقط در JSON Dry Run افزونه Program Intelligence آزمایش کنید.</p></div>';
  SpreadsheetApp.getUi().showModalDialog(HtmlService.createHtmlOutput(html).setWidth(520).setHeight(260), 'ST-TDE Export');
}

function stTdeSaveRemovalManifestToDrive_(manifest) {
  var filename = 'st-tde-removals-' + Utilities.formatDate(new Date(), ST_TDE.TIMEZONE, 'yyyyMMdd-HHmmss') + '.json';
  var file = DriveApp.createFile(Utilities.newBlob(JSON.stringify(manifest, null, 2), 'application/json', filename));
  var html = '<div style="font-family:Tahoma,sans-serif;padding:18px;direction:rtl"><h2>Removal Manifest آماده است</h2>' +
    '<p>Programı Kaldır: <b>' + manifest.removals.length + '</b></p>' +
    '<p><a href="' + stTdeEscapeHtml_(file.getUrl()) + '" target="_blank">بازکردن فایل در Google Drive</a></p>' +
    '<p><b>این فایل را در Candidate Import وارد نکنید.</b> مسیر صحیح: WordPress → Program Intelligence → Removal Review.</p>' +
    '<p>Archive فقط بعد از preflight و تأیید صریح مدیر انجام می‌شود؛ Delete وجود ندارد.</p></div>';
  SpreadsheetApp.getUi().showModalDialog(HtmlService.createHtmlOutput(html).setWidth(560).setHeight(300), 'ST-TDE Removal Manifest');
}

function stTdeLoadHotelIndex_() {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(ST_TDE.HOTEL_SHEET);
  var index = {byName: {}, ambiguous: {}};
  if (!sheet || sheet.getLastRow() < 2) return index;
  var rows = sheet.getRange(2, 1, sheet.getLastRow() - 1, 5).getDisplayValues();
  rows.forEach(function(row) {
    var id = stTdeText_(row[0]).toUpperCase();
    if (!/^STH-\d{6}$/.test(id)) return;
    [row[1], row[2]].concat(stTdeText_(row[3]).split('|')).forEach(function(name) {
      var key = stTdeNameKey_(name);
      if (!key) return;
      if (index.byName[key] && index.byName[key] !== id) { index.ambiguous[key] = true; delete index.byName[key]; }
      else if (!index.ambiguous[key]) index.byName[key] = id;
    });
  });
  return index;
}

function stTdeResolveHotel_(name, index) {
  var key = stTdeNameKey_(name);
  if (!key) return {status: 'missing', hotel_id: null};
  if (index.ambiguous[key]) return {status: 'ambiguous', hotel_id: null};
  return index.byName[key] ? {status: 'resolved', hotel_id: index.byName[key]} : {status: 'unresolved', hotel_id: null};
}

function stTdeFindHeaderRow_(sheet) {
  var limit = Math.min(10, Math.max(1, sheet.getLastRow()));
  var rows = sheet.getRange(1, 1, limit, ST_TDE.MAX_COLUMNS).getDisplayValues();
  for (var i = 0; i < rows.length; i++) {
    var b = stTdeNameKey_(rows[i][1]);
    var d = stTdeNameKey_(rows[i][3]);
    if (b === 'program no' || d === 'baslik') return i + 1;
  }
  throw new Error('ردیف عنوان ستون‌های Home پیدا نشد.');
}

function stTdeParseDestinations_(text) {
  var result = [];
  stTdeLines_(text).forEach(function(line) {
    var match;
    var regex = /(\d+)\s*Gece\s+([^,]+)/gi;
    while ((match = regex.exec(line)) !== null) {
      var rawDestination = stTdeText_(match[2]);
      // The first line is usually "9 Gece 10 Gün"; it is duration, not a stay.
      if (/^\d+\s*G[uü]n\b/i.test(rawDestination)) continue;
      var geo = stTdeGeo_(rawDestination);
      result.push({sequence: result.length + 1, country: geo.country, city: geo.city, nights: parseInt(match[1], 10)});
    }
  });
  return result;
}

function stTdeGeo_(raw) {
  var key = stTdeNameKey_(raw);
  if (key.indexOf('medine') !== -1 || key.indexOf('madinah') !== -1) return {country: 'Saudi Arabia', city: 'Madinah'};
  if (key.indexOf('mekke') !== -1 || key.indexOf('makkah') !== -1) return {country: 'Saudi Arabia', city: 'Makkah'};
  if (key.indexOf('kahire') !== -1 || key.indexOf('cairo') !== -1) return {country: 'Egypt', city: 'Cairo'};
  return {country: 'Unknown', city: stTdeText_(raw)};
}

function stTdeHotelNameForDestination_(city, row) {
  if (city === 'Madinah') return stTdeText_(row[15]);
  if (city === 'Makkah') return stTdeText_(row[18]);
  return stTdeText_(row[12]);
}

function stTdeMealPlan_(city, row) {
  var value = city === 'Madinah' ? row[30] : (city === 'Makkah' ? row[31] : 'B.B');
  return stTdeText_(value) || null;
}

function stTdeRoomNotes_(city, row) {
  var value = city === 'Madinah' ? row[32] : (city === 'Makkah' ? row[33] : '');
  return stTdeText_(value) || null;
}

function stTdeParseDuration_(text, start, end) {
  var match = text.match(/(\d+)\s*Gece\s*(\d+)\s*Gün/i);
  if (match) return {nights: parseInt(match[1], 10), days: parseInt(match[2], 10)};
  if (start && end) {
    var nights = Math.round((new Date(end + 'T00:00:00Z') - new Date(start + 'T00:00:00Z')) / 86400000);
    return {nights: nights, days: nights + 1};
  }
  return {nights: null, days: null};
}

function stTdeParseChildRules_(text) {
  var rules = [], notes = [], bedIncluded = !/YATAK\s+DAH[İI]L\s+DE[GĞ][İI]LD[İI]R/i.test(text);
  stTdeLines_(text).forEach(function(line) {
    var match = line.match(/(\d+)\s*-\s*(\d+)\s*Yaş\s*:\s*([\d.,]+)/i);
    if (match) {
      rules.push({min_age: Number(match[1]), max_age: Number(match[2]), bed_included: bedIncluded, pricing_method: 'fixed', amount: stTdeNumber_(match[3], match[3]), discount_amount: null, notes: null});
    } else if (line) notes.push(line);
  });
  return {rules: rules, notes: notes};
}

function stTdeTier_(value) {
  var key = stTdeNameKey_(value);
  if (key === 'luks') return 'luxury';
  if (key === 'eko' || key === 'ekonomik') return 'economy';
  return key || null;
}

function stTdeIsoDate_(value) {
  if (Object.prototype.toString.call(value) === '[object Date]' && !isNaN(value.getTime())) return Utilities.formatDate(value, ST_TDE.TIMEZONE, 'yyyy-MM-dd');
  var text = stTdeText_(value);
  if (!text) return null;
  var iso = text.match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (iso) return iso[1] + '-' + iso[2] + '-' + iso[3];
  var tr = text.match(/^(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})$/);
  if (tr) return tr[3] + '-' + ('0' + tr[2]).slice(-2) + '-' + ('0' + tr[1]).slice(-2);
  return null;
}

function stTdeDateTime_(isoDate) { return isoDate ? isoDate + 'T00:00:00+03:00' : null; }
function stTdeTruthy_(value) { return value === true || String(value).toLowerCase() === 'true' || String(value) === '1'; }
function stTdeText_(value) { return value === null || typeof value === 'undefined' ? '' : String(value).trim().replace(/\s+/g, ' '); }
function stTdeRawText_(value) { return value === null || typeof value === 'undefined' ? '' : String(value).trim(); }
function stTdeLines_(value) { return String(value || '').split(/\r?\n/).map(function(line) { return line.trim(); }).filter(function(line) { return !!line; }); }
function stTdeIntegerOrNull_(value) { var number = Number(value); return value === '' || value === null || !isFinite(number) ? null : Math.max(0, Math.round(number)); }
function stTdeUrlOrNull_(value) { return /^https?:\/\//i.test(value) ? value : null; }
function stTdePhone_(value) {
  var digits = String(value || '').replace(/\D/g, '');
  if (!digits) return null;
  if (/^0\d{10}$/.test(digits)) digits = '90' + digits.slice(1);
  else if (/^\d{10}$/.test(digits)) digits = '90' + digits;
  return '+' + digits;
}
function stTdeFileSafe_(value) { return String(value || 'program').replace(/[^a-z0-9_-]+/gi, '-').replace(/^-+|-+$/g, '').toLowerCase(); }
function stTdeIssue_(scope, field, message, severity) { return {severity: severity || 'error', scope: scope, field: field, message: message}; }
function stTdeEscapeHtml_(value) { return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;'); }
function stTdeNameKey_(value) {
  return stTdeText_(value).toLocaleLowerCase('tr-TR')
    .replace(/[ç]/g, 'c').replace(/[ğ]/g, 'g').replace(/[ıİi]/g, 'i')
    .replace(/[ö]/g, 'o').replace(/[ş]/g, 's').replace(/[ü]/g, 'u')
    .replace(/[^a-z0-9]+/g, ' ').trim().replace(/\s+/g, ' ');
}
function stTdeNumber_(value, display) {
  if (typeof value === 'number' && isFinite(value)) return value;
  var text = String(display || value || '').replace(/[^\d,.-]/g, '').trim();
  if (!text) return null;
  if (text.indexOf(',') !== -1 && text.indexOf('.') === -1) text = text.replace(',', '.');
  else text = text.replace(/,/g, '');
  var number = Number(text);
  return isFinite(number) ? number : null;
}
