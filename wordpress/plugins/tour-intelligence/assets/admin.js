
document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('stti-editor-form');
  if (!form) return;

  // ---------- Utilities ----------
  const $ = (sel, root=document) => root.querySelector(sel);
  const $$ = (sel, root=document) => Array.from(root.querySelectorAll(sel));
  const esc = (v) => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
  const clone = (v) => JSON.parse(JSON.stringify(v));
  const parseJsonField = (name) => {
    const el = document.getElementById(name);
    if (!el) return [];
    try {
      const v = JSON.parse(el.value || '[]');
      return Array.isArray(v) ? v : [];
    } catch(e) { return []; }
  };
  const writeJsonField = (name, value) => {
    const el = document.getElementById(name);
    if (el) el.value = JSON.stringify(Array.isArray(value) ? value : []);
  };
  const nextId = (prefix, items, key) => {
    let n = 1;
    const used = new Set((items || []).map(x => String((x || {})[key] || '')));
    while (used.has(prefix + n)) n++;
    return prefix + n;
  };
  const normalizeMoney = (v) => {
    const s = String(v ?? '').trim().replace(',', '.').replace(/[^0-9.]/g,'');
    if (!s) return '';
    const n = Number(s);
    return Number.isFinite(n) ? String(n) : '';
  };

  // ---------- Hybrid dates + derived duration ----------
  const dateTexts = $$('[data-stti-date-text]');
  const datePickers = $$('[data-stti-date-picker]');
  const calendarBtns = $$('[data-stti-calendar-for]');

  function parseTypedDate(value) {
    const v = (value || '').trim();
    if (!v) return null;
    let m = v.match(/^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{4})$/);
    if (m) {
      const dd=String(m[1]).padStart(2,'0'), mm=String(m[2]).padStart(2,'0'), yyyy=m[3];
      const iso = `${yyyy}-${mm}-${dd}`;
      const d = new Date(`${iso}T00:00:00`);
      if (!Number.isNaN(d.getTime()) && d.getFullYear()===Number(yyyy) && d.getMonth()+1===Number(mm) && d.getDate()===Number(dd)) {
        return {iso, display:`${dd}/${mm}/${yyyy}`, date:d};
      }
    }
    m = v.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (m) {
      const d = new Date(`${v}T00:00:00`);
      if (!Number.isNaN(d.getTime()) && d.getFullYear()===Number(m[1]) && d.getMonth()+1===Number(m[2]) && d.getDate()===Number(m[3])) {
        return {iso:v, display:`${m[3]}/${m[2]}/${m[1]}`, date:d};
      }
    }
    return null;
  }
  function displayFromIso(iso) {
    const m=(iso||'').match(/^(\d{4})-(\d{2})-(\d{2})$/);
    return m ? `${m[3]}/${m[2]}/${m[1]}` : '';
  }
  function linkedPicker(name) { return $(`[data-stti-date-picker="${name}"]`); }
  function openPicker(name) {
    const picker=linkedPicker(name);
    if (!picker) return;
    try { if (typeof picker.showPicker === 'function') picker.showPicker(); else picker.click(); }
    catch(e) { try { picker.click(); } catch(_) {} }
  }
  function syncTyped(input) {
    const parsed=parseTypedDate(input.value);
    const picker=linkedPicker(input.dataset.sttiDateText);
    if (parsed) {
      input.value=parsed.display;
      if (picker) picker.value=parsed.iso;
      input.classList.remove('stti-date-invalid');
    } else if (input.value.trim()) {
      input.classList.add('stti-date-invalid');
    } else {
      input.classList.remove('stti-date-invalid');
      if (picker) picker.value='';
    }
    deriveDates(true);
  }
  calendarBtns.forEach(btn=>btn.addEventListener('click',()=>openPicker(btn.dataset.sttiCalendarFor)));
  dateTexts.forEach(input=>{
    input.addEventListener('blur',()=>syncTyped(input));
    input.addEventListener('change',()=>syncTyped(input));
    input.addEventListener('dblclick',()=>openPicker(input.dataset.sttiDateText));
  });
  datePickers.forEach(picker=>picker.addEventListener('change',()=>{
    const name=picker.dataset.sttiDatePicker;
    const input=$(`[data-stti-date-text="${name}"]`);
    if (input) { input.value=displayFromIso(picker.value); input.classList.remove('stti-date-invalid'); }
    deriveDates(true);
  }));

  function addDays(date, days) {
    const d = new Date(date.getTime());
    d.setDate(d.getDate() + days);
    return d;
  }
  function isoFromDate(d) {
    const y=d.getFullYear(), m=String(d.getMonth()+1).padStart(2,'0'), day=String(d.getDate()).padStart(2,'0');
    return `${y}-${m}-${day}`;
  }

  // ---------- State ----------
  let routeStops = parseJsonField('route_stops_json');
  let itinerary = parseJsonField('itinerary_json');
  let hotels = parseJsonField('hotel_relations_json');
  let transports = parseJsonField('transport_segments_json');
  let pricingItems = parseJsonField('pricing_items_json');
  let included = parseJsonField('included_services_json');
  let excluded = parseJsonField('excluded_services_json');
  let requirements = parseJsonField('requirements_items_json');

  function deriveDates(syncItin=false) {
    const startInput=$('[data-stti-date-text="start_date"]');
    const endInput=$('[data-stti-date-text="end_date"]');
    const days=$('input[name="duration_days"]');
    const nights=$('input[name="duration_nights"]');
    const temporal=$('select[name="temporal"]');
    const mode=$('select[name="date_mode"]');
    if (!startInput || !endInput || !days || !nights) return;
    const a=parseTypedDate(startInput.value), b=parseTypedDate(endInput.value);
    if (mode && mode.value==='exact' && a && b && b.date>=a.date) {
      const diff=Math.round((b.date-a.date)/86400000);
      nights.value=String(diff);
      days.value=String(diff+1);
      if (temporal) {
        const today=new Date(); today.setHours(0,0,0,0);
        if (today<a.date) temporal.value='upcoming';
        else if (today>b.date) temporal.value='completed';
        else temporal.value='in_progress';
      }
      if (syncItin) syncItineraryToDates(false);
    } else {
      days.value=''; nights.value='';
      if (temporal && mode && mode.value!=='exact') temporal.value='undated';
    }
  }

  // ---------- Tabs ----------
  const tabs = $$('.stti-editor-tab');
  const panes = $$('.stti-editor-pane');
  tabs.forEach(tab => tab.addEventListener('click', function () {
    const index = tab.getAttribute('data-tab');
    tabs.forEach(t=>t.classList.remove('is-active'));
    panes.forEach(p=>p.classList.remove('is-active'));
    tab.classList.add('is-active');
    const pane=$(`.stti-editor-pane[data-pane="${index}"]`);
    if (pane) pane.classList.add('is-active');
  }));

  // ---------- Generic row controls ----------
  function rowButtons(index, total) {
    return `<div class="stti-row-actions">
      <span class="dashicons dashicons-move stti-drag-handle" title="Sürükle"></span>
      <button type="button" class="button button-small" data-act="up" ${index===0?'disabled':''}>↑</button>
      <button type="button" class="button button-small" data-act="down" ${index===total-1?'disabled':''}>↓</button>
      <button type="button" class="button button-small stti-danger-btn" data-act="delete">Sil</button>
    </div>`;
  }
  function installRowDelegation(container, stateGetter, stateSetter, renderFn) {
    if (!container) return;
    container.addEventListener('input', e => {
      const row=e.target.closest('[data-index]');
      if (!row || !e.target.dataset.field) return;
      const state=stateGetter();
      const i=Number(row.dataset.index);
      if (!state[i]) return;
      state[i][e.target.dataset.field]=e.target.value;
      stateSetter(state);
      if (container.id==='stti-route-builder') updateRouteSummary();
    });
    container.addEventListener('change', e => {
      const row=e.target.closest('[data-index]');
      if (!row || !e.target.dataset.field) return;
      const state=stateGetter();
      const i=Number(row.dataset.index);
      if (!state[i]) return;
      state[i][e.target.dataset.field]=e.target.value;
      stateSetter(state);
      if (container.id==='stti-route-builder') updateRouteSummary();
    });
    container.addEventListener('click', e => {
      const btn=e.target.closest('[data-act]');
      if (!btn) return;
      const row=btn.closest('[data-index]');
      if (!row) return;
      let state=stateGetter();
      const i=Number(row.dataset.index);
      const act=btn.dataset.act;
      if (act==='delete') state.splice(i,1);
      if (act==='up' && i>0) [state[i-1],state[i]]=[state[i],state[i-1]];
      if (act==='down' && i<state.length-1) [state[i+1],state[i]]=[state[i],state[i+1]];
      stateSetter(state);
      renderFn();
      if (container.id==='stti-route-builder') updateRouteSummary();
    });

    let dragIndex=null;
    container.addEventListener('dragstart', e => {
      const row=e.target.closest('[data-index]');
      if (!row) return;
      dragIndex=Number(row.dataset.index);
      row.classList.add('is-dragging');
      if (e.dataTransfer) e.dataTransfer.effectAllowed='move';
    });
    container.addEventListener('dragend', e => {
      const row=e.target.closest('[data-index]');
      if (row) row.classList.remove('is-dragging');
      dragIndex=null;
    });
    container.addEventListener('dragover', e => {
      const row=e.target.closest('[data-index]');
      if (!row || dragIndex===null) return;
      e.preventDefault();
    });
    container.addEventListener('drop', e => {
      const row=e.target.closest('[data-index]');
      if (!row || dragIndex===null) return;
      e.preventDefault();
      const target=Number(row.dataset.index);
      if (target===dragIndex) return;
      const state=stateGetter();
      const [moved]=state.splice(dragIndex,1);
      state.splice(target,0,moved);
      stateSetter(state);
      renderFn();
      if (container.id==='stti-route-builder') updateRouteSummary();
    });
  }

  // ---------- Route Builder ----------
  const routeContainer=$('#stti-route-builder');
  const routeSummary=$('textarea[name="route"]');
  if (routeSummary) routeSummary.readOnly=true;

  function updateRouteSummary() {
    if (!routeSummary) return;
    const labels=routeStops.map(s => String(s.city || s.label || '').trim()).filter(Boolean);
    routeSummary.value=labels.join(' → ');
    writeJsonField('route_stops_json', routeStops);
  }
  function renderRoute() {
    if (!routeContainer) return;
    if (!routeStops.length) {
      routeContainer.innerHTML='<div class="stti-builder-empty">Henüz structured stop yok.</div>';
      updateRouteSummary();
      return;
    }
    routeContainer.innerHTML=routeStops.map((s,i)=>`
      <article class="stti-repeat-row" data-index="${i}" draggable="true">
        <div class="stti-row-head"><strong>${esc(s.stop_id || `R${i+1}`)} · ${esc(s.city || s.label || 'Unnamed stop')}</strong>${rowButtons(i,routeStops.length)}</div>
        <div class="stti-mini-grid stti-mini-grid-3">
          <label><span>Type</span><select data-field="type">
            ${['departure','stop','return','overnight','transit'].map(v=>`<option value="${v}" ${String(s.type||'stop')===v?'selected':''}>${v}</option>`).join('')}
          </select></label>
          <label><span>Country</span><input data-field="country" value="${esc(s.country||'')}"></label>
          <label><span>City</span><input data-field="city" value="${esc(s.city||'')}"></label>
          <label><span>Label / POI</span><input data-field="label" value="${esc(s.label||'')}"></label>
          <label class="stti-span-2"><span>Note</span><input data-field="note" value="${esc(s.note||'')}"></label>
        </div>
      </article>`).join('');
    writeJsonField('route_stops_json', routeStops);
    updateRouteSummary();
  }
  installRowDelegation(routeContainer,()=>routeStops,v=>{routeStops=v;writeJsonField('route_stops_json',v);},renderRoute);
  const addRouteBtn=$('#stti-add-route-stop');
  if (addRouteBtn) addRouteBtn.addEventListener('click',()=>{
    routeStops.push({stop_id:nextId('R',routeStops,'stop_id'),type:'stop',country:'',city:'',label:'',note:''});
    renderRoute();
  });

  // ---------- Itinerary ----------
  const itinContainer=$('#stti-itinerary-builder');
  function syncItineraryToDates(forceRender=true) {
    const mode=$('select[name="date_mode"]');
    const start=parseTypedDate($('[data-stti-date-text="start_date"]')?.value || '');
    const duration=Number($('input[name="duration_days"]')?.value || 0);
    if (!mode || mode.value!=='exact' || !start || !duration) {
      if (forceRender) renderItinerary();
      return;
    }
    itinerary.forEach((d,i)=>{
      const day=Number(d.day_number || (i+1));
      d.day_number=day;
      d.date=isoFromDate(addDays(start.date,day-1));
    });
    while (itinerary.length<duration) {
      const day=itinerary.length+1;
      itinerary.push({
        day_number:day,date:isoFromDate(addDays(start.date,day-1)),title:'',city:'',summary:'',
        activities:'',meals:'',transport_ref:'',hotel_ref:'',media_refs:''
      });
    }
    writeJsonField('itinerary_json',itinerary);
    if (forceRender) renderItinerary();
  }
  function renderItinerary() {
    if (!itinContainer) return;
    if (!itinerary.length) {
      itinContainer.innerHTML='<div class="stti-builder-empty">Exact tarihler varsa gün kabukları otomatik oluşur. İçerik bilinmiyorsa boş bırakın.</div>';
      writeJsonField('itinerary_json',itinerary);
      return;
    }
    const duration=Number($('input[name="duration_days"]')?.value || 0);
    const warning = duration && itinerary.length>duration ? `<div class="stti-builder-warning">Programda ${itinerary.length} gün var; derived duration ${duration}. Fazla günler güvenlik nedeniyle otomatik silinmedi.</div>` : '';
    itinContainer.innerHTML=warning + itinerary.map((d,i)=>`
      <article class="stti-day-card stti-repeat-row" data-index="${i}" draggable="true">
        <div class="stti-row-head">
          <div><span class="stti-day-no">GÜN ${esc(d.day_number || i+1)}</span><strong>${esc(d.date ? displayFromIso(d.date) : 'Tarih bilinmiyor')} · ${esc(d.city || d.title || 'İçerik bekliyor')}</strong></div>
          ${rowButtons(i,itinerary.length)}
        </div>
        <div class="stti-mini-grid stti-mini-grid-2">
          <label><span>Başlık</span><input data-field="title" value="${esc(d.title||'')}"></label>
          <label><span>Şehir</span><input data-field="city" value="${esc(d.city||'')}"></label>
          <label class="stti-span-2"><span>Özet / Açıklama</span><textarea data-field="summary" rows="3">${esc(d.summary||'')}</textarea></label>
          <label><span>Aktiviteler</span><input data-field="activities" value="${esc(d.activities||'')}" placeholder="Kaynakta doğrulanan aktiviteler"></label>
          <label><span>Yemekler</span><input data-field="meals" value="${esc(d.meals||'')}" placeholder="Örn. kahvaltı, akşam yemeği"></label>
          <label><span>Transport Ref</span><input data-field="transport_ref" value="${esc(d.transport_ref||'')}" placeholder="T1"></label>
          <label><span>Hotel Ref</span><input data-field="hotel_ref" value="${esc(d.hotel_ref||'')}" placeholder="H1 veya STH ID"></label>
          <label class="stti-span-2"><span>Media Refs</span><input data-field="media_refs" value="${esc(d.media_refs||'')}" placeholder="Future attachment refs"></label>
        </div>
      </article>`).join('');
    writeJsonField('itinerary_json',itinerary);
  }
  installRowDelegation(itinContainer,()=>itinerary,v=>{
    itinerary=v.map((d,i)=>Object.assign({},d,{day_number:i+1}));
    writeJsonField('itinerary_json',itinerary);
  },()=>{ syncItineraryToDates(false); renderItinerary(); });
  $('#stti-sync-itinerary')?.addEventListener('click',()=>syncItineraryToDates(true));
  $('#stti-add-itinerary-day')?.addEventListener('click',()=>{
    const day=itinerary.length+1;
    itinerary.push({day_number:day,date:'',title:'',city:'',summary:'',activities:'',meals:'',transport_ref:'',hotel_ref:'',media_refs:''});
    syncItineraryToDates(false); renderItinerary();
  });

  // ---------- Hotels ----------
  const hotelContainer=$('#stti-hotel-builder');
  function calcNights(a,b) {
    const da=parseTypedDate(a), db=parseTypedDate(b);
    if (!da || !db || db.date<da.date) return '';
    return String(Math.round((db.date-da.date)/86400000));
  }
  function renderHotels() {
    if (!hotelContainer) return;
    if (!hotels.length) {
      hotelContainer.innerHTML='<div class="stti-builder-empty">Hotel relation yok. Kaynakta otel adı yoksa boş bırakmak geçerlidir.</div>';
      writeJsonField('hotel_relations_json',hotels); return;
    }
    hotelContainer.innerHTML=hotels.map((h,i)=>{
      const nights=calcNights(h.check_in||'',h.check_out||'');
      if (nights!=='') h.nights=Number(nights); else h.nights=null;
      return `<article class="stti-repeat-row" data-index="${i}" draggable="true">
        <div class="stti-row-head"><strong>${esc(h.relation_id || `H${i+1}`)} · ${esc(h.hotel_stable_id || h.unresolved_name || 'Hotel relation')}</strong>${rowButtons(i,hotels.length)}</div>
        <div class="stti-mini-grid stti-mini-grid-3">
          <label><span>Mode</span><select data-field="mode">
            <option value="hotel_intelligence" ${String(h.mode)==='hotel_intelligence'?'selected':''}>Hotel Intelligence</option>
            <option value="unresolved" ${String(h.mode||'unresolved')==='unresolved'?'selected':''}>Unresolved source hotel</option>
          </select></label>
          <label><span>Hotel Stable ID</span><input data-field="hotel_stable_id" value="${esc(h.hotel_stable_id||'')}" placeholder="STH-..."></label>
          <label><span>Unresolved Name</span><input data-field="unresolved_name" value="${esc(h.unresolved_name||'')}" placeholder="Kaynakta yazan ad"></label>
          <label><span>City</span><input data-field="city" value="${esc(h.city||'')}"></label>
          <label><span>Check-in</span><input data-field="check_in" value="${esc(h.check_in ? displayFromIso(h.check_in) : '')}" placeholder="dd/mm/yyyy"></label>
          <label><span>Check-out</span><input data-field="check_out" value="${esc(h.check_out ? displayFromIso(h.check_out) : '')}" placeholder="dd/mm/yyyy"></label>
          <label><span>Nights · AUTO</span><input value="${esc(nights)}" readonly></label>
          <label class="stti-span-2"><span>Relation Note</span><input data-field="note" value="${esc(h.note||'')}"></label>
        </div>
      </article>`;
    }).join('');
    writeJsonField('hotel_relations_json',hotels);
  }
  installRowDelegation(hotelContainer,()=>hotels,v=>{hotels=v;writeJsonField('hotel_relations_json',v);},renderHotels);
  $('#stti-add-hotel')?.addEventListener('click',()=>{
    hotels.push({relation_id:nextId('H',hotels,'relation_id'),mode:'unresolved',hotel_stable_id:'',unresolved_name:'',city:'',check_in:'',check_out:'',nights:null,note:''});
    renderHotels();
  });

  // ---------- Transport ----------
  const transportContainer=$('#stti-transport-builder');
  function renderTransports() {
    if (!transportContainer) return;
    if (!transports.length) {
      transportContainer.innerHTML='<div class="stti-builder-empty">Ulaşım segmenti yok. Kaynak belirtmiyorsa boş bırakın.</div>';
      writeJsonField('transport_segments_json',transports); return;
    }
    transports.forEach((t,i)=>{ if (!t.segment_id) t.segment_id=`T${i+1}`; });
    transportContainer.innerHTML=transports.map((t,i)=>`
      <article class="stti-repeat-row" data-index="${i}" draggable="true">
        <div class="stti-row-head"><strong>${esc(t.segment_id)} · ${esc(t.from||'?')} → ${esc(t.to||'?')}</strong>${rowButtons(i,transports.length)}</div>
        <div class="stti-mini-grid stti-mini-grid-3">
          <label><span>Type</span><select data-field="type">${['flight','train','coach','minibus','private_vehicle','ferry','cruise','domestic_flight','walking','other'].map(v=>`<option value="${v}" ${String(t.type||'other')===v?'selected':''}>${v}</option>`).join('')}</select></label>
          <label><span>From</span><input data-field="from" value="${esc(t.from||'')}"></label>
          <label><span>To</span><input data-field="to" value="${esc(t.to||'')}"></label>
          <label><span>Provider</span><input data-field="provider" value="${esc(t.provider||'')}"></label>
          <label><span>Reference</span><input data-field="reference" value="${esc(t.reference||'')}"></label>
          <label><span>Note</span><input data-field="note" value="${esc(t.note||'')}"></label>
        </div>
      </article>`).join('');
    writeJsonField('transport_segments_json',transports);
  }
  installRowDelegation(transportContainer,()=>transports,v=>{transports=v;writeJsonField('transport_segments_json',v);},renderTransports);
  $('#stti-add-transport')?.addEventListener('click',()=>{
    transports.push({segment_id:nextId('T',transports,'segment_id'),type:'other',from:'',to:'',provider:'',reference:'',note:''});
    renderTransports();
  });

  // ---------- Pricing ----------
  const pricingContainer=$('#stti-pricing-builder');
  function renderPricing() {
    if (!pricingContainer) return;
    if (!pricingItems.length) {
      pricingContainer.innerHTML='<div class="stti-builder-empty">Structured fiyat satırı yok.</div>';
      writeJsonField('pricing_items_json',pricingItems); return;
    }
    pricingContainer.innerHTML=pricingItems.map((p,i)=>`
      <article class="stti-repeat-row" data-index="${i}" draggable="true">
        <div class="stti-row-head"><strong>${esc(p.price_id || `P${i+1}`)} · ${esc(p.label || 'Price option')}</strong>${rowButtons(i,pricingItems.length)}</div>
        <div class="stti-mini-grid stti-mini-grid-4">
          <label><span>Label</span><input data-field="label" value="${esc(p.label||'')}" placeholder="2 kişilik oda / Adult"></label>
          <label><span>Type</span><select data-field="type">${['exact','from','on_request'].map(v=>`<option value="${v}" ${String(p.type||'on_request')===v?'selected':''}>${v}</option>`).join('')}</select></label>
          <label><span>Amount</span><input data-field="amount" value="${esc(p.amount??'')}"></label>
          <label><span>Currency</span><select data-field="currency">${['EUR','USD','TRY','GBP'].map(v=>`<option value="${v}" ${String(p.currency||'EUR')===v?'selected':''}>${v}</option>`).join('')}</select></label>
          <label><span>Basis</span><select data-field="basis">${['unknown','per_person','per_room','package','family','custom'].map(v=>`<option value="${v}" ${String(p.basis||'unknown')===v?'selected':''}>${v}</option>`).join('')}</select></label>
          <label><span>Occupancy</span><input data-field="occupancy" value="${esc(p.occupancy||'')}" placeholder="2 / 3 / adult / child"></label>
          <label class="stti-span-2"><span>Note</span><input data-field="note" value="${esc(p.note||'')}"></label>
        </div>
      </article>`).join('');
    writeJsonField('pricing_items_json',pricingItems);
  }
  installRowDelegation(pricingContainer,()=>pricingItems,v=>{
    pricingItems=v.map((p,i)=>Object.assign({},p,{price_id:p.price_id||`P${i+1}`,amount:normalizeMoney(p.amount)}));
    writeJsonField('pricing_items_json',pricingItems);
  },renderPricing);
  $('#stti-add-price')?.addEventListener('click',()=>{
    pricingItems.push({price_id:nextId('P',pricingItems,'price_id'),label:'',type:'on_request',amount:'',currency:$('select[name="currency"]')?.value||'EUR',basis:'unknown',occupancy:'',note:''});
    renderPricing();
  });

  // ---------- Included / Excluded / Requirements ----------
  function installSimpleBuilder(containerId, addBtnId, stateName, prefix, labelText) {
    const container=$(containerId);
    const fieldMap={
      included_services_json:()=>included,
      excluded_services_json:()=>excluded,
      requirements_items_json:()=>requirements
    };
    const setMap={
      included_services_json:v=>included=v,
      excluded_services_json:v=>excluded=v,
      requirements_items_json:v=>requirements=v
    };
    function render() {
      const state=fieldMap[stateName]();
      if (!container) return;
      if (!state.length) {
        container.innerHTML=`<div class="stti-builder-empty">${esc(labelText)} yok. Kaynak doğrulamıyorsa boş bırakın.</div>`;
        writeJsonField(stateName,state); return;
      }
      container.innerHTML=state.map((s,i)=>`
        <article class="stti-repeat-row stti-repeat-row-compact" data-index="${i}" draggable="true">
          <div class="stti-row-head"><strong>${esc(s.item_id || `${prefix}${i+1}`)} · ${esc(s.label || labelText)}</strong>${rowButtons(i,state.length)}</div>
          <div class="stti-mini-grid stti-mini-grid-2">
            <label><span>Label</span><input data-field="label" value="${esc(s.label||'')}"></label>
            <label><span>Note / Source context</span><input data-field="note" value="${esc(s.note||'')}"></label>
          </div>
        </article>`).join('');
      writeJsonField(stateName,state);
    }
    installRowDelegation(container,()=>fieldMap[stateName](),v=>{setMap[stateName](v);writeJsonField(stateName,v);},render);
    $(addBtnId)?.addEventListener('click',()=>{
      const state=fieldMap[stateName]();
      state.push({item_id:nextId(prefix,state,'item_id'),label:'',note:''});
      setMap[stateName](state); render();
    });
    return render;
  }
  const renderIncluded=installSimpleBuilder('#stti-included-builder','#stti-add-included','included_services_json','I','Dahil');
  const renderExcluded=installSimpleBuilder('#stti-excluded-builder','#stti-add-excluded','excluded_services_json','E','Hariç');
  const renderRequirements=installSimpleBuilder('#stti-requirements-builder','#stti-add-requirement','requirements_items_json','Q','Requirement');

  // ---------- Initial structured bootstrap ----------
  // Preserve legacy route facts by turning only the existing route summary into stops.
  if (!routeStops.length && routeSummary && routeSummary.value.trim()) {
    const parts=routeSummary.value.split(/\s*(?:→|›|>|·)\s*/).map(s=>s.trim()).filter(Boolean);
    routeStops=parts.map((city,i)=>({stop_id:`R${i+1}`,type:'stop',country:'',city,label:'',note:''}));
  }
  // Mirror the canonical primary price into one structured row if no row exists.
  if (!pricingItems.length) {
    const amount=$('input[name="price_amount"]')?.value || '';
    const type=$('select[name="price_type"]')?.value || 'on_request';
    if (amount || type!=='on_request') {
      pricingItems=[{
        price_id:'P1',label:'Primary source price',type,
        amount:normalizeMoney(amount),currency:$('select[name="currency"]')?.value||'EUR',
        basis:$('select[name="price_basis"]')?.value||'unknown',occupancy:'',note:'Auto-seeded from primary price; no extra fact inferred.'
      }];
    }
  }

  deriveDates(false);
  syncItineraryToDates(false);
  renderRoute();
  renderItinerary();
  renderHotels();
  renderTransports();
  renderPricing();
  renderIncluded();
  renderExcluded();
  renderRequirements();

  const dateMode=$('select[name="date_mode"]');
  if (dateMode) dateMode.addEventListener('change',()=>deriveDates(true));

  // Normalize hotel date typing on blur and re-render nights.
  hotelContainer?.addEventListener('blur', e => {
    if (!['check_in','check_out'].includes(e.target.dataset.field)) return;
    const row=e.target.closest('[data-index]');
    if (!row) return;
    const i=Number(row.dataset.index);
    const p=parseTypedDate(e.target.value);
    hotels[i][e.target.dataset.field]=p ? p.iso : e.target.value.trim();
    writeJsonField('hotel_relations_json',hotels);
    renderHotels();
  }, true);

  // ---------- Persist all builder state ----------
  function syncAllHidden() {
    deriveDates(false);
    syncItineraryToDates(false);
    updateRouteSummary();
    writeJsonField('route_stops_json',routeStops);
    writeJsonField('itinerary_json',itinerary);
    writeJsonField('hotel_relations_json',hotels);
    writeJsonField('transport_segments_json',transports);
    writeJsonField('pricing_items_json',pricingItems);
    writeJsonField('included_services_json',included);
    writeJsonField('excluded_services_json',excluded);
    writeJsonField('requirements_items_json',requirements);
  }
  form.addEventListener('submit',syncAllHidden);

  // ---------- JSON preview / draft evidence ----------
  const previewBtn=$('#stti-json-preview');
  const draftDownloadBtn=$('#stti-json-download-draft');
  const dialog=$('#stti-json-dialog');
  const closeBtn=$('#stti-json-close');
  const output=$('#stti-json-output');

  function getFormValue(fd,k) {
    const v=fd.get(k);
    return v===null ? null : v;
  }
  function buildEditorExport() {
    syncAllHidden();
    const fd=new FormData(form);
    const get=(k)=>getFormValue(fd,k);
    const parsedStart=parseTypedDate(get('start_date'));
    const parsedEnd=parseTypedDate(get('end_date'));
    const amountRaw=get('price_amount');
    const numericAmount=amountRaw===null || amountRaw==='' ? null : Number(String(amountRaw).replace(',','.'));
    const payload={
      schema:'STTI-TOUR-1.1.0',
      stable_id:get('stable_id') || '[allocated on save]',
      identity:{tour_code:get('tour_code'),public_title:get('public_title'),short_title:get('short_title'),slug:get('slug'),language:get('language')},
      destinations:{
        primary_country:get('primary_country'),
        countries:(get('countries')||'').split(',').map(s=>s.trim()).filter(Boolean),
        primary_city:get('primary_city'),
        cities:(get('cities')||'').split(',').map(s=>s.trim()).filter(Boolean),
        departure_city:get('departure_city'),return_city:get('return_city')
      },
      date:{
        mode:get('date_mode'),precision:get('date_precision'),
        start_date:parsedStart?parsedStart.iso:null,end_date:parsedEnd?parsedEnd.iso:null,
        month:get('month'),duration_days:get('duration_days')?Number(get('duration_days')):null,
        duration_nights:get('duration_nights')?Number(get('duration_nights')):null
      },
      route:{summary:get('route'),stops:clone(routeStops)},
      itinerary:clone(itinerary),
      stays:{hotels:clone(hotels)},
      transport:{segments:clone(transports)},
      pricing:{
        type:get('price_type'),amount:Number.isFinite(numericAmount)?numericAmount:null,
        currency:get('currency'),basis:get('price_basis'),items:clone(pricingItems)
      },
      services:{included:clone(included),excluded:clone(excluded)},
      requirements:{visa_status:get('visa_status'),visa_notes:get('visa_notes'),items:clone(requirements)},
      media:{items:[]},
      content:{short_description:get('short_description')},
      lifecycle:{editorial:get('editorial'),schedule:get('schedule_status'),availability:get('availability'),temporal:get('temporal')},
      publication:{renderer:'private',public_route:false,hub_visible:false,homepage_visible:false,indexable:false,sitemap:false},
      provenance:{source_type:get('source_type'),source_ref:get('source_ref'),origin_fixture_id:get('origin_fixture_id'),source_completeness:get('source_completeness')}
    };
    const rawForm={};
    fd.forEach((value,key)=>{if(!key.startsWith('_wp')) rawForm[key]=value;});
    return {
      export_contract:'STTI-EDITOR-DRAFT-1.1.0',
      generated_at:new Date().toISOString(),
      source:'wp-admin structured editor draft',
      saved:Boolean(get('stable_id')),
      release_locks:{public_renderer:false,public_routes:false,sitemap:false,indexation:false,homepage_adapter:false,schema_output:false},
      canonical_candidate:payload,
      structured_counts:{
        route_stops:routeStops.length,itinerary_days:itinerary.length,hotel_relations:hotels.length,
        transport_segments:transports.length,pricing_items:pricingItems.length,
        included_services:included.length,excluded_services:excluded.length,requirements:requirements.length
      },
      raw_form:rawForm
    };
  }

  previewBtn?.addEventListener('click',()=>{
    const data=buildEditorExport();
    if (output) output.textContent=JSON.stringify(data.canonical_candidate,null,2);
    if (dialog) {
      if (typeof dialog.showModal==='function') dialog.showModal();
      else dialog.setAttribute('open','open');
    }
  });
  draftDownloadBtn?.addEventListener('click',()=>{
    const data=buildEditorExport();
    const title=((data.canonical_candidate.identity||{}).slug || (data.canonical_candidate.identity||{}).tour_code || 'new-tour')
      .toString().toLowerCase().replace(/[^a-z0-9_-]+/g,'-').replace(/^-+|-+$/g,'') || 'new-tour';
    const blob=new Blob([JSON.stringify(data,null,2)],{type:'application/json;charset=utf-8'});
    const url=URL.createObjectURL(blob);
    const a=document.createElement('a');
    a.href=url;a.download=`stti-editor-draft-${title}.json`;
    document.body.appendChild(a);a.click();a.remove();
    setTimeout(()=>URL.revokeObjectURL(url),1500);
  });
  closeBtn?.addEventListener('click',()=>{if(dialog){if(typeof dialog.close==='function')dialog.close();else dialog.removeAttribute('open');}});
});
