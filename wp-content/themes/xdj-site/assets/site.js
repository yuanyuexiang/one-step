(() => {
    'use strict';
    const menuToggle = document.querySelector('.nav-toggle');
    const siteHeader = menuToggle?.closest('header');
    if (menuToggle && siteHeader) {
        document.documentElement.classList.add('nav-ready');
        const closeMenu = () => {
            siteHeader.removeAttribute('data-nav-open');
            menuToggle.setAttribute('aria-expanded', 'false');
        };
        menuToggle.addEventListener('click', () => {
            const open = menuToggle.getAttribute('aria-expanded') !== 'true';
            siteHeader.toggleAttribute('data-nav-open', open);
            menuToggle.setAttribute('aria-expanded', String(open));
        });
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && menuToggle.getAttribute('aria-expanded') === 'true') {
                closeMenu(); menuToggle.focus();
            }
        });
        window.matchMedia('(max-width: 600px)').addEventListener('change', closeMenu);
    }
    const reduce = window.matchMedia('(prefers-reduced-motion: reduce)');
    document.querySelectorAll('[data-carousel]').forEach(carousel => {
        const slides = [...carousel.querySelectorAll('[data-slide]')];
        const dots = [...carousel.querySelectorAll('[data-dot]')];
        const pauseButton = carousel.querySelector('[data-pause]');
        let index = 0, paused = reduce.matches || carousel.hasAttribute('data-manual'), timer;
        function show(next) {
            index = (next + slides.length) % slides.length;
            slides.forEach((slide, i) => { slide.hidden = i !== index; });
            dots.forEach((dot, i) => dot.setAttribute('aria-pressed', String(i === index)));
        }
        function stop() { clearInterval(timer); }
        function start() { stop(); if (!paused && !document.hidden && !reduce.matches && !carousel.matches(':hover') && !carousel.matches(':focus-within') && !carousel.closest('details:not([open])')) timer = setInterval(() => show(index + 1), 4000); }
        function label() { pauseButton.textContent = paused ? '▶' : 'Ⅱ'; pauseButton.setAttribute('aria-label', paused ? pauseButton.dataset.playLabel : pauseButton.dataset.pauseLabel); }
        carousel.querySelector('[data-prev]').addEventListener('click', () => { show(index - 1); start(); });
        carousel.querySelector('[data-next]').addEventListener('click', () => { show(index + 1); start(); });
        dots.forEach((dot, i) => dot.addEventListener('click', () => { show(i); start(); }));
        pauseButton.addEventListener('click', () => { paused = !paused; label(); start(); });
        carousel.addEventListener('mouseenter', stop); carousel.addEventListener('mouseleave', start);
        carousel.addEventListener('focusin', stop); carousel.addEventListener('focusout', event => { if (!carousel.contains(event.relatedTarget)) start(); });
        carousel.addEventListener('keydown', event => { if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') { event.preventDefault(); show(index + (event.key === 'ArrowRight' ? 1 : -1)); } });
        let touchX;
        carousel.addEventListener('touchstart', event => { touchX = event.changedTouches[0].clientX; stop(); }, {passive:true});
        carousel.addEventListener('touchend', event => { const dx = event.changedTouches[0].clientX - touchX; if (Math.abs(dx) > 45) show(index + (dx < 0 ? 1 : -1)); start(); }, {passive:true});
        document.addEventListener('visibilitychange', start);
        carousel.closest('details')?.addEventListener('toggle', start);
        reduce.addEventListener('change', () => { paused = reduce.matches; label(); start(); });
        show(0); label(); start();
    });
    document.querySelectorAll('[data-gallery]').forEach(gallery => {
        const main = gallery.querySelector('.gallery-main');
        gallery.querySelectorAll('[data-gallery-index]').forEach(button => button.addEventListener('click', () => {
            main.href = button.dataset.src; const preview = main.querySelector('img');
            preview.srcset = button.dataset.srcset || ''; preview.sizes = '(max-width: 900px) 90vw, 50vw';
            preview.src = button.dataset.preview || button.dataset.src;
            gallery.querySelectorAll('[data-gallery-index]').forEach(item => item.setAttribute('aria-pressed', String(item === button)));
        }));
    });
    const imageDialog = document.querySelector('[data-image-dialog]');
    let imageSet = [], imageIndex = 0, lastFocus;
    function openImage(index) {
        imageIndex = (index + imageSet.length) % imageSet.length;
        const image = imageSet[imageIndex];
        imageDialog.querySelector('figure img').src = image.src;
        imageDialog.querySelector('figure img').alt = image.alt;
        imageDialog.querySelector('figcaption').textContent = `${image.alt} · ${imageIndex + 1} / ${imageSet.length}`;
        if (!imageDialog.open) { lastFocus = document.activeElement; imageDialog.showModal(); }
    }
    document.querySelectorAll('[data-lightbox]').forEach(anchor => anchor.addEventListener('click', event => {
        if (!imageDialog?.showModal) return;
        event.preventDefault();
        const gallery = anchor.closest('[data-gallery]');
        if (gallery) imageSet = [...gallery.querySelectorAll('[data-gallery-index]')].map(button => ({src:button.dataset.src, alt:anchor.querySelector('img').alt}));
        else imageSet = [...anchor.closest('.archive-gallery, .technical-content, .page-content').querySelectorAll('[data-lightbox]')].map(link => ({src:link.href, alt:link.querySelector('img')?.alt || ''}));
        openImage(Math.max(0, imageSet.findIndex(image => image.src === anchor.href)));
    }));
    document.querySelectorAll('.technical-content img, .source-manual img').forEach(img => {
        if (img.closest('a')) return;
        const a = document.createElement('a'); a.href = img.dataset.originalSrc || img.src; a.dataset.lightbox = '';
        img.replaceWith(a); a.append(img);
        a.addEventListener('click', event => { if (!imageDialog?.showModal) return; event.preventDefault(); imageSet = [{src:a.href,alt:img.alt}]; openImage(0); });
    });
    imageDialog?.querySelector('[data-image-prev]').addEventListener('click', () => openImage(imageIndex - 1));
    imageDialog?.querySelector('[data-image-next]').addEventListener('click', () => openImage(imageIndex + 1));
    imageDialog?.addEventListener('keydown', event => { if (event.key === 'ArrowLeft') openImage(imageIndex - 1); if (event.key === 'ArrowRight') openImage(imageIndex + 1); });
    document.querySelectorAll('dialog').forEach(dialog => {
        dialog.querySelector('[data-dialog-close]')?.addEventListener('click', () => dialog.close());
        dialog.addEventListener('click', event => { if (event.target === dialog) dialog.close(); });
        dialog.addEventListener('close', () => { if (dialog === imageDialog) { dialog.querySelector('figure img').removeAttribute('src'); lastFocus?.focus(); } });
    });
    const videoDialog=document.querySelector('[data-video-dialog]');
    document.querySelectorAll('[data-video-open]').forEach(link=>link.addEventListener('click',event=>{if(!videoDialog?.showModal)return;event.preventDefault();videoDialog.showModal();videoDialog.querySelector('video').play().catch(()=>{});}));
    videoDialog?.addEventListener('close',()=>videoDialog.querySelector('video').pause());
    const topButton = document.querySelector('[data-top]');
    function updateTop() { if (topButton) topButton.hidden = window.scrollY < window.innerHeight; }
    window.addEventListener('scroll', updateTop, {passive:true}); updateTop();
    topButton?.addEventListener('click', () => window.scrollTo({top:0,behavior:reduce.matches?'auto':'smooth'}));
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(entries => entries.forEach(entry => {
            if (!entry.isIntersecting) return; observer.unobserve(entry.target);
            const el = entry.target, target = Number(el.dataset.counter), begin = performance.now();
            if (reduce.matches) return;
            function frame(now) { const progress = Math.min(1,(now-begin)/2000); el.textContent = Math.round(target*(1-Math.pow(1-progress,3))).toLocaleString(document.documentElement.lang); if(progress<1) requestAnimationFrame(frame); }
            requestAnimationFrame(frame);
        }), {threshold:0.5});
        document.querySelectorAll('[data-counter]').forEach(el => observer.observe(el));
    }
    const assistant = document.querySelector('[data-assistant]');
    document.querySelector('[data-open-assistant]')?.addEventListener('click', () => assistant?.showModal());
    assistant?.querySelector('form').addEventListener('submit', async event => {
        event.preventDefault(); const button = event.target.querySelector('button'), box=assistant.querySelector('.assistant-results');
        button.disabled=true; box.textContent='…';
        try {
            const url=new URL(assistant.dataset.endpoint); url.searchParams.set('q',event.target.elements.q.value); url.searchParams.set('lang',assistant.dataset.language);
            const useAI=assistant.querySelector('[name="ai_consent"]')?.checked;
            const response=useAI?await fetch(assistant.dataset.chatEndpoint,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({q:event.target.elements.q.value,lang:assistant.dataset.language,consent:true})}):await fetch(url); if(!response.ok)throw new Error(String(response.status)); const data=await response.json();
            box.replaceChildren(); const intro=document.createElement('p');intro.textContent=data.message;box.append(intro);
            for(const item of data.results){const article=document.createElement('article'),a=document.createElement('a'),p=document.createElement('p');a.href=item.url;a.textContent=item.title+' ↗';p.textContent=item.excerpt;article.append(a,p);box.append(article);}
        } catch {box.textContent=assistant.querySelector('p').textContent;} finally {button.disabled=false;}
    });
    document.querySelectorAll('.nav-dropdown').forEach(detail => detail.addEventListener('toggle', () => {if(detail.open)document.querySelectorAll('.nav-dropdown').forEach(other => {if(other!==detail)other.open=false;});}));
    document.addEventListener('click', event => {document.querySelectorAll('details.nav-dropdown[open],details.header-search[open]').forEach(detail => {if(!detail.contains(event.target))detail.open=false;});});
    document.addEventListener('keydown', event => {if(event.key==='Escape')document.querySelectorAll('details[open]').forEach(detail=>detail.open=false);});
})();
