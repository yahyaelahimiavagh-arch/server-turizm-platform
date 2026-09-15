/**
 * Server Turizm — Umrah exporter / canonical Sheet adapter.
 * Depends only on ST-Umrah-Config.gs.
 * NO Tour code.
 */

function stUmrahValidateActivePrograms() {
  var built = stUmrahBuildActiveBatch_();
  stUmrahShowValidation_(built);
}

function stUmrahExportActiveProgramsJson() {
  var built = stUmrahBuildActiveBatch_();
  if (built.report.errors.length) {
    stUmrahShowValidation_(built);
    return;
  }
  stUmrahSaveBatchToDrive_(built.batch, built.report, 'active-programs');
}

function stUmrahExportSelectedProgramJson() {
  var built = stUmrahBuildSelectedRow_({allowRemoved: false});
  if (built.report.errors.length) {
    stUmrahShowValidation_({batch: built.batch, report: built.report});
    return;
  }
  stUmrahSaveBatchToDrive_(built.batch, built.report, 'program-' + stUmrahFileSafe_(built.program.program_code));
}

function stUmrahImportHotelDirectory() {
  var html = HtmlService.createHtmlOutput(
    '<div style="font-family:Arial,sans-serif;padding:16px;direction:rtl">' +
      '<h3>Hotel Directory JSON</h3>' +
      '<p>JSON خروجی WordPress → Program Intelligence → Hotel Directory را وارد کنید.</p>' +
      '<textarea id="j" style="width:100%;height:260px;direction:ltr;font-family:monospace"></textarea>' +
      '<div id="s" style="margin:12px 0"></div>' +
      '<button onclick="save()" style="padding:10px 18px">ذخیره</button>' +
      '<script>function save(){var s=document.getElementById("s");s.textContent="...";' +
      'google.script.run.withSuccessHandler(function(r){s.textContent=r;setTimeout(function(){google.script.host.close()},1000)})' +
      '.withFailureHandler(function(e){s.textContent="Hata: "+e.message})' +
      '.stUmrahSaveHotelDirectoryJson(document.getElementById("j").value)}</script>' +
    '</div>'
  ).setWidth(720).setHeight(470);
  SpreadsheetApp.getUi().showModalDialog(html, 'Umrah Hotel Directory');
}

