/**
 * Server Turizm — Umrah Direct Sync.
 * Independent Apps Script stack (NO Tour functions).
 * Depends on ST-Umrah-Config.gs and ST-Umrah-Exporter.gs.
 */

function stUmrahConfigureSync() {
  var ui = SpreadsheetApp.getUi();
  var props = PropertiesService.getScriptProperties();
  var endpoint = ui.prompt('Umrah Direct Sync','WordPress endpoint:',ui.ButtonSet.OK_CANCEL);
  if (endpoint.getSelectedButton() !== ui.Button.OK) return;
  var key = ui.prompt('Umrah Direct Sync','Key ID:',ui.ButtonSet.OK_CANCEL);
  if (key.getSelectedButton() !== ui.Button.OK) return;
  var secret = ui.prompt('Umrah Direct Sync','HMAC secret:',ui.ButtonSet.OK_CANCEL);
  if (secret.getSelectedButton() !== ui.Button.OK) return;
  props.setProperties({
    ST_DIRECT_SYNC_ENDPOINT:String(endpoint.getResponseText()||'').trim(),
    ST_DIRECT_SYNC_KEY_ID:String(key.getResponseText()||'').trim(),
    ST_DIRECT_SYNC_SECRET:String(secret.getResponseText()||'')
  }, false);
  stUmrahEnsureStateSheet_();
  ui.alert('Umrah Direct Sync','Ayarlar Script Properties içine kaydedildi.',ui.ButtonSet.OK);
}

function stUmrahValidateSelected() {
  var pilot = stUmrahBuildSelectedSync_();
  var response = stUmrahSend_('validate',{
    batch:pilot.batch,
    controls:pilot.controls,
    removals:[]
  });
  stUmrahShowSyncResult_('Umrah Ön Kontrol — WordPress yazma yok',response);
}

function stUmrahSyncSelected() {
  var pilot = stUmrahBuildSelectedSync_();
  var preflight = stUmrahSend_('validate',{
    batch:pilot.batch,
    controls:pilot.controls,
    removals:[]
  });

  var blocked = (preflight.results||[]).some(function(r){
    if(!r)return true;
    if(['CONFLICT','ERROR','INVALID'].indexOf(r.operation)!==-1)return true;
    return r.operation==='UPDATE' && r.public_impact && r.public_impact.protected && !r.public_impact.auto_refresh_supported;
  });
  if(!preflight.ok || blocked){
    stUmrahShowSyncResult_('Umrah Ön Kontrol BLOCKED — Apply yapılmadı',preflight);
    return;
  }

  var ui=SpreadsheetApp.getUi();
  var op=(preflight.results&&preflight.results[0]&&preflight.results[0].operation)||'?';
  var message='Satır '+pilot.source_row+' hedeflenecek.\nOperation: '+op+
    '\n\nPublic/indexation gate açılmaz. Devam edilsin mi?';
  if(ui.alert('Umrah Sync',message,ui.ButtonSet.YES_NO)!==ui.Button.YES)return;

  var response=stUmrahSend_('apply',{
    batch:pilot.batch,
    controls:pilot.controls,
    removals:[]
  });
  stUmrahApplyState_(pilot.batch.source,response.results||[]);
  stUmrahShowSyncResult_('Umrah Direct Sync',response);
}

function stUmrahSyncAllActive() {
  var built=stUmrahBuildActiveBatch_();
  if(built.report.errors.length){
    stUmrahShowValidation_(built);
    return;
  }

  var batch=JSON.parse(JSON.stringify(built.batch));
  var state=stUmrahStateIndex_();
  var controls=[];

  batch.programs.forEach(function(program){
    var row=Number(program.provenance&&program.provenance.source_row||0);
    var key=stUmrahStateKey_(batch.source.document_ref,batch.source.worksheet,row);
    var saved=state[key]||null;
    if(saved&&saved.stable_id)program.program_id=saved.stable_id;
    controls.push({
      source_row:row,
      stable_id:saved?saved.stable_id:null,
      expected_checksum_sha256:saved?saved.checksum:null
    });
  });

  var preflight=stUmrahSend_('validate',{batch:batch,controls:controls,removals:[]});
  var publicBlock=(preflight.results||[]).some(function(r){
    return r&&r.public_impact&&r.public_impact.protected&&
      (r.operation==='UPDATE'||r.operation==='CONFLICT');
  });
  if(!preflight.ok||publicBlock){
    stUmrahShowSyncResult_('Tüm Aktif Umrah — Ön Kontrol / HİÇBİR WRITE YAPILMADI',preflight);
    return;
  }

  var ui=SpreadsheetApp.getUi();
  if(ui.alert(
    'Tüm Aktif Umrah',
    'Ön kontrol PASS. '+batch.programs.length+' aktif Program senkronize edilecek.\n\nProgramı Kaldır satırları bu işlemde ARCHIVE edilmez; arşiv menüsü ayrıdır.\n\nDevam edilsin mi?',
    ui.ButtonSet.YES_NO
  )!==ui.Button.YES)return;

  var response=stUmrahSend_('apply',{batch:batch,controls:controls,removals:[]});
  stUmrahApplyState_(batch.source,response.results||[]);
  stUmrahShowSyncResult_('Tüm Aktif Umrah',response);
}

