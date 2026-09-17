(function($){
  'use strict';

  var cfg=window.STTI_V122_EDITOR_MEDIA||{};

  function esc(value){
    return String(value==null?'':value).replace(/[&<>"']/g,function(c){return({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[c];});
  }

  function defaultsOnly(){
    var $root=$('.stti-v121-admin');
    if(!$root.length)return;
    var $panels=$root.find('.stti-v121-panel');
    if($panels.length>1)$panels.slice(1).remove();
    var $head=$root.find('.stti-v121-admin-head>div:first-child');
    if($head.length && !$head.find('.stti-v122-default-note').length){
      $head.append('<p class="stti-v122-default-note"><strong>Tur bazlı görseller:</strong> Tur Intelligence → Tur Düzenle → Medya içinden yönetilir.</p>');
    }
  }

  function preview($box,url){
    $box.toggleClass('has-image',!!url).css('background-image',url?'url("'+String(url).replace(/"/g,'\\"')+'")':'');
  }

  function openMedia(target,$box){
    if(!window.wp||!wp.media)return;
    var frame=wp.media({title:'Tur görseli seç',button:{text:'Bu görseli kullan'},multiple:false,library:{type:'image'}});
    frame.on('select',function(){
      var item=frame.state().get('selection').first().toJSON();
      var url=item.url||'';
      $(target).val(url);
      preview($box,url);
    });
    frame.open();
  }

  function mediaField(id,label,value,help){
    return '<div class="stti-v122-media-field">'+
      '<label>'+esc(label)+'</label>'+
      '<div class="stti-v122-media-row">'+
        '<input type="url" id="'+id+'" value="'+esc(value)+'" placeholder="https://..." />'+
        '<button type="button" class="button stti-v122-pick" data-input="#'+id+'">Medya Kütüphanesi</button>'+
        '<button type="button" class="button stti-v122-clear" data-input="#'+id+'">Temizle</button>'+
      '</div>'+
      '<div class="stti-v122-media-preview'+(value?' has-image':'')+'" data-for="'+id+'"'+(value?' style="background-image:url(\''+esc(value)+'\')"':'')+'></div>'+
      '<small>'+esc(help)+'</small>'+
    '</div>';
  }

  function mountEditor(){
    if(!cfg.stableId)return;
    var $pane=$('.stti-editor-pane[data-pane="10"]');
    if(!$pane.length)return;
    $pane.find('.stti-placeholder-grid').remove();
    $pane.find('.stti-v122-media-manager').remove();

    var html='<div class="stti-v122-media-manager">'+
      '<div class="stti-v122-media-head"><div><span>V1.2.2 · TUR GÖRSELLERİ</span><h3>Bu tura özel görseller</h3><p>Hero ve Hub kartı bu tur kaydına aittir. Site geneli fallback görselleri Görsel Ayarları ekranında kalır.</p></div><code>'+esc(cfg.stableId)+'</code></div>'+
      '<div class="stti-v122-media-grid">'+
        mediaField('stti-v122-hero','Detay Hero Görseli',cfg.hero||'','Tur detay sayfası için geniş yatay görsel.')+
        mediaField('stti-v122-cover','Hub Kart Görseli',cfg.cover||'','Kültür Turları kartında kullanılan kapak görseli.')+
      '</div>'+
      '<div class="stti-v122-controls">'+
        '<label><span>Hero Overlay</span><input id="stti-v122-overlay" type="number" min="0.25" max="0.92" step="0.01" value="'+esc(cfg.overlay||0.72)+'"><small>0.25 açık · 0.92 koyu</small></label>'+
        '<label><span>Odak X (%)</span><input id="stti-v122-focal-x" type="number" min="0" max="100" step="1" value="'+esc(cfg.focalX==null?72:cfg.focalX)+'"></label>'+
        '<label><span>Odak Y (%)</span><input id="stti-v122-focal-y" type="number" min="0" max="100" step="1" value="'+esc(cfg.focalY==null?50:cfg.focalY)+'"></label>'+
        '<label><span>Hero Yüksekliği</span><input id="stti-v122-height" type="number" min="560" max="900" step="10" value="'+esc(cfg.height||700)+'"><small>px</small></label>'+
      '</div>'+
      '<div class="stti-v122-save-row"><div class="stti-v122-media-status" role="status"></div><button type="button" class="button button-primary stti-v122-save">Görselleri Kaydet</button></div>'+
    '</div>';
    $pane.append(html);

    var $destPane=$('.stti-editor-pane[data-pane="1"]');
    if($destPane.length&&!$destPane.find('.stti-v122-direction-note').length){
      $destPane.append('<div class="stti-v122-direction-note"><b>Gidiş / Dönüş</b><span>Departure City ve Return City alanları müşteri sayfasında rota çizgisinin ilk ve son durağında GİDİŞ / DÖNÜŞ etiketi olarak kullanılır. Boşsa şehir adı tahmin edilmez.</span></div>');
    }

    addQuickButton();
  }

  function addQuickButton(){
    if($('.stti-v122-open-media').length)return;
    var $actions=$('.stti-operator-toolbar-actions').first();
    if(!$actions.length)$actions=$('.stti-operator-detail-actions').first();
    if(!$actions.length)return;
    $actions.prepend('<button type="button" class="button stti-v122-open-media">Görseller</button>');
  }

  $(document).on('click','.stti-v122-open-media',function(){
    $('body').addClass('stti-operator-advanced');
    var $tab=$('.stti-editor-tab[data-tab="10"]');
    if($tab.length){$tab.trigger('click');window.setTimeout(function(){$('.stti-v122-media-manager')[0]?.scrollIntoView({behavior:'smooth',block:'start'});},120);}
  });

  $(document).on('click','.stti-v122-pick',function(){
    var selector=$(this).data('input');
    var $input=$(selector);var id=$input.attr('id');var $box=$('.stti-v122-media-preview[data-for="'+id+'"]');
    openMedia(selector,$box);
  });
  $(document).on('click','.stti-v122-clear',function(){
    var selector=$(this).data('input');var $input=$(selector);$input.val('');preview($('.stti-v122-media-preview[data-for="'+$input.attr('id')+'"]'),'');
  });
  $(document).on('input change','#stti-v122-hero,#stti-v122-cover',function(){preview($('.stti-v122-media-preview[data-for="'+this.id+'"]'),this.value);});

  $(document).on('click','.stti-v122-save',function(){
    var $btn=$(this),$status=$('.stti-v122-media-status');
    if(!cfg.ajaxUrl||!cfg.nonce||!cfg.stableId)return;
    $btn.prop('disabled',true);$status.removeClass('is-ok is-error').text('Kaydediliyor…');
    $.post(cfg.ajaxUrl,{
      action:'stti_v122_save_editor_visuals',nonce:cfg.nonce,stable_id:cfg.stableId,
      hero_image_url:$('#stti-v122-hero').val(),cover_image_url:$('#stti-v122-cover').val(),
      hero_overlay:$('#stti-v122-overlay').val(),hero_focal_x:$('#stti-v122-focal-x').val(),
      hero_focal_y:$('#stti-v122-focal-y').val(),hero_height:$('#stti-v122-height').val()
    }).done(function(res){
      if(res&&res.success){$status.addClass('is-ok').text((res.data&&res.data.message)||'Görseller kaydedildi.');}
      else{$status.addClass('is-error').text((res&&res.data&&res.data.message)||'Kayıt başarısız.');}
    }).fail(function(xhr){
      var msg='Kayıt başarısız.';try{var r=JSON.parse(xhr.responseText);if(r&&r.data&&r.data.message)msg=r.data.message;}catch(e){}
      $status.addClass('is-error').text(msg);
    }).always(function(){$btn.prop('disabled',false);});
  });

  function boot(){
    if(cfg.mode==='defaults')defaultsOnly();
    if(cfg.mode==='editor')mountEditor();
  }
  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',boot);else boot();
})(jQuery);
