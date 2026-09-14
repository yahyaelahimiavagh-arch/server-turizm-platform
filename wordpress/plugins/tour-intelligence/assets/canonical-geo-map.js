(function(){
  'use strict';
  function status(text,state){var el=document.getElementById('stti-cx-map-status');if(!el)return;el.classList.remove('ok','warn');if(state)el.classList.add(state);el.textContent=text;}
  function esc(v){return String(v||'').replace(/[&<>"']/g,function(c){return({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[c];});}
  function icon(stop){return window.L.divIcon({className:'stti-map-div-icon',html:'<div class="stti-map-pin is-visible">'+String(stop.order||'').padStart(2,'0')+'<span class="stti-map-pin-label">'+esc(stop.name||'Durak')+'</span></div>',iconSize:[38,38],iconAnchor:[19,19]});}
  function render(){
    var el=document.getElementById('stti-cx-route-map'),c=window.STTI_V071_GEO_MAP||null;if(!el||!c)return;
    var stops=Array.isArray(c.stops)?c.stops:[], unresolved=Array.isArray(c.unresolved)?c.unresolved:[];
    if(!window.L){status('Harita altyapısı yüklenemedi; canonical geo verisi korunuyor.','warn');return;}
    var map=window.L.map(el,{scrollWheelZoom:false,zoomControl:true,worldCopyJump:true,minZoom:Number(c.minZoom||2)}).setView([20,0],2);
    window.L.tileLayer(c.tiles,{attribution:c.tileAttribution||'&copy; OpenStreetMap contributors',maxZoom:19,minZoom:Number(c.minZoom||2)}).addTo(map);
    var ll=[];stops.forEach(function(s){var lat=Number(s.lat),lng=Number(s.lng);if(!isFinite(lat)||!isFinite(lng))return;ll.push([lat,lng]);window.L.marker([lat,lng],{icon:icon(s),keyboard:true}).addTo(map).bindTooltip(esc(s.name||''),{direction:'top',offset:[0,-20],opacity:.95});});
    if(ll.length>=2){window.L.polyline(ll,{color:'#071b4d',weight:9,opacity:.22,interactive:false}).addTo(map);window.L.polyline(ll,{color:'#d4af37',weight:4,opacity:.98,interactive:false,className:'stti-route-anim-path'}).addTo(map);map.fitBounds(window.L.latLngBounds(ll),{padding:[72,72],maxZoom:Number(c.maxZoom||13)});}else if(ll.length===1){map.setView(ll[0],8);}else{map.setView([20,0],2);}
    if(!stops.length){status('Canonical olarak doğrulanmış rota koordinatı yok. Harita tahmin yapmıyor.','warn');}
    else if(unresolved.length){status(stops.length+' durak canonical geo ile gösteriliyor · '+unresolved.length+' durak unresolved kaldı.','warn');}
    else{status(stops.length+' durak canonical ve human-confirmed koordinatlarla gösteriliyor.','ok');}
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',render);else render();
})();
