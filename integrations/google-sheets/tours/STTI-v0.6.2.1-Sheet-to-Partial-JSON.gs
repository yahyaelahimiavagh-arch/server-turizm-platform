/**
 * Server Turizm Tour Intelligence (STTI)
 * v0.6.2.1 — Google Sheets -> PARTIAL JSON Generator
 * Contract: STTI-TOUR-IMPORT-1.0.0
 *
 * Visible operator sheet stays unchanged.
 * Technical columns are appended after the visible sheet and auto-hidden:
 *   Z  = STTI Stable ID
 *   AA = Expected Checksum
 *
 * New tours: leave both technical cells blank -> CREATE dry-run.
 * Existing tours: both values must exist -> UPDATE / UNCHANGED / CONFLICT safe targeting.
 *
 * IMPORTANT:
 * - This script writes ONLY the two technical helper cells in Google Sheets when you link a row.
 * - It does NOT write canonical STTI data in WordPress.
 * - It never requests publication/indexation/sitemap/schema/canonical changes.
 */

const STTI_IMPORT_CONTRACT = 'STTI-TOUR-IMPORT-1.0.0';
const STTI_GENERATOR_VERSION = '0.6.2.1';
const STTI_HEADER_ROW = 2;
const STTI_FIRST_DATA_ROW = 3;

const STTI_TECH_STABLE_ID_HEADER = 'STTI Stable ID';
const STTI_TECH_CHECKSUM_HEADER = 'Expected Checksum';
const STTI_TECH_STABLE_ID_COL = 26; // Z
const STTI_TECH_CHECKSUM_COL = 27;  // AA

function onOpen() {
  sttiEnsureTechnicalColumns_();

  SpreadsheetApp.getUi()
    .createMenu('STTI')
    .addItem('Teknik kolonları hazırla / gizle', 'sttiPrepareTechnicalColumns')
    .addItem('Seçili satırı mevcut STTI kaydına bağla', 'sttiLinkSelectedRow')
    .addItem('Seçili satır STTI bağını temizle', 'sttiClearSelectedRowLink')
    .addSeparator()
    .addItem('Seçili satırı kontrol et', 'sttiValidateSelectedRow')
    .addSeparator()
    .addItem('Partial JSON göster / kopyala', 'sttiShowSelectedRowJson')
    .addItem('Partial JSON indir', 'sttiDownloadSelectedRowJson')
    .addToUi();
}

function sttiPrepareTechnicalColumns() {
  sttiEnsureTechnicalColumns_();
  SpreadsheetApp.getActiveSpreadsheet().toast(
    'Z:AA teknik kolonları hazırlandı ve gizlendi.',
    'STTI',
    4
  );
}

function sttiEnsureTechnicalColumns_() {
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();

  const current = sheet.getRange(
    STTI_HEADER_ROW,
    STTI_TECH_STABLE_ID_COL,
    1,
    2
  ).getDisplayValues()[0];

  const z = String(current[0] || '').trim();
  const aa = String(current[1] || '').trim();

  if (z && z !== STTI_TECH_STABLE_ID_HEADER) {
    throw new Error(
      'Z sütunu dolu ve STTI teknik kolonu değil: "' + z + '". Otomatik üzerine yazılmadı.'
    );
  }
  if (aa && aa !== STTI_TECH_CHECKSUM_HEADER) {
    throw new Error(
      'AA sütunu dolu ve STTI teknik kolonu değil: "' + aa + '". Otomatik üzerine yazılmadı.'
    );
  }

  sheet.getRange(STTI_HEADER_ROW, STTI_TECH_STABLE_ID_COL, 1, 2)
    .setValues([[STTI_TECH_STABLE_ID_HEADER, STTI_TECH_CHECKSUM_HEADER]]);

  sheet.hideColumns(STTI_TECH_STABLE_ID_COL, 2);
}