function stUmrahArchiveMarkedPrograms() {
  var ss=SpreadsheetApp.getActiveSpreadsheet();
  var sheet=ss.getSheetByName(ST_UMRAH.HOME_SHEET);
  if(!sheet)throw new Error('Home sheet bulunamadı.');

  var headerRow=stUmrahFindHeaderRow_(sheet);
  stUmrahAssertLayout_(sheet,headerRow);
  var count=Math.max(0,sheet.getLastRow()-headerRow);
  if(!count)throw new Error('Program satırı bulunamadı.');

  var flags=sheet.getRange(headerRow+1,ST_UMRAH.COL_REMOVE,count,1).getValues();
  var codes=sheet.getRange(headerRow+1,ST_UMRAH.COL_PROGRAM_NO,count,1).getDisplayValues();
  var state=stUmrahStateIndex_();
  var source={
    type:'google_sheets',
    mode:'partial',
    document_ref:ss.getId(),
    worksheet:ST_UMRAH.HOME_SHEET,
    timezone:ST_UMRAH.TIMEZONE
  };
  var removals=[];
  var skippedSheetOnly=[];

  flags.forEach(function(row,index){
    if(!stUmrahTruthy_(row[0]))return;
    var sourceRow=headerRow+1+index;
    var programCode=String(codes[index][0]||'').trim();
    var key=stUmrahStateKey_(source.document_ref,source.worksheet,sourceRow);
    var saved=state[key]||null;

    // Historical rows that predate Direct Sync and have no STP/checksum are
    // Sheet-only closed rows. They are NOT created merely to archive them.
    if(!saved||!saved.stable_id||!saved.checksum){
      skippedSheetOnly.push('row '+sourceRow+(programCode?' / '+programCode:''));
      return;
    }

    removals.push({
      source_row:sourceRow,
      program_code:programCode||null,
      stable_id:saved.stable_id,
      expected_checksum_sha256:saved.checksum
    });
  });

  if(!removals.length){
    var msg='WordPress arşivlenecek bağlı Program yok.';
    if(skippedSheetOnly.length)msg+='\n\nSheet-only geçmiş satırlar:\n'+skippedSheetOnly.join('\n');
    SpreadsheetApp.getUi().alert('Programı Kaldır',msg.slice(0,9000),SpreadsheetApp.getUi().ButtonSet.OK);
    return;
  }

  var batch={
    schema_version:ST_UMRAH.SCHEMA_VERSION,
    export_id:'STX-UMRAH-ARCHIVE-'+Utilities.formatDate(new Date(),ST_UMRAH.TIMEZONE,'yyyyMMdd-HHmmss'),
    generated_at:new Date().toISOString(),
    source:source,
    programs:[]
  };

  var preflight=stUmrahSend_('validate',{batch:batch,controls:[],removals:removals});
  var unexpected=(preflight.results||[]).some(function(r){
    return !r || ['ARCHIVE','UNCHANGED'].indexOf(String(r.operation||''))===-1 || (r.errors&&r.errors.length);
  });
  if(!preflight.ok||unexpected){
    stUmrahShowSyncResult_('Programı Kaldır — Ön Kontrol / HİÇBİR WRITE YAPILMADI',preflight);
    return;
  }

  var pending=(preflight.results||[]).filter(function(r){return r&&r.operation==='ARCHIVE';});
  if(!pending.length){
    stUmrahShowSyncResult_('Programı Kaldır — Değişiklik yok',preflight);
    return;
  }

  var lines=pending.map(function(r){return r.stable_id;});
  var note=skippedSheetOnly.length?'\n\nSheet-only geçmiş satır '+skippedSheetOnly.length+' adet atlanacak.':'';
  var ui=SpreadsheetApp.getUi();
  if(ui.alert(
    'Umrah Controlled Archive',
    'Aşağıdaki bağlı Programlar SİLİNMEYECEK; Stable ID korunarak ARCHIVE olacak:\n\n'+
      lines.join('\n')+note+'\n\nDevam edilsin mi?',
    ui.ButtonSet.YES_NO
  )!==ui.Button.YES)return;

  var approved=removals.map(function(item){
    var x=JSON.parse(JSON.stringify(item));
    x.controlled_archive_approved=true;
    return x;
  });
  var response=stUmrahSend_('apply',{batch:batch,controls:[],removals:approved});
  stUmrahApplyState_(source,response.results||[]);
  response.sheet_only_skipped=skippedSheetOnly;
  stUmrahShowSyncResult_('Umrah Controlled Archive',response);
}

