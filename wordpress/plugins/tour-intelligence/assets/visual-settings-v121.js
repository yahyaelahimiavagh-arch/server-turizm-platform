(function($){
  'use strict';

  function setPreview(name, url){
    var $preview=$('[data-preview="'+name+'"]');
    if(!$preview.length) return;
    if(url){
      $preview.addClass('has-image').css('background-image','url("'+url.replace(/"/g,'\\"')+'")');
    }else{
      $preview.removeClass('has-image').css('background-image','');
    }
  }

  $(document).on('click','.stti-v121-media-pick',function(e){
    e.preventDefault();
    var target=$(this).data('target');
    var $input=$('[name="'+target+'"]');
    if(!$input.length || !window.wp || !wp.media) return;
    var frame=wp.media({title:'Görsel seç',button:{text:'Bu görseli kullan'},multiple:false,library:{type:'image'}});
    frame.on('select',function(){
      var item=frame.state().get('selection').first().toJSON();
      var url=item.url||'';
      $input.val(url).trigger('change');
      setPreview(target,url);
    });
    frame.open();
  });

  $(document).on('click','.stti-v121-media-clear',function(e){
    e.preventDefault();
    var target=$(this).data('target');
    $('[name="'+target+'"]').val('').trigger('change');
    setPreview(target,'');
  });

  $(document).on('input change','.stti-v121-media-field input[type=url]',function(){
    setPreview($(this).attr('name'),$(this).val());
  });
})(jQuery);