function sttiLinkSelectedRow() {
  try {
    sttiEnsureTechnicalColumns_();

    const ui = SpreadsheetApp.getUi();
    const sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
    const row = sheet.getActiveRange().getRow();

    if (row < STTI_FIRST_DATA_ROW) {
      throw new Error('Bir tur satırı seç. Veri satırları 3. satırdan başlıyor.');
    }

    const title = sheet.getRange(row, 4).getDisplayValue().trim(); // D = Tur Adı
    if (!title) {
      throw new Error('Tur Adı boş.');
    }

    const idPrompt = ui.prompt(
      'STTI kaydına bağla',
      title + '\n\nStable ID gir (örnek: STT-000001):',
      ui.ButtonSet.OK_CANCEL
    );
    if (idPrompt.getSelectedButton() !== ui.Button.OK) return;

    const stableId = idPrompt.getResponseText().trim().toUpperCase();
    if (!/^STT-\d{6}$/.test(stableId)) {
      throw new Error('Stable ID formatı geçersiz. Örnek: STT-000001');
    }

    const checksumPrompt = ui.prompt(
      'Expected Checksum',
      '64 karakter SHA-256 checksum gir:',
      ui.ButtonSet.OK_CANCEL
    );
    if (checksumPrompt.getSelectedButton() !== ui.Button.OK) return;

    const checksum = checksumPrompt.getResponseText().trim().toLowerCase();
    if (!/^[a-f0-9]{64}$/.test(checksum)) {
      throw new Error('Checksum 64 karakter SHA-256 hex olmalıdır.');
    }

    sheet.getRange(row, STTI_TECH_STABLE_ID_COL, 1, 2)
      .setValues([[stableId, checksum]]);
    sheet.hideColumns(STTI_TECH_STABLE_ID_COL, 2);

    ui.alert(
      'STTI',
      title + '\n\nBağlandı ✅\n' + stableId,
      ui.ButtonSet.OK
    );
  } catch (err) {
    SpreadsheetApp.getUi().alert(
      'STTI — Hata',
      String(err && err.message ? err.message : err),
      SpreadsheetApp.getUi().ButtonSet.OK
    );
  }
}

function sttiClearSelectedRowLink() {
  try {
    sttiEnsureTechnicalColumns_();

    const ui = SpreadsheetApp.getUi();
    const sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
    const row = sheet.getActiveRange().getRow();

    if (row < STTI_FIRST_DATA_ROW) {
      throw new Error('Bir tur satırı seç.');
    }

    const title = sheet.getRange(row, 4).getDisplayValue().trim() || ('Satır ' + row);
    const response = ui.alert(
      'STTI bağını temizle',
      title + '\n\nStable ID ve Expected Checksum temizlensin mi?\nBu işlem yalnız Google Sheet teknik hücrelerini temizler.',
      ui.ButtonSet.YES_NO
    );
    if (response !== ui.Button.YES) return;

    sheet.getRange(row, STTI_TECH_STABLE_ID_COL, 1, 2).clearContent();
    sheet.hideColumns(STTI_TECH_STABLE_ID_COL, 2);

    ui.alert('STTI', 'Bağ temizlendi.', ui.ButtonSet.OK);
  } catch (err) {
    SpreadsheetApp.getUi().alert(
      'STTI — Hata',
      String(err && err.message ? err.message : err),
      SpreadsheetApp.getUi().ButtonSet.OK
    );
  }
}

function sttiValidateSelectedRow() {
  try {
    const result = sttiBuildSelectedRow_();
    const warnings = result.warnings.length
      ? '\n\nUyarılar:\n• ' + result.warnings.join('\n• ')
      : '\n\nUyarı yok.';

    const targetText = result.payload.target.stable_id
      ? '\nTarget: ' + result.payload.target.stable_id
      : '\nTarget: NEW';

    SpreadsheetApp.getUi().alert(
      'STTI Partial JSON',
      'SATIR UYGUN ✅\n\n' +
      'Tur: ' + result.title + '\n' +
      'Satır: ' + result.row + '\n' +
      'Contract: ' + STTI_IMPORT_CONTRACT +
      targetText +
      warnings,
      SpreadsheetApp.getUi().ButtonSet.OK
    );
  } catch (err) {
    SpreadsheetApp.getUi().alert(
      'STTI — Hata',
      String(err && err.message ? err.message : err),
      SpreadsheetApp.getUi().ButtonSet.OK
    );
  }
}

