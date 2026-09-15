/**
 * Server Turizm — Tour menu.
 * Independent menu; NO Umrah entries.
 * Run stTourInstall() once.
 */
function stTourInstall() {
  var ss=SpreadsheetApp.getActiveSpreadsheet();
  ScriptApp.getProjectTriggers().forEach(function(trigger){
    if(trigger.getHandlerFunction()==='stTourOnOpen_')ScriptApp.deleteTrigger(trigger);
  });
  ScriptApp.newTrigger('stTourOnOpen_').forSpreadsheet(ss).onOpen().create();
  stTourOnOpen_();
  SpreadsheetApp.getUi().alert('Server Turizm Tours','Menü kuruldu.',SpreadsheetApp.getUi().ButtonSet.OK);
}

function stTourOnOpen_() {
  stTourEnsureTechnicalColumns_();
  SpreadsheetApp.getUi()
    .createMenu('🧭 Server Turizm Tours')
    .addItem('Ön Kontrol — Seçili Tur', 'stTourValidateSelected')
    .addItem('Siteyi Güncelle — Seçili Tur', 'stTourSyncSelected')
    .addItem('Seçili Turu Arşivle', 'stTourArchiveSelected')
    .addSeparator()
    .addItem('Seçili Satırı STTI Kaydına Bağla', 'stTourLinkSelectedRow')
    .addItem('Seçili Satır Bağını Temizle', 'stTourClearSelectedRowLink')
    .addItem('Teknik Stable ID/Checksum Hazırla / Gizle', 'stTourPrepareTechnicalColumns')
    .addSeparator()
    .addItem('Local Satır Kontrolü', 'stTourValidateSelectedRowLocal')
    .addItem('Partial JSON Göster', 'stTourShowSelectedJson')
    .addItem('Partial JSON İndir', 'stTourDownloadSelectedJson')
    .addSeparator()
    .addItem('Direct Sync Ayarları', 'stTourConfigureSync')
    .addToUi();
}
