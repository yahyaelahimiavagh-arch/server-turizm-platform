(function($){
    'use strict';
    if(typeof STHI === 'undefined'){ return; }

    function toast(message, isError){
        var $toast=$('#sthi-toast');
        $toast.text(message).toggleClass('is-error',!!isError).addClass('is-visible');
        clearTimeout(window.STHIToastTimer);
        window.STHIToastTimer=setTimeout(function(){ $toast.removeClass('is-visible'); },1800);
    }


    function publicationReadiness($row){
        var missing=[];
        function fieldValue(field){ return String($row.find('[data-field="'+field+'"]').val()||'').trim(); }
        function ruleOk(rule){
            if(rule==='hotel_name'){
                var v=fieldValue('post_title').toLowerCase();
                return !!v && v!=='yeni otel' && v!=='untitled hotel';
            }
            if(rule==='wp_status'){ return fieldValue('post_status')==='publish'; }
            if(rule==='official_name'){
                var v=fieldValue('_sthi_official_name').toLowerCase();
                return !!v && v!=='yeni otel' && v!=='untitled hotel';
            }
            if(rule==='country'){ return !!fieldValue('_sthi_country'); }
            if(rule==='city'){ return !!fieldValue('_sthi_city'); }
            if(rule==='verification'){ return fieldValue('_sthi_verification_status')==='verified'; }
            if(rule==='last_verified'){ return !!fieldValue('_sthi_last_verified_at'); }
            if(rule==='workflow'){ return fieldValue('_sthi_workflow_status')==='published'; }
            if(rule==='destinations'){ return !!fieldValue('taxonomy:sthi_destination'); }
            if(rule==='media'){
                var n=parseInt($row.find('.sthi-media-count-badge').text(),10)||0;
                return n>0;
            }
            return true;
        }
        var labels={
            hotel_name:'Hotel Name',wp_status:'WP Status = Published',official_name:'Official Name',country:'Country',city:'City',
            verification:'Verification = Verified',last_verified:'Last Verified At',workflow:'Workflow = Published',destinations:'Destinations',media:'Media'
        };
        $row.find('.sthi-publish-required').each(function(){
            var $cell=$(this), rule=String($cell.data('publish-rule')||''), ok=ruleOk(rule);
            $cell.toggleClass('is-publish-missing',!ok).toggleClass('is-publish-ready',ok);
            $cell.attr('title', ok ? 'Publication requirement complete' : 'Required for publication: '+(labels[rule]||rule));
            if(!ok && missing.indexOf(labels[rule]||rule)===-1){ missing.push(labels[rule]||rule); }
        });
        var $badge=$row.find('.sthi-publish-ready-badge');
        if(!missing.length){
            $badge.removeClass('is-blocked').addClass('is-ready').attr('title','All publication requirements are complete');
            $badge.find('strong').text('READY');
            $badge.find('small').text('All required fields complete');
        }else{
            $badge.removeClass('is-ready').addClass('is-blocked').attr('title','Missing: '+missing.join(', '));
            $badge.find('strong').text(missing.length+' missing');
            $badge.find('small').text(missing.slice(0,2).join(' • ')+(missing.length>2?' +'+(missing.length-2):''));
        }
        return missing;
    }
    function refreshPublicationReadiness($row){ if($row&&$row.length){ publicationReadiness($row); } }
    function refreshAllPublicationReadiness(){ $('#sthi-hotel-grid tbody tr').each(function(){ publicationReadiness($(this)); }); }

    function saveCell($el){
        var $row=$el.closest('tr'), postId=parseInt($row.data('post-id'),10)||0;
        if(!postId){ return; }
        var field=$el.data('field'), value=$el.val();
        if(!field){ return; }
        $el.addClass('is-saving');
        $.post(STHI.ajaxUrl,{action:'sthi_grid_update',nonce:STHI.nonce,post_id:postId,field:field,value:value})
            .done(function(resp){
                if(resp&&resp.success){
                    if(resp.data&&typeof resp.data.normalized_value!=='undefined'){ $el.val(resp.data.normalized_value); value=resp.data.normalized_value; }
                    $el.data('last',String(value));
                    if(resp.data&&typeof resp.data.country_value!=='undefined'){ $row.find('[data-field="_sthi_country"]').val(resp.data.country_value).data('last',String(resp.data.country_value)); }
                    if(resp.data&&typeof resp.data.city_value!=='undefined'){ $row.find('[data-field="_sthi_city"]').val(resp.data.city_value).data('last',String(resp.data.city_value)); }
                    if(resp.data&&typeof resp.data.destination_value!=='undefined'){ $row.find('[data-field="taxonomy:sthi_destination"]').val(resp.data.destination_value).data('last',String(resp.data.destination_value)); }
                    if(resp.data&&typeof resp.data.media_count!=='undefined'){ $row.find('.sthi-media-count-badge').text(resp.data.media_count); }
                    if(resp.data&&typeof resp.data.health!=='undefined'){ var $h=$row.find('.sthi-health-score'); $h.attr('data-health-score',resp.data.health).find('strong').text(resp.data.health+'%'); $h.find('small').text(resp.data.health_label||''); }
                    refreshPublicationReadiness($row);
                    toast('Saved');
                }
                else { toast((resp&&resp.data&&resp.data.message)||'Save failed',true); }
            })
            .fail(function(){ toast('Save failed',true); })
            .always(function(){ $el.removeClass('is-saving'); });
    }

    $(document).on('focus','.sthi-grid-field',function(){ $(this).data('last',String($(this).val())); });
    $(document).on('change blur','.sthi-grid-field',function(e){
        var $el=$(this), current=String($el.val());
        if(e.type==='change' || $el.data('last')!==current){ saveCell($el); }
    });

    function applySearch(){
        var q=($('#sthi-grid-search').val()||'').toLowerCase();
        $('#sthi-hotel-grid tbody tr').each(function(){
            $(this).toggle(!q || $(this).text().toLowerCase().indexOf(q)!==-1 || $(this).find('input').filter(function(){ return String($(this).val()).toLowerCase().indexOf(q)!==-1; }).length>0);
        });
        updateSelectionState();
    }
    $('#sthi-grid-search').on('input',applySearch);

    $('#sthi-filter-destination,#sthi-filter-collection').on('change',function(){
        var u=new URL(window.location.href);
        u.searchParams.set('page','sthi-hotel-grid');
        var d=$('#sthi-filter-destination').val()||'';
        var c=$('#sthi-filter-collection').val()||'';
        if(d){u.searchParams.set('sthi_destination',d);}else{u.searchParams.delete('sthi_destination');}
        if(c){u.searchParams.set('sthi_collection',c);}else{u.searchParams.delete('sthi_collection');}
        window.location.href=u.toString();
    });

    function applyView(){
        var view=$('#sthi-grid-view').val()||'essential';
        var $cells=$('#sthi-hotel-grid th,#sthi-hotel-grid td');
        $cells.removeClass('sthi-col-hidden');
        if(view==='all'){ return; }
        $cells.each(function(){
            var $c=$(this), group=String($c.data('group')||'');
            if(view==='essential'){
                if(group!=='system' && !$c.is('[data-essential="1"]')){ $c.addClass('sthi-col-hidden'); }
            } else if(group!=='system' && group!=='taxonomy' && group!==view){
                $c.addClass('sthi-col-hidden');
            } else if(group==='taxonomy' && view!=='location' && view!=='identity'){
                $c.addClass('sthi-col-hidden');
            }
        });
    }
    $('#sthi-grid-view').on('change',applyView);
    applyView();
    refreshAllPublicationReadiness();

    $('#sthi-add-row').on('click',function(){
        var $btn=$(this), old=$btn.text();
        $btn.prop('disabled',true).text('Adding…');
        $.post(STHI.ajaxUrl,{action:'sthi_grid_create',nonce:STHI.nonce})
            .done(function(resp){
                if(resp&&resp.success&&resp.data.row_html){
                    $('#sthi-hotel-grid tbody').append(resp.data.row_html);
                    applyView(); applySearch();
                    var $row=$('#sthi-hotel-grid tbody tr[data-post-id="'+resp.data.post_id+'"]');
                    var $title=$row.find('[data-field="post_title"]');
                    $title.trigger('focus').select();
                    $row[0].scrollIntoView({behavior:'smooth',block:'center'});
                    toast('New hotel row created: '+(resp.data.hotel_id||''));
                } else { toast((resp&&resp.data&&resp.data.message)||'Could not add hotel',true); }
            })
            .fail(function(){ toast('Could not add hotel',true); })
            .always(function(){ $btn.prop('disabled',false).text(old); });
    });

    function trashIds(ids){
        ids=(ids||[]).map(function(v){return parseInt(v,10)||0;}).filter(Boolean);
        if(!ids.length){ return; }
        if(!window.confirm((ids.length===1?'Delete this hotel':'Delete '+ids.length+' hotels')+'? They will be moved to WordPress Trash and can be restored.')){ return; }
        $.post(STHI.ajaxUrl,{action:'sthi_grid_trash',nonce:STHI.nonce,ids:ids})
            .done(function(resp){
                if(resp&&resp.success){
                    (resp.data.ids||[]).forEach(function(id){ $('#sthi-hotel-grid tbody tr[data-post-id="'+id+'"]').remove(); });
                    updateSelectionState(); toast('Moved to Trash');
                } else { toast((resp&&resp.data&&resp.data.message)||'Delete failed',true); }
            }).fail(function(){ toast('Delete failed',true); });
    }
    $(document).on('click','.sthi-trash-row',function(){ trashIds([$(this).closest('tr').data('post-id')]); });
    $('#sthi-bulk-trash').on('click',function(){
        var ids=[]; $('.sthi-row-select:checked').each(function(){ ids.push($(this).closest('tr').data('post-id')); });
        trashIds(ids);
    });

    function updateSelectionState(){
        var $visible=$('#sthi-hotel-grid tbody tr:visible .sthi-row-select'), selected=$visible.filter(':checked').length;
        $('#sthi-selected-count').text(selected+' selected');
        $('#sthi-bulk-trash').prop('disabled',selected===0);
        $('#sthi-select-all').prop('checked',$visible.length>0 && selected===$visible.length).prop('indeterminate',selected>0 && selected<$visible.length);
    }
    $(document).on('change','.sthi-row-select',updateSelectionState);
    $('#sthi-select-all').on('change',function(){ $('#sthi-hotel-grid tbody tr:visible .sthi-row-select').prop('checked',this.checked); updateSelectionState(); });

    var orderMode=false, dragged=null;
    $('#sthi-toggle-order').on('click',function(){
        orderMode=!orderMode;
        $(this).text('Manual Order: '+(orderMode?'On':'Off')).toggleClass('button-primary',orderMode);
        $('#sthi-hotel-grid tbody tr').attr('draggable',orderMode?'true':'false');
        $('#sthi-hotel-grid').toggleClass('is-order-mode',orderMode);
    });
    $(document).on('dragstart','#sthi-hotel-grid tbody tr',function(e){ if(!orderMode){e.preventDefault();return;} dragged=this; this.classList.add('is-dragging'); e.originalEvent.dataTransfer.effectAllowed='move'; });
    $(document).on('dragend','#sthi-hotel-grid tbody tr',function(){ this.classList.remove('is-dragging'); dragged=null; });
    $(document).on('dragover','#sthi-hotel-grid tbody tr',function(e){ if(!orderMode||!dragged||dragged===this){return;} e.preventDefault(); var r=this.getBoundingClientRect(), after=e.originalEvent.clientY>r.top+r.height/2; this.parentNode.insertBefore(dragged,after?this.nextSibling:this); });
    $(document).on('drop','#sthi-hotel-grid tbody',function(e){
        if(!orderMode){return;} e.preventDefault(); var ids=[];
        $('#sthi-hotel-grid tbody tr').each(function(){ids.push($(this).data('post-id'));});
        $.post(STHI.ajaxUrl,{action:'sthi_grid_reorder',nonce:STHI.nonce,ids:ids}).done(function(resp){if(resp&&resp.success){toast('Order saved');}else{toast('Order save failed',true);}}).fail(function(){toast('Order save failed',true);});
    });

    // Spreadsheet keyboard navigation.
    $(document).on('keydown','.sthi-grid-field',function(e){
        if(e.key!=='Enter'){ return; }
        if($(this).attr('type')==='url'){ return; }
        e.preventDefault();
        var $row=$(this).closest('tr'), $visible=$row.find('.sthi-grid-field:visible'), idx=$visible.index(this);
        if(idx>=0 && idx+1<$visible.length){ $visible.eq(idx+1).trigger('focus').select(); }
    });

    updateSelectionState();
})(jQuery);

