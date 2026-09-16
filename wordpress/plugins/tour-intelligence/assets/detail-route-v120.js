(function(){
  'use strict';

  function status(text,state){
    var el=document.getElementById('stti-cx-map-status');
    if(!el)return;
    el.classList.remove('ok','warn','active');
    if(state)el.classList.add(state);
    el.textContent=text;
  }

  function esc(v){
    return String(v||'').replace(/[&<>"']/g,function(c){
      return({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[c];
    });
  }

  function sleep(ms){return new Promise(function(resolve){window.setTimeout(resolve,ms);});}

  function icon(stop){
    return window.L.divIcon({
      className:'stti-map-div-icon',
      html:'<div class="stti-map-pin stti-v120-pin">'+String(stop.order||'').padStart(2,'0')+'<span class="stti-map-pin-label">'+esc(stop.name||'Durak')+'</span></div>',
      iconSize:[44,44],
      iconAnchor:[22,22]
    });
  }

  function pathLength(path){
    try{return path&&path.getTotalLength?Math.max(1,path.getTotalLength()):0;}catch(e){return 0;}
  }

  function drawSegment(map,a,b,duration,reduced){
    var points=[[a.lat,a.lng],[b.lat,b.lng]];
    var shadow=window.L.polyline(points,{
      color:'#071b4d',weight:11,opacity:.16,lineCap:'round',lineJoin:'round',interactive:false,className:'stti-v120-route-shadow'
    }).addTo(map);
    var line=window.L.polyline(points,{
      color:'#d4af37',weight:5,opacity:1,lineCap:'round',lineJoin:'round',interactive:false,className:'stti-route-anim-path stti-v120-route-line'
    }).addTo(map);
    if(reduced)return Promise.resolve({shadow:shadow,line:line});
    return new Promise(function(resolve){
      window.requestAnimationFrame(function(){
        var path=line.getElement?line.getElement():null;
        var length=pathLength(path);
        if(!path||!length){resolve({shadow:shadow,line:line});return;}
        path.style.transition='none';
        path.style.strokeDasharray=length+' '+length;
        path.style.strokeDashoffset=String(length);
        path.getBoundingClientRect();
        window.requestAnimationFrame(function(){
          path.style.transition='stroke-dashoffset '+duration+'ms cubic-bezier(.22,.68,.26,1)';
          path.style.strokeDashoffset='0';
          window.setTimeout(function(){
            path.style.strokeDasharray='none';
            path.style.strokeDashoffset='0';
            resolve({shadow:shadow,line:line});
          },duration+70);
        });
      });
    });
  }

  function waitVisible(el,reduced){
    if(reduced||!('IntersectionObserver' in window))return Promise.resolve();
    var r=el.getBoundingClientRect();
    if(r.top<window.innerHeight*.88&&r.bottom>window.innerHeight*.12)return Promise.resolve();
    return new Promise(function(resolve){
      var obs=new IntersectionObserver(function(entries){
        if(entries.some(function(e){return e.isIntersecting;})){
          obs.disconnect();resolve();
        }
      },{threshold:.22});
      obs.observe(el);
    });
  }

  function render(){
    var el=document.getElementById('stti-cx-route-map');
    var c=window.STTI_V120_DETAIL_MAP||null;
    if(!el||!c)return;
    var stops=Array.isArray(c.stops)?c.stops.slice():[];
    var unresolved=Array.isArray(c.unresolved)?c.unresolved:[];
    if(!window.L){status('Harita altyapısı yüklenemedi; rota bilgisi korunuyor.','warn');return;}

    var map=window.L.map(el,{
      scrollWheelZoom:false,zoomControl:true,worldCopyJump:true,minZoom:Number(c.minZoom||2),attributionControl:true
    }).setView([20,0],2);
    window.L.tileLayer(c.tiles,{
      attribution:c.tileAttribution||'&copy; OpenStreetMap contributors',maxZoom:19,minZoom:Number(c.minZoom||2)
    }).addTo(map);

    var timelineItems=Array.prototype.slice.call(document.querySelectorAll('.stti-cx-route-line article'));
    var entries=[];
    stops.sort(function(a,b){return Number(a.order||0)-Number(b.order||0);});
    stops.forEach(function(s){
      var lat=Number(s.lat),lng=Number(s.lng);
      if(!isFinite(lat)||!isFinite(lng))return;
      var marker=window.L.marker([lat,lng],{icon:icon(s),keyboard:true,opacity:1}).addTo(map)
        .bindTooltip(esc(s.name||''),{direction:'top',offset:[0,-22],opacity:.98,className:'stti-v120-tooltip'});
      entries.push({stop:s,lat:lat,lng:lng,marker:marker});
    });

    var ll=entries.map(function(p){return[p.lat,p.lng];});
    if(ll.length>=2){map.fitBounds(window.L.latLngBounds(ll),{padding:[72,72],maxZoom:Number(c.maxZoom||13)});}
    else if(ll.length===1){map.setView(ll[0],8);}else{map.setView([20,0],2);}

    var reduced=!!(window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    var shell=el.closest?el.closest('.stti-cx-map-shell'):null;

    function pin(index,current){
      var entry=entries[index];if(!entry)return;
      var markerEl=entry.marker.getElement?entry.marker.getElement():null;
      if(!markerEl)return;
      var p=markerEl.querySelector('.stti-map-pin');if(!p)return;
      p.classList.add('is-visible');
      p.classList.toggle('is-current',!!current);
    }
    function reached(order){
      var idx=Number(order||0)-1;
      if(idx>=0&&timelineItems[idx])timelineItems[idx].classList.add('is-reached');
    }

    function completeStatus(){
      if(!entries.length){status('Canonical olarak doğrulanmış rota koordinatı yok. Harita tahmin yapmıyor.','warn');return;}
      if(unresolved.length){status(entries.length+' doğrulanmış durak gösteriliyor · '+unresolved.length+' durak unresolved kaldı.','warn');}
      else{status(entries.length+' durak canonical ve human-confirmed koordinatlarla gösteriliyor.','ok');}
    }

    waitVisible(el,reduced).then(function(){
      if(!entries.length){completeStatus();return;}
      if(shell)shell.classList.add('is-animating');
      status('Rota başlıyor · '+(entries[0].stop.name||'Başlangıç'),'active');
      pin(0,true);reached(entries[0].stop.order);
      if(reduced){
        for(var i=1;i<entries.length;i++){
          drawSegment(map,entries[i-1],entries[i],0,true);
          pin(i-1,false);pin(i,true);reached(entries[i].stop.order);
        }
        if(shell)shell.classList.remove('is-animating');completeStatus();return;
      }
      var seq=Promise.resolve();
      entries.slice(1).forEach(function(entry,idx){
        var prev=entries[idx],currentIndex=idx+1;
        seq=seq.then(function(){
          status('Rota çiziliyor · '+(currentIndex+1)+'/'+entries.length+' · '+(entry.stop.name||'Durak'),'active');
          return drawSegment(map,prev,entry,Number(c.segmentDurationMs||980),false);
        }).then(function(){
          pin(currentIndex-1,false);pin(currentIndex,true);reached(entry.stop.order);
          return sleep(Number(c.markerRevealMs||260));
        });
      });
      return seq.then(function(){
        if(shell)shell.classList.remove('is-animating');
        completeStatus();
      });
    });

    window.setTimeout(function(){map.invalidateSize();},160);
  }

  function enhanceAccordions(){
    document.querySelectorAll('.stti-cx-day').forEach(function(item){
      item.addEventListener('toggle',function(){
        if(item.open){
          document.querySelectorAll('.stti-cx-day[open]').forEach(function(other){if(other!==item)other.removeAttribute('open');});
        }
      });
    });
  }

  function revealSections(){
    var nodes=Array.prototype.slice.call(document.querySelectorAll('.stti-cx-section-head,.stti-cx-summary-card,.stti-cx-day,.stti-cx-detail-grid>*,.stti-cx-service-grid>*'));
    if(!nodes.length)return;
    var reduced=window.matchMedia&&window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if(reduced||!('IntersectionObserver' in window)){nodes.forEach(function(n){n.classList.add('stti-v120-revealed');});return;}
    var obs=new IntersectionObserver(function(entries){entries.forEach(function(entry){if(entry.isIntersecting){entry.target.classList.add('stti-v120-revealed');obs.unobserve(entry.target);}});},{threshold:.12,rootMargin:'0px 0px -6% 0px'});
    nodes.forEach(function(n){n.classList.add('stti-v120-reveal');obs.observe(n);});
  }

  function boot(){enhanceAccordions();revealSections();render();}
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
})();
