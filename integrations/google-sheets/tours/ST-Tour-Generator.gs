/**
 * Server Turizm — Tour Control Panel generator.
 * Independent Apps Script stack (NO Umrah functions).
 * Contract: STTI-TOUR-IMPORT-1.0.0
 */
var ST_TOUR = Object.freeze({
  VERSION: '1.0.2',
  PRODUCER_VERSION: '0.6.2.1',
  IMPORT_CONTRACT: 'STTI-TOUR-IMPORT-1.0.0',
  HEADER_ROW: 2,
  FIRST_DATA_ROW: 3,
  TECH_STABLE_ID_HEADER: 'STTI Stable ID',
  TECH_CHECKSUM_HEADER: 'Expected Checksum'
});

function stTourPrepareTechnicalColumns() {
  var tech=stTourEnsureTechnicalColumns_();
  SpreadsheetApp.getActiveSpreadsheet().toast(
    stTourColumnLetter_(tech.stable)+'/'+stTourColumnLetter_(tech.checksum)+' teknik kolonları hazırlandı ve gizlendi.',
    'Tour',
    4
  );
}

function stTourEnsureTechnicalColumns_() {
  var sheet=SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  var last=Math.max(sheet.getLastColumn(),1);
  var headers=sheet.getRange(ST_TOUR.HEADER_ROW,1,1,last).getDisplayValues()[0];
  var stable=0, checksum=0;

  for(var i=0;i<headers.length;i++){
    var h=String(headers[i]||'').trim();
    if(h===ST_TOUR.TECH_STABLE_ID_HEADER) stable=i+1;
    if(h===ST_TOUR.TECH_CHECKSUM_HEADER) checksum=i+1;
  }

  if((stable&&!checksum)||(!stable&&checksum)){
    throw new Error('STTI teknik kolonlarından yalnız biri bulundu. Stable ID ve Expected Checksum birlikte olmalı.');
  }

  if(stable&&checksum){
    if(checksum!==stable+1){
      throw new Error('STTI teknik kolonları bitişik değil. Mevcut kolonlara dokunulmadı.');
    }
    sheet.hideColumns(stable,2);
    return {stable:stable,checksum:checksum};
  }

  // Never reuse or overwrite visible business columns (e.g. Vize).
  // Append technical controls after the current last used column.
  stable=last+1;
  checksum=last+2;
  if(sheet.getMaxColumns()<checksum){
    sheet.insertColumnsAfter(sheet.getMaxColumns(),checksum-sheet.getMaxColumns());
  }
  sheet.getRange(ST_TOUR.HEADER_ROW,stable,1,2)
    .setValues([[ST_TOUR.TECH_STABLE_ID_HEADER,ST_TOUR.TECH_CHECKSUM_HEADER]]);
  sheet.hideColumns(stable,2);
  return {stable:stable,checksum:checksum};
}

function stTourColumnLetter_(column) {
  var n=Number(column||0), out='';
  while(n>0){
    var r=(n-1)%26;
    out=String.fromCharCode(65+r)+out;
    n=Math.floor((n-1)/26);
  }
  return out||'?';
}

function stTourLinkSelectedRow() {
  stTourEnsureTechnicalColumns_();
  var ui=SpreadsheetApp.getUi();
  var sheet=SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  var row=sheet.getActiveRange().getRow();
  if(row<ST_TOUR.FIRST_DATA_ROW)throw new Error('Bir tur satırı seç.');

  var title=sheet.getRange(row,4).getDisplayValue().trim();
  if(!title)throw new Error('Tur Adı boş.');

  var idPrompt=ui.prompt('STTI kaydına bağla',title+'\n\nStable ID (STT-000001):',ui.ButtonSet.OK_CANCEL);
  if(idPrompt.getSelectedButton()!==ui.Button.OK)return;
  var stableId=idPrompt.getResponseText().trim().toUpperCase();
  if(!/^STT-\d{6}$/.test(stableId))throw new Error('Stable ID geçersiz.');

  var checksumPrompt=ui.prompt('Expected Checksum','64 karakter SHA-256:',ui.ButtonSet.OK_CANCEL);
  if(checksumPrompt.getSelectedButton()!==ui.Button.OK)return;
  var checksum=checksumPrompt.getResponseText().trim().toLowerCase();
  if(!/^[a-f0-9]{64}$/.test(checksum))throw new Error('Checksum geçersiz.');

  var tech=stTourEnsureTechnicalColumns_();
  sheet.getRange(row,tech.stable,1,2).setValues([[stableId,checksum]]);
  sheet.hideColumns(tech.stable,2);
  ui.alert('Tour',stableId+' bağlandı.',ui.ButtonSet.OK);
}