(function($){
    'use strict';

    var $manager = $('.sthi-media-manager');
    if(!$manager.length || typeof STHI === 'undefined'){ return; }

    var items = [];
    var $json = $('#sthi-gallery-json');
    var $primary = $('#sthi-primary-image-key');
    var primaryKey = $primary.val() || '';
    try { items = JSON.parse($json.val() || '[]'); } catch(e) { items = []; }

    function escapeHtml(value){
        return $('<div>').text(value == null ? '' : String(value)).html();
    }

    function itemKey(item){
        if(item.key){ return item.key; }
        if(item.type === 'attachment'){ return 'attachment:' + item.id; }
        return 'url:' + (item.url || '');
    }

    function sync(){
        items = items.filter(function(item){ return item && item.type && (item.url || item.id); });
        $json.val(JSON.stringify(items));
        if(!primaryKey && items.length){ primaryKey=itemKey(items[0]); }
        if(primaryKey && !items.some(function(item){return itemKey(item)===primaryKey;})){ primaryKey=items.length?itemKey(items[0]):''; }
        $primary.val(primaryKey);
        $('#sthi-media-count-number').text(items.length);
        render();
    }

    function render(){
        var html = '';
        items.forEach(function(item){
            var key = itemKey(item);
            var src = item.thumb || item.url || '';
            var title = item.title || src.split('/').pop() || 'Image';
            var source = item.type === 'attachment' ? 'WordPress Media' : 'Hosting Folder';
            var isPrimary = key === primaryKey;
            html += '<div class="sthi-gallery-item'+(isPrimary?' is-primary':'')+'" draggable="true" data-key="'+escapeHtml(key)+'">' +
                '<div class="sthi-gallery-thumb"><img src="'+escapeHtml(src)+'" alt="" loading="lazy">'+(isPrimary?'<span class="sthi-gallery-primary-badge">PRIMARY</span>':'')+'</div>' +
                '<div class="sthi-gallery-meta"><strong title="'+escapeHtml(title)+'">'+escapeHtml(title)+'</strong><span>'+source+'</span></div>' +
                '<button type="button" class="button sthi-gallery-primary'+(isPrimary?' is-primary':'')+'" aria-pressed="'+(isPrimary?'true':'false')+'">'+(isPrimary?'Primary image':'Set primary')+'</button>' +
                '<button type="button" class="button-link-delete sthi-gallery-remove" aria-label="Remove image">Remove</button>' +
                '<span class="sthi-gallery-drag" aria-hidden="true">☰</span></div>';
        });
        $('#sthi-gallery-list').html(html);
    }

    function addItems(newItems){
        var seen = {};
        items.forEach(function(item){ seen[itemKey(item)] = true; });
        (newItems || []).forEach(function(item){
            var key = itemKey(item);
            if(!key || seen[key]){ return; }
            seen[key] = true;
            items.push(item);
        });
        sync();
    }

    var mediaFrame = null;
    $('#sthi-media-library').on('click', function(e){
        e.preventDefault();
        if(typeof wp === 'undefined' || !wp.media){ return; }
        if(mediaFrame){ mediaFrame.open(); return; }
        mediaFrame = wp.media({
            title: 'Select hotel images',
            button: { text: 'Add selected images' },
            library: { type: 'image' },
            multiple: true
        });
        mediaFrame.on('select', function(){
            var selected = mediaFrame.state().get('selection').toJSON();
            addItems(selected.map(function(a){
                var thumb = a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url;
                return { key:'attachment:'+a.id, type:'attachment', id:a.id, url:a.url, thumb:thumb, title:a.title || a.filename || 'Image' };
            }));
        });
        mediaFrame.open();
    });

    $('#sthi-media-computer').on('click', function(e){
        e.preventDefault();
        $('#sthi-media-file-input').trigger('click');
    });

    $('#sthi-media-file-input').on('change', function(){
        var files = this.files;
        if(!files || !files.length){ return; }
        if(files.length > 50){ alert('Please upload up to 50 images at a time.'); this.value=''; return; }
        var postId = parseInt($manager.data('post-id'), 10) || 0;
        if(!postId){ alert('Save the hotel first, then upload images.'); return; }
        var fd = new FormData();
        fd.append('action', 'sthi_media_upload');
        fd.append('nonce', STHI.mediaNonce);
        fd.append('post_id', postId);
        for(var i=0;i<files.length;i++){ fd.append('files[]', files[i]); }

        var $button = $('#sthi-media-computer');
        var oldText = $button.text();
        $button.prop('disabled', true).text('Uploading '+files.length+' image(s)…');
        $.ajax({ url:STHI.ajaxUrl, method:'POST', data:fd, processData:false, contentType:false })
            .done(function(resp){
                if(resp && resp.success){
                    addItems(resp.data.items || []);
                    if(resp.data.errors && resp.data.errors.length){ $('#sthi-folder-status').text(resp.data.errors.join(' | ')).addClass('is-error'); }
                } else {
                    alert((resp && resp.data && resp.data.message) || 'Upload failed.');
                }
            })
            .fail(function(xhr){
                var msg = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message;
                alert(msg || 'Upload failed.');
            })
            .always(function(){ $button.prop('disabled', false).text(oldText); $('#sthi-media-file-input').val(''); });
    });

    $('#sthi-scan-folder').on('click', function(e){
        e.preventDefault();
        var url = $.trim($('#sthi-folder-url').val());
        var $status = $('#sthi-folder-status').removeClass('is-error');
        if(!url){ $status.text('Paste a folder URL first.').addClass('is-error'); return; }
        var $button = $(this); var oldText = $button.text();
        $button.prop('disabled', true).text('Scanning…');
        $status.text('Scanning the server folder…');
        $.post(STHI.ajaxUrl, { action:'sthi_scan_folder', nonce:STHI.mediaNonce, url:url })
            .done(function(resp){
                if(resp && resp.success){
                    addItems(resp.data.items || []);
                    // Keep the manual Media Folder URL as the saved source of truth.
                    $('input[name="sthi_meta[_sthi_media_folder_url]"]').val(url);
                    var postId=parseInt($manager.data('post-id'),10)||0;
                    if(postId){
                        $.post(STHI.ajaxUrl,{action:'sthi_grid_update',nonce:STHI.nonce,post_id:postId,field:'_sthi_media_folder_url',value:url});
                    }
                    var message = resp.data.count + ' image(s) found and linked.';
                    if(resp.data.limited){ message += ' Scan stopped at the 250-image safety limit.'; }
                    $status.text(message);
                } else {
                    $status.text((resp && resp.data && resp.data.message) || 'Folder scan failed.').addClass('is-error');
                }
            })
            .fail(function(xhr){
                var msg = xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message;
                $status.text(msg || 'Folder scan failed.').addClass('is-error');
            })
            .always(function(){ $button.prop('disabled', false).text(oldText); });
    });

    $(document).on('click', '.sthi-gallery-primary', function(){
        primaryKey = String($(this).closest('.sthi-gallery-item').data('key') || '');
        $primary.val(primaryKey);
        render();
    });

    $(document).on('click', '.sthi-gallery-remove', function(){
        var key = $(this).closest('.sthi-gallery-item').data('key');
        items = items.filter(function(item){ return itemKey(item) !== key; });
        sync();
    });

    var draggingKey = null;
    $(document).on('dragstart', '.sthi-gallery-item', function(e){
        draggingKey = $(this).data('key');
        this.classList.add('is-dragging');
        if(e.originalEvent && e.originalEvent.dataTransfer){ e.originalEvent.dataTransfer.effectAllowed='move'; }
    });
    $(document).on('dragend', '.sthi-gallery-item', function(){ this.classList.remove('is-dragging'); draggingKey=null; });
    $(document).on('dragover', '.sthi-gallery-item', function(e){
        if(!draggingKey || $(this).data('key') === draggingKey){ return; }
        e.preventDefault();
        var dragged = document.querySelector('.sthi-gallery-item[data-key="'+CSS.escape(String(draggingKey))+'"]');
        if(!dragged){ return; }
        var rect=this.getBoundingClientRect();
        var after=(e.originalEvent.clientY > rect.top + rect.height/2);
        this.parentNode.insertBefore(dragged, after ? this.nextSibling : this);
    });
    $(document).on('drop', '#sthi-gallery-list', function(e){
        if(!draggingKey){ return; }
        e.preventDefault();
        var ordered=[];
        $(this).children('.sthi-gallery-item').each(function(){
            var key=$(this).data('key');
            var found=items.find(function(item){ return itemKey(item)===key; });
            if(found){ ordered.push(found); }
        });
        items=ordered;
        sync();
    });

    // Normalize old/saved markup and keep hidden JSON current.
    sync();
})(jQuery);


