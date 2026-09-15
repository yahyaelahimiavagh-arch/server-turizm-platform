/** Server Turizm Direct Sync v0.1.4 — controlled Umrah archive operator flow. */
var ST_DIRECT_SYNC_ARCHIVE = Object.freeze({
  VERSION: '0.1.4',
  HEADER_NAMES: ['KALDIR', 'PROGRAMI KALDIR', 'PROGRAM KALDIR']
});

function stDirectSyncArchiveMarkedUmrah() {
  if (typeof stDirectSyncSend_ !== 'function') throw new Error('Shared Direct Sync client bulunamadı.');
  if (typeof stDirectSyncStateIndex_ !== 'function') throw new Error('Direct Sync state helper bulunamadı.');
  if (typeof stTdeFindHeaderRow_ !== 'function' || typeof ST_TDE === 'undefined') throw new Error('ST-TDE exporter bulunamadı.');

  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(ST_TDE.HOME_SHEET);
  if (!sheet) throw new Error('Umrah Home sheet bulunamadı: ' + ST_TDE.HOME_SHEET);

  var headerRow = stTdeFindHeaderRow_(sheet);
  var removalCol = stDirectSyncFindUmrahRemovalColumn_(sheet, headerRow);
  var count = Math.max(0, sheet.getLastRow() - headerRow);
  if (!count) throw new Error('Arşivlenecek Program satırı bulunamadı.');

  var flags = sheet.getRange(headerRow + 1, removalCol, count, 1).getValues();
  var state = stDirectSyncStateIndex_();
  var source = {
    type: 'google_sheets',
    mode: 'partial',
    document_ref: ss.getId(),
    worksheet: ST_TDE.HOME_SHEET,
    timezone: ST_TDE.TIMEZONE || 'Europe/Istanbul'
  };
  var removals = [];
  var missingState = [];

  flags.forEach(function(row, index) {
    if (!stDirectSyncTruthy_(row[0])) return;
    var sourceRow = headerRow + 1 + index;
    var programCode = String(sheet.getRange(sourceRow, 2).getDisplayValue() || '').trim();
    var key = stDirectSyncStateKey_('umrah', source.document_ref, source.worksheet, sourceRow);
    var saved = state[key] || null;
    if (!saved || !saved.stable_id || !saved.checksum) {
      missingState.push('row ' + sourceRow + (programCode ? ' / ' + programCode : ''));
      return;
    }
    removals.push({
      source_row: sourceRow,
      program_code: programCode || null,
      stable_id: saved.stable_id,
      expected_checksum_sha256: saved.checksum
    });
  });

  if (missingState.length) {
    throw new Error('KALDIR işaretli bazı satırlar Direct Sync state ile bağlı değil: ' + missingState.join(', ') + '. Önce satırı normal Direct Sync ile bağla.');
  }
  if (!removals.length) {
    SpreadsheetApp.getUi().alert('Umrah Archive', 'KALDIR işaretli Program bulunamadı.', SpreadsheetApp.getUi().ButtonSet.OK);
    return;
  }

  var batch = {
    schema_version: ST_TDE.SCHEMA_VERSION,
    export_id: 'STX-ARCHIVE-' + Utilities.formatDate(new Date(), source.timezone, 'yyyyMMdd-HHmmss'),
    generated_at: new Date().toISOString(),
    source: source,
    programs: []
  };

  var preflight = stDirectSyncSend_('umrah', 'validate', {batch: batch, controls: [], removals: removals});
  var results = preflight.results || [];
  var unexpected = results.some(function(result) {
    var op = String(result && result.operation || '');
    return ['ARCHIVE', 'UNCHANGED'].indexOf(op) === -1;
  });
  if (!preflight.ok || unexpected) {
    stDirectSyncShowResult_('Umrah Archive — Ön Kontrol / HİÇBİR WRITE YAPILMADI', preflight);
    return;
  }

  var pending = results.filter(function(result) { return result && result.operation === 'ARCHIVE'; });
  if (!pending.length) {
    stDirectSyncShowResult_('Umrah Archive — Değişiklik yok', preflight);
    return;
  }

  var lines = pending.map(function(result) {
    var match = removals.filter(function(item) { return item.stable_id === result.stable_id; })[0] || {};
    return result.stable_id + (match.program_code ? ' — ' + match.program_code : '');
  });
  var ui = SpreadsheetApp.getUi();
  var confirm = ui.alert(
    'Umrah Controlled Archive',
    'Aşağıdaki Programlar SİLİNMEYECEK; canonical kayıt ve Stable ID korunarak ARCHIVE durumuna alınacak ve varsa public/noindex final route PREPARED durumuna kapatılacak:\n\n' +
      lines.join('\n') +
      '\n\nDevam edilsin mi?',
    ui.ButtonSet.YES_NO
  );
  if (confirm !== ui.Button.YES) return;

  var approvedRemovals = removals.map(function(item) {
    var copy = JSON.parse(JSON.stringify(item));
    copy.controlled_archive_approved = true;
    return copy;
  });
  var response = stDirectSyncSend_('umrah', 'apply', {batch: batch, controls: [], removals: approvedRemovals});
  stDirectSyncApplyUmrahState_(source, response.results || []);
  stDirectSyncShowResult_('Umrah Controlled Archive', response);
}

function stDirectSyncFindUmrahRemovalColumn_(sheet, headerRow) {
  var width = Math.max(sheet.getLastColumn(), 1);
  var headers = sheet.getRange(headerRow, 1, 1, width).getDisplayValues()[0];
  var accepted = {};
  ST_DIRECT_SYNC_ARCHIVE.HEADER_NAMES.forEach(function(name) { accepted[stDirectSyncArchiveHeaderKey_(name)] = true; });
  for (var i = 0; i < headers.length; i++) {
    if (accepted[stDirectSyncArchiveHeaderKey_(headers[i])]) return i + 1;
  }
  throw new Error('KALDIR sütunu bulunamadı. Header adı KALDIR veya Programı Kaldır olmalı. Sabit kolon numarası kullanılmadı.');
}

function stDirectSyncArchiveHeaderKey_(value) {
  return String(value || '').trim().toLocaleUpperCase('tr-TR').replace(/\s+/g, ' ');
}

function stDirectSyncTruthy_(value) {
  if (value === true || value === 1) return true;
  var text = String(value === null || value === undefined ? '' : value).trim().toLocaleLowerCase('tr-TR');
  return ['true', '1', 'evet', 'yes', 'x', 'kaldır', 'kaldir'].indexOf(text) !== -1;
}