function stUmrahSaveHotelDirectoryJson(raw) {
  if (!raw || raw.length > 1500000) throw new Error('JSON boş veya çok büyük.');
  var payload;
  try { payload = JSON.parse(raw); } catch (e) { throw new Error('JSON geçersiz: ' + e.message); }
  if (!payload || payload.schema_version !== '1.0.0' || !Array.isArray(payload.hotels)) {
    throw new Error('Hotel Directory Contract 1.0.0 bekleniyor.');
  }
  if (payload.ready !== true) throw new Error('Hotel Directory READY değil.');

  var seen = {};
  var rows = payload.hotels.map(function(hotel) {
    var id = String(hotel.hotel_id || '').trim().toUpperCase();
    if (!/^STH-\d{6}$/.test(id)) throw new Error('Hotel ID geçersiz: ' + id);
    if (seen[id]) throw new Error('Hotel ID tekrar ediyor: ' + id);
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
  var sheet = ss.getSheetByName(ST_UMRAH.HOTEL_SHEET) || ss.insertSheet(ST_UMRAH.HOTEL_SHEET);
  sheet.clearContents();
  var headers = [['Stable Hotel ID','Display Name','Official Name','Aliases','City','Verification','Workflow','Public URL','Primary Image','Hotel Modified At','Directory Generated At']];
  sheet.getRange(1,1,1,headers[0].length).setValues(headers).setFontWeight('bold');
  if (rows.length) sheet.getRange(2,1,rows.length,headers[0].length).setValues(rows);
  sheet.setFrozenRows(1);
  return rows.length + ' hotel yüklendi.';
}

function stUmrahBuildActiveBatch_() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(ST_UMRAH.HOME_SHEET);
  if (!sheet) throw new Error('Home sheet bulunamadı.');

  var headerRow = stUmrahFindHeaderRow_(sheet);
  stUmrahAssertLayout_(sheet, headerRow);

  var lastRow = sheet.getLastRow();
  if (lastRow <= headerRow) throw new Error('Program satırı bulunamadı.');

  var count = lastRow - headerRow;
  var values = sheet.getRange(headerRow + 1, 1, count, ST_UMRAH.MAX_COLUMNS).getValues();
  var displays = sheet.getRange(headerRow + 1, 1, count, ST_UMRAH.MAX_COLUMNS).getDisplayValues();
  var hotelIndex = stUmrahLoadHotelIndex_();
  var issues = {errors: [], warnings: []};
  var programs = [];

  values.forEach(function(row, index) {
    var sourceRow = headerRow + 1 + index;
    if (stUmrahTruthy_(row[ST_UMRAH.COL_REMOVE - 1])) return; // AD Programı Kaldır
    var program = stUmrahBuildProgram_(row, displays[index], sourceRow, hotelIndex, issues);
    if (program) programs.push(program);
  });

  var batch = stUmrahBatchEnvelope_(programs, 'partial');
  return {batch: batch, report: stUmrahValidateBatch_(batch, issues)};
}

function stUmrahBuildSelectedRow_(options) {
  options = options || {};
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getActiveSheet();
  if (sheet.getName() !== ST_UMRAH.HOME_SHEET) throw new Error('Home sheet üzerinde bir Umrah satırı seç.');

  var headerRow = stUmrahFindHeaderRow_(sheet);
  stUmrahAssertLayout_(sheet, headerRow);
  var rowNumber = sheet.getActiveRange().getRow();
  if (rowNumber <= headerRow) throw new Error('Bir veri satırı seç.');

  var values = sheet.getRange(rowNumber, 1, 1, ST_UMRAH.MAX_COLUMNS).getValues()[0];
  var displays = sheet.getRange(rowNumber, 1, 1, ST_UMRAH.MAX_COLUMNS).getDisplayValues()[0];

  if (!options.allowRemoved && stUmrahTruthy_(values[ST_UMRAH.COL_REMOVE - 1])) {
    throw new Error('Bu satır Programı Kaldır olarak işaretli. Sync yerine arşiv akışını kullan.');
  }

  var hotelIndex = stUmrahLoadHotelIndex_();
  var issues = {errors: [], warnings: []};
  var program = stUmrahBuildProgram_(values, displays, rowNumber, hotelIndex, issues);
  if (!program) throw new Error('Seçili satır Umrah Programına dönüştürülemedi.');

  var batch = stUmrahBatchEnvelope_([program], 'partial');
  var report = stUmrahValidateBatch_(batch, issues);
  return {program: program, batch: batch, report: report, source_row: rowNumber};
}

function stUmrahBatchEnvelope_(programs, mode) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  return {
    schema_version: ST_UMRAH.SCHEMA_VERSION,
    export_id: 'STX-UMRAH-' + Utilities.formatDate(new Date(), ST_UMRAH.TIMEZONE, 'yyyyMMdd-HHmmss'),
    generated_at: new Date().toISOString(),
    source: {
      type: 'google_sheets',
      mode: mode || 'partial',
      document_ref: ss.getId(),
      worksheet: ST_UMRAH.HOME_SHEET,
      timezone: ST_UMRAH.TIMEZONE
    },
    programs: programs
  };
}