function sttiShowSelectedRowJson() {
  try {
    const result = sttiBuildSelectedRow_();
    const jsonText = JSON.stringify(result.payload, null, 2);
    const b64 = Utilities.base64Encode(jsonText, Utilities.Charset.UTF_8);

    const html = HtmlService.createHtmlOutput(
      sttiJsonDialogHtml_(b64, result.fileName, false)
    ).setWidth(900).setHeight(650);

    SpreadsheetApp.getUi().showModalDialog(html, 'STTI — Partial JSON');
  } catch (err) {
    SpreadsheetApp.getUi().alert(
      'STTI — Hata',
      String(err.message || err),
      SpreadsheetApp.getUi().ButtonSet.OK
    );
  }
}

function sttiDownloadSelectedRowJson() {
  try {
    const result = sttiBuildSelectedRow_();
    const jsonText = JSON.stringify(result.payload, null, 2);
    const b64 = Utilities.base64Encode(jsonText, Utilities.Charset.UTF_8);

    const html = HtmlService.createHtmlOutput(
      sttiJsonDialogHtml_(b64, result.fileName, true)
    ).setWidth(560).setHeight(260);

    SpreadsheetApp.getUi().showModalDialog(html, 'STTI — JSON İndir');
  } catch (err) {
    SpreadsheetApp.getUi().alert(
      'STTI — Hata',
      String(err.message || err),
      SpreadsheetApp.getUi().ButtonSet.OK
    );
  }
}

