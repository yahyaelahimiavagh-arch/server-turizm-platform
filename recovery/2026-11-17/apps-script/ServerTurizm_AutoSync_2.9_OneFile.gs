/**
 * Server Turizm — Sheet-authoritative Sync 2.7
 * Single-file Apps Script. Replace ONLY the previous ST2 one-file source, preserving Script Properties and existing triggers.
 * Installs a 1-minute reconciliation trigger and edit/change triggers; never stores secrets in cells.
 */
var ST2 = Object.freeze({
  home:'Home', hotels:'Hotels', homeStart:3, hotelStart:2, homeCols:43, hotelCols:8,
  handlers:['st2OnOpen','st2OnEdit','st2OnChange','st2Tick'],
  maxRows:1500, endpointPath:'/wp-json/server-turizm/v2/live', contract:'ST-SNAPSHOT-2.1'
});
function st2OnOpen(){
  SpreadsheetApp.getUi().createMenu('🔁 Server Turizm — Auto Sync')
    .addItem('Otomatik Senkronizasyonu Kur','st2InstallLive')
    .addItem('Şimdi Senkronize Et','st2SyncNow')
    .addItem('Son Durum','st2Status')
    .addSeparator()
    .addItem('Otomatik Senkronizasyonu Durdur','st2StopLive').addToUi();
}
function st2InstallLive(){
  var ss=SpreadsheetApp.getActiveSpreadsheet(); if(!ss)throw new Error('Bu kodu asıl Google Sheet projesinde çalıştırın.');
  var props=PropertiesService.getScriptProperties();
  var endpoint=st2Endpoint_(),key=props.getProperty('ST_DIRECT_SYNC_KEY_ID'),secret=props.getProperty('ST_DIRECT_SYNC_SECRET');
  if(!/^https:\/\//i.test(endpoint)||!key||!secret)throw new Error('Mevcut Direct Sync endpoint / Key ID / secret ayarları eksik.');
  ScriptApp.getProjectTriggers().forEach(function(t){
    if(ST2.handlers.indexOf(t.getHandlerFunction())>=0 || /^stUmrahOnOpen_$/.test(t.getHandlerFunction())) ScriptApp.deleteTrigger(t);
  });
  props.setProperty('ST2_SHEET_ID',ss.getId());
  ScriptApp.newTrigger('st2OnOpen').forSpreadsheet(ss).onOpen().create();
  ScriptApp.newTrigger('st2OnEdit').forSpreadsheet(ss).onEdit().create();
  ScriptApp.newTrigger('st2OnChange').forSpreadsheet(ss).onChange().create();
  ScriptApp.newTrigger('st2Tick').timeBased().everyMinutes(1).create();
  props.setProperty('ST2_ENABLED','1');props.setProperty('ST2_DIRTY','1');props.setProperty('ST2_FAST_ARCHIVE_PENDING','0');
  st2Sync_(false);
  SpreadsheetApp.getUi().alert('Otomatik senkronizasyon etkinleştirildi. Her dakika ve yapılan değişikliklerden sonra güncellenir.');
}
function st2StopLive(){
  ScriptApp.getProjectTriggers().forEach(function(t){if(ST2.handlers.indexOf(t.getHandlerFunction())>=0)ScriptApp.deleteTrigger(t);});
  PropertiesService.getScriptProperties().setProperty('ST2_ENABLED','0');PropertiesService.getScriptProperties().setProperty('ST2_FAST_ARCHIVE_PENDING','0');
  SpreadsheetApp.getUi().alert('Auto Sync durduruldu.');
}
function st2OnEdit(e){if(e&&e.range&&['Home','Hotels'].indexOf(e.range.getSheet().getName())>=0) PropertiesService.getScriptProperties().setProperty('ST2_DIRTY','1');}
function st2OnChange(e){PropertiesService.getScriptProperties().setProperty('ST2_DIRTY','1');}
function st2Tick(){
  var p=PropertiesService.getScriptProperties();
  if(p.getProperty('ST2_ENABLED')!=='1')return;
  var last=Number(p.getProperty('ST2_LAST_SENT_MS')||0);
  // Normal state: avoid unnecessary full snapshots for 5 minutes.
  // An explicit archive awaiting its second signed confirmation: send on
  // every 1-minute trigger so WP can apply it without rescanning 64 rows.
  var pending=Number(p.getProperty('ST2_FAST_ARCHIVE_PENDING')||'0');
  if(p.getProperty('ST2_DIRTY')!=='1' && !pending && Date.now()-last<300000)return;
  st2Sync_(false);
}
function st2SyncNow(){st2Sync_(true);}
function st2Status(){var p=PropertiesService.getScriptProperties();SpreadsheetApp.getUi().alert('Son başarılı işlem: '+(p.getProperty('ST2_LAST_OK')||'Yok')+'\nSon hata: '+(p.getProperty('ST2_LAST_ERROR')||'Yok')+'\nHotel bridge: '+(p.getProperty('ST2_HOTEL_LAST_WARNING')||'OK'));}
function st2Endpoint_(){
  var p=PropertiesService.getScriptProperties(),direct=String(p.getProperty('ST_DIRECT_SYNC_ENDPOINT')||'').trim();
  var custom=String(p.getProperty('ST2_LIVE_ENDPOINT')||'').trim();
  if(custom)return custom;
  var marker='/wp-json/server-turizm/v1/direct-sync';
  if(direct.indexOf(marker)<0)throw new Error('Direct Sync endpoint geçerli değil.');
  return direct.substring(0,direct.indexOf(marker))+ST2.endpointPath;
}
function st2Uuid_(){return Utilities.getUuid().toLowerCase();}
function st2ValidUuid_(v){return /^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/.test(String(v||'').toLowerCase());}
function st2Truthy_(v){return v===true||String(v||'').trim().toLowerCase()==='true'||String(v||'').trim()==='1';}
function st2Hex_(b){return b.map(function(x){return ('0'+((x+256)%256).toString(16)).slice(-2);}).join('');}
function st2Identity_(sh,start,width,uuidIndex,stableIndex){
  var n=Math.max(0,sh.getLastRow()-start+1);if(n>ST2.maxRows)throw new Error('Aşırı uzun sayfa: '+sh.getName());
  if(!n)return [];
  var values=sh.getRange(start,1,n,width).getDisplayValues(),usedU={},usedId={};
  values.forEach(function(row,i){
    var key=sh.getName()==='Home'?String(row[1]||row[3]||'').trim():String(row[0]||'').trim();
    if(!key)return;
    var current=String(row[uuidIndex]||'').trim().toLowerCase();
    var stable=stableIndex>=0?String(row[stableIndex]||'').trim():'';
    var duplicate=!!usedU[current],valid=st2ValidUuid_(current);
    if(!valid||duplicate){
      current=st2Uuid_();sh.getRange(start+i,uuidIndex+1).setValue(current);row[uuidIndex]=current;
      if(duplicate&&stableIndex>=0&&stable){sh.getRange(start+i,stableIndex+1).clearContent();row[stableIndex]='';stable='';}
    }
    if(stable){
      if(usedId[stable]){sh.getRange(start+i,stableIndex+1).clearContent();row[stableIndex]='';}
      else usedId[stable]=true;
    }
    usedU[current]=true;
  });
  return values;
}
function st2Snapshot_(ss){
  var home=ss.getSheetByName(ST2.home),hotels=ss.getSheetByName(ST2.hotels);if(!home||!hotels)throw new Error('Home ve Hotels sayfaları bulunamadı.');
  if(String(home.getRange('AP1').getDisplayValue()).trim()!=='ST Source UUID' || String(home.getRange('AQ1').getDisplayValue()).trim()!=='ST Program ID')throw new Error('Home AP/AQ teknik sütunları değişmiş.');
  if(hotels.getMaxColumns()<6)hotels.insertColumnsAfter(hotels.getMaxColumns(),6-hotels.getMaxColumns());
  var title=String(hotels.getRange('F1').getDisplayValue()).trim();
  if(!title)hotels.getRange('F1').setValue('ST Hotel Row UUID');
  else if(title!=='ST Hotel Row UUID')throw new Error('Hotels!F sütunu kullanılmakta; UUID yerleşimi güvensiz.');
  var a=st2Identity_(home,ST2.homeStart,ST2.homeCols,41,42),b=st2Identity_(hotels,ST2.hotelStart,ST2.hotelCols,5,-1);
  SpreadsheetApp.flush();
  // Re-read after correction; avoid emitting stale duplicate UUIDs.
  if(a.length)a=home.getRange(ST2.homeStart,1,a.length,ST2.homeCols).getDisplayValues();
  if(b.length)b=hotels.getRange(ST2.hotelStart,1,b.length,ST2.hotelCols).getDisplayValues();
  var programs=[],list=[],hotelByName={};
  function hotelKey_(s){return String(s||'').toLowerCase().normalize('NFKD').replace(/[̀-ͯ]/g,'').replace(/[^a-z0-9]/g,'');}
  b.forEach(function(row){var key=hotelKey_(row[0]),id=String(row[6]||'').trim();
    if(!key||!/^STH-\d{6}$/.test(id))return;
    if(hotelByName[key] && hotelByName[key]!==id)hotelByName[key]='';
    else if(!(key in hotelByName))hotelByName[key]=id;
  });
  a.forEach(function(row,i){if(!String(row[1]||row[3]||'').trim())return;
    var fields={};var names=['number','code','tier','title','counter_date','remaining','departure','transfer_1','transfer_2','return_date','nights','other_city','other_hotel','other_hotel_images','other_hotel_map','medinah_hotel','medinah_images','medinah_map','makkah_hotel','makkah_images','makkah_map','price_double','price_triple','price_quad','child_fares','notes','phone','capacity','sold_out','removed','meal_1','meal_2','room_1','room_2','hero','logos','highlight','highlight_show','albaraka_tooltip','albaraka_show','albaraka_logo'];
    names.forEach(function(name,index){if(index!==5)fields[name]=String(row[index]||'').trim();});
    [['medinah_hotel','medinah_hotel_id'],['makkah_hotel','makkah_hotel_id'],['other_hotel','other_hotel_id']].forEach(function(pair){
      fields[pair[1]]=hotelByName[hotelKey_(fields[pair[0]])]||'';
    });
    programs.push({uuid:String(row[41]||'').toLowerCase(),stable_id:String(row[42]||'').trim(),source_row:ST2.homeStart+i,removed:st2Truthy_(row[29]),fields:fields});
  });
  b.forEach(function(row,i){if(!String(row[0]||'').trim())return;
    list.push({uuid:String(row[5]||'').toLowerCase(),source_row:ST2.hotelStart+i,fields:{name:String(row[0]||'').trim(),map:String(row[1]||'').trim(),images:String(row[2]||'').trim(),city:String(row[3]||'').trim(),logo:String(row[4]||'').trim(),wp_hotel_id:String(row[6]||'').trim()}});
  });
  return {contract:ST2.contract,sheet_id:ss.getId(),generated_at:new Date().toISOString(),complete:true,programs:programs,hotels:list};
}
function st2Sync_(show){
  var lock=LockService.getScriptLock();if(!lock.tryLock(2000))return;
  try{
    var p=PropertiesService.getScriptProperties();
    var ss=SpreadsheetApp.openById(p.getProperty('ST2_SHEET_ID')||SpreadsheetApp.getActiveSpreadsheet().getId());
    var hotelBridgeNote=st2PullHotels_(ss);
    var snap=st2Snapshot_(ss),body=JSON.stringify(snap),url=st2Endpoint_(),key=p.getProperty('ST_DIRECT_SYNC_KEY_ID'),secret=p.getProperty('ST_DIRECT_SYNC_SECRET');
    if(body.length>7*1024*1024)throw new Error('Snapshot çok büyük.');
    if(!key||!secret||!/^https:\/\//i.test(url))throw new Error('HMAC / endpoint ayarları eksik.');
    var timestamp=String(Math.floor(Date.now()/1000)),nonce=st2Uuid_().replace(/-/g,'')+st2Uuid_().replace(/-/g,'').substring(0,12);
    var digest=st2Hex_(Utilities.computeDigest(Utilities.DigestAlgorithm.SHA_256,body,Utilities.Charset.UTF_8));
    var signature=st2Hex_(Utilities.computeHmacSha256Signature('ST-DIRECT-SYNC-1\n'+timestamp+'\n'+nonce+'\n'+digest,secret,Utilities.Charset.UTF_8));
    var res=UrlFetchApp.fetch(url,{method:'post',contentType:'application/json',payload:body,muteHttpExceptions:true,
      headers:{'X-ST-Sync-Timestamp':timestamp,'X-ST-Sync-Nonce':nonce,'X-ST-Sync-Key-Id':key,'X-ST-Sync-Signature':signature}});
    var parsed;try{parsed=JSON.parse(res.getContentText())}catch(e){throw new Error('Server JSON invalid: HTTP '+res.getResponseCode());}
    if(res.getResponseCode()!==200||!parsed.ok)throw new Error('Sync HTTP '+res.getResponseCode()+': '+res.getContentText().slice(0,350));
    if(parsed.receipts&&parsed.receipts.length){
      var sh=ss.getSheetByName('Home'),rows=Math.max(0,sh.getLastRow()-2),ids=rows?sh.getRange(3,42,rows,2).getDisplayValues():[],position={};
      ids.forEach(function(r,i){position[r[0]]=i+3;});
      parsed.receipts.forEach(function(r){var target=position[r.uuid];if(target&&r.stable_id&&String(sh.getRange(target,43).getDisplayValue()||'').trim()==='')sh.getRange(target,43).setValue(r.stable_id);});
    }
    p.setProperty('ST2_FAST_ARCHIVE_PENDING',String(Math.max(0,Number(parsed.fast_archive_pending)||0)));
    p.setProperty('ST2_LAST_SENT_MS',String(Date.now()));p.setProperty('ST2_LAST_OK',new Date().toISOString());p.deleteProperty('ST2_LAST_ERROR');p.deleteProperty('ST2_DIRTY');
    if(hotelBridgeNote)p.setProperty('ST2_HOTEL_LAST_WARNING',hotelBridgeNote);else p.deleteProperty('ST2_HOTEL_LAST_WARNING');
    if(show)SpreadsheetApp.getUi().alert('Sync kabul edildi. Programlar: '+snap.programs.length+' | Oteller: '+snap.hotels.length+'\nİşlem WordPress kuyruğunda devam eder.');
    return parsed;
  }catch(e){
    PropertiesService.getScriptProperties().setProperty('ST2_LAST_ERROR',String(e.message||e).slice(0,1400));
    if(show)SpreadsheetApp.getUi().alert('Sync hatası: '+e.message);
    else Logger.log('ST2 error: '+e.message);
    throw e;
  }finally{lock.releaseLock();}
}

/**
 * v2.7 upgrade helper: after replacing the existing single-file ST2 source,
 * run ONCE to mark the next one-minute tick dirty. Existing installed triggers,
 * HMAC secrets, Script Properties and bound spreadsheet remain unchanged.
 */
function st2UpgradeFastArchive(){
  var p=PropertiesService.getScriptProperties();
  if(p.getProperty('ST2_ENABLED')!=='1')throw new Error('Önce var olan otomatik senkronizasyon etkin olmalıdır.');
  p.setProperty('ST2_FAST_ARCHIVE_PENDING','1');
  p.setProperty('ST2_DIRTY','1');
  st2Sync_(true);
}



/**
 * 2.9: read the Hotel Intelligence catalog over signed GET, then add only
 * missing WP hotels to Hotels!A:H and bind existing unique names in G/H.
 * G/H are reserved for WP Hotel ID and public page. The historical A:F data
 * (especially photos, maps, and row UUID) is never overwritten.
 * No Hotel Intelligence field is edited through this bridge.
 */
function st2PullHotels_(ss){
  var p=PropertiesService.getScriptProperties(),sh=ss.getSheetByName('Hotels');
  if(!sh)return 'Hotels tab missing';
  if(sh.getMaxColumns()<8)sh.insertColumnsAfter(sh.getMaxColumns(),8-sh.getMaxColumns());
  var header=sh.getRange('G1:H1').getDisplayValues()[0];
  if((header[0]&&header[0]!=='WP Hotel ID')||(header[1]&&header[1]!=='WP Hotel URL'))return 'G/H columns are already used; no Hotel import';
  var baseUrl=st2Endpoint_().replace(/\/live\/?$/,'/hotel-directory');
  if(baseUrl===st2Endpoint_())return 'Could not derive signed hotel directory URL';
  var key=p.getProperty('ST_DIRECT_SYNC_KEY_ID'),secret=p.getProperty('ST_DIRECT_SYNC_SECRET');
  if(!key||!secret)return 'Direct Sync credentials are not configured';
  var ts=String(Math.floor(Date.now()/1000)),nonce=st2Uuid_().replace(/-/g,'')+st2Uuid_().replace(/-/g,'').substring(0,12);
  var digest=st2Hex_(Utilities.computeDigest(Utilities.DigestAlgorithm.SHA_256,'',Utilities.Charset.UTF_8));
  var sig=st2Hex_(Utilities.computeHmacSha256Signature('ST-DIRECT-SYNC-1\n'+ts+'\n'+nonce+'\n'+digest,secret,Utilities.Charset.UTF_8));
  var response;
  try{
    response=UrlFetchApp.fetch(baseUrl,{method:'get',muteHttpExceptions:true,headers:{
      'X-ST-Sync-Timestamp':ts,'X-ST-Sync-Nonce':nonce,'X-ST-Sync-Key-Id':key,'X-ST-Sync-Signature':sig
    }});
  }catch(e){return 'Hotel catalog request failed: '+e.message;}
  if(response.getResponseCode()!==200)return 'Hotel catalog HTTP '+response.getResponseCode();
  var parsed;try{parsed=JSON.parse(response.getContentText());}catch(e){return 'Invalid hotel directory JSON';}
  if(!parsed||parsed.ok!==true||!Array.isArray(parsed.hotels))return 'Invalid hotel directory format';
  var list=parsed.hotels;
  if(list.length>500)return 'Hotel directory exceeds 500 records';
  var count=Math.max(0,sh.getLastRow()-1),data=count?sh.getRange(2,1,count,8).getDisplayValues():[];
  var byID={},byName={},bad=[];
  function keyName(s){return String(s||'').toLowerCase().normalize('NFKD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]/g,'');}
  data.forEach(function(r,i){
    var name=keyName(r[0]),id=String(r[6]||'').trim();
    if(id){if(byID[id]!==undefined)bad.push('duplicate Sheet ID '+id);else byID[id]=i;}
    if(name){if(byName[name]!==undefined)byName[name]=-1;else byName[name]=i;}
  });
  if(bad.length)return bad.join('; ');
  var existingKeys={},added=[];
  list.forEach(function(h){
    var id=String(h.hotel_id||''),name=String(h.name||'').trim();
    if(!/^STH-\d{6}$/.test(id)||!name||existingKeys[id])return;
    existingKeys[id]=true;
    var idx=byID[id];
    if(idx===undefined){
      var aliases=Array.isArray(h.aliases)?h.aliases:[name];
      var idxs={};aliases.forEach(function(a){var q=keyName(a);if(q&&byName[q]!==undefined&&byName[q]>=0)idxs[byName[q]]=true;});
      var choices=Object.keys(idxs);
      if(aliases.some(function(a){var k=keyName(a);return k && byName[k]===-1;})){bad.push('duplicate sheet hotel name '+id);return;}
      if(choices.length>1){bad.push('ambiguous hotel '+id);return;}
      if(choices.length===1){idx=Number(choices[0]);if(data[idx][6]&&data[idx][6]!==id){bad.push('conflicting hotel '+id);return;}}
    }
    if(idx===undefined){
      var destination=String(h.city||'');if(/^cairo$/i.test(destination))destination='Kahirah';
      var line=[name,String(h.map_embed||''),String(h.primary_image||''),destination,'',st2Uuid_(),id,String(h.url||'')];
      var at=sh.getLastRow()+1;
      if(at>ST2.maxRows+1){bad.push('too many hotels');return;}
      sh.getRange(at,1,1,8).setValues([line]);
      data.push(line);idx=data.length-1;
      added.push(id);
      var k=keyName(name);if(k)byName[k]=idx;
    }else{
      var current=data[idx];
      if(!current[6]){sh.getRange(idx+2,7).setValue(id);current[6]=id;}
      if(!current[7]&&h.url){sh.getRange(idx+2,8).setValue(String(h.url));current[7]=String(h.url);}
    }
    byID[id]=idx;
  });
  if(!header[0])sh.getRange('G1').setValue('WP Hotel ID');
  if(!header[1])sh.getRange('H1').setValue('WP Hotel URL');
  if(added.length)p.setProperty('ST2_DIRTY','1');
  try{st2RefreshHotelDropdowns_(ss,list);}catch(e){bad.push('Hotel dropdown refresh: '+e.message);}
  return bad.length?bad.join('; '):'';
}
/**
 * Home uses static ONE_OF_LIST hotel validations, NOT range-backed formulas.
 * Extend existing options; keep legacy region/placeholders and other choices.
 * This writes only validation rules (never the selected program values).
 */
function st2RefreshHotelDropdowns_(ss,hotels){
  if(typeof SpreadsheetApp==='undefined' || typeof SpreadsheetApp.newDataValidation!=='function')return;
  var home=ss.getSheetByName('Home');if(!home)return;
  var groups=[{col:16,city:'madinah'},{col:19,city:'makkah'},{col:13,city:'cairo'}];
  groups.forEach(function(g){
    var seed=home.getRange(3,g.col).getDataValidation();
    if(!seed || seed.getCriteriaType()!==SpreadsheetApp.DataValidationCriteria.VALUE_IN_LIST)return;
    var old=seed.getCriteriaValues()[0]||[],names=old.slice(),keys={};
    function norm(x){return String(x||'').toLowerCase().normalize('NFKD').replace(/[\u0300-\u036f]/g,'').replace(/[^a-z0-9]/g,'');}
    names.forEach(function(name){keys[norm(name)]=true;});
    var changed=false;
    hotels.forEach(function(h){
      var city=String(h.city||'').toLowerCase();
      if(g.city==='cairo')city=city==='kahira'||city==='kahirah'?'cairo':city;
      if(city!==g.city)return;
      var name=String(h.name||'').trim(),key=norm(name);
      if(!name||!key||keys[key])return;
      keys[key]=true;names.push(name);changed=true;
    });
    if(!changed)return;
    if(names.length>300)throw new Error('Too many hotel dropdown values in column '+g.col);
    var builder=SpreadsheetApp.newDataValidation().requireValueInList(names,true).setAllowInvalid(false);
    if(seed.getHelpText && seed.getHelpText())builder.setHelpText(seed.getHelpText());
    home.getRange(3,g.col,Math.max(1,home.getMaxRows()-2),1).setDataValidation(builder.build());
  });
}