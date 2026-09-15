/** Server Turizm Shared Direct Sync client v0.1.3.2 — Umrah + Tours. */
var ST_DIRECT_SYNC = Object.freeze({
  VERSION: '0.1.3.2',
  CONTRACT: 'ST-DIRECT-SYNC-1.0.0',
  STATE_SHEET: 'ST Direct Sync State',
  ENDPOINT_PROPERTY: 'ST_DIRECT_SYNC_ENDPOINT',
  KEY_ID_PROPERTY: 'ST_DIRECT_SYNC_KEY_ID',
  SECRET_PROPERTY: 'ST_DIRECT_SYNC_SECRET',
  TRANSPORT_MAX_ATTEMPTS: 3,
  TRANSPORT_RETRY_DELAY_MS: 1500
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

  // Full-batch production rollout remains deliberately stricter than selected-row sync.
  // A live UPDATE is auto-refresh-capable at the server, but mass apply stays blocked
  // until the all-or-nothing batch gate is accepted separately.
  var preflight = stDirectSyncSend_('umrah', 'validate', {batch: batch, controls: controls, removals: removals});
  var publicBlock = (preflight.results || []).some(function(r) {
    return r && r.public_impact && r.public_impact.protected && (r.operation === 'UPDATE' || r.operation === 'ARCHIVE' || r.operation === 'CONFLICT');
  });
  if (!preflight.ok || publicBlock) {
    stDirectSyncShowResult_('Umrah Direct Sync — Ön Kontrol / HİÇBİR WRITE YAPILMADI', preflight);
    return;
  }

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

  // The request ID and raw body are created exactly once and reused across transport
  // retries. WordPress idempotency therefore guarantees an APPLY can never execute
  // twice even if Apps Script times out after the first request reached the server.
  var requestId = 'STS-' + Utilities.formatDate(new Date(), 'UTC', 'yyyyMMddHHmmss') + '-' + Utilities.getUuid().replace(/-/g, '').slice(0, 16);
  var envelope = {contract: ST_DIRECT_SYNC.CONTRACT, request_id: requestId, adapter: adapter, mode: mode, payload: payload};
  var body = JSON.stringify(envelope);
  var bodyHash = stDirectSyncSha256Hex_(body);
  var maxAttempts = Number(ST_DIRECT_SYNC.TRANSPORT_MAX_ATTEMPTS || 1);
  var lastError = null;

  for (var attempt = 1; attempt <= maxAttempts; attempt++) {
    var res;
    try {
      res = stDirectSyncSignedFetch_(endpoint, keyId, secret, body, bodyHash);
    } catch (e) {
      lastError = e;
      if (!stDirectSyncIsTransientTransportError_(e) || attempt >= maxAttempts) throw e;
      Utilities.sleep(ST_DIRECT_SYNC.TRANSPORT_RETRY_DELAY_MS * attempt);
      continue;
    }

    var code = res.getResponseCode();
    var text = res.getContentText();
    var parsed;
    try { parsed = JSON.parse(text); } catch (e) { throw new Error('WordPress JSON cevabı okunamadı. HTTP ' + code); }

    // If the first request is still completing after Apps Script timed out, the same
    // request_id returns stds_processing. Wait briefly and poll with a fresh nonce/signature.
    if (code === 409 && parsed && parsed.code === 'stds_processing' && attempt < maxAttempts) {
      Utilities.sleep(ST_DIRECT_SYNC.TRANSPORT_RETRY_DELAY_MS * attempt);
      continue;
    }

    if (code < 200 || code >= 300) throw new Error('Direct Sync HTTP ' + code + ': ' + (parsed.message || parsed.code || text));
    if (attempt > 1) {
      parsed.transport_retry_recovered = true;
      parsed.transport_attempts = attempt;
    }
    return parsed;
  }

  throw lastError || new Error('Direct Sync transport failed after retries.');
}

function stDirectSyncSignedFetch_(endpoint, keyId, secret, body, bodyHash) {
  // Timestamp/nonce/signature are transport credentials, so each retry gets fresh
  // values while request_id and body remain byte-for-byte identical.
  var timestamp = String(Math.floor(Date.now() / 1000));
  var nonce = Utilities.getUuid().replace(/-/g, '') + Utilities.getUuid().replace(/-/g, '').slice(0, 12);
  var canonical = 'ST-DIRECT-SYNC-1\n' + timestamp + '\n' + nonce + '\n' + bodyHash;
  var signature = stDirectSyncBytesHex_(Utilities.computeHmacSha256Signature(canonical, secret, Utilities.Charset.UTF_8));
  return UrlFetchApp.fetch(endpoint, {
    method: 'post',
    contentType: 'application/json',
    payload: body,
    muteHttpExceptions: true,
    headers: {
      'X-ST-Sync-Timestamp': timestamp,
      'X-ST-Sync-Nonce': nonce,
      'X-ST-Sync-Key-Id': keyId,
      'X-ST-Sync-Signature': signature
    }
  });
}

function stDirectSyncIsTransientTransportError_(error) {
  var message = String(error && error.message ? error.message : error || '');
  return /timeout|timed\s*out|dns\s*error|name\s*or\s*service\s*not\s*known|temporary\s*failure\s*in\s*name\s*resolution|network\s*error|connection\s*(?:reset|refused|timed\s*out)|socket\s*error|address\s*unavailable|host\s*(?:lookup|resolution)\s*failed/i.test(message);
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
    var impact = '';
    if (r.public_impact && r.public_impact.protected) {
      if (r.public_impact.auto_refreshed) impact = ' — CANLI PROGRAM: approval + route/hash otomatik yenilendi';
      else if (r.public_impact.auto_refresh_supported) impact = ' — CANLI PROGRAM: otomatik approval + route/hash refresh hazır';
      else impact = ' — CANLI PROGRAM: güvenlik nedeniyle otomatik refresh kullanılamıyor';
    }
    return (r.stable_id || ('row '+(r.source_row||'?'))) + ' — ' + r.operation + impact + (r.errors && r.errors.length ? ' — ' + r.errors.join('; ') : '');
  });
  var transport = response.transport_retry_recovered ? '\n\nGeçici bağlantı hatası otomatik retry ile kurtarıldı (' + response.transport_attempts + '. deneme).' : '';
  SpreadsheetApp.getUi().alert(title, (response.ok ? 'SYNC OK' : 'SYNC WITH ERRORS') + '\n\n' + lines.join('\n') + transport, SpreadsheetApp.getUi().ButtonSet.OK);
}
