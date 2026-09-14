(function(){
  'use strict';

  function parseJSONAttr(el, name){
    try { return JSON.parse(el.getAttribute(name) || '[]'); } catch(e) { return []; }
  }

  function emitInteraction(root, action, extra){
    try {
      var detail = Object.assign({
        action: action,
        programId: root.getAttribute('data-stppi-program-id') || '',
        programName: root.getAttribute('data-program-name') || ''
      }, extra || {});
      document.dispatchEvent(new CustomEvent('stppi:interaction', {detail: detail}));
    } catch(e) {}
  }

  function bootCard(root){
    if(!root || root.getAttribute('data-stppi-legacy-booted') === '1') return;
    root.setAttribute('data-stppi-legacy-booted','1');

    var modal = root.querySelector('.lc-modal');
    var content = modal ? modal.querySelector('.lc-media-content') : null;
    var closeBtn = modal ? modal.querySelector('.lc-close') : null;
    var modalKicker = modal ? modal.querySelector('.lc-modal-kicker') : null;
    var modalTitle = modal ? modal.querySelector('.lc-modal-title') : null;
    var modalCounter = modal ? modal.querySelector('.lc-gallery-counter') : null;
    var modalHint = modal ? modal.querySelector('.lc-gallery-hint') : null;
    var phone = (root.getAttribute('data-whatsapp') || '').replace(/\D+/g,'');
    var progName = root.getAttribute('data-program-name') || '';
    var programUrl = root.getAttribute('data-program-url') || '';
    var soldOut = root.getAttribute('data-sold-out') === '1';
    var curImgs = [], curIdx = 0, currentBlock = null, lastTrigger = null;
    var touchStartX = null, touchStartY = null;

    function openModal(trigger){
      if(!modal || !content) return;
      lastTrigger = trigger || document.activeElement;
      modal.style.display = 'flex';
      modal.setAttribute('aria-hidden','false');
      document.documentElement.classList.add('stppi-lc-modal-open');
      if(closeBtn) window.setTimeout(function(){ closeBtn.focus(); }, 0);
    }

    function closeModal(){
      if(!modal) return;
      modal.style.display = 'none';
      modal.setAttribute('aria-hidden','true');
      if(content) content.innerHTML = '';
      document.documentElement.classList.remove('stppi-lc-modal-open');
      modal.classList.remove('is-gallery','is-map');
      if(lastTrigger && typeof lastTrigger.focus === 'function') {
        try { lastTrigger.focus(); } catch(e) {}
      }
      lastTrigger = null;
    }

    if(closeBtn) closeBtn.addEventListener('click', function(e){ e.preventDefault(); e.stopPropagation(); closeModal(); });
    if(modal) modal.addEventListener('click', function(e){ if(e.target === modal) closeModal(); });

    function syncActiveThumb(){
      if(!currentBlock) return;
      currentBlock.querySelectorAll('.lc-thumb-img').forEach(function(thumb, idx){
        var active = idx === curIdx;
        thumb.classList.toggle('is-active', active);
        if(active) thumb.setAttribute('aria-current','true'); else thumb.removeAttribute('aria-current');
      });
    }

    function moveG(d){
      if(!curImgs.length) return;
      curIdx += d;
      if(curIdx < 0) curIdx = curImgs.length - 1;
      if(curIdx >= curImgs.length) curIdx = 0;
      renderG();
    }

    function renderG(){
      if(!content || !curImgs.length) return;
      var img = document.createElement('img');
      img.className = 'lc-frame';
      img.src = curImgs[curIdx];
      img.alt = (modalTitle ? modalTitle.textContent : 'Otel') + ' fotoğraf ' + (curIdx + 1);
      img.decoding = 'async';
      content.innerHTML = '';
      content.appendChild(img);
      syncActiveThumb();
      if(modalCounter) modalCounter.textContent = (curIdx + 1) + '/' + curImgs.length;
      if(curImgs.length > 1){
        var prev=document.createElement('button'); prev.type='button'; prev.className='lc-nav lc-prev'; prev.textContent='‹'; prev.setAttribute('aria-label','Önceki fotoğraf');
        var next=document.createElement('button'); next.type='button'; next.className='lc-nav lc-next'; next.textContent='›'; next.setAttribute('aria-label','Sonraki fotoğraf');
        prev.addEventListener('click',function(e){e.stopPropagation();moveG(-1);});
        next.addEventListener('click',function(e){e.stopPropagation();moveG(1);});
        content.appendChild(prev); content.appendChild(next);
      }
    }

    function openGal(imgs, idx, hotelName, block, trigger){
      if(!imgs || !imgs.length) return;
      curImgs = imgs.slice(); curIdx = Number.isInteger(idx) ? idx : 0; currentBlock = block || null;
      if(modal){ modal.classList.remove('is-map'); modal.classList.add('is-gallery'); }
      if(modalKicker) modalKicker.textContent='OTEL GALERİSİ';
      if(modalTitle) modalTitle.textContent=hotelName || 'OTEL';
      if(modalHint) modalHint.style.display='';
      if(modalCounter) modalCounter.style.display='';
      emitInteraction(root, 'gallery_open', {hotelName: hotelName || '', imageIndex: curIdx});
      renderG(); openModal(trigger);
    }

    function showMap(url, hotelName, trigger){
      if(!content || !url) return;
      currentBlock = null;
      if(modal){ modal.classList.remove('is-gallery'); modal.classList.add('is-map'); }
      if(modalKicker) modalKicker.textContent='OTEL KONUMU';
      if(modalTitle) modalTitle.textContent=hotelName || 'OTEL';
      if(modalHint) modalHint.style.display='none';
      if(modalCounter) modalCounter.style.display='none';
      content.innerHTML = '';
      var frame=document.createElement('iframe');
      frame.src=url; frame.width='100%'; frame.height='520'; frame.style.border='0';
      frame.loading='lazy'; frame.allowFullscreen=true; frame.referrerPolicy='no-referrer-when-downgrade';
      frame.title=(hotelName || 'Otel') + ' konumu';
      content.appendChild(frame);
      emitInteraction(root, 'hotel_map', {hotelName: hotelName || ''});
      openModal(trigger);
    }

    root.querySelectorAll('.lc-hotel').forEach(function(block){
      var imgs = parseJSONAttr(block,'data-gallery');
      var mapUrl = block.getAttribute('data-map-url') || '';
      var hotelName = block.getAttribute('data-hotel-name') || '';
      var hotelId = block.getAttribute('data-hotel-id') || '';
      var galleryBtn = block.querySelector('.lc-gallery-btn');
      var mapBtn = block.querySelector('.lc-map-btn');
      var hotelLink = block.querySelector('.lc-h-link');

      if(galleryBtn) galleryBtn.addEventListener('click',function(e){e.preventDefault();openGal(imgs,0,hotelName,block,galleryBtn);});
      if(mapBtn) mapBtn.addEventListener('click',function(e){e.preventDefault();showMap(mapUrl,hotelName,mapBtn);});
      if(hotelLink) hotelLink.addEventListener('click', function(){ emitInteraction(root,'hotel_click',{hotelId:hotelId,hotelName:hotelName,url:hotelLink.href}); });

      block.querySelectorAll('.lc-thumb-img').forEach(function(img,idx){
        function openFromThumb(){ openGal(imgs,idx,hotelName,block,img); }
        img.addEventListener('click',openFromThumb);
        img.addEventListener('keydown',function(e){ if(e.key === 'Enter' || e.key === ' '){ e.preventDefault(); openFromThumb(); } });
      });
    });

    root.querySelectorAll('.lc-acc-head').forEach(function(btn){
      btn.addEventListener('click', function(e){
        e.preventDefault();
        var panel=this.nextElementSibling;
        if(!panel) return;
        var open=this.getAttribute('aria-expanded') === 'true';
        this.setAttribute('aria-expanded', open ? 'false' : 'true');
        panel.style.maxHeight = open ? null : panel.scrollHeight + 'px';
      });
    });

    var pRows = root.querySelectorAll('.lc-p-row');
    var cta = root.querySelector('.lc-cta');
    var details = root.querySelector('.lc-details');

    function selectPrice(row){
      pRows.forEach(function(r){ r.classList.remove('selected'); r.setAttribute('aria-pressed','false'); });
      row.classList.add('selected'); row.setAttribute('aria-pressed','true'); updateLink();
    }

    function updateLink(){
      if(!cta) return;
      if(soldOut){
        cta.href='javascript:void(0);'; cta.style.cursor='not-allowed'; cta.setAttribute('aria-disabled','true');
        cta.onclick=function(e){e.preventDefault();window.alert('Bu program için kontenjan dolmuştur.');};
        return;
      }
      cta.onclick=null; cta.removeAttribute('aria-disabled'); cta.style.cursor='';
      var selected=root.querySelector('.lc-p-row.selected');
      var msg='Merhaba, '+progName+' hakkında bilgi almak istiyorum.';
      if(selected){
        var rType=selected.querySelector('.lc-p-cat');
        var rPrice=selected.querySelector('.lc-p-val');
        msg='Merhaba, '+progName+' için rezervasyon yapmak istiyorum.\nSeçilen Oda: '+(rType?rType.innerText:'')+' ('+(rPrice?rPrice.innerText:'')+')';
      }
      cta.href=phone ? ('https://wa.me/'+phone+'?text='+encodeURIComponent(msg)) : (programUrl || '#');
      cta.target='_blank'; cta.rel='noopener';
    }

    pRows.forEach(function(row){
      row.addEventListener('click',function(){selectPrice(this);});
      row.addEventListener('keydown',function(e){ if(e.key === 'Enter' || e.key === ' '){ e.preventDefault(); selectPrice(this); } });
    });
    updateLink();

    if(details && programUrl){
      details.addEventListener('click', function(e){
        e.preventDefault();
        emitInteraction(root,'program_detail_click',{url:programUrl});
        details.classList.add('is-clicked');
        window.setTimeout(function(){ window.location.assign(programUrl); }, 110);
      });
    }
    if(cta){
      cta.addEventListener('click',function(){
        if(!soldOut) emitInteraction(root,'whatsapp_reservation',{url:cta.href});
      });
    }

    document.addEventListener('keydown', function(e){
      if(!modal || modal.style.display !== 'flex') return;
      if(e.key === 'Escape') { closeModal(); }
      else if(modal.classList.contains('is-gallery') && e.key === 'ArrowLeft') { e.preventDefault(); moveG(-1); }
      else if(modal.classList.contains('is-gallery') && e.key === 'ArrowRight') { e.preventDefault(); moveG(1); }
    });

    if(content){
      content.addEventListener('touchstart', function(e){
        if(!modal || !modal.classList.contains('is-gallery') || !e.touches || !e.touches.length) return;
        touchStartX=e.touches[0].clientX; touchStartY=e.touches[0].clientY;
      }, {passive:true});
      content.addEventListener('touchend', function(e){
        if(touchStartX === null || !modal || !modal.classList.contains('is-gallery')) return;
        var t=e.changedTouches && e.changedTouches[0]; if(!t) return;
        var dx=t.clientX-touchStartX, dy=t.clientY-touchStartY;
        touchStartX=null; touchStartY=null;
        if(Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy)*1.2) moveG(dx < 0 ? 1 : -1);
      }, {passive:true});
    }

    var title=root.querySelector('.lc-title');
    if(title && programUrl){ title.style.cursor='pointer'; title.title='Program detayını aç'; title.addEventListener('click',function(){emitInteraction(root,'program_title_click',{url:programUrl});window.location.href=programUrl;}); }
    var hero=root.querySelector('.lc-img');
    if(hero && programUrl){ hero.style.cursor='pointer'; hero.title='Program detayını aç'; hero.addEventListener('dblclick',function(){emitInteraction(root,'program_hero_double_click',{url:programUrl});window.location.href=programUrl;}); }

    try{
      var fmt=new Intl.DateTimeFormat('tr-TR-u-ca-islamic',{day:'numeric',month:'long',year:'numeric'});
      root.querySelectorAll('.lc-hijri[data-date]').forEach(function(el){
        var ymd=el.getAttribute('data-date'); if(!ymd) return;
        var raw=fmt.format(new Date(ymd+'T00:00:00+03:00'));
        el.innerText='Hicri '+raw.replace(/Hicri/ig,'').replace(/AH/ig,'').trim();
      });
    }catch(e){}

    var destStr=root.getAttribute('data-start-date');
    if(destStr){
      var temporal=root.getAttribute('data-temporal') || '';
      var todayStr=root.getAttribute('data-server-today') || '';
      var elD=root.querySelector('.timer-d');
      var caption=root.querySelector('.lc-timer-caption');
      var timerWrap=root.querySelector('.lc-timer-wrap');
      function dayNumber(ymd){
        var m=/^(\d{4})-(\d{2})-(\d{2})$/.exec(ymd || '');
        return m ? Math.floor(Date.UTC(Number(m[1]),Number(m[2])-1,Number(m[3]))/86400000) : null;
      }
      if(temporal === 'in_progress'){
        if(caption) caption.innerText='PROGRAM BAŞLADI';
        if(elD) elD.innerText='0';
        if(timerWrap) timerWrap.classList.add('is-started');
      }else{
        var destDay=dayNumber(destStr), todayDay=dayNumber(todayStr);
        var days=(destDay!==null && todayDay!==null) ? Math.max(0,destDay-todayDay) : null;
        if(elD) elD.innerText=(days===null ? '—' : String(days));
        if(caption) caption.innerText='KALKIŞA KALAN';
        if(timerWrap) timerWrap.classList.remove('is-started');
      }
    }
  }

  function boot(){ document.querySelectorAll('.lc-card-template[data-stppi-legacy-card="1"]').forEach(bootCard); }
  if(document.readyState==='loading') document.addEventListener('DOMContentLoaded',boot,{once:true}); else boot();
})();