function sttiBuildSelectedRow_() {
  sttiEnsureTechnicalColumns_();

  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheet = ss.getActiveSheet();
  const row = sheet.getActiveRange().getRow();

  if (row < STTI_FIRST_DATA_ROW) {
    throw new Error('Bir tur satırı seç. Veri satırları 3. satırdan başlıyor.');
  }

  const lastColumn = Math.max(sheet.getLastColumn(), STTI_TECH_CHECKSUM_COL);
  const headers = sheet.getRange(STTI_HEADER_ROW, 1, 1, lastColumn).getDisplayValues()[0];
  const values = sheet.getRange(row, 1, 1, lastColumn).getValues()[0];
  const display = sheet.getRange(row, 1, 1, lastColumn).getDisplayValues()[0];

  const rowData = sttiRowObject_(headers, values, display);
  const title = sttiText_(sttiGet_(rowData, 'Tur Adı'));

  if (!title) {
    throw new Error('Tur Adı boş. JSON üretmek için Tur Adı zorunludur.');
  }

  const warnings = [];
  const sourceId = 'SRC-SHEET-' + row;

  const stableId = sttiText_(sttiGet_(rowData, STTI_TECH_STABLE_ID_HEADER)).toUpperCase();
  const expectedChecksum = sttiText_(sttiGet_(rowData, STTI_TECH_CHECKSUM_HEADER)).toLowerCase();

  if (stableId && !/^STT-\d{6}$/.test(stableId)) {
    throw new Error('STTI Stable ID formatı geçersiz: ' + stableId);
  }
  if (expectedChecksum && !/^[a-f0-9]{64}$/.test(expectedChecksum)) {
    throw new Error('Expected Checksum geçersiz; 64 karakter SHA-256 hex olmalı.');
  }
  if ((stableId && !expectedChecksum) || (!stableId && expectedChecksum)) {
    throw new Error(
      'Mevcut kayıt hedeflemek için STTI Stable ID ve Expected Checksum birlikte olmalıdır.'
    );
  }

  const payload = {
    import_contract: STTI_IMPORT_CONTRACT,
    mode: 'partial',
    policy: {
      facts: 'source_only_no_invention',
      missing_values: 'omit_or_null_or_explicit_unknown',
      publication: 'force_private'
    },
    producer: {
      type: 'google_sheet',
      name: ss.getName(),
      version: STTI_GENERATOR_VERSION,
      generated_at: new Date().toISOString()
    },
    target: {
      stable_id: stableId || null,
      expected_checksum_sha256: expectedChecksum || null
    },
    sources: [
      {
        source_id: sourceId,
        type: 'google_sheet',
        // Visible operator facts only. Hidden technical columns Z:AA are control metadata.
        ref: ss.getUrl() + '#gid=' + sheet.getSheetId() + '&range=A' + row + ':Y' + row,
        label: title + ' — Google Sheets satırı ' + row,
        language: 'tr-TR',
        completeness: 'source_partial',
        notes: sttiSourceNotes_(rowData)
      }
    ],
    tour: {
      identity: {
        public_title: title,
        language: 'tr-TR'
      },
      lifecycle: {
        editorial: 'needs_review',
        schedule: 'tentative',
        availability: 'open'
      },
      provenance: {
        primary_source_id: sourceId,
        source_completeness: 'source_partial',
        notes: 'Google Sheets satırından otomatik PARTIAL JSON. Eksik bilgiler AI/human review aşamasında tamamlanmalıdır.'
      }
    }
  };

  // Identity
  const programNo = sttiText_(sttiGet_(rowData, 'Program No'));
  if (programNo) payload.tour.identity.tour_code = programNo;

  // Destinations
  const destination = sttiText_(sttiGet_(rowData, 'Ülke / Hedef'));
  const cities = sttiSplitList_(sttiGet_(rowData, 'Şehirler'));
  const extraCountries = sttiSplitList_(sttiGet_(rowData, 'Ek Ülkeler'));

  if (destination || cities.length || extraCountries.length) {
    payload.tour.destinations = {};
    if (destination) {
      payload.tour.destinations.primary_country = destination;
      payload.tour.destinations.countries = sttiUnique_([destination].concat(extraCountries));
    } else if (extraCountries.length) {
      payload.tour.destinations.countries = extraCountries;
    }
    if (cities.length) {
      payload.tour.destinations.primary_city = cities[0];
      payload.tour.destinations.cities = cities;
    }
  }

  // Dates
  const startDate = sttiDate_(sttiGetRaw_(rowData, 'Gidiş'));
  const endDate = sttiDate_(sttiGetRaw_(rowData, 'Dönüş'));
  if (startDate && endDate) {
    if (endDate.getTime() < startDate.getTime()) {
      throw new Error('Dönüş tarihi Gidiş tarihinden önce olamaz.');
    }

    const durationNights = Math.round(
      (sttiUtcMidnight_(endDate) - sttiUtcMidnight_(startDate)) / 86400000
    );

    payload.tour.date = {
      mode: 'exact',
      precision: 'exact',
      start_date: sttiIsoDate_(startDate, ss.getSpreadsheetTimeZone()),
      end_date: sttiIsoDate_(endDate, ss.getSpreadsheetTimeZone()),
      duration_days: durationNights + 1,
      duration_nights: durationNights
    };
    payload.tour.lifecycle.schedule = 'scheduled';
  } else if (startDate || endDate) {
    warnings.push('Gidiş/Dönüş tarihlerinden biri eksik; date bölümü JSON’a eklenmedi.');
  }

  // Route: label is always preserved.
  // city is added ONLY when the route token is explicitly present in the Şehirler column.
  const routeSummary = sttiText_(sttiGet_(rowData, 'Rota'));
  const routeTokens = sttiSplitRoute_(routeSummary || sttiText_(sttiGet_(rowData, 'Şehirler')));
  if (routeSummary || routeTokens.length) {
    payload.tour.route = {};
    if (routeSummary) payload.tour.route.summary = routeSummary;

    if (routeTokens.length) {
      const cityLookup = {};
      cities.forEach(function(city) {
        cityLookup[sttiNormalizeKey_(city)] = city;
      });

      payload.tour.route.stops = routeTokens.map(function(label, idx) {
        const stop = {
          stop_id: 'R' + (idx + 1),
          type: 'stop',
          label: label,
          source_ids: [sourceId]
        };

        const matchedCity = cityLookup[sttiNormalizeKey_(label)];
        if (matchedCity) stop.city = matchedCity;

        return stop;
      });
    }
  }

  // Hotels
  const hotelNames = sttiSplitHotel_(sttiGet_(rowData, 'Otel(ler)'));
  const hotelPlan = sttiText_(sttiGet_(rowData, 'Otel / Gece Planı'));
  if (hotelNames.length) {
    payload.tour.stays = {
      hotels: hotelNames.map(function(name, idx) {
        const h = {
          relation_id: 'H' + (idx + 1),
          mode: 'unresolved',
          unresolved_name: name,
          source_ids: [sourceId]
        };
        if (hotelPlan) h.note = hotelPlan;
        return h;
      })
    };
  } else if (hotelPlan) {
    warnings.push('Otel / Gece Planı var fakat Otel(ler) boş; hotel relation üretilmedi.');
  }

  // Transport
  const transportRaw = sttiText_(sttiGet_(rowData, 'Ulaşım'));
  const airline = sttiText_(sttiGet_(rowData, 'Havayolu'));
  const transferNote = sttiText_(sttiGet_(rowData, 'Uçuş / Transfer Notu'));

  if (transportRaw || airline || transferNote) {
    const transportType = sttiTransportType_(transportRaw);
    const seg = {
      segment_id: 'T1',
      type: transportType,
      source_ids: [sourceId]
    };
    if (airline) seg.provider = airline;

    const noteParts = [];
    if (transportRaw && transportType === 'other') noteParts.push('Ulaşım: ' + transportRaw);
    if (transferNote) noteParts.push(transferNote);
    if (noteParts.length) seg.note = noteParts.join(' | ');

    payload.tour.transport = {segments: [seg]};
  }

  // Pricing
  const currency = sttiText_(sttiGet_(rowData, 'Para Birimi')).toUpperCase();
  const priceDefs = [
    ['2 Kişilik', '2 kişilik'],
    ['3 Kişilik', '3 kişilik'],
    ['4 Kişilik', '4 kişilik'],
    ['Çocuk', 'çocuk']
  ];
  const priceItems = [];

  priceDefs.forEach(function(def) {
    const raw = sttiGetRaw_(rowData, def[0]);
    const parsed = sttiParsePrice_(raw);
    if (!parsed.hasValue) return;

    if (!currency) {
      throw new Error(def[0] + ' fiyatı var fakat Para Birimi boş.');
    }
    if (['EUR', 'USD', 'TRY', 'GBP'].indexOf(currency) === -1) {
      throw new Error(
        'Para Birimi contract tarafından desteklenmiyor: ' +
        currency +
        '. Desteklenen: EUR, USD, TRY, GBP.'
      );
    }

    const item = {
      price_id: 'P' + (priceItems.length + 1),
      label: def[0] + ' fiyatı',
      type: parsed.type,
      amount: parsed.amount,
      currency: currency,
      basis: 'unknown',
      occupancy: def[1],
      source_ids: [sourceId]
    };
    if (parsed.note) item.note = parsed.note;
    priceItems.push(item);
  });

  if (priceItems.length) {
    const primary = priceItems[0];
    payload.tour.pricing = {
      type: primary.type,
      amount: primary.amount,
      currency: primary.currency,
      basis: 'unknown',
      items: priceItems
    };
    warnings.push(
      'Fiyat basis (kişi/oda/paket) Sheet’te ayrı alan olmadığı için UNKNOWN bırakıldı.'
    );
  }

  // Availability
  const soldOutRaw = sttiGetRaw_(rowData, 'Dolu');
  if (soldOutRaw === true || sttiTruthyText_(soldOutRaw)) {
    payload.tour.lifecycle.availability = 'sold_out';
  }

  // Visa
  const visaRaw = sttiGetRaw_(rowData, 'Vize');
  const visa = sttiVisaStatus_(visaRaw);
  payload.tour.requirements = {
    visa_status: visa.status,
    items: []
  };
  if (visa.note) payload.tour.requirements.visa_notes = visa.note;

  // Clean empty fields while preserving contract-required structure.
  sttiPrune_(payload.tour);

  payload.tour.identity.public_title = title;
  payload.tour.identity.language = 'tr-TR';
  payload.tour.lifecycle = payload.tour.lifecycle || {
    editorial: 'needs_review',
    schedule: 'tentative',
    availability: 'open'
  };
  payload.tour.provenance = payload.tour.provenance || {
    primary_source_id: sourceId,
    source_completeness: 'source_partial'
  };

  const fileBase = programNo || title;
  const fileName = sttiSafeFileName_(fileBase) + '.partial.json';

  return {
    payload: payload,
    warnings: warnings,
    row: row,
    title: title,
    fileName: fileName
  };
}