function stUmrahBuildSelectedSync_() {
  var built=stUmrahBuildSelectedRow_({allowRemoved:false});
  if(built.report.errors.length)throw new Error(
    built.report.errors.map(function(x){return x.message||String(x);}).join(' | ')
  );

  var batch=JSON.parse(JSON.stringify(built.batch));
  var state=stUmrahStateIndex_();
  var key=stUmrahStateKey_(batch.source.document_ref,batch.source.worksheet,built.source_row);
  var saved=state[key]||null;
  if(saved&&saved.stable_id)batch.programs[0].program_id=saved.stable_id;

  return {
    batch:batch,
    source_row:built.source_row,
    controls:[{
      source_row:built.source_row,
      stable_id:saved?saved.stable_id:null,
      expected_checksum_sha256:saved?saved.checksum:null
    }]
  };
}

function stUmrahEnsureStateSheet_() {
  var ss=SpreadsheetApp.getActiveSpreadsheet();
  var sheet=ss.getSheetByName(ST_UMRAH.STATE_SHEET)||ss.insertSheet(ST_UMRAH.STATE_SHEET);
  var headers=['Document Ref','Worksheet','Source Row','Stable ID','Expected Checksum','Updated At'];
  sheet.getRange(1,1,1,headers.length).setValues([headers]);
  sheet.hideSheet();
  return sheet;
}

function stUmrahStateIndex_() {
  var sheet=stUmrahEnsureStateSheet_();
  var values=sheet.getLastRow()>1?sheet.getRange(2,1,sheet.getLastRow()-1,6).getDisplayValues():[];
  var out={};
  values.forEach(function(r,i){
    var key=stUmrahStateKey_(r[0],r[1],Number(r[2]||0));
    out[key]={row:i+2,stable_id:String(r[3]||'').trim(),checksum:String(r[4]||'').trim().toLowerCase()};
  });
  return out;
}

function stUmrahApplyState_(source,results) {
  var sheet=stUmrahEnsureStateSheet_();
  var state=stUmrahStateIndex_();
  results.forEach(function(result){
    if(!result||!result.source_row||!result.stable_id||!result.checksum||(result.errors&&result.errors.length))return;
    var key=stUmrahStateKey_(source.document_ref,source.worksheet,result.source_row);
    var row=state[key]?state[key].row:sheet.getLastRow()+1;
    sheet.getRange(row,1,1,6).setValues([[
      source.document_ref,
      source.worksheet,
      result.source_row,
      result.stable_id,
      result.checksum,
      new Date().toISOString()
    ]]);
  });
  sheet.hideSheet();
}

function stUmrahStateKey_(documentRef,worksheet,sourceRow){
  return [String(documentRef||''),String(worksheet||''),String(sourceRow||0)].join('|');
}