function stUmrahBuildProgram_(row, display, rowNumber, hotelIndex, issues) {
  var code = stUmrahText_(row[ST_UMRAH.COL_PROGRAM_NO - 1]);
  if (!code) return null;

  var title = stUmrahText_(row[ST_UMRAH.COL_TITLE - 1]);
  var start = stUmrahIsoDate_(row[ST_UMRAH.COL_START_DATE - 1]);
  var transition1 = stUmrahIsoDate_(row[ST_UMRAH.COL_TRANSFER_1 - 1]);
  var transition2 = stUmrahIsoDate_(row[ST_UMRAH.COL_TRANSFER_2 - 1]);
  var end = stUmrahIsoDate_(row[ST_UMRAH.COL_END_DATE - 1]);
  var durationAndRoute = stUmrahRawText_(display[ST_UMRAH.COL_DURATION_ROUTE - 1] || row[ST_UMRAH.COL_DURATION_ROUTE - 1]);
  var duration = stUmrahParseDuration_(durationAndRoute, start, end);
  var destinations = stUmrahParseDestinations_(durationAndRoute);
  var exact = !!(start && end);
  var scope = 'row-' + rowNumber;

  if (!title) issues.errors.push(stUmrahIssue_(scope, 'title', 'Başlık boş.'));
  if (start && end && end < start) issues.errors.push(stUmrahIssue_(scope, 'schedule.end_date', 'Dönüş tarihi Gidiş tarihinden önce.'));
  if (!start || !end) issues.warnings.push(stUmrahIssue_(scope, 'schedule', 'Gidiş/Dönüş tarihi tam değil.', 'warning'));
  if (!destinations.length) issues.errors.push(stUmrahIssue_(scope, 'destinations', 'K sütunundan gece/rota okunamadı.'));

  var eventDates = [start];
  if (transition1) eventDates.push(transition1);
  if (transition2) eventDates.push(transition2);
  eventDates.push(end);
  var allEventDates = eventDates.filter(function(v){ return !!v; });

  var segments = [];
  if (start && destinations.length) {
    segments.push({sequence:1,type:'departure',origin:null,destination:destinations[0].city,depart_at:stUmrahDateTime_(start),arrive_at:null,transport_ref:null,notes:null});
  }
  [
    {date: transition1, slot: 'H'},
    {date: transition2, slot: 'I'}
  ].filter(function(x){return !!x.date;}).forEach(function(item, index) {
    segments.push({
      sequence: segments.length + 1,
      type: 'transfer',
      origin: destinations[index] ? destinations[index].city : null,
      destination: destinations[index + 1] ? destinations[index + 1].city : null,
      depart_at: stUmrahDateTime_(item.date),
      arrive_at: null,
      transport_ref: null,
      notes: 'Source date slot ' + item.slot
    });
  });
  if (end) {
    segments.push({sequence:segments.length+1,type:'return',origin:destinations.length?destinations[destinations.length-1].city:null,destination:null,depart_at:stUmrahDateTime_(end),arrive_at:null,transport_ref:null,notes:null});
  }

  var stays = [];
  destinations.forEach(function(destination, index) {
    var legacyName = stUmrahHotelNameForDestination_(destination.city, row);
    var hotelMatch = stUmrahResolveHotel_(legacyName, hotelIndex);
    if (legacyName && hotelMatch.status !== 'resolved') {
      issues.warnings.push(stUmrahIssue_(scope, 'stays.'+index+'.hotel_id', 'Hotel unresolved: ' + legacyName, 'warning'));
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
      meal_plan: stUmrahMealPlan_(destination.city, row),
      room_notes: stUmrahRoomNotes_(destination.city, row)
    });
  });

  var priceEntries = [];
  [
    ['double', ST_UMRAH.COL_PRICE_DOUBLE - 1, '2 kişilik oda'],
    ['triple', ST_UMRAH.COL_PRICE_TRIPLE - 1, '3 kişilik oda'],
    ['quad', ST_UMRAH.COL_PRICE_QUAD - 1, '4 kişilik oda']
  ].forEach(function(spec) {
    var amount = stUmrahNumber_(row[spec[1]], display[spec[1]]);
    if (amount !== null) priceEntries.push({occupancy:spec[0],amount:amount,unit:'per_person',label:spec[2]});
  });

  var childParsed = stUmrahParseChildRules_(stUmrahRawText_(display[ST_UMRAH.COL_CHILD_RULES - 1] || row[ST_UMRAH.COL_CHILD_RULES - 1]));
  var importantNotes = stUmrahLines_(stUmrahRawText_(row[ST_UMRAH.COL_IMPORTANT_NOTES - 1]));
  var hero = stUmrahUrlOrNull_(stUmrahText_(row[ST_UMRAH.COL_HERO_IMAGE - 1]));
  var phone = stUmrahPhone_(stUmrahText_(row[ST_UMRAH.COL_PHONE - 1]));
  var soldOut = stUmrahTruthy_(row[ST_UMRAH.COL_SOLD_OUT - 1]);

  return {
    program_id: null,
    program_code: code,
    service_type: 'umrah',
    publication_mode: exact ? 'scheduled_departure' : 'interest_campaign',
    title: title,
    locale: 'tr_TR',
    tier: stUmrahTier_(row[ST_UMRAH.COL_TIER - 1]),
    workflow: {
      editorial: 'needs_review',
      schedule: exact ? 'scheduled' : 'tentative',
      availability: soldOut ? 'sold_out' : 'open',
      featured: false,
      priority: 100
    },
    schedule: {
      date_precision: exact ? 'exact' : 'unknown',
      start_date: start,
      end_date: end,
      date_label: exact ? null : stUmrahText_(display[ST_UMRAH.COL_COUNTER_LABEL - 1]),
      timezone: ST_UMRAH.TIMEZONE,
      duration_days: duration.days,
      duration_nights: duration.nights
    },
    destinations: destinations,
    segments: segments,
    itinerary_days: [],
    stays: stays,
    transport: [],
    pricing: {
      currency: 'USD',
      price_type: priceEntries.length ? 'fixed' : 'on_request',
      entries: priceEntries,
      child_rules: childParsed.rules,
      surcharges: [],
      valid_from: null,
      valid_until: null,
      disclaimer: null
    },
    capacity: {
      total: stUmrahIntegerOrNull_(row[ST_UMRAH.COL_CAPACITY - 1]),
      remaining: null,
      show_exact_remaining: false
    },
    inclusions: importantNotes,
    exclusions: [],
    notes: childParsed.notes,
    contact: {
      phone: phone,
      whatsapp: phone,
      cta_mode: soldOut ? 'closed' : (exact ? 'reserve' : 'pre_register')
    },
    media: {
      hero_image_url: hero,
      gallery: [],
      media_rights_note: hero ? 'Legacy Sheet media; rights/source approval required.' : null,
      design_template: 'umrah-program'
    },
    seo: {
      seo_title: null,
      meta_description: null,
      primary_topic: title ? title.toLocaleLowerCase('tr-TR') : null,
      index_policy: 'noindex'
    },
    provenance: {
      source_type: 'google_sheets',
      source_ref: 'Home row ' + rowNumber,
      verified_at: null,
      verified_by: null,
      source_row: rowNumber,
      notes: 'Shadow export; operator verification required before approval.'
    }
  };
}