function sttiRowObject_(headers, values, displayValues) {
  const obj = {};
  headers.forEach(function(header, idx) {
    const key = sttiText_(header);
    if (!key) return;
    obj[key] = {
      raw: values[idx],
      display: displayValues[idx]
    };
  });
  return obj;
}

function sttiGet_(rowData, header) {
  return rowData[header] ? rowData[header].display : '';
}

function sttiGetRaw_(rowData, header) {
  return rowData[header] ? rowData[header].raw : '';
}

function sttiText_(value) {
  if (value === null || value === undefined) return '';
  return String(value).trim();
}

function sttiNormalizeKey_(value) {
  return sttiText_(value).toLocaleLowerCase('tr-TR');
}

function sttiSplitList_(value) {
  const s = sttiText_(value);
  if (!s) return [];
  return sttiUnique_(
    s.split(/\r?\n|\||;|,|·|→|->/)
      .map(function(x) { return x.trim(); })
      .filter(Boolean)
  );
}

function sttiSplitRoute_(value) {
  const s = sttiText_(value);
  if (!s) return [];
  return sttiUnique_(
    s.split(/\r?\n|→|->|·|\||;|,/)
      .map(function(x) { return x.trim(); })
      .filter(Boolean)
  );
}

function sttiSplitHotel_(value) {
  const s = sttiText_(value);
  if (!s) return [];
  return sttiUnique_(
    s.split(/\r?\n|\||;|,/)
      .map(function(x) { return x.trim(); })
      .filter(Boolean)
  );
}

