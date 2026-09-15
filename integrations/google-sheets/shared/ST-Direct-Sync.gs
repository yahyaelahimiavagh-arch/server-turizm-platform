/** Server Turizm Shared Direct Sync v0.1.3 — Umrah + Tours. */
var ST_DIRECT_SYNC = Object.freeze({
  VERSION: '0.1.3',
  CONTRACT: 'ST-DIRECT-SYNC-1.0.0',
  STATE_SHEET: 'ST Direct Sync State',
  ENDPOINT_PROPERTY: 'ST_DIRECT_SYNC_ENDPOINT',
  KEY_ID_PROPERTY: 'ST_DIRECT_SYNC_KEY_ID',
  SECRET_PROPERTY: 'ST_DIRECT_SYNC_SECRET'
});

function stDirectSyncConfigure() {
  var ui = SpreadsheetApp.getUi();
  var props = PropertiesService.getScriptProperties();
  var endpoint = ui.prompt('Direct Sync', 'WordPress endpoint (https://.../wp-json/server-turizm/v1/direct-sync):', ui.ButtonSet.OK_CANCEL);
  if (endpoint.getSelectedButton() !== ui.Button.OK) return;
  var key = ui.prompt('Direct Sync', 'Key ID:', ui.ButtonSet.OK_CANCEL);
  if (key.getSelectedButton() !== ui.Button.OK) return;
  var secret = ui.prompt('Direct Sync', 'HMAC secret (Script Properties içinde saklanır):', ui.ButtonSet.OK_CANCEL);
  if (secret.getSelectedButton() !== ui.Button.OK) return;
  props.setProperties({
    ST_DIRECT_SYNC_ENDPOINT: String(endpoint.getResponseText() || '').trim(),
    ST_DIRECT_SYNC_KEY_ID: String(key.getResponseText() || '').trim(),
    ST_DIRECT_SYNC_SECRET: String(secret.getResponseText() || '')
  }, false);
  stDirectSyncEnsureStateSheet_();
  ui.alert('Direct Sync', 'Ayarlar Script Properties içinde kaydedildi. Secret hücrelere yazılmadı.', ui.ButtonSet.OK);
}

function stDirectSyncUmrahActivePrograms() {
  if (typeof stTdeBuildActiveBatch_ !== 'function') throw new Error('ST-TDE exporter bulunamadı.');
  var built = stTdeBuildActiveBatch_();
  if (built.report && built.report.errors && built.report.errors.length) {
    throw new Error('ST-TDE validation hatası: ' + built.report.errors.map(function(x){ return x.message || x; }).join(' | '));
  }
  var batch = JSON.parse(JSON.stringify(built.batch));
  var state = stDirectSyncStateIndex_();
  var controls = [];
  batch.programs.forEach(function(program) {
    var row = Number(program.provenance && program.provenance.source_row || 0);
    var key = stDirectSyncStateKey_('umrah', batch.source.document_ref, batch.source.worksheet, row);
    var saved = state[key] || null;
    if (saved && saved.stable_id) program.program_id = saved.stable_id;
    controls.push({source_row: row, stable_id: saved ? saved.stable_id : null, expected_checksum_sha256: saved ? saved.checksum : null});
  });
  var removals = stDirectSyncUmrahRemovals_(batch.source, state);
  var response = stDirectSyncSend_('umrah', 'apply', {batch: batch, controls: controls, removals: removals});
  stDirectSyncApplyUmrahState_(batch.source, response.results || []);
  stDirectSyncShowResult_('Umrah Direct Sync', response);
}

function stDirectSyncSelectedTour() {
  if (typeof sttiBuildSelectedRow_ !== 'function') throw new Error('STTI Tour Sheet generator bulunamadı.');
  var built = sttiBuildSelectedRow_();
  var response = stDirectSyncSend_('tour', 'apply', {documents: [built.payload], archives: []});
  var result = response.results && response.results[0];
  if (result && !result.errors.length && result.stable_id && result.checksum) {
    var sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
    sheet.getRange(built.row, STTI_TECH_STABLE_ID_COL, 1, 2).setValues([[result.stable_id, result.checksum]]);
    sheet.hideColumns(STTI_TECH_STABLE_ID_COL, 2);
  }
  stDirectSyncShowResult_('Tour Direct Sync', response);
}

