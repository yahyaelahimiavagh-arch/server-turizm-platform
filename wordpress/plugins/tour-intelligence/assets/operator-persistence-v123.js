(function(){
  'use strict';

  function setName(id,name){
    var el=document.getElementById(id);
    if(el)el.setAttribute('name',name);
  }

  function bindDescription(form){
    var canonical=form.querySelector('[name="short_description"]');
    var grid=document.querySelector('.stti-operator-grid');
    if(!canonical||!grid||document.getElementById('stti-v123-description'))return;

    var label=document.createElement('label');
    label.className='stti-op-field stti-op-wide stti-v123-description-field';
    label.innerHTML='<span>Kısa Açıklama</span><textarea id="stti-v123-description" rows="4" placeholder="Turun müşteriye görünen kısa açıklaması"></textarea><small>Bu açıklama ana Tur kaydıyla birlikte kaydedilir.</small>';
    grid.appendChild(label);

    var textarea=document.getElementById('stti-v123-description');
    textarea.value=canonical.value||'';
    function sync(){
      canonical.value=textarea.value;
      canonical.dispatchEvent(new Event('input',{bubbles:true}));
      canonical.dispatchEvent(new Event('change',{bubbles:true}));
    }
    textarea.addEventListener('input',sync);
    textarea.addEventListener('change',sync);
    form.addEventListener('submit',sync);
  }

  function boot(){
    var form=document.getElementById('stti-editor-form');
    if(!form)return;

    // The v1.2.2 media manager is mounted inside the canonical Tour form. Giving
    // its controls names makes the main “Değişiklikleri Kaydet” button persist
    // the same visual values as the dedicated AJAX button.
    setName('stti-v122-hero','stti_v123_hero_image_url');
    setName('stti-v122-cover','stti_v123_cover_image_url');
    setName('stti-v122-overlay','stti_v123_hero_overlay');
    setName('stti-v122-focal-x','stti_v123_hero_focal_x');
    setName('stti-v122-focal-y','stti_v123_hero_focal_y');
    setName('stti-v122-height','stti_v123_hero_height');

    bindDescription(form);
  }

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
})();
