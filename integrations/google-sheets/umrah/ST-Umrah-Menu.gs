/**
 * Server Turizm — Umrah menu.
 * Independent menu; NO Tour entries.
 * Run stUmrahInstall() once.
 */
function stUmrahInstall() {
  var ss=SpreadsheetApp.getActiveSpreadsheet();
  ScriptApp.getProjectTriggers().forEach(function(trigger){
    if(trigger.getHandlerFunction()==='stUmrahOnOpen_')ScriptApp.deleteTrigger(trigger);
  });
  ScriptApp.newTrigger('stUmrahOnOpen_').forSpreadsheet(ss).onOpen().create();
  stUmrahOnOpen_();
  SpreadsheetApp.getUi().alert('Server Turizm Umrah','Menü kuruldu.',SpreadsheetApp.getUi().ButtonSet.OK);
}

function stUmrahOnOpen_() {
  SpreadsheetApp.getUi()
    .createMenu('🕋 Server Turizm Umrah')
    .addItem('Ön Kontrol — Seçili Umrah', 'stUmrahValidateSelected')
    .addItem('Siteyi Güncelle — Seçili Umrah', 'stUmrahSyncSelected')
    .addItem('Siteyi Güncelle — Tüm Aktif Umrah', 'stUmrahSyncAllActive')
    .addSeparator()
    .addItem('Programı Kaldır → Arşivle', 'stUmrahArchiveMarkedPrograms')
    .addSeparator()
    .addItem('Aktif Programları Kontrol Et', 'stUmrahValidateActivePrograms')
    .addItem('Seçili Program JSON', 'stUmrahExportSelectedProgramJson')
    .addItem('Tüm Aktif Programlar JSON', 'stUmrahExportActiveProgramsJson')
    .addSeparator()
    .addItem('Hotel Directory Yükle', 'stUmrahImportHotelDirectory')
    .addSeparator()
    .addItem('Direct Sync Ayarları', 'stUmrahConfigureSync')
    .addToUi();
}