function stUmrahValidateBatch_(batch, seedIssues) {
  var errors = (seedIssues && seedIssues.errors ? seedIssues.errors : []).slice();
  var warnings = (seedIssues && seedIssues.warnings ? seedIssues.warnings : []).slice();
  var rows = [];
  if (!batch.programs.length) errors.push(stUmrahIssue_('batch','programs','Aktif program bulunamadı.'));
  batch.programs.forEach(function(program) {
    var scope = 'row-' + program.provenance.source_row;
    var unresolved = program.stays.filter(function(s){ return !s.hotel_id; }).length;
    var rowErrors = errors.filter(function(e){ return e.scope === scope; }).length;
    rows.push({
      code: program.program_code,
      title: program.title,
      gate: rowErrors ? 'BLOCKED' : 'READY',
      unresolved_hotels: unresolved
    });
  });
  return {program_count:batch.programs.length,errors:errors,warnings:warnings,programs:rows};
}

function stUmrahShowValidation_(built) {
  var report = built.report;
  var message = 'Program: ' + report.program_count +
    '\nError: ' + report.errors.length +
    '\nWarning: ' + report.warnings.length;
  if (report.errors.length) {
    message += '\n\nERRORS:\n' + report.errors.map(function(e){return e.scope+' / '+e.field+' — '+e.message;}).join('\n');
  }
  if (report.warnings.length) {
    message += '\n\nWARNINGS:\n' + report.warnings.map(function(e){return e.scope+' / '+e.field+' — '+e.message;}).join('\n');
  }
  SpreadsheetApp.getUi().alert('Umrah Validation', message.slice(0,9000), SpreadsheetApp.getUi().ButtonSet.OK);
}

function stUmrahSaveBatchToDrive_(batch, report, label) {
  var filename = 'st-umrah-' + label + '-' + Utilities.formatDate(new Date(), ST_UMRAH.TIMEZONE, 'yyyyMMdd-HHmmss') + '.json';
  var file = DriveApp.createFile(Utilities.newBlob(JSON.stringify(batch,null,2),'application/json',filename));
  SpreadsheetApp.getUi().alert('Umrah JSON', 'Hazır:\n' + file.getUrl(), SpreadsheetApp.getUi().ButtonSet.OK);
}

function stUmrahLoadHotelIndex_() {
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getSheetByName(ST_UMRAH.HOTEL_SHEET);
  var index = {byName:{},ambiguous:{}};
  if (!sheet || sheet.getLastRow() < 2) return index;
  var rows = sheet.getRange(2,1,sheet.getLastRow()-1,5).getDisplayValues();
  rows.forEach(function(row) {
    var id = stUmrahText_(row[0]).toUpperCase();
    if (!/^STH-\d{6}$/.test(id)) return;
    [row[1],row[2]].concat(stUmrahText_(row[3]).split('|')).forEach(function(name) {
      var key = stUmrahNameKey_(name);
      if (!key) return;
      if (index.byName[key] && index.byName[key] !== id) {
        index.ambiguous[key] = true;
        delete index.byName[key];
      } else if (!index.ambiguous[key]) index.byName[key] = id;
    });
  });
  return index;
}