function sttiUnique_(arr) {
  const seen = {};
  return arr.filter(function(x) {
    const k = String(x).toLocaleLowerCase('tr-TR');
    if (seen[k]) return false;
    seen[k] = true;
    return true;
  });
}

function sttiDate_(value) {
  if (
    Object.prototype.toString.call(value) === '[object Date]' &&
    !isNaN(value.getTime())
  ) return value;
  return null;
}

function sttiUtcMidnight_(date) {
  return Date.UTC(date.getFullYear(), date.getMonth(), date.getDate());
}

function sttiIsoDate_(date, tz) {
  return Utilities.formatDate(
    date,
    tz || Session.getScriptTimeZone(),
    'yyyy-MM-dd'
  );
}

function sttiTransportType_(raw) {
  const s = sttiText_(raw).toLocaleLowerCase('tr-TR');
  if (!s) return 'other';
  if (/uçak|flight/.test(s)) return 'flight';
  if (/tren|train/.test(s)) return 'train';
  if (/otobüs|otobus|coach|bus/.test(s)) return 'coach';
  if (/minibüs|minibus/.test(s)) return 'minibus';
  if (/özel araç|ozel arac|private/.test(s)) return 'private_vehicle';
  if (/feribot|ferry/.test(s)) return 'ferry';
  if (/cruise|kruvaziyer|gemi/.test(s)) return 'cruise';
  if (/yürü|yuru|walking/.test(s)) return 'walking';
  return 'other';
}

function sttiParsePrice_(raw) {
  if (raw === null || raw === undefined || raw === '') {
    return {hasValue: false};
  }
  if (typeof raw === 'number' && isFinite(raw)) {
    return {hasValue: true, type: 'exact', amount: raw};
  }

  const s = sttiText_(raw);
  if (!s) return {hasValue: false};

  const lower = s.toLocaleLowerCase('tr-TR');
  if (/talep|request/.test(lower)) {
    return {
      hasValue: true,
      type: 'on_request',
      amount: null,
      note: s
    };
  }

  const normalized = s
    .replace(/\s/g, '')
    .replace(/\.(?=\d{3}(?:\D|$))/g, '')
    .replace(',', '.');

  const m = normalized.match(/\d+(?:\.\d+)?/);
  if (!m) {
    throw new Error('Fiyat değeri okunamadı: ' + s);
  }

  const amount = Number(m[0]);
  const type = /(dan|den)\s*başlayan|başlangıç|from/i.test(s)
    ? 'from'
    : 'exact';

  return {
    hasValue: true,
    type: type,
    amount: amount,
    note: type === 'from' ? s : null
  };
}

function sttiVisaStatus_(raw) {
  if (raw === true) {
    return {status: 'required', note: 'Sheet Vize alanı işaretli.'};
  }

  if (
    raw === false ||
    raw === null ||
    raw === undefined ||
    raw === ''
  ) {
    return {status: 'unknown'};
  }

  const s = sttiText_(raw).toLocaleLowerCase('tr-TR');

  if (/gerekli değil|gerekli degil|not required/.test(s)) {
    return {status: 'not_required'};
  }
  if (/gerekli|required/.test(s)) {
    return {status: 'required'};
  }
  if (/şart|sart|conditional/.test(s)) {
    return {status: 'conditional', note: sttiText_(raw)};
  }

  return {status: 'unknown', note: sttiText_(raw)};
}

