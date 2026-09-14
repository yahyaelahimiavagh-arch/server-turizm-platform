(function(){
    'use strict';



    function initOwnedHomepageFullBleed(){
        const root=document.querySelector('.porto-custom-wrapper');
        if(!root) return;
        let raf=0;
        function sync(){
            raf=0;
            const width=document.documentElement.clientWidth;
            if(width>0){ root.style.setProperty('--sthi-home-viewport-width',width+'px'); }
        }
        function requestSync(){
            if(raf) cancelAnimationFrame(raf);
            raf=requestAnimationFrame(sync);
        }
        sync();
        window.addEventListener('resize',requestSync,{passive:true});
        window.addEventListener('orientationchange',requestSync,{passive:true});
    }

    function initCampaignEmptyState(){
        const marker=document.querySelector('[data-sthi-home-campaign-empty]');
        if(!marker) return;
        const galleryArea=marker.closest('.hero-gallery-area');
        const host=marker.closest('.col-lg-5');
        if(galleryArea){
            galleryArea.hidden=true;
            galleryArea.setAttribute('aria-hidden','true');
        }
        if(host){
            host.classList.add('sthi-home-campaign-empty-host');
            const searchBtn=host.querySelector('#open-search-btn');
            if(searchBtn){ searchBtn.classList.add('sthi-home-campaign-empty-search'); }
        }
    }

    function initCampaignPager(){
        const root=document.querySelector('[data-sthi-home-campaign]');
        if(!root) return;
        const pages=Array.from(root.querySelectorAll('[data-sthi-campaign-page]'));
        if(pages.length<2) return;
        const prev=root.querySelector('[data-sthi-campaign-prev]');
        const next=root.querySelector('[data-sthi-campaign-next]');
        const dots=Array.from(root.querySelectorAll('[data-sthi-campaign-dot]'));
        const counter=root.querySelector('[data-sthi-campaign-page-counter]');
        let pageIndex=0;
        let pointerStartX=null;

        function show(i,focusDot){
            pageIndex=(i+pages.length)%pages.length;
            pages.forEach((page,index)=>{
                const active=index===pageIndex;
                page.hidden=!active;
                page.setAttribute('aria-hidden',active?'false':'true');
            });
            dots.forEach((dot,index)=>{
                const active=index===pageIndex;
                dot.classList.toggle('is-active',active);
                dot.setAttribute('aria-selected',active?'true':'false');
                dot.tabIndex=active?0:-1;
            });
            if(counter) counter.textContent=(pageIndex+1)+' / '+pages.length;
            if(focusDot && dots[pageIndex]) dots[pageIndex].focus();
        }

        if(prev) prev.addEventListener('click',()=>show(pageIndex-1,false));
        if(next) next.addEventListener('click',()=>show(pageIndex+1,false));
        dots.forEach((dot,index)=>dot.addEventListener('click',()=>show(index,false)));

        root.addEventListener('keydown',e=>{
            if(e.target && e.target.matches('[data-sthi-lightbox-open]')) return;
            if(e.key==='ArrowLeft'){ e.preventDefault(); show(pageIndex-1,false); }
            if(e.key==='ArrowRight'){ e.preventDefault(); show(pageIndex+1,false); }
        });

        root.addEventListener('pointerdown',e=>{
            if(e.pointerType==='mouse') return;
            pointerStartX=e.clientX;
        },{passive:true});
        root.addEventListener('pointerup',e=>{
            if(pointerStartX===null || e.pointerType==='mouse') return;
            const delta=e.clientX-pointerStartX;
            pointerStartX=null;
            if(Math.abs(delta)<42) return;
            show(pageIndex+(delta<0?1:-1),false);
        },{passive:true});
        root.addEventListener('pointercancel',()=>{pointerStartX=null;},{passive:true});

        show(0,false);
    }

    function initLightbox(){
        const modal=document.querySelector('[data-sthi-lightbox]');
        if(!modal) return;
        const items=Array.from(document.querySelectorAll('[data-sthi-lightbox-open]'));
        if(!items.length) return;
        const image=modal.querySelector('[data-sthi-lightbox-image]');
        const caption=modal.querySelector('[data-sthi-lightbox-caption]');
        const counter=modal.querySelector('[data-sthi-lightbox-counter]');
        const prev=modal.querySelector('[data-sthi-lightbox-prev]');
        const next=modal.querySelector('[data-sthi-lightbox-next]');
        const closeButtons=modal.querySelectorAll('[data-sthi-lightbox-close]');

        // Portal to body so transformed legacy Homepage ancestors cannot scope
        // position:fixed to the hero panel.
        if(modal.parentElement!==document.body){ document.body.appendChild(modal); }

        let index=0, lastFocus=null;
        function render(){
            const item=items[index];
            image.src=item.dataset.sthiLightboxSrc||'';
            image.alt=item.dataset.sthiLightboxAlt||'';
            caption.textContent=item.dataset.sthiLightboxCaption||'';
            counter.textContent=(index+1)+' / '+items.length;
            const disabled=items.length<2;
            prev.disabled=disabled;
            next.disabled=disabled;
        }
        function open(i,trigger){
            index=i;
            lastFocus=trigger||document.activeElement;
            render();
            modal.hidden=false;
            modal.setAttribute('aria-hidden','false');
            document.documentElement.classList.add('sthi-home-lightbox-open');
            document.body.classList.add('sthi-home-lightbox-open');
            const close=modal.querySelector('.sthi-home-lightbox__close');
            if(close) close.focus();
        }
        function close(){
            modal.hidden=true;
            modal.setAttribute('aria-hidden','true');
            document.documentElement.classList.remove('sthi-home-lightbox-open');
            document.body.classList.remove('sthi-home-lightbox-open');
            image.removeAttribute('src');
            if(lastFocus && typeof lastFocus.focus==='function') lastFocus.focus();
        }
        function move(delta){
            index=(index+delta+items.length)%items.length;
            render();
        }

        items.forEach((item,i)=>item.addEventListener('click',()=>open(i,item)));
        prev.addEventListener('click',()=>move(-1));
        next.addEventListener('click',()=>move(1));
        closeButtons.forEach(btn=>btn.addEventListener('click',close));
        document.addEventListener('keydown',function(e){
            if(modal.hidden) return;
            if(e.key==='Escape'){ e.preventDefault(); close(); }
            else if(e.key==='ArrowLeft'){ e.preventDefault(); move(-1); }
            else if(e.key==='ArrowRight'){ e.preventDefault(); move(1); }
        });
    }

    initOwnedHomepageFullBleed();
    initCampaignEmptyState();
    initCampaignPager();
    initLightbox();
})();
