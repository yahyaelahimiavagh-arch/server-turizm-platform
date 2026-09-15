/**
 * Server Turizm — Tour Direct Sync.
 * Independent Apps Script stack (NO Umrah functions).
 * Depends on ST-Tour-Generator.gs.
 */
var ST_TOUR_SYNC = Object.freeze({
  VERSION:'1.0.1',
  CONTRACT:'ST-DIRECT-SYNC-1.0.0',
  ENDPOINT_PROPERTY:'ST_DIRECT_SYNC_ENDPOINT',
  KEY_ID_PROPERTY:'ST_DIRECT_SYNC_KEY_ID',
  SECRET_PROPERTY:'ST_DIRECT_SYNC_SECRET',
  TRANSPORT_MAX_ATTEMPTS:3,
  TRANSPORT_RETRY_DELAY_MS:1500
});

function stTourConfigureSync() {
  var ui=SpreadsheetApp.getUi();
  var props=PropertiesService.getScriptProperties();
  var endpoint=ui.prompt('Tour Direct Sync','WordPress endpoint:',ui.ButtonSet.OK_CANCEL);
  if(endpoint.getSelectedButton()!==ui.Button.OK)return;
  var key=ui.prompt('Tour Direct Sync','Key ID:',ui.ButtonSet.OK_CANCEL);
  if(key.getSelectedButton()!==ui.Button.OK)return;
  var secret=ui.prompt('Tour Direct Sync','HMAC secret:',ui.ButtonSet.OK_CANCEL);
  if(secret.getSelectedButton()!==ui.Button.OK)return;
  props.setProperties({
    ST_DIRECT_SYNC_ENDPOINT:String(endpoint.getResponseText()||'').trim(),
    ST_DIRECT_SYNC_KEY_ID:String(key.getResponseText()||'').trim(),
    ST_DIRECT_SYNC_SECRET:String(secret.getResponseText()||'')
  },false);
  ui.alert('Tour Direct Sync','Ayarlar kaydedildi.',ui.ButtonSet.OK);
}

function stTourValidateSelected() {
  var built=stTourBuildSelectedRow_();
  var response=stTourSend_('validate',{documents:[built.payload],archives:[]});
  stTourShowSyncResult_('Tour Ön Kontrol — WordPress yazma yok',response);
}

function stTourSyncSelected() {
  var built=stTourBuildSelectedRow_();
  var preflight=stTourSend_('validate',{documents:[built.payload],archives:[]});
  var r=preflight.results&&preflight.results[0];
  var blocked=!preflight.ok||!r||['CONFLICT','ERROR','INVALID'].indexOf(String(r.operation||''))!==-1||(r.errors&&r.errors.length);
  if(blocked){
    stTourShowSyncResult_('Tour Ön Kontrol BLOCKED — Apply yapılmadı',preflight);
    return;
  }

  var ui=SpreadsheetApp.getUi();
  if(ui.alert(
    'Tour Direct Sync',
    'Target: '+(r.stable_id||'NEW')+'\nOperation: '+r.operation+
      '\n\nPublication private/off kalır. Devam edilsin mi?',
    ui.ButtonSet.YES_NO
  )!==ui.Button.YES)return;

  var response=stTourSend_('apply',{documents:[built.payload],archives:[]});
  stTourWriteState_(built.row,response.results&&response.results[0]);
  stTourShowSyncResult_('Tour Direct Sync',response);
}

function stTourArchiveSelected() {
  var tech=stTourEnsureTechnicalColumns_();
  var sheet=SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  var row=sheet.getActiveRange().getRow();
  if(row<ST_TOUR.FIRST_DATA_ROW)throw new Error('Bir tur satırı seç.');

  var values=sheet.getRange(row,tech.stable,1,2).getDisplayValues()[0];
  var stableId=String(values[0]||'').trim().toUpperCase();
  var checksum=String(values[1]||'').trim().toLowerCase();
  if(!/^STT-\d{6}$/.test(stableId)||!/^[a-f0-9]{64}$/.test(checksum)){
    throw new Error('Satır STT-* + checksum ile bağlı değil.');
  }

  var archive={stable_id:stableId,expected_checksum_sha256:checksum};
  var preflight=stTourSend_('validate',{documents:[],archives:[archive]});
  var r=preflight.results&&preflight.results[0];
  if(!preflight.ok||!r||['ARCHIVE','UNCHANGED'].indexOf(String(r.operation||''))===-1||(r.errors&&r.errors.length)){
    stTourShowSyncResult_('Tour Archive Ön Kontrol BLOCKED',preflight);
    return;
  }
  if(r.operation==='UNCHANGED'){
    stTourShowSyncResult_('Tour Archive — Değişiklik yok',preflight);
    return;
  }

  var ui=SpreadsheetApp.getUi();
  if(ui.alert(
    'Tour Archive',
    stableId+' silinmeden arşivlenecek; publication private/off kalacak.\n\nDevam edilsin mi?',
    ui.ButtonSet.YES_NO
  )!==ui.Button.YES)return;

  var response=stTourSend_('apply',{documents:[],archives:[archive]});
  stTourWriteState_(row,response.results&&response.results[0]);
  stTourShowSyncResult_('Tour Archive',response);
}