function stTourClearSelectedRowLink() {
  stTourEnsureTechnicalColumns_();
  var ui=SpreadsheetApp.getUi();
  var sheet=SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  var row=sheet.getActiveRange().getRow();
  if(row<ST_TOUR.FIRST_DATA_ROW)throw new Error('Bir tur satırı seç.');
  var tech=stTourEnsureTechnicalColumns_();
  if(ui.alert('STTI bağını temizle',stTourColumnLetter_(tech.stable)+':'+stTourColumnLetter_(tech.checksum)+' teknik bağlantı temizlensin mi?',ui.ButtonSet.YES_NO)!==ui.Button.YES)return;
  sheet.getRange(row,tech.stable,1,2).clearContent();
  sheet.hideColumns(tech.stable,2);
}

function stTourValidateSelectedRowLocal() {
  try{
    var result=stTourBuildSelectedRow_();
    var target=result.payload.target.stable_id||'NEW';
    var warning=result.warnings.length?'\n\nUyarılar:\n• '+result.warnings.join('\n• '):'\n\nUyarı yok.';
    SpreadsheetApp.getUi().alert(
      'Tour Partial JSON',
      'SATIR UYGUN ✅\n\nTur: '+result.title+'\nSatır: '+result.row+'\nTarget: '+target+warning,
      SpreadsheetApp.getUi().ButtonSet.OK
    );
  }catch(e){
    SpreadsheetApp.getUi().alert('Tour Hata',String(e&&e.message?e.message:e),SpreadsheetApp.getUi().ButtonSet.OK);
  }
}

function stTourShowSelectedJson() {
  var result=stTourBuildSelectedRow_();
  var jsonText=JSON.stringify(result.payload,null,2);
  var b64=Utilities.base64Encode(jsonText,Utilities.Charset.UTF_8);
  var html=HtmlService.createHtmlOutput(stTourJsonDialogHtml_(b64,result.fileName,false)).setWidth(900).setHeight(650);
  SpreadsheetApp.getUi().showModalDialog(html,'Tour Partial JSON');
}

function stTourDownloadSelectedJson() {
  var result=stTourBuildSelectedRow_();
  var jsonText=JSON.stringify(result.payload,null,2);
  var b64=Utilities.base64Encode(jsonText,Utilities.Charset.UTF_8);
  var html=HtmlService.createHtmlOutput(stTourJsonDialogHtml_(b64,result.fileName,true)).setWidth(560).setHeight(260);
  SpreadsheetApp.getUi().showModalDialog(html,'Tour JSON İndir');
}

