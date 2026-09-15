/** Production rollout hardening for Shared Direct Sync. Selected-row pilot helpers only. */

function stDirectSyncPilotStateIndex_() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getSheetByName(ST_DIRECT_SYNC.STATE_SHEET);
  if (!sheet || sheet.getLastRow() <= 1) return {};
  var values = sheet.getRange(2, 1, sheet.getLastRow() - 1, 7).getDisplayValues();
  var out = {};
  values.forEach(function(r, i) {
    var key = stDirectSyncStateKey_(r[0], r[1], r[2], Number(r[3] || 0));
    out[key] = {
      row: i + 2,
      stable_id: String(r[4] || '').trim(),
      checksum: String(r[5] || '').trim().toLowerCase()
    };
  });
  return out;
}

function stDirectSyncBuildSelectedUmrah_() {
  if (typeof stTdeBuildActiveBatch_ !== 'function') throw new Error('ST-TDE exporter bulunamadı.');
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheet = ss.getActiveSheet();
  if (!ST_TDE || sheet.getName() !== ST_TDE.HOME_SHEET) {
    throw new Error('Pilot için Home sayfasında bir Umrah satırı seç.');
  }
  var selectedRow = sheet.getActiveRange().getRow();
  var headerRow = stTdeFindHeaderRow_(sheet);
  if (selectedRow <= headerRow) throw new Error('Bir veri satırı seç.');

  var built = stTdeBuildActiveBatch_();
  if (built.report && built.report.errors && built.report.errors.length) {
    throw new Error('ST-TDE validation hatası: ' + built.report.errors.map(function(x){ return x.message || x; }).join(' | '));
  }

  var batch = JSON.parse(JSON.stringify(built.batch));
  var matches = batch.programs.filter(function(program) {
    return Number(program.provenance && program.provenance.source_row || 0) === selectedRow;
  });
  if (matches.length !== 1) {
    throw new Error('Seçili satır aktif/exportable tek bir Umrah programına çözülmedi. Satır: ' + selectedRow);
  }

  batch.programs = [matches[0]];
  var state = stDirectSyncPilotStateIndex_();
  var key = stDirectSyncStateKey_('umrah', batch.source.document_ref, batch.source.worksheet, selectedRow);
  var saved = state[key] || null;
  if (saved && saved.stable_id) batch.programs[0].program_id = saved.stable_id;

  return {
    batch: batch,
    source_row: selectedRow,
    controls: [{
      source_row: selectedRow,
      stable_id: saved ? saved.stable_id : null,
      expected_checksum_sha256: saved ? saved.checksum : null
    }]
  };
}

function stDirectSyncValidateSelectedUmrah() {
  var pilot = stDirectSyncBuildSelectedUmrah_();
  var response = stDirectSyncSend_('umrah', 'validate', {
    batch: pilot.batch,
    controls: pilot.controls,
    removals: []
  });
  stDirectSyncShowResult_('Umrah Pilot Ön Kontrol — WordPress yazma yok', response);
}

function stDirectSyncSelectedUmrah() {
  var pilot = stDirectSyncBuildSelectedUmrah_();
  var preflight = stDirectSyncSend_('umrah', 'validate', {
    batch: pilot.batch,
    controls: pilot.controls,
    removals: []
  });
  var publicBlock = (preflight.results || []).some(function(r) {
    return r && r.operation === 'UPDATE' && r.public_impact && r.public_impact.protected;
  });
  if (!preflight.ok || publicBlock) {
    stDirectSyncShowResult_('Umrah Pilot — Canlı Etki Koruması / Apply yapılmadı', preflight);
    return;
  }

  var ui = SpreadsheetApp.getUi();
  var confirm = ui.alert(
    'Umrah Pilot Güncelle',
    'Ön kontrol PASS. Sadece seçili Home satırı (' + pilot.source_row + ') WordPress private/canonical store ile senkronize edilecek.\n\nPublic/indexation gate açılmaz. Devam edilsin mi?',
    ui.ButtonSet.YES_NO
  );
  if (confirm !== ui.Button.YES) return;

  var response = stDirectSyncSend_('umrah', 'apply', {
    batch: pilot.batch,
    controls: pilot.controls,
    removals: []
  });
  stDirectSyncApplyUmrahState_(pilot.batch.source, response.results || []);
  stDirectSyncShowResult_('Umrah Pilot Güncelle', response);
}

function stDirectSyncValidateSelectedTour() {
  if (typeof sttiBuildSelectedRow_ !== 'function') throw new Error('STTI Tour Sheet generator bulunamadı.');
  var built = sttiBuildSelectedRow_();
  var response = stDirectSyncSend_('tour', 'validate', {
    documents: [built.payload],
    archives: []
  });
  stDirectSyncShowResult_('Tour Ön Kontrol — WordPress yazma yok', response);
}