function stTourWriteState_(row,result) {
  if(!result||!result.stable_id||!result.checksum||(result.errors&&result.errors.length))return;
  var sheet=SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  var tech=stTourEnsureTechnicalColumns_();
  sheet.getRange(row,tech.stable,1,2).setValues([[result.stable_id,result.checksum]]);
  sheet.hideColumns(tech.stable,2);
}

function stTourSend_(mode,payload) {
  var props=PropertiesService.getScriptProperties();
  var endpoint=String(props.getProperty(ST_TOUR_SYNC.ENDPOINT_PROPERTY)||'').trim();
  var keyId=String(props.getProperty(ST_TOUR_SYNC.KEY_ID_PROPERTY)||'').trim();
  var secret=String(props.getProperty(ST_TOUR_SYNC.SECRET_PROPERTY)||'');
  if(!/^https:\/\//i.test(endpoint)||!keyId||!secret)throw new Error('Direct Sync ayarları eksik.');

  var requestId='STS-TOUR-'+Utilities.formatDate(new Date(),'UTC','yyyyMMddHHmmss')+'-'+Utilities.getUuid().replace(/-/g,'').slice(0,16);
  var envelope={contract:ST_TOUR_SYNC.CONTRACT,request_id:requestId,adapter:'tour',mode:mode,payload:payload};
  var body=JSON.stringify(envelope);
  var bodyHash=stTourSha256Hex_(body);
  var maxAttempts=Number(ST_TOUR_SYNC.TRANSPORT_MAX_ATTEMPTS||1);
  var lastError=null;

  for(var attempt=1;attempt<=maxAttempts;attempt++){
    var res;
    try{
      res=stTourSignedFetch_(endpoint,keyId,secret,body,bodyHash);
    }catch(e){
      lastError=e;
      if(!stTourIsTransientTransportError_(e)||attempt>=maxAttempts)throw e;
      Utilities.sleep(ST_TOUR_SYNC.TRANSPORT_RETRY_DELAY_MS*attempt);
      continue;
    }

    var code=res.getResponseCode();
    var text=res.getContentText();
    var parsed;
    try{parsed=JSON.parse(text);}catch(e2){throw new Error('WordPress JSON okunamadı. HTTP '+code);}
    if(code===409&&parsed&&parsed.code==='stds_processing'&&attempt<maxAttempts){
      Utilities.sleep(ST_TOUR_SYNC.TRANSPORT_RETRY_DELAY_MS*attempt);
      continue;
    }
    if(code<200||code>=300)throw new Error('Direct Sync HTTP '+code+': '+(parsed.message||parsed.code||text));
    if(attempt>1){parsed.transport_retry_recovered=true;parsed.transport_attempts=attempt;}
    return parsed;
  }
  throw lastError||new Error('Direct Sync transport failed.');
}

function stTourSignedFetch_(endpoint,keyId,secret,body,bodyHash) {
  var timestamp=String(Math.floor(Date.now()/1000));
  var nonce=Utilities.getUuid().replace(/-/g,'')+Utilities.getUuid().replace(/-/g,'').slice(0,12);
  var canonical='ST-DIRECT-SYNC-1\n'+timestamp+'\n'+nonce+'\n'+bodyHash;
  var signature=stTourBytesHex_(Utilities.computeHmacSha256Signature(canonical,secret,Utilities.Charset.UTF_8));
  return UrlFetchApp.fetch(endpoint,{
    method:'post',
    contentType:'application/json',
    payload:body,
    muteHttpExceptions:true,
    headers:{
      'X-ST-Sync-Timestamp':timestamp,
      'X-ST-Sync-Nonce':nonce,
      'X-ST-Sync-Key-Id':keyId,
      'X-ST-Sync-Signature':signature
    }
  });
}

function stTourIsTransientTransportError_(error) {
  var message=String(error&&error.message?error.message:error||'');
  return /timeout|timed\s*out|dns\s*error|name\s*or\s*service\s*not\s*known|temporary\s*failure\s*in\s*name\s*resolution|network\s*error|connection\s*(?:reset|refused|timed\s*out)|socket\s*error|address\s*unavailable|host\s*(?:lookup|resolution)\s*failed/i.test(message);
}
function stTourSha256Hex_(text){return stTourBytesHex_(Utilities.computeDigest(Utilities.DigestAlgorithm.SHA_256,text,Utilities.Charset.UTF_8));}
function stTourBytesHex_(bytes){return bytes.map(function(b){var v=(b+256)%256;return ('0'+v.toString(16)).slice(-2);}).join('');}

function stTourShowSyncResult_(title,response) {
  var lines=(response.results||[]).map(function(r){
    return (r.stable_id||'NEW')+' — '+r.operation+
      (r.errors&&r.errors.length?' — '+r.errors.join('; '):'');
  });
  var extra=response.transport_retry_recovered?
    '\n\nGeçici bağlantı hatası retry ile kurtarıldı ('+response.transport_attempts+'. deneme).':'';
  SpreadsheetApp.getUi().alert(
    title,
    (response.ok?'SYNC OK':'SYNC WITH ERRORS')+'\n\n'+lines.join('\n')+extra,
    SpreadsheetApp.getUi().ButtonSet.OK
  );
}
