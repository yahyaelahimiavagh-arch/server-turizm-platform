(function () {
    'use strict';

    var dialog = document.getElementById('stpubLightbox');
    if (!dialog) { return; }

    var panel = dialog.querySelector('.stpub-lightbox-panel');
    var image = dialog.querySelector('.stpub-lightbox-image');
    var title = dialog.querySelector('#stpubLightboxTitle');
    var counter = dialog.querySelector('.stpub-lightbox-counter');
    var closeButton = dialog.querySelector('.stpub-lightbox-close');
    var prevButton = dialog.querySelector('.stpub-lightbox-prev');
    var nextButton = dialog.querySelector('.stpub-lightbox-next');
    var items = [];
    var index = 0;
    var lastTrigger = null;

    function cardItems(trigger) {
        var card = trigger.closest('.hotel-card');
        if (!card) { return []; }
        var nodes = Array.prototype.slice.call(card.querySelectorAll('[data-gallery-src]'));
        var seen = Object.create(null);
        return nodes.filter(function (node) {
            var src = node.getAttribute('data-gallery-src') || '';
            if (!src || seen[src]) { return false; }
            seen[src] = true;
            return true;
        });
    }

    function update() {
        if (!items.length) { return; }
        var item = items[index];
        var src = item.getAttribute('data-gallery-src') || '';
        var alt = item.getAttribute('data-gallery-alt') || '';
        var hotel = item.getAttribute('data-gallery-hotel') || 'Otel fotoğrafları';
        image.src = src;
        image.alt = alt;
        title.textContent = hotel;
        counter.textContent = (index + 1) + ' / ' + items.length;
        var single = items.length < 2;
        prevButton.hidden = single;
        nextButton.hidden = single;
    }

    function openGallery(trigger) {
        items = cardItems(trigger);
        if (!items.length) { return; }
        lastTrigger = trigger;
        index = items.indexOf(trigger);
        if (index < 0) { index = 0; }
        update();
        if (typeof dialog.showModal === 'function') {
            dialog.showModal();
        } else {
            dialog.setAttribute('open', '');
        }
        closeButton.focus();
    }

    function move(delta) {
        if (items.length < 2) { return; }
        index = (index + delta + items.length) % items.length;
        update();
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-gallery-src]');
        if (trigger) {
            event.preventDefault();
            openGallery(trigger);
            return;
        }
        if (event.target === dialog) {
            dialog.close();
        }
    });

    closeButton.addEventListener('click', function () { dialog.close(); });
    prevButton.addEventListener('click', function () { move(-1); });
    nextButton.addEventListener('click', function () { move(1); });

    dialog.addEventListener('keydown', function (event) {
        if (event.key === 'ArrowLeft') {
            event.preventDefault();
            move(-1);
        } else if (event.key === 'ArrowRight') {
            event.preventDefault();
            move(1);
        }
    });

    dialog.addEventListener('close', function () {
        image.removeAttribute('src');
        image.alt = '';
        if (lastTrigger && typeof lastTrigger.focus === 'function') {
            lastTrigger.focus();
        }
    });
})();