function stUmrahResolveHotel_(name,index) {
  var key = stUmrahNameKey_(name);
  if (!key) return {status:'missing',hotel_id:null};
  if (index.ambiguous[key]) return {status:'ambiguous',hotel_id:null};
  return index.byName[key] ? {status:'resolved',hotel_id:index.byName[key]} : {status:'unresolved',hotel_id:null};
}

function stUmrahFindHeaderRow_(sheet) {
  var limit = Math.min(10, Math.max(1, sheet.getLastRow()));
  var rows = sheet.getRange(1,1,limit,ST_UMRAH.MAX_COLUMNS).getDisplayValues();
  for (var i=0;i<rows.length;i++) {
    var b = stUmrahNameKey_(rows[i][ST_UMRAH.COL_PROGRAM_NO - 1]);
    var d = stUmrahNameKey_(rows[i][ST_UMRAH.COL_TITLE - 1]);
    if (b === 'program no' || d === 'baslik') return i + 1;
  }
  throw new Error('Home header satırı bulunamadı.');
}

function stUmrahAssertLayout_(sheet, headerRow) {
  var removeHeader = stUmrahNameKey_(sheet.getRange(headerRow, ST_UMRAH.COL_REMOVE).getDisplayValue());
  if (removeHeader !== stUmrahNameKey_(ST_UMRAH.REMOVE_HEADER)) {
    throw new Error('AD sütunu "' + ST_UMRAH.REMOVE_HEADER + '" olmalı. Başka KALDIR sütunu kullanılmaz.');
  }
}

function stUmrahParseDestinations_(text) {
  var result = [];
  stUmrahLines_(text).forEach(function(line) {
    var match;
    var regex = /(\d+)\s*Gece\s+([^,]+)/gi;
    while ((match = regex.exec(line)) !== null) {
      var raw = stUmrahText_(match[2]);
      if (/^\d+\s*G[uü]n\b/i.test(raw)) continue;
      var geo = stUmrahGeo_(raw);
      result.push({sequence:result.length+1,country:geo.country,city:geo.city,nights:parseInt(match[1],10)});
    }
  });
  return result;
}

function stUmrahGeo_(raw) {
  var key = stUmrahNameKey_(raw);
  if (key.indexOf('medine') !== -1 || key.indexOf('madinah') !== -1) return {country:'Saudi Arabia',city:'Madinah'};
  if (key.indexOf('mekke') !== -1 || key.indexOf('makkah') !== -1) return {country:'Saudi Arabia',city:'Makkah'};
  if (key.indexOf('kahire') !== -1 || key.indexOf('cairo') !== -1) return {country:'Egypt',city:'Cairo'};
  return {country:'Unknown',city:stUmrahText_(raw)};
}

function stUmrahHotelNameForDestination_(city,row) {
  if (city === 'Madinah') return stUmrahText_(row[ST_UMRAH.COL_MEDINAH_HOTEL - 1]);
  if (city === 'Makkah') return stUmrahText_(row[ST_UMRAH.COL_MAKKAH_HOTEL - 1]);
  return stUmrahText_(row[ST_UMRAH.COL_OTHER_HOTEL - 1]);
}

function stUmrahMealPlan_(city,row) {
  var value = city === 'Madinah' ? row[ST_UMRAH.COL_MEDINAH_MEAL - 1] :
    (city === 'Makkah' ? row[ST_UMRAH.COL_MAKKAH_MEAL - 1] : 'B.B');
  return stUmrahText_(value) || null;
}

function stUmrahRoomNotes_(city,row) {
  var value = city === 'Madinah' ? row[ST_UMRAH.COL_MEDINAH_ROOM - 1] :
    (city === 'Makkah' ? row[ST_UMRAH.COL_MAKKAH_ROOM - 1] : '');
  return stUmrahText_(value) || null;
}

