/** Installable menu hook for Shared Direct Sync. Existing onOpen functions remain untouched. */
function stDirectSyncInstall() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  ScriptApp.getProjectTriggers().forEach(function(trigger) {
    if (trigger.getHandlerFunction() === 'stDirectSyncOnOpen_') ScriptApp.deleteTrigger(trigger);
  });
  ScriptApp.newTrigger('stDirectSyncOnOpen_').forSpreadsheet(ss).onOpen().create();
  stDirectSyncOnOpen_();
  SpreadsheetApp.getUi().alert('Direct Sync', 'Server Turizm Sync menüsü kuruldu.', SpreadsheetApp.getUi().ButtonSet.OK);
}

function stDirectSyncOnOpen_() {
  SpreadsheetApp.getUi()
    .createMenu('🔄 Server Turizm Sync')
    .addItem('Ön Kontrol — Seçili Umrah (WP yazma yok)', 'stDirectSyncValidateSelectedUmrah')
    .addItem('Pilot Güncelle — Seçili Umrah', 'stDirectSyncSelectedUmrah')
    .addItem('Siteyi Güncelle — Tüm Aktif Umrah', 'stDirectSyncUmrahActivePrograms')
    .addSeparator()
    .addItem('Ön Kontrol — Seçili Tur (WP yazma yok)', 'stDirectSyncValidateSelectedTour')
    .addItem('Siteyi Güncelle — Seçili Tur', 'stDirectSyncSelectedTour')
    .addSeparator()
    .addItem('Seçili Turu Arşivle', 'stDirectSyncArchiveSelectedTour')
    .addSeparator()
    .addItem('Direct Sync Ayarları', 'stDirectSyncConfigure')
    .addToUi();
}