function stDirectSyncArchiveSelectedTour() {
  if (typeof sttiEnsureTechnicalColumns_ !== 'function') throw new Error('STTI Tour Sheet generator bulunamadı.');
  sttiEnsureTechnicalColumns_();
  var sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  var row = sheet.getActiveRange().getRow();
  if (row < STTI_FIRST_DATA_ROW) throw new Error('Bir tur satırı seç.');
  var values = sheet.getRange(row, STTI_TECH_STABLE_ID_COL, 1, 2).getDisplayValues()[0];
  var stableId = String(values[0] || '').trim().toUpperCase();
  var checksum = String(values[1] || '').trim().toLowerCase();
  if (!/^STT-\d{6}$/.test(stableId) || !/^[a-f0-9]{64}$/.test(checksum)) throw new Error('Satır Direct Sync state ile bağlı değil.');
  var ui = SpreadsheetApp.getUi();
  if (ui.alert('Tour Archive', stableId + ' silinmeden arşivlensin mi?', ui.ButtonSet.YES_NO) !== ui.Button.YES) return;
  var response = stDirectSyncSend_('tour', 'apply', {documents: [], archives: [{stable_id: stableId, expected_checksum_sha256: checksum}]});
  var result = response.results && response.results[0];
  if (result && result.checksum) sheet.getRange(row, STTI_TECH_CHECKSUM_COL).setValue(result.checksum);
  stDirectSyncShowResult_('Tour Archive', response);
}

function stDirectSyncSend_(adapter, mode, payload) {
  var props = PropertiesService.getScriptProperties();
  var endpoint = String(props.getProperty(ST_DIRECT_SYNC.ENDPOINT_PROPERTY) || '').trim();
  var keyId = String(props.getProperty(ST_DIRECT_SYNC.KEY_ID_PROPERTY) || '').trim();
  var secret = String(props.getProperty(ST_DIRECT_SYNC.SECRET_PROPERTY) || '');
  if (!/^https:\/\//i.test(endpoint) || !keyId || !secret) throw new Error('Direct Sync ayarları eksik. Önce Ayarlar çalıştır.');
  var requestId = 'STS-' + Utilities.formatDate(new Date(), 'UTC', 'yyyyMMddHHmmss') + '-' + Utilities.getUuid().replace(/-/g, '').slice(0, 16);
  var envelope = {contract: ST_DIRECT_SYNC.CONTRACT, request_id: requestId, adapter: adapter, mode: mode, payload: payload};
  var body = JSON.stringify(envelope);
  var timestamp = String(Math.floor(Date.now() / 1000));
  var nonce = Utilities.getUuid().replace(/-/g, '') + Utilities.getUuid().replace(/-/g, '').slice(0, 12);
  var bodyHash = stDirectSyncSha256Hex_(body);
  var canonical = 'ST-DIRECT-SYNC-1\n' + timestamp + '\n' + nonce + '\n' + bodyHash;
  var signature = stDirectSyncBytesHex_(Utilities.computeHmacSha256Signature(canonical, secret, Utilities.Charset.UTF_8));
  var res = UrlFetchApp.fetch(endpoint, {method: 'post', contentType: 'application/json', payload: body, muteHttpExceptions: true, headers: {'X-ST-Sync-Timestamp': timestamp, 'X-ST-Sync-Nonce': nonce, 'X-ST-Sync-Key-Id': keyId, 'X-ST-Sync-Signature': signature}});
  var code = res.getResponseCode();
  var parsed;
  try { parsed = JSON.parse(res.getContentText()); } catch (e) { throw new Error('WordPress JSON cevabı okunamadı. HTTP ' + code); }
  if (code < 200 || code >= 300) throw new Error('Direct Sync HTTP ' + code + ': ' + (parsed.message || parsed.code || res.getContentText()));
  return parsed;
}

function stDirectSyncUmrahRemovals_(source, state) {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(ST_TDE.HOME_SHEET);
  if (!sheet) return [];
  var headerRow = stTdeFindHeaderRow_(sheet);
  var count = Math.max(0, sheet.getLastRow() - headerRow);
  if (!count) return [];
  var rows = sheet.getRange(headerRow + 1, 1, count, ST_TDE.MAX_COLUMNS).getValues();
  var removals = [];
  rows.forEach(function(values, index) {
    var rowNumber = headerRow + 1 + index;
    if (!stTdeTruthy_(values[29])) return;
    var key = stDirectSyncStateKey_('umrah', source.document_ref, source.worksheet, rowNumber);
    var saved = state[key];
    if (saved && saved.stable_id && saved.checksum) removals.push({source_row: rowNumber, stable_id: saved.stable_id, expected_checksum_sha256: saved.checksum});
  });
  return removals;
}

function stDirectSyncEnsureStateSheet_() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(ST_DIRECT_SYNC.STATE_SHEET) || ss.insertSheet(ST_DIRECT_SYNC.STATE_SHEET);
  var headers = ['Adapter','Document Ref','Worksheet','Source Row','Stable ID','Expected Checksum','Updated At'];
  sheet.getRange(1, 1, 1, headers.length).setValues([headers]);
  sheet.hideSheet();
  return sheet;
}