function stTourBuildSelectedRow_() {
  var tech=stTourEnsureTechnicalColumns_();
  var ss=SpreadsheetApp.getActiveSpreadsheet();
  var sheet=ss.getActiveSheet();
  var row=sheet.getActiveRange().getRow();
  if(row<ST_TOUR.FIRST_DATA_ROW)throw new Error('Bir tur satırı seç.');

  var lastColumn=Math.max(sheet.getLastColumn(),tech.checksum);
  var headers=sheet.getRange(ST_TOUR.HEADER_ROW,1,1,lastColumn).getDisplayValues()[0];
  var values=sheet.getRange(row,1,1,lastColumn).getValues()[0];
  var display=sheet.getRange(row,1,1,lastColumn).getDisplayValues()[0];
  var rowData=stTourRowObject_(headers,values,display);
  var title=stTourText_(stTourGet_(rowData,'Tur Adı'));
  if(!title)throw new Error('Tur Adı boş.');

  var warnings=[];
  var sourceId='SRC-SHEET-'+row;
  var stableId=stTourText_(stTourGet_(rowData,ST_TOUR.TECH_STABLE_ID_HEADER)).toUpperCase();
  var expected=stTourText_(stTourGet_(rowData,ST_TOUR.TECH_CHECKSUM_HEADER)).toLowerCase();

  if(stableId&&!/^STT-\d{6}$/.test(stableId))throw new Error('STTI Stable ID formatı geçersiz.');
  if(expected&&!/^[a-f0-9]{64}$/.test(expected))throw new Error('Expected Checksum geçersiz.');
  if((stableId&&!expected)||(!stableId&&expected))throw new Error('Stable ID ve checksum birlikte olmalı.');

  var payload={
    import_contract:ST_TOUR.IMPORT_CONTRACT,
    mode:'partial',
    policy:{
      facts:'source_only_no_invention',
      missing_values:'omit_or_null_or_explicit_unknown',
      publication:'force_private'
    },
    producer:{
      type:'google_sheet',
      name:ss.getName(),
      version:ST_TOUR.PRODUCER_VERSION,
      generated_at:new Date().toISOString()
    },
    target:{
      stable_id:stableId||null,
      expected_checksum_sha256:expected||null
    },
    sources:[{
      source_id:sourceId,
      type:'google_sheet',
      ref:ss.getUrl()+'#gid='+sheet.getSheetId()+'&range=A'+row+':Y'+row,
      label:title+' — Google Sheets satırı '+row,
      language:'tr-TR',
      completeness:'source_partial',
      notes:stTourSourceNotes_(rowData)
    }],
    tour:{
      identity:{public_title:title,language:'tr-TR'},
      lifecycle:{editorial:'needs_review',schedule:'tentative',availability:'open'},
      provenance:{
        primary_source_id:sourceId,
        source_completeness:'source_partial',
        notes:'Google Sheets satırından otomatik PARTIAL JSON. Eksik bilgiler AI/human review aşamasında tamamlanmalıdır.'
      }
    }
  };

  var programNo=stTourText_(stTourGet_(rowData,'Program No'));
  if(programNo)payload.tour.identity.tour_code=programNo;

  var destination=stTourText_(stTourGet_(rowData,'Ülke / Hedef'));
  var cities=stTourSplitList_(stTourGet_(rowData,'Şehirler'));
  var extraCountries=stTourSplitList_(stTourGet_(rowData,'Ek Ülkeler'));
  if(destination||cities.length||extraCountries.length){
    payload.tour.destinations={};
    if(destination){
      payload.tour.destinations.primary_country=destination;
      payload.tour.destinations.countries=stTourUnique_([destination].concat(extraCountries));
    }else if(extraCountries.length)payload.tour.destinations.countries=extraCountries;
    if(cities.length){
      payload.tour.destinations.primary_city=cities[0];
      payload.tour.destinations.cities=cities;
    }
  }

  var startDate=stTourDate_(stTourGetRaw_(rowData,'Gidiş'));
  var endDate=stTourDate_(stTourGetRaw_(rowData,'Dönüş'));
  if(startDate&&endDate){
    if(endDate.getTime()<startDate.getTime())throw new Error('Dönüş tarihi Gidiş tarihinden önce.');
    var nights=Math.round((stTourUtcMidnight_(endDate)-stTourUtcMidnight_(startDate))/86400000);
    payload.tour.date={
      mode:'exact',
      precision:'exact',
      start_date:stTourIsoDate_(startDate,ss.getSpreadsheetTimeZone()),
      end_date:stTourIsoDate_(endDate,ss.getSpreadsheetTimeZone()),
      duration_days:nights+1,
      duration_nights:nights
    };
    payload.tour.lifecycle.schedule='scheduled';
  }else if(startDate||endDate)warnings.push('Gidiş/Dönüş tarihlerinden biri eksik.');

  var routeSummary=stTourText_(stTourGet_(rowData,'Rota'));
  var routeTokens=stTourSplitRoute_(routeSummary||stTourText_(stTourGet_(rowData,'Şehirler')));
  if(routeSummary||routeTokens.length){
    payload.tour.route={};
    if(routeSummary)payload.tour.route.summary=routeSummary;
    if(routeTokens.length){
      var cityLookup={};
      cities.forEach(function(city){cityLookup[stTourNormalizeKey_(city)]=city;});
      payload.tour.route.stops=routeTokens.map(function(label,idx){
        var stop={stop_id:'R'+(idx+1),type:'stop',label:label,source_ids:[sourceId]};
        var matched=cityLookup[stTourNormalizeKey_(label)];
        if(matched)stop.city=matched;
        return stop;
      });
    }
  }

  var hotelNames=stTourSplitHotel_(stTourGet_(rowData,'Otel(ler)'));
  var hotelPlan=stTourText_(stTourGet_(rowData,'Otel / Gece Planı'));
  if(hotelNames.length){
    payload.tour.stays={hotels:hotelNames.map(function(name,idx){
      var h={relation_id:'H'+(idx+1),mode:'unresolved',unresolved_name:name,source_ids:[sourceId]};
      if(hotelPlan)h.note=hotelPlan;
      return h;
    })};
  }else if(hotelPlan)warnings.push('Otel / Gece Planı var fakat Otel(ler) boş.');

  var transportRaw=stTourText_(stTourGet_(rowData,'Ulaşım'));
  var airline=stTourText_(stTourGet_(rowData,'Havayolu'));
  var transferNote=stTourText_(stTourGet_(rowData,'Uçuş / Transfer Notu'));
  if(transportRaw||airline||transferNote){
    var transportType=stTourTransportType_(transportRaw);
    var seg={segment_id:'T1',type:transportType,source_ids:[sourceId]};
    if(airline)seg.provider=airline;
    var noteParts=[];
    if(transportRaw&&transportType==='other')noteParts.push('Ulaşım: '+transportRaw);
    if(transferNote)noteParts.push(transferNote);
    if(noteParts.length)seg.note=noteParts.join(' | ');
    payload.tour.transport={segments:[seg]};
  }

  var currency=stTourText_(stTourGet_(rowData,'Para Birimi')).toUpperCase();
  var priceDefs=[
    ['2 Kişilik','2 kişilik'],
    ['3 Kişilik','3 kişilik'],
    ['4 Kişilik','4 kişilik'],
    ['Çocuk','çocuk']
  ];
  var priceItems=[];
  priceDefs.forEach(function(def){
    var parsed=stTourParsePrice_(stTourGetRaw_(rowData,def[0]));
    if(!parsed.hasValue)return;
    if(!currency)throw new Error(def[0]+' fiyatı var fakat Para Birimi boş.');
    if(['EUR','USD','TRY','GBP'].indexOf(currency)===-1)throw new Error('Para Birimi desteklenmiyor: '+currency);
    var item={
      price_id:'P'+(priceItems.length+1),
      label:def[0]+' fiyatı',
      type:parsed.type,
      amount:parsed.amount,
      currency:currency,
      basis:'unknown',
      occupancy:def[1],
      source_ids:[sourceId]
    };
    if(parsed.note)item.note=parsed.note;
    priceItems.push(item);
  });
  if(priceItems.length){
    var primary=priceItems[0];
    payload.tour.pricing={
      type:primary.type,
      amount:primary.amount,
      currency:primary.currency,
      basis:'unknown',
      items:priceItems
    };
    warnings.push('Fiyat basis Sheet’te ayrı alan olmadığı için UNKNOWN bırakıldı.');
  }

  var soldOutRaw=stTourGetRaw_(rowData,'Dolu');
  if(soldOutRaw===true||stTourTruthyText_(soldOutRaw))payload.tour.lifecycle.availability='sold_out';

  var visa=stTourVisaStatus_(stTourGetRaw_(rowData,'Vize'));
  payload.tour.requirements={visa_status:visa.status,items:[]};
  if(visa.note)payload.tour.requirements.visa_notes=visa.note;

  stTourPrune_(payload.tour);
  payload.tour.identity.public_title=title;
  payload.tour.identity.language='tr-TR';
  payload.tour.lifecycle=payload.tour.lifecycle||{editorial:'needs_review',schedule:'tentative',availability:'open'};
  payload.tour.provenance=payload.tour.provenance||{primary_source_id:sourceId,source_completeness:'source_partial'};

  return {
    payload:payload,
    warnings:warnings,
    row:row,
    title:title,
    fileName:stTourSafeFileName_(programNo||title)+'.partial.json'
  };
}

