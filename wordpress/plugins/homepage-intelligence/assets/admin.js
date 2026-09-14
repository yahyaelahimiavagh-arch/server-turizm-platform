(function($){
    function setRemoveState(item, enabled){
        const remove = item ? item.querySelector('[data-sthi-remove-media]') : null;
        if(remove) remove.disabled = !enabled;
    }

    function openMedia(button){
        const item = button.closest('[data-sthi-item]');
        const frame = wp.media({
            title: STHI_HOME_ADMIN.choose,
            button: { text: STHI_HOME_ADMIN.use },
            multiple: false,
            library: { type: 'image' }
        });
        frame.on('select', function(){
            const att = frame.state().get('selection').first().toJSON();
            item.querySelector('[data-sthi-attachment-id]').value = att.id || 0;
            item.querySelector('[data-sthi-image-url]').value = att.url || '';
            const preview = item.querySelector('[data-sthi-media-preview]');
            preview.innerHTML = '<img src="' + (att.sizes && att.sizes.medium ? att.sizes.medium.url : att.url) + '" alt="" />';

            // SEO-safe convenience: only fill ALT/title when the operator has
            // not already written something. Never overwrite editorial text.
            const alt = item.querySelector('input[name$="[alt]"]');
            if(alt && !alt.value.trim()){
                alt.value = (att.alt || att.title || '').trim();
            }
            const title = item.querySelector('input[name$="[title]"]');
            if(title && !title.value.trim()){
                title.value = (att.title || '').trim();
            }
            setRemoveState(item, true);
        });
        frame.open();
    }

    function removeMedia(button){
        const item = button.closest('[data-sthi-item]');
        if(!item) return;
        const aid = item.querySelector('[data-sthi-attachment-id]');
        const url = item.querySelector('[data-sthi-image-url]');
        const preview = item.querySelector('[data-sthi-media-preview]');
        const active = item.querySelector('input[type="checkbox"][name$="[enabled]"]');

        if(aid) aid.value = '0';
        if(url) url.value = '';
        if(preview) preview.innerHTML = '<span>Görsel yok</span>';
        if(active) active.checked = false;
        setRemoveState(item, false);
    }

    function syncActionFields(item){
        if(!item) return;
        const select = item.querySelector('[data-sthi-action-mode]');
        if(!select) return;
        const show = select.value !== 'none';
        item.querySelectorAll('[data-sthi-link-field]').forEach(el=>{
            el.style.display = show ? '' : 'none';
        });
        const help = item.querySelector('[data-sthi-link-help]');
        if(help) help.style.display = show ? '' : 'none';
    }


    function byName(name){
        const list = document.getElementsByName(name);
        return list && list.length ? list[0] : null;
    }

    function setField(name, value){
        const el = byName(name);
        if(!el) return false;
        if(el.type === 'checkbox'){
            el.checked = !!value;
        } else {
            el.value = value == null ? '' : String(value);
            el.dispatchEvent(new Event('input', {bubbles:true}));
            el.dispatchEvent(new Event('change', {bubbles:true}));
        }
        return true;
    }

    function isSafeUrl(value){
        if(value == null || value === '') return true;
        const v = String(value).trim();
        return /^https?:\/\//i.test(v) || v.startsWith('/');
    }

    function clampNumber(value, min, max){
        const n = Number(value);
        if(!Number.isFinite(n)) return null;
        return Math.max(min, Math.min(max, Math.round(n)));
    }

    function applyJsonPayload(payload){
        if(!payload || typeof payload !== 'object' || Array.isArray(payload)){
            throw new Error('JSON kök değeri object olmalıdır.');
        }
        if(payload.schema !== 'sthi-homepage-ai/v1'){
            throw new Error('schema tam olarak "sthi-homepage-ai/v1" olmalıdır.');
        }

        const setText = (name, obj, key)=>{
            if(obj && Object.prototype.hasOwnProperty.call(obj,key)) setField(name, obj[key]);
        };
        const setUrl = (name, obj, key)=>{
            if(!obj || !Object.prototype.hasOwnProperty.call(obj,key)) return;
            if(!isSafeUrl(obj[key])) throw new Error(key + ' için yalnızca http(s) veya /... URL kabul edilir.');
            setField(name, obj[key]);
        };

        const seo = payload.seo || {};
        setText('page_settings[seo_title]', seo, 'title');
        setText('page_settings[meta_description]', seo, 'meta_description');
        setText('page_settings[social_title]', seo, 'social_title');
        setText('page_settings[social_description]', seo, 'social_description');

        const hero = payload.hero || {};
        setText('page_settings[hero_h1]', hero, 'h1');
        setText('page_settings[hero_intro]', hero, 'intro');
        setText('page_settings[search_button_label]', hero, 'search_button_label');

        const bg = hero.background || {};
        if(Object.prototype.hasOwnProperty.call(bg,'zoom')){
            const v = clampNumber(bg.zoom,100,180); if(v === null) throw new Error('hero.background.zoom sayısal olmalıdır.');
            setField('page_settings[hero_background_zoom]',v);
        }
        if(Object.prototype.hasOwnProperty.call(bg,'x')){
            const v = clampNumber(bg.x,0,100); if(v === null) throw new Error('hero.background.x sayısal olmalıdır.');
            setField('page_settings[hero_background_x]',v);
        }
        if(Object.prototype.hasOwnProperty.call(bg,'y')){
            const v = clampNumber(bg.y,0,100); if(v === null) throw new Error('hero.background.y sayısal olmalıdır.');
            setField('page_settings[hero_background_y]',v);
        }

        if(Array.isArray(hero.quick_links)){
            hero.quick_links.slice(0,6).forEach((item,i)=>{
                if(!item || typeof item !== 'object') return;
                const base='hero_links['+i+']';
                if(Object.prototype.hasOwnProperty.call(item,'enabled')) setField(base+'[enabled]', !!item.enabled);
                setText(base+'[label]', item, 'label');
                if(Object.prototype.hasOwnProperty.call(item,'url')){
                    if(!isSafeUrl(item.url)) throw new Error('hero.quick_links['+i+'].url geçersiz.');
                    setField(base+'[url]', item.url);
                }
                setText(base+'[icon]', item, 'icon');
                if(Object.prototype.hasOwnProperty.call(item,'order')){
                    const v=clampNumber(item.order,1,20); if(v!==null) setField(base+'[order]',v);
                }
            });
        }

        const umre = payload.featured_umre || {};
        setText('page_settings[umre_section_title]', umre, 'title');
        setText('page_settings[umre_cta_label]', umre, 'cta_label');
        setUrl('page_settings[umre_cta_url]', umre, 'cta_url');

        const applyGrid = (prefix,obj,isTour)=>{
            if(!obj || typeof obj !== 'object') return;
            if(Object.prototype.hasOwnProperty.call(obj,'enabled')) setField(prefix+'[enabled]', obj.enabled ? '1':'0');
            if(Object.prototype.hasOwnProperty.call(obj,'layout_mode')) setField(prefix+'[layout_mode]', obj.layout_mode === 'manual' ? 'manual':'auto');
            if(Object.prototype.hasOwnProperty.call(obj,'columns')){
                const v=clampNumber(obj.columns,1,5); if(v!==null) setField(prefix+'[columns]',v);
            }
            if(Object.prototype.hasOwnProperty.call(obj,'direction')) setField(prefix+'[direction]', obj.direction === 'rtl' ? 'rtl':'ltr');
            if(Object.prototype.hasOwnProperty.call(obj,'flow')) setField(prefix+'[flow]', obj.flow === 'column' ? 'column':'row');
            if(Object.prototype.hasOwnProperty.call(obj,'max_items')){
                const v=clampNumber(obj.max_items,1,10); if(v!==null) setField(prefix+'[max_items]',v);
            }
            if(isTour){
                setText(prefix+'[title]', obj, 'title');
                setText(prefix+'[intro]', obj, 'intro');
            }
        };
        applyGrid('campaign_settings', payload.campaign_grid || {}, false);
        applyGrid('tour_settings', payload.culture_tours || {}, true);

    }

    function setJsonStatus(message, isError){
        const el = document.querySelector('[data-sthi-json-status]');
        if(!el) return;
        el.textContent = message || '';
        el.classList.toggle('is-error', !!isError);
        el.classList.toggle('is-success', !isError && !!message);
    }


    document.querySelectorAll('[data-sthi-item]').forEach(item=>{
        syncActionFields(item);
        const hasMedia = !!((item.querySelector('[data-sthi-image-url]') || {}).value || parseInt(((item.querySelector('[data-sthi-attachment-id]') || {}).value || '0'), 10));
        setRemoveState(item, hasMedia);
    });

    document.querySelectorAll('[data-sthi-count-input]').forEach(input=>{
        const targetId = input.getAttribute('data-sthi-count-target');
        const target = targetId ? document.getElementById(targetId) : null;
        const sync = ()=>{ if(target) target.textContent = String((input.value || '').length); };
        input.addEventListener('input', sync);
        sync();
    });

    document.addEventListener('change', function(e){
        if(e.target.matches('[data-sthi-action-mode]')){
            syncActionFields(e.target.closest('[data-sthi-item]'));
        }
    });

    document.addEventListener('click', function(e){
        const media = e.target.closest('[data-sthi-choose-media]');
        if(media){ e.preventDefault(); openMedia(media); return; }

        const remove = e.target.closest('[data-sthi-remove-media]');
        if(remove){ e.preventDefault(); removeMedia(remove); return; }


        const jsonApply = e.target.closest('[data-sthi-json-apply]');
        if(jsonApply){
            e.preventDefault();
            const input=document.querySelector('[data-sthi-json-input]');
            try{
                const payload=JSON.parse((input && input.value) || '');
                applyJsonPayload(payload);
                setJsonStatus('JSON forma uygulandı. Henüz kaydedilmedi; Preview/Save gate zorunlu.', false);
            }catch(err){
                setJsonStatus('JSON uygulanmadı: ' + (err && err.message ? err.message : String(err)), true);
            }
            return;
        }

        const jsonClear = e.target.closest('[data-sthi-json-clear]');
        if(jsonClear){
            e.preventDefault();
            const input=document.querySelector('[data-sthi-json-input]');
            if(input) input.value='';
            setJsonStatus('', false);
            return;
        }

        const jsonCopy = e.target.closest('[data-sthi-json-copy]');
        if(jsonCopy){
            e.preventDefault();
            const source=document.querySelector('[data-sthi-json-template]');
            if(source){
                const text=source.value || '';
                if(navigator.clipboard && navigator.clipboard.writeText){
                    navigator.clipboard.writeText(text).then(()=>setJsonStatus('Mevcut AI JSON panoya kopyalandı.',false)).catch(()=>{source.select();document.execCommand('copy');setJsonStatus('Mevcut AI JSON panoya kopyalandı.',false);});
                }else{
                    source.select();document.execCommand('copy');setJsonStatus('Mevcut AI JSON panoya kopyalandı.',false);
                }
            }
            return;
        }

        const tab = e.target.closest('[data-sthi-tab-button]');
        if(tab){
            e.preventDefault();
            const name = tab.getAttribute('data-sthi-tab-button');
            document.querySelectorAll('[data-sthi-tab-button]').forEach(el=>el.classList.toggle('is-active', el===tab));
            document.querySelectorAll('[data-sthi-tab]').forEach(el=>el.classList.toggle('is-active', el.getAttribute('data-sthi-tab')===name));
        }
    });
})(jQuery);