(function($){
    'use strict';
    if(typeof STHI === 'undefined' || !STHI.bridgeNonce){ return; }

    var previewToken='';

    function esc(value){ return $('<div>').text(value == null ? '' : String(value)).html(); }
    function bridgeError(message){ window.alert(message || 'Hotel Data Bridge error.'); }
    function downloadText(filename, text){
        var blob=new Blob([text],{type:'text/csv;charset=utf-8'}), url=URL.createObjectURL(blob), a=document.createElement('a');
        a.href=url; a.download=filename||'hotels.csv'; document.body.appendChild(a); a.click(); a.remove(); setTimeout(function(){URL.revokeObjectURL(url);},1000);
    }
    function selectedIds(){
        var ids=[]; $('.sthi-row-select:checked').each(function(){ ids.push(parseInt($(this).closest('tr').data('post-id'),10)||0); });
        return ids.filter(Boolean);
    }

    $('#sthi-open-bridge').on('click',function(){ $('#sthi-data-bridge').prop('hidden',false)[0].scrollIntoView({behavior:'smooth',block:'start'}); });
    $('#sthi-close-bridge').on('click',function(){ $('#sthi-data-bridge').prop('hidden',true); });

    $('#sthi-bridge-file').on('change',function(){
        var file=this.files&&this.files[0]; if(!file){return;}
        var reader=new FileReader();
        reader.onload=function(){ $('#sthi-bridge-raw').val(String(reader.result||'')); };
        reader.onerror=function(){ bridgeError('Could not read this file.'); };
        reader.readAsText(file,'UTF-8');
    });

    $('#sthi-bridge-copy-headers').on('click',function(){
        var text=$('#sthi-bridge-header-source').val()||'';
        if(navigator.clipboard&&navigator.clipboard.writeText){ navigator.clipboard.writeText(text).then(function(){window.alert('Hotel Data Bridge headers copied.');}); }
        else { var ta=document.getElementById('sthi-bridge-header-source'); ta.hidden=false; ta.select(); document.execCommand('copy'); ta.hidden=true; window.alert('Hotel Data Bridge headers copied.'); }
    });

    function exportCsv(mode){
        var data={action:'sthi_bridge_export',nonce:STHI.bridgeNonce,mode:mode||'data'};
        if(mode!=='template'){ data.ids=selectedIds(); }
        $.post(STHI.ajaxUrl,data).done(function(resp){
            if(resp&&resp.success){ downloadText(resp.data.filename,resp.data.csv||''); }
            else { bridgeError((resp&&resp.data&&resp.data.message)||'Export failed.'); }
        }).fail(function(xhr){ bridgeError(xhr.responseJSON&&xhr.responseJSON.data&&xhr.responseJSON.data.message||'Export failed.'); });
    }
    $('#sthi-export-csv').on('click',function(){ exportCsv('data'); });
    $('#sthi-bridge-template').on('click',function(){ exportCsv('template'); });

    $('#sthi-bridge-analyze').on('click',function(){
        var raw=$('#sthi-bridge-raw').val()||'', $btn=$(this), old=$btn.text();
        $btn.prop('disabled',true).text('Analyzing…');
        $.post(STHI.ajaxUrl,{action:'sthi_bridge_analyze',nonce:STHI.bridgeNonce,raw:raw}).done(function(resp){
            if(!(resp&&resp.success)){ bridgeError(resp&&resp.data&&resp.data.message||'Could not analyze data.'); return; }
            renderMapping(resp.data);
        }).fail(function(xhr){ bridgeError(xhr.responseJSON&&xhr.responseJSON.data&&xhr.responseJSON.data.message||'Could not analyze data.'); })
          .always(function(){ $btn.prop('disabled',false).text(old); });
    });

    function renderMapping(data){
        var options='<option value="">Ignore column</option>';
        (data.options||[]).forEach(function(o){ options+='<option value="'+esc(o.value)+'">'+esc(o.label)+'</option>'; });
        var html='<div class="sthi-bridge-step-head"><div><h3>2. Map Columns</h3><p>'+esc(data.row_count)+' data row(s) detected · '+esc(data.delimiter)+'</p></div><button type="button" class="button button-primary" id="sthi-bridge-preview-btn">Run Dry Run</button></div>';
        html+='<div class="sthi-mapping-scroll"><table class="widefat striped"><thead><tr><th>Input column</th><th>Map to Hotel field</th><th>Sample</th></tr></thead><tbody>';
        (data.headers||[]).forEach(function(header,index){
            var samples=[]; (data.sample||[]).forEach(function(row){ if(typeof row[index]!=='undefined'&&String(row[index]).trim()!==''){samples.push(String(row[index]));} });
            html+='<tr><td><strong>'+esc(header||('(Column '+(index+1)+')'))+'</strong></td><td><select class="sthi-map-select" data-index="'+index+'">'+options+'</select></td><td class="sthi-map-sample">'+esc(samples.slice(0,2).join(' · '))+'</td></tr>';
        });
        html+='</tbody></table></div><p class="description">If ChatGPT uses the standard headers, most columns are mapped automatically. Review them before Dry Run.</p>';
        var $box=$('#sthi-bridge-mapping').html(html).prop('hidden',false);
        (data.suggested||[]).forEach(function(value,index){ if(value){ $box.find('.sthi-map-select[data-index="'+index+'"]').val(value); } });
        $('#sthi-bridge-preview').prop('hidden',true).empty(); previewToken='';
        $box[0].scrollIntoView({behavior:'smooth',block:'start'});
    }

    $(document).on('click','#sthi-bridge-preview-btn',function(){
        var mapping={}; $('.sthi-map-select').each(function(){mapping[$(this).data('index')]=$(this).val()||'';});
        var raw=$('#sthi-bridge-raw').val()||'', $btn=$(this), old=$btn.text();
        $btn.prop('disabled',true).text('Checking…');
        $.post(STHI.ajaxUrl,{action:'sthi_bridge_preview',nonce:STHI.bridgeNonce,raw:raw,mapping:JSON.stringify(mapping)}).done(function(resp){
            if(!(resp&&resp.success)){ bridgeError(resp&&resp.data&&resp.data.message||'Dry Run failed.'); return; }
            previewToken=resp.data.token||''; renderPreview(resp.data);
        }).fail(function(xhr){ bridgeError(xhr.responseJSON&&xhr.responseJSON.data&&xhr.responseJSON.data.message||'Dry Run failed.'); })
          .always(function(){ $btn.prop('disabled',false).text(old); });
    });

    function renderPreview(data){
        var s=data.summary||{}, actionable=(parseInt(s.create,10)||0)+(parseInt(s.update,10)||0);
        var html='<div class="sthi-bridge-step-head"><div><h3>3. Dry Run Preview</h3><p>No WordPress data has changed yet.</p></div><button type="button" class="button button-primary" id="sthi-bridge-apply" '+(actionable?'':'disabled')+'>Approve Import ('+actionable+')</button></div>';
        html+='<div class="sthi-preview-cards">'+
            '<div><span>CREATE</span><strong>'+esc(s.create||0)+'</strong></div><div><span>UPDATE</span><strong>'+esc(s.update||0)+'</strong></div><div><span>UNCHANGED</span><strong>'+esc(s.unchanged||0)+'</strong></div><div><span>CONFLICT</span><strong>'+esc(s.conflict||0)+'</strong></div><div><span>INVALID</span><strong>'+esc(s.invalid||0)+'</strong></div></div>';
        html+='<div class="sthi-preview-scroll"><table class="widefat striped"><thead><tr><th>Row</th><th>Action</th><th>Hotel ID</th><th>Hotel</th><th>Changes / reason</th></tr></thead><tbody>';
        (data.plans||[]).forEach(function(p){
            var cls='op-'+String(p.operation||'').toLowerCase();
            var detail=(p.changes&&p.changes.length)?p.changes.join(' | '):(p.message||'');
            html+='<tr><td>'+esc(p.row)+'</td><td><span class="sthi-op '+cls+'">'+esc(p.operation)+'</span></td><td><code>'+esc(p.hotel_id||'—')+'</code></td><td>'+esc(p.name||'')+'</td><td>'+esc(detail)+'</td></tr>';
        });
        html+='</tbody></table></div>';
        var $box=$('#sthi-bridge-preview').html(html).prop('hidden',false); $box[0].scrollIntoView({behavior:'smooth',block:'start'});
    }

    $(document).on('click','#sthi-bridge-apply',function(){
        if(!previewToken){bridgeError('Run Dry Run again.');return;}
        if(!window.confirm('Apply the CREATE/UPDATE actions shown in this Dry Run? An audit batch and rollback point will be created.')){return;}
        var $btn=$(this), old=$btn.text(); $btn.prop('disabled',true).text('Applying…');
        $.post(STHI.ajaxUrl,{action:'sthi_bridge_apply',nonce:STHI.bridgeNonce,token:previewToken}).done(function(resp){
            if(!(resp&&resp.success)){bridgeError(resp&&resp.data&&resp.data.message||'Import failed.');return;}
            var s=resp.data.summary||{}, msg='Import complete. Created: '+(s.created||0)+' · Updated: '+(s.updated||0)+' · Skipped: '+(s.skipped||0);
            if(resp.data.errors&&resp.data.errors.length){msg+='\n\n'+resp.data.errors.join('\n');}
            window.alert(msg); window.location.reload();
        }).fail(function(xhr){bridgeError(xhr.responseJSON&&xhr.responseJSON.data&&xhr.responseJSON.data.message||'Import failed.');})
          .always(function(){ $btn.prop('disabled',false).text(old); });
    });

    $(document).on('click','.sthi-bridge-rollback',function(){
        var batchId=parseInt($(this).data('batch-id'),10)||0; if(!batchId){return;}
        if(!window.confirm('Rollback this import? Hotels created by the batch will be moved to Trash. Updated hotels are restored only if they were not edited after the import.')){return;}
        var $btn=$(this), old=$btn.text(); $btn.prop('disabled',true).text('Rolling back…');
        $.post(STHI.ajaxUrl,{action:'sthi_bridge_rollback',nonce:STHI.bridgeNonce,batch_id:batchId}).done(function(resp){
            if(!(resp&&resp.success)){bridgeError(resp&&resp.data&&resp.data.message||'Rollback failed.');return;}
            var msg='Rollback complete. Restored: '+(resp.data.restored||0)+' · New hotels moved to Trash: '+(resp.data.trashed||0);
            if(resp.data.conflicts&&resp.data.conflicts.length){msg+='\nSkipped because they changed later:\n'+resp.data.conflicts.join('\n');}
            window.alert(msg); window.location.reload();
        }).fail(function(xhr){bridgeError(xhr.responseJSON&&xhr.responseJSON.data&&xhr.responseJSON.data.message||'Rollback failed.');})
          .always(function(){ $btn.prop('disabled',false).text(old); });
    });
})(jQuery);

