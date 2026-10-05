(function(){
  'use strict';

  function appendEndpoint(article,label,city,isReturn){
    if(!article)return;
    var chip=document.createElement('span');
    chip.className='stti-v122-direction'+(isReturn?' is-return':'');
    chip.textContent=label;
    article.appendChild(chip);
    if(city){
      var note=document.createElement('small');
      note.className='stti-v122-endpoint-note';
      note.innerHTML=(isReturn?'Dönüş: ':'Çıkış: ')+'<strong></strong>';
      note.querySelector('strong').textContent=city;
      article.appendChild(note);
    }
  }

  function enhanceTimeline(){
    var timeline=document.querySelector('.stti-cx-route-line');
    if(!timeline)return;
    var items=Array.prototype.slice.call(timeline.querySelectorAll(':scope > article'));
    var count=items.length;
    if(!count)return;

    timeline.setAttribute('data-stop-count',String(count));
    timeline.style.setProperty('--stti-v122-stop-count',String(count));
    timeline.style.setProperty('--stti-v122-edge',(50/count)+'%');
    var maxWidth=count<=5?Math.max(320,count*220):1180;
    timeline.style.setProperty('--stti-v122-route-max-width',maxWidth+'px');

    var meta=window.STTI_V122_ROUTE_META||{};
    var first=items[0];
    var last=items[count-1];
    first.classList.add('stti-v122-start');
    appendEndpoint(first,'GİDİŞ',String(meta.departureCity||''),false);

    if(last===first){
      appendEndpoint(last,'DÖNÜŞ',String(meta.returnCity||''),true);
    }else{
      last.classList.add('stti-v122-end');
      appendEndpoint(last,'DÖNÜŞ',String(meta.returnCity||''),true);
    }
  }

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',enhanceTimeline);
  else enhanceTimeline();
})();