function stUmrahParseDuration_(text,start,end) {
  var match = String(text || '').match(/(\d+)\s*Gece\s*(\d+)\s*Gün/i);
  if (match) return {nights:parseInt(match[1],10),days:parseInt(match[2],10)};
  if (start && end) {
    var nights = Math.round((new Date(end+'T00:00:00Z') - new Date(start+'T00:00:00Z'))/86400000);
    return {nights:nights,days:nights+1};
  }
  return {nights:null,days:null};
}

function stUmrahParseChildRules_(text) {
  var rules = [], notes = [];
  var bedIncluded = !/YATAK\s+DAH[İI]L\s+DE[GĞ][İI]LD[İI]R/i.test(text);
  stUmrahLines_(text).forEach(function(line) {
    var match = line.match(/(\d+)\s*-\s*(\d+)\s*Yaş\s*:\s*([\d.,]+)/i);
    if (match) {
      rules.push({
        min_age:Number(match[1]),
        max_age:Number(match[2]),
        bed_included:bedIncluded,
        pricing_method:'fixed',
        amount:stUmrahNumber_(match[3],match[3]),
        discount_amount:null,
        notes:null
      });
    } else if (line) notes.push(line);
  });
  return {rules:rules,notes:notes};
}

function stUmrahTier_(value) {
  var key = stUmrahNameKey_(value);
  if (key === 'luks') return 'luxury';
  if (key === 'eko' || key === 'ekonomik') return 'economy';
  return key || null;
}

function stUmrahIsoDate_(value) {
  if (Object.prototype.toString.call(value) === '[object Date]' && !isNaN(value.getTime())) {
    return Utilities.formatDate(value, ST_UMRAH.TIMEZONE, 'yyyy-MM-dd');
  }
  var text = stUmrahText_(value);
  if (!text) return null;
  var iso = text.match(/^(\d{4})-(\d{2})-(\d{2})/);
  if (iso) return iso[1]+'-'+iso[2]+'-'+iso[3];
  var tr = text.match(/^(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})$/);
  if (tr) return tr[3]+'-'+('0'+tr[2]).slice(-2)+'-'+('0'+tr[1]).slice(-2);
  return null;
}

function stUmrahDateTime_(isoDate){ return isoDate ? isoDate + 'T00:00:00+03:00' : null; }
function stUmrahTruthy_(value){ return value === true || String(value).toLowerCase() === 'true' || String(value) === '1'; }
function stUmrahText_(value){ return value === null || value === undefined ? '' : String(value).trim().replace(/\s+/g,' '); }
function stUmrahRawText_(value){ return value === null || value === undefined ? '' : String(value).trim(); }
function stUmrahLines_(value){ return String(value||'').split(/\r?\n/).map(function(x){return x.trim();}).filter(function(x){return !!x;}); }
function stUmrahIntegerOrNull_(value){ var n=Number(value); return value===''||value===null||!isFinite(n)?null:Math.max(0,Math.round(n)); }
function stUmrahUrlOrNull_(value){ return /^https?:\/\//i.test(String(value||'')) ? String(value).trim() : null; }
function stUmrahPhone_(value){
  var digits=String(value||'').replace(/\D/g,'');
  if(!digits)return null;
  if(/^0\d{10}$/.test(digits))digits='90'+digits.slice(1);
  else if(/^\d{10}$/.test(digits))digits='90'+digits;
  return '+'+digits;
}
function stUmrahFileSafe_(value){ return String(value||'program').replace(/[^a-z0-9_-]+/gi,'-').replace(/^-+|-+$/g,'').toLowerCase(); }
function stUmrahIssue_(scope,field,message,severity){ return {severity:severity||'error',scope:scope,field:field,message:message}; }
function stUmrahNameKey_(value){
  return stUmrahText_(value).toLocaleLowerCase('tr-TR')
    .replace(/[ç]/g,'c').replace(/[ğ]/g,'g').replace(/[ıİi]/g,'i')
    .replace(/[ö]/g,'o').replace(/[ş]/g,'s').replace(/[ü]/g,'u')
    .replace(/[^a-z0-9]+/g,' ').trim().replace(/\s+/g,' ');
}
function stUmrahNumber_(value,display){
  if(typeof value==='number'&&isFinite(value))return value;
  var text=String(display||value||'').replace(/[^\d,.-]/g,'').trim();
  if(!text)return null;
  if(text.indexOf(',')!==-1&&text.indexOf('.')===-1)text=text.replace(',','.');
  else text=text.replace(/,/g,'');
  var n=Number(text);
  return isFinite(n)?n:null;
}
