(function(){
  'use strict';
  var cfg=window.STTI_V071_GEO||{records:[]};
  var existing={}; (cfg.records||[]).forEach(function(r){if(r&&r.stop_ref)existing[r.stop_ref]=r;});

  function esc(v){return String(v==null?'':v).replace(/[&<>"']/g,function(c){return({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[c];});}
  function parseStops(){
    var input=document.querySelector('[name="route_stops_json"]'); if(!input)return[];
    try{var rows=JSON.parse(input.value||'[]');return Array.isArray(rows)?rows:[];}catch(e){return[];}
  }
  function ensureHidden(form){var h=form.querySelector('[name="route_geo_json"]');if(!h){h=document.createElement('input');h.type='hidden';h.name='route_geo_json';form.appendChild(h);}return h;}
  function rowData(row){
    function val(sel){var e=row.querySelector(sel);return e?e.value:'';}
    return {stop_ref:row.getAttribute('data-stop-ref')||'',latitude:val('[data-f="lat"]'),longitude:val('[data-f="lng"]'),source_type:val('[data-f="source_type"]'),source_ref:val('[data-f="source_ref"]'),review_status:val('[data-f="review_status"]'),review_note:val('[data-f="review_note"]')};
  }
  function sync(panel,hidden){hidden.value=JSON.stringify(Array.prototype.map.call(panel.querySelectorAll('.stti-v071-geo-row'),rowData));}
  function render(){
    var action=document.querySelector('input[name="action"][value="stti_save_candidate"]');
    var form=action? action.form : null; if(!form)return;
    var hidden=ensureHidden(form), stops=parseStops();
    var panel=document.getElementById('stti-v071-geo-panel');
    if(!panel){panel=document.createElement('section');panel.id='stti-v071-geo-panel';panel.className='stti-panel stti-v071-geo-panel';form.appendChild(panel);}
    var html='<div class="stti-panel-head"><div><span class="stti-kicker">v0.7.1 · CANONICAL GEO</span><h2>Route Stop Coordinates</h2><p>Only source-backed, human-confirmed coordinates are renderer-authoritative. No browser geocoding.</p></div><span class="stti-badge stti-badge-gold">PRIVATE</span></div>';
    if(!stops.length){html+='<p>No route stops yet. Geo remains unresolved.</p>';panel.innerHTML=html;hidden.value='[]';return;}
    html+='<div class="stti-v071-geo-grid">';
    stops.forEach(function(s,i){var ref=String(s.stop_id||'').trim(), old=existing[ref]||{}, name=String(s.city||s.label||('Stop '+(i+1)));html+='<article class="stti-v071-geo-row" data-stop-ref="'+esc(ref)+'"><header><b>'+String(i+1).padStart(2,'0')+' · '+esc(name)+'</b><code>'+(ref?esc(ref):'MISSING stop_id')+'</code></header>'+
      '<div class="stti-v071-fields"><label>Latitude<input data-f="lat" inputmode="decimal" value="'+esc(old.latitude==null?'':old.latitude)+'" '+(ref?'':'disabled')+'></label><label>Longitude<input data-f="lng" inputmode="decimal" value="'+esc(old.longitude==null?'':old.longitude)+'" '+(ref?'':'disabled')+'></label>'+
      '<label>Source Type<select data-f="source_type" '+(ref?'':'disabled')+'>'+['unknown','source','manual_verified','external_reference','hotel_intelligence'].map(function(x){return '<option value="'+x+'" '+((old.source_type||'unknown')===x?'selected':'')+'>'+x+'</option>';}).join('')+'</select></label>'+
      '<label>Source Ref<input data-f="source_ref" value="'+esc(old.source_ref||'')+'" '+(ref?'':'disabled')+'></label>'+
      '<label>Review<select data-f="review_status" '+(ref?'':'disabled')+'>'+['pending','confirmed','rejected'].map(function(x){return '<option value="'+x+'" '+((old.review_status||'pending')===x?'selected':'')+'>'+x+'</option>';}).join('')+'</select></label>'+
      '<label class="wide">Review Note<input data-f="review_note" value="'+esc(old.review_note||'')+'" '+(ref?'':'disabled')+'></label></div>'+
      (ref?'':'<p class="stti-v071-blocker">Assign a canonical stop_id before geo can be stored.</p>')+'</article>';});
    html+='</div><div class="stti-no-write"><strong>Truth policy</strong><p>Blank coordinates stay unresolved. Confirmed geo requires a source type + source reference. Client reviewer/timestamp fields are ignored.</p></div>';
    panel.innerHTML=html; sync(panel,hidden);
    panel.addEventListener('input',function(){sync(panel,hidden);}); panel.addEventListener('change',function(){sync(panel,hidden);});
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',render);else render();
})();