function stDirectSyncStateIndex_() {
  var sheet = stDirectSyncEnsureStateSheet_();
  var values = sheet.getLastRow() > 1 ? sheet.getRange(2,1,sheet.getLastRow()-1,7).getDisplayValues() : [];
  var out = {};
  values.forEach(function(r, i) {
    var key = stDirectSyncStateKey_(r[0], r[1], r[2], Number(r[3] || 0));
    out[key] = {row: i + 2, stable_id: String(r[4] || '').trim(), checksum: String(r[5] || '').trim().toLowerCase()};
  });
  return out;
}

function stDirectSyncApplyUmrahState_(source, results) {
  var sheet = stDirectSyncEnsureStateSheet_();
  var state = stDirectSyncStateIndex_();
  results.forEach(function(result) {
    if (!result || !result.source_row || !result.stable_id || !result.checksum || (result.errors && result.errors.length)) return;
    var key = stDirectSyncStateKey_('umrah', source.document_ref, source.worksheet, result.source_row);
    var row = state[key] ? state[key].row : sheet.getLastRow() + 1;
    sheet.getRange(row,1,1,7).setValues([['umrah',source.document_ref,source.worksheet,result.source_row,result.stable_id,result.checksum,new Date().toISOString()]]);
  });
  sheet.hideSheet();
}

function stDirectSyncStateKey_(adapter, documentRef, worksheet, sourceRow) { return [String(adapter||''),String(documentRef||''),String(worksheet||''),String(sourceRow||0)].join('|'); }
function stDirectSyncSha256Hex_(text) { return stDirectSyncBytesHex_(Utilities.computeDigest(Utilities.DigestAlgorithm.SHA_256, text, Utilities.Charset.UTF_8)); }
function stDirectSyncBytesHex_(bytes) { return bytes.map(function(b){ var v=(b+256)%256; return ('0'+v.toString(16)).slice(-2); }).join(''); }
function stDirectSyncShowResult_(title, response) {
  var lines = (response.results || []).map(function(r){
    var impact = r.public_impact && r.public_impact.protected ? ' — CANLI PROGRAM KORUMASI: Review/Approve + route refresh gerekli; doğrudan Apply bloklu' : '';
    return (r.stable_id || ('row '+(r.source_row||'?'))) + ' — ' + r.operation + impact + (r.errors && r.errors.length ? ' — ' + r.errors.join('; ') : '');
  });
  SpreadsheetApp.getUi().alert(title, (response.ok ? 'SYNC OK' : 'SYNC WITH ERRORS') + '\n\n' + lines.join('\n'), SpreadsheetApp.getUi().ButtonSet.OK);
}