function stUmrahSend_(mode,payload) {
  var props=PropertiesService.getScriptProperties();
  var endpoint=String(props.getProperty(ST_UMRAH_SYNC.ENDPOINT_PROPERTY)||'').trim();
  var keyId=String(props.getProperty(ST_UMRAH_SYNC.KEY_ID_PROPERTY)||'').trim();
  var secret=String(props.getProperty(ST_UMRAH_SYNC.SECRET_PROPERTY)||'');
  if(!/^https:\/\//i.test(endpoint)||!keyId||!secret)throw new Error('Direct Sync ayarları eksik.');

  var requestId='STS-UMRAH-'+Utilities.formatDate(new Date(),'UTC','yyyyMMddHHmmss')+'-'+Utilities.getUuid().replace(/-/g,'').slice(0,16);
  var envelope={contract:ST_UMRAH_SYNC.CONTRACT,request_id:requestId,adapter:'umrah',mode:mode,payload:payload};
  var body=JSON.stringify(envelope);
  var bodyHash=stUmrahSha256Hex_(body);
  var maxAttempts=Number(ST_UMRAH_SYNC.TRANSPORT_MAX_ATTEMPTS||1);
  var lastError=null;

  for(var attempt=1;attempt<=maxAttempts;attempt++){
    var res;
    try{
      res=stUmrahSignedFetch_(endpoint,keyId,secret,body,bodyHash);
    }catch(e){
      lastError=e;
      if(!stUmrahIsTransientTransportError_(e)||attempt>=maxAttempts)throw e;
      Utilities.sleep(ST_UMRAH_SYNC.TRANSPORT_RETRY_DELAY_MS*attempt);
      continue;
    }

    var code=res.getResponseCode();
    var text=res.getContentText();
    var parsed;
    try{parsed=JSON.parse(text);}catch(e2){throw new Error('WordPress JSON okunamadı. HTTP '+code);}
    if(code===409&&parsed&&parsed.code==='stds_processing'&&attempt<maxAttempts){
      Utilities.sleep(ST_UMRAH_SYNC.TRANSPORT_RETRY_DELAY_MS*attempt);
      continue;
    }
    if(code<200||code>=300)throw new Error('Direct Sync HTTP '+code+': '+(parsed.message||parsed.code||text));
    if(attempt>1){parsed.transport_retry_recovered=true;parsed.transport_attempts=attempt;}
    return parsed;
  }
  throw lastError||new Error('Direct Sync transport failed.');
}

function stUmrahSignedFetch_(endpoint,keyId,secret,body,bodyHash){
  var timestamp=String(Math.floor(Date.now()/1000));
  var nonce=Utilities.getUuid().replace(/-/g,'')+Utilities.getUuid().replace(/-/g,'').slice(0,12);
  var canonical='ST-DIRECT-SYNC-1\n'+timestamp+'\n'+nonce+'\n'+bodyHash;
  var signature=stUmrahBytesHex_(Utilities.computeHmacSha256Signature(canonical,secret,Utilities.Charset.UTF_8));
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

function stUmrahIsTransientTransportError_(error){
  var message=String(error&&error.message?error.message:error||'');
  return /timeout|timed\s*out|dns\s*error|name\s*or\s*service\s*not\s*known|temporary\s*failure\s*in\s*name\s*resolution|network\s*error|connection\s*(?:reset|refused|timed\s*out)|socket\s*error|address\s*unavailable|host\s*(?:lookup|resolution)\s*failed/i.test(message);
}
function stUmrahSha256Hex_(text){return stUmrahBytesHex_(Utilities.computeDigest(Utilities.DigestAlgorithm.SHA_256,text,Utilities.Charset.UTF_8));}
function stUmrahBytesHex_(bytes){return bytes.map(function(b){var v=(b+256)%256;return ('0'+v.toString(16)).slice(-2);}).join('');}

function stUmrahShowSyncResult_(title,response){
  var lines=(response.results||[]).map(function(r){
    var impact='';
    if(r.public_impact&&r.public_impact.protected){
      if(r.public_impact.controlled_archived)impact=' — CANLI PROGRAM ARCHIVE edildi';
      else if(r.public_impact.auto_refreshed)impact=' — CANLI PROGRAM route/hash yenilendi';
      else if(r.public_impact.auto_refresh_supported)impact=' — CANLI PROGRAM auto-refresh hazır';
      else impact=' — CANLI PROGRAM korumalı';
    }
    return (r.stable_id||('row '+(r.source_row||'?')))+' — '+r.operation+impact+
      (r.errors&&r.errors.length?' — '+r.errors.join('; '):'');
  });
  var extra='';
  if(response.sheet_only_skipped&&response.sheet_only_skipped.length){
    extra+='\n\nSheet-only geçmiş satırlar (WordPress işlemi yok):\n'+response.sheet_only_skipped.join('\n');
  }
  if(response.transport_retry_recovered){
    extra+='\n\nGeçici bağlantı hatası retry ile kurtarıldı ('+response.transport_attempts+'. deneme).';
  }
  SpreadsheetApp.getUi().alert(
    title,
    (response.ok?'SYNC OK':'SYNC WITH ERRORS')+'\n\n'+lines.join('\n')+extra,
    SpreadsheetApp.getUi().ButtonSet.OK
  );
}