function stTourRowObject_(headers,values,displayValues){
  var obj={};
  headers.forEach(function(header,idx){
    var key=stTourText_(header);
    if(!key)return;
    obj[key]={raw:values[idx],display:displayValues[idx]};
  });
  return obj;
}
function stTourGet_(rowData,header){return rowData[header]?rowData[header].display:'';}
function stTourGetRaw_(rowData,header){return rowData[header]?rowData[header].raw:'';}
function stTourText_(value){return value===null||value===undefined?'':String(value).trim();}
function stTourNormalizeKey_(value){return stTourText_(value).toLocaleLowerCase('tr-TR');}
function stTourSplitList_(value){
  var s=stTourText_(value);if(!s)return[];
  return stTourUnique_(s.split(/\r?\n|\||;|,|·|→|->/).map(function(x){return x.trim();}).filter(Boolean));
}
function stTourSplitRoute_(value){
  var s=stTourText_(value);if(!s)return[];
  return stTourUnique_(s.split(/\r?\n|→|->|·|\||;|,/).map(function(x){return x.trim();}).filter(Boolean));
}
function stTourSplitHotel_(value){
  var s=stTourText_(value);if(!s)return[];
  return stTourUnique_(s.split(/\r?\n|\||;|,/).map(function(x){return x.trim();}).filter(Boolean));
}
function stTourUnique_(arr){
  var seen={};
  return arr.filter(function(x){var k=String(x).toLocaleLowerCase('tr-TR');if(seen[k])return false;seen[k]=true;return true;});
}
function stTourDate_(value){
  if(Object.prototype.toString.call(value)==='[object Date]'&&!isNaN(value.getTime()))return value;
  return null;
}
function stTourUtcMidnight_(date){return Date.UTC(date.getFullYear(),date.getMonth(),date.getDate());}
function stTourIsoDate_(date,tz){return Utilities.formatDate(date,tz||Session.getScriptTimeZone(),'yyyy-MM-dd');}
function stTourTransportType_(raw){
  var s=stTourText_(raw).toLocaleLowerCase('tr-TR');
  if(/uçak|flight/.test(s))return'flight';
  if(/tren|train/.test(s))return'train';
  if(/otobüs|otobus|coach|bus/.test(s))return'coach';
  if(/minibüs|minibus/.test(s))return'minibus';
  if(/özel araç|ozel arac|private/.test(s))return'private_vehicle';
  if(/feribot|ferry/.test(s))return'ferry';
  if(/cruise|kruvaziyer|gemi/.test(s))return'cruise';
  if(/yürü|yuru|walking/.test(s))return'walking';
  return'other';
}
function stTourParsePrice_(raw){
  if(raw===null||raw===undefined||raw==='')return{hasValue:false};
  if(typeof raw==='number'&&isFinite(raw))return{hasValue:true,type:'exact',amount:raw};
  var s=stTourText_(raw);if(!s)return{hasValue:false};
  var lower=s.toLocaleLowerCase('tr-TR');
  if(/talep|request/.test(lower))return{hasValue:true,type:'on_request',amount:null,note:s};
  var normalized=s.replace(/\s/g,'').replace(/\.(?=\d{3}(?:\D|$))/g,'').replace(',','.');
  var m=normalized.match(/\d+(?:\.\d+)?/);
  if(!m)throw new Error('Fiyat okunamadı: '+s);
  var type=/(dan|den)\s*başlayan|başlangıç|from/i.test(s)?'from':'exact';
  return{hasValue:true,type:type,amount:Number(m[0]),note:type==='from'?s:null};
}
function stTourVisaStatus_(raw){
  if(raw===true)return{status:'required',note:'Sheet Vize alanı işaretli.'};
  if(raw===false||raw===null||raw===undefined||raw==='')return{status:'unknown'};
  var s=stTourText_(raw).toLocaleLowerCase('tr-TR');
  if(/gerekli değil|gerekli degil|not required/.test(s))return{status:'not_required'};
  if(/gerekli|required/.test(s))return{status:'required'};
  if(/şart|sart|conditional/.test(s))return{status:'conditional',note:stTourText_(raw)};
  return{status:'unknown',note:stTourText_(raw)};
}
function stTourTruthyText_(raw){
  var s=stTourText_(raw).toLocaleLowerCase('tr-TR');
  return['evet','yes','dolu','sold out','sold_out'].indexOf(s)!==-1;
}
function stTourSourceNotes_(rowData){
  var notes=[];
  var type=stTourText_(stTourGet_(rowData,'Tür'));
  var capacity=stTourText_(stTourGet_(rowData,'Kontenjan Sayısı'));
  if(type)notes.push('Tür: '+type);
  if(capacity)notes.push('Kontenjan Sayısı: '+capacity);
  notes.push('Kaynak: seçili Google Sheets satırı. Bu çıktı PARTIAL JSON’dur.');
  return notes.join(' | ');
}
function stTourPrune_(obj){
  if(!obj||typeof obj!=='object')return obj;
  Object.keys(obj).forEach(function(key){
    var value=obj[key];
    if(value&&typeof value==='object'&&!Array.isArray(value)){
      stTourPrune_(value);
      if(Object.keys(value).length===0)delete obj[key];
    }else if(Array.isArray(value)){
      value.forEach(function(v){if(v&&typeof v==='object')stTourPrune_(v);});
      if(value.length===0&&key!=='items')delete obj[key];
    }else if(value===''||value===undefined){
      delete obj[key];
    }
  });
  return obj;
}
function stTourSafeFileName_(text){
  return (stTourText_(text)||'tour').toLocaleLowerCase('tr-TR')
    .replace(/ı/g,'i').replace(/ğ/g,'g').replace(/ü/g,'u').replace(/ş/g,'s').replace(/ö/g,'o').replace(/ç/g,'c')
    .replace(/[^a-z0-9]+/g,'-').replace(/^-+|-+$/g,'')||'tour';
}
function stTourJsonDialogHtml_(base64Json,fileName,autoDownload){
  var auto=autoDownload?'true':'false';
  return '<!doctype html><html><head><base target="_top"><style>'+ 
    'body{font-family:Arial;margin:18px;color:#172033}.bar{display:flex;gap:8px;margin-bottom:12px}'+
    'button{border:0;border-radius:8px;padding:10px 14px;cursor:pointer;font-weight:700}textarea{width:100%;height:500px;box-sizing:border-box;font-family:monospace;font-size:12px}'+
    '</style></head><body><div class="bar"><button onclick="copyJson()">JSON Kopyala</button><button onclick="downloadJson()">.json İndir</button></div>'+ 
    '<textarea id="json" readonly></textarea><script>'+ 
    'const b64='+JSON.stringify(base64Json)+';const fileName='+JSON.stringify(fileName)+';'+
    'const bytes=Uint8Array.from(atob(b64),c=>c.charCodeAt(0));const json=new TextDecoder("utf-8").decode(bytes);document.getElementById("json").value=json;'+
    'async function copyJson(){try{await navigator.clipboard.writeText(json);}catch(e){const t=document.getElementById("json");t.select();document.execCommand("copy");}}'+
    'function downloadJson(){const blob=new Blob([json],{type:"application/json;charset=utf-8"});const url=URL.createObjectURL(blob);const a=document.createElement("a");a.href=url;a.download=fileName;document.body.appendChild(a);a.click();a.remove();setTimeout(()=>URL.revokeObjectURL(url),1000);}'+
    'if('+auto+'){setTimeout(downloadJson,250);}</script></body></html>';
}