function sttiTruthyText_(raw) {
  const s = sttiText_(raw).toLocaleLowerCase('tr-TR');
  return ['evet', 'yes', 'dolu', 'sold out', 'sold_out'].indexOf(s) !== -1;
}

function sttiSourceNotes_(rowData) {
  const notes = [];
  const type = sttiText_(sttiGet_(rowData, 'Tür'));
  const capacity = sttiText_(sttiGet_(rowData, 'Kontenjan Sayısı'));

  if (type) notes.push('Tür: ' + type);
  if (capacity) notes.push('Kontenjan Sayısı: ' + capacity);

  notes.push('Kaynak: seçili Google Sheets satırı. Bu çıktı PARTIAL JSON’dur.');
  return notes.join(' | ');
}

function sttiPrune_(obj) {
  if (!obj || typeof obj !== 'object') return obj;

  Object.keys(obj).forEach(function(key) {
    const value = obj[key];

    if (value && typeof value === 'object' && !Array.isArray(value)) {
      sttiPrune_(value);
      if (Object.keys(value).length === 0) delete obj[key];
    } else if (Array.isArray(value)) {
      value.forEach(function(v) {
        if (v && typeof v === 'object') sttiPrune_(v);
      });
      if (value.length === 0 && key !== 'items') delete obj[key];
    } else if (value === '' || value === undefined) {
      delete obj[key];
    }
  });

  return obj;
}

function sttiSafeFileName_(text) {
  const s = sttiText_(text) || 'tour';

  return s
    .toLocaleLowerCase('tr-TR')
    .replace(/ı/g, 'i')
    .replace(/ğ/g, 'g')
    .replace(/ü/g, 'u')
    .replace(/ş/g, 's')
    .replace(/ö/g, 'o')
    .replace(/ç/g, 'c')
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '') || 'tour';
}

function sttiJsonDialogHtml_(base64Json, fileName, autoDownload) {
  const auto = autoDownload ? 'true' : 'false';

  return '<!doctype html><html><head><base target="_top">' +
    '<style>' +
      'body{font-family:Arial,sans-serif;margin:18px;color:#172033}' +
      '.bar{display:flex;gap:8px;margin-bottom:12px;align-items:center}' +
      'button{border:0;border-radius:8px;padding:10px 14px;cursor:pointer;font-weight:700}' +
      '.primary{background:#0b1b2e;color:white}' +
      '.gold{background:#d4af37;color:#111}' +
      'textarea{width:100%;height:500px;box-sizing:border-box;font-family:monospace;font-size:12px;white-space:pre;}' +
      '.msg{font-size:12px;color:#666;margin-left:auto}' +
    '</style></head><body>' +
    '<div class="bar">' +
      '<button class="primary" onclick="copyJson()">JSON Kopyala</button>' +
      '<button class="gold" onclick="downloadJson()">.json İndir</button>' +
      '<span class="msg" id="msg"></span>' +
    '</div>' +
    '<textarea id="json" readonly></textarea>' +
    '<script>' +
      'const b64=' + JSON.stringify(base64Json) + ';' +
      'const fileName=' + JSON.stringify(fileName) + ';' +
      'const bytes=Uint8Array.from(atob(b64),c=>c.charCodeAt(0));' +
      'const json=new TextDecoder("utf-8").decode(bytes);' +
      'document.getElementById("json").value=json;' +
      'async function copyJson(){try{' +
        'await navigator.clipboard.writeText(json);' +
        'document.getElementById("msg").textContent="Kopyalandı ✅";' +
      '}catch(e){' +
        'const t=document.getElementById("json");' +
        't.select();document.execCommand("copy");' +
        'document.getElementById("msg").textContent="Kopyalandı ✅";' +
      '}}' +
      'function downloadJson(){' +
        'const blob=new Blob([json],{type:"application/json;charset=utf-8"});' +
        'const url=URL.createObjectURL(blob);' +
        'const a=document.createElement("a");' +
        'a.href=url;a.download=fileName;' +
        'document.body.appendChild(a);a.click();a.remove();' +
        'setTimeout(()=>URL.revokeObjectURL(url),1000);' +
        'document.getElementById("msg").textContent="İndirildi ✅";' +
      '}' +
      'if(' + auto + '){setTimeout(downloadJson,250);}' +
    '</script></body></html>';
}