(function($){
    'use strict';
    if(typeof STHI === 'undefined'){ return; }

    var currentPostId=0;
    var $modal=$('#sthi-details-modal');
    if(!$modal.length){ return; }

    function openModal(){
        $modal.addClass('is-open').attr('aria-hidden','false');
        $('body').addClass('sthi-details-open');
    }
    function closeModal(){
        $modal.removeClass('is-open').attr('aria-hidden','true');
        $('body').removeClass('sthi-details-open');
        currentPostId=0;
        $('#sthi-details-content').empty();
    }
    function healthText(score,label){ return String(score||0)+'% · '+String(label||''); }
    function setHealth(score,label){
        $('#sthi-details-health').text(healthText(score,label)).attr('data-score',score||0);
        if(currentPostId){
            var $cell=$('#sthi-hotel-grid tbody tr[data-post-id="'+currentPostId+'"]').find('.sthi-health-score');
            $cell.attr('data-health-score',score||0).find('strong').text(String(score||0)+'%');
            $cell.find('small').text(label||'');
        }
    }

    $(document).on('click','.sthi-open-details',function(){
        var $row=$(this).closest('tr');
        currentPostId=parseInt($row.data('post-id'),10)||0;
        if(!currentPostId){return;}
        $('#sthi-details-title').text($row.find('[data-field="post_title"]').val()||'Structured Details');
        $('#sthi-details-subtitle').text($row.find('.sthi-sticky-id').text()||'');
        $('#sthi-details-health').text('Loading…');
        $('#sthi-details-loading').show(); $('#sthi-details-content').empty();
        openModal();
        $.post(STHI.ajaxUrl,{action:'sthi_details_get',nonce:STHI.nonce,post_id:currentPostId})
            .done(function(resp){
                if(!(resp&&resp.success)){ window.alert(resp&&resp.data&&resp.data.message||'Could not load hotel details.'); closeModal(); return; }
                $('#sthi-details-title').text(resp.data.hotel_name||'Structured Details');
                $('#sthi-details-subtitle').text(resp.data.hotel_id||'');
                $('#sthi-details-content').html(resp.data.html||'');
                $('#sthi-details-loading').hide();
                setHealth(resp.data.health,resp.data.health_label);
            })
            .fail(function(xhr){ window.alert(xhr.responseJSON&&xhr.responseJSON.data&&xhr.responseJSON.data.message||'Could not load hotel details.'); closeModal(); });
    });

    $(document).on('click','[data-sthi-details-close]',closeModal);
    $(document).on('keydown',function(e){ if(e.key==='Escape'&&$modal.hasClass('is-open')){closeModal();} });

    $(document).on('click','[data-sthi-details-tab]',function(){
        var tab=$(this).data('sthi-details-tab');
        $('[data-sthi-details-tab]').removeClass('is-active'); $(this).addClass('is-active');
        $('[data-sthi-details-pane]').removeClass('is-active'); $('[data-sthi-details-pane="'+tab+'"]').addClass('is-active');
    });

    function newRowId(prefix){ return prefix+'-'+Date.now().toString(36)+'-'+Math.random().toString(36).slice(2,9); }
    $(document).on('click','[data-sthi-add-row]',function(){
        var type=$(this).data('sthi-add-row'), template=document.getElementById('sthi-template-'+type);
        if(!template){return;}
        var targetMap={room:'rooms',reference:'references',contact:'contacts',scene:'scenes'};
        var target=targetMap[type]; if(!target){return;}
        var fragment=template.content.cloneNode(true), row=fragment.querySelector('[data-sthi-row]');
        if(row){ row.setAttribute('data-row-id',newRowId(type)); }
        $('[data-sthi-repeater="'+target+'"]').append(fragment);
    });
    $(document).on('click','[data-sthi-remove-row]',function(){ $(this).closest('[data-sthi-row]').remove(); });

    function collectRows(container){
        var rows=[];
        $('[data-sthi-repeater="'+container+'"]').children('[data-sthi-row]').each(function(){
            var row={id:String($(this).attr('data-row-id')||'')};
            $(this).find('[data-field]').each(function(){
                var key=String($(this).data('field')||''); if(!key){return;}
                if($(this).is(':checkbox')){ row[key]=$(this).is(':checked'); }
                else { row[key]=$(this).val(); }
            });
            rows.push(row);
        });
        return rows;
    }
    function collectPayload(){
        var amenities={}; $('[data-sthi-amenity]').each(function(){amenities[String($(this).data('sthi-amenity'))]=$(this).val();});
        return {
            amenities:amenities,
            rooms:collectRows('rooms'),
            references:collectRows('references'),
            contacts:collectRows('contacts'),
            scenes:collectRows('scenes')
        };
    }

    $('#sthi-details-save').on('click',function(){
        if(!currentPostId){return;}
        var $btn=$(this), old=$btn.text(); $btn.prop('disabled',true).text('Saving…');
        $.post(STHI.ajaxUrl,{action:'sthi_details_save',nonce:STHI.nonce,post_id:currentPostId,payload:JSON.stringify(collectPayload())})
            .done(function(resp){
                if(!(resp&&resp.success)){ window.alert(resp&&resp.data&&resp.data.message||'Could not save details.'); return; }
                setHealth(resp.data.health,resp.data.health_label);
                $btn.text('Saved ✓'); setTimeout(function(){$btn.text(old);},900);
            })
            .fail(function(xhr){window.alert(xhr.responseJSON&&xhr.responseJSON.data&&xhr.responseJSON.data.message||'Could not save details.');})
            .always(function(){ $btn.prop('disabled',false); if($btn.text()==='Saving…'){$btn.text(old);} });
    });
})(jQuery);


/* v0.7.0 — Hotel editorial verification helper */
(function($){
    'use strict';
    $(document).on('click','[data-sthi-content-verify-now]',function(){
        var $box=$(this).closest('[data-sthi-content-editor]');
        var now=new Date();
        var yyyy=now.getFullYear();
        var mm=String(now.getMonth()+1).padStart(2,'0');
        var dd=String(now.getDate()).padStart(2,'0');
        $box.find('[name="sthi_content[verified_at]"]').val(yyyy+'-'+mm+'-'+dd);
        if(!$box.find('[name="sthi_content[verified_by]"]').val()){
            $box.find('[name="sthi_content[verified_by]"]').val($box.data('current-user')||'');
        }
    });
    $(document).on('click','[data-sthi-content-clear-verify]',function(){
        var $box=$(this).closest('[data-sthi-content-editor]');
        $box.find('[name="sthi_content[verified_at]"]').val('');
        $box.find('[name="sthi_content[verified_by]"]').val('');
    });
})(jQuery);
