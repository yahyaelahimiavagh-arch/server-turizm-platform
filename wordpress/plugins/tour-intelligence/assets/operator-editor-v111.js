(function () {
  'use strict';

  function $(selector, root) {
    return (root || document).querySelector(selector);
  }

  function canonical(form, name) {
    return form.querySelector('[name="' + name + '"]');
  }

  function emit(el) {
    if (!el) return;
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function isoToDisplay(iso) {
    var m = String(iso || '').match(/^(\d{4})-(\d{2})-(\d{2})$/);
    return m ? m[3] + '/' + m[2] + '/' + m[1] : '';
  }

  function displayToIso(display) {
    var text = String(display || '').trim();
    var m = text.match(/^(\d{2})[./-](\d{2})[./-](\d{4})$/);
    if (m) return m[3] + '-' + m[2] + '-' + m[1];
    m = text.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    return m ? text : '';
  }

  function daysBetween(startIso, endIso) {
    if (!startIso || !endIso) return null;
    var start = new Date(startIso + 'T00:00:00Z');
    var end = new Date(endIso + 'T00:00:00Z');
    var diff = Math.round((end.getTime() - start.getTime()) / 86400000);
    return diff >= 0 ? diff : null;
  }

  function setCanonicalValue(form, name, value) {
    var el = canonical(form, name);
    if (!el) return;
    el.value = value == null ? '' : String(value);
    emit(el);
  }

  function getCanonicalValue(form, name) {
    var el = canonical(form, name);
    return el ? el.value : '';
  }

  function setCanonicalDate(form, name, iso) {
    var text = canonical(form, name);
    var picker = form.querySelector('[data-stti-date-picker="' + name + '"]');
    if (text) {
      text.value = isoToDisplay(iso);
      emit(text);
    }
    if (picker) picker.value = iso || '';
  }

  function getCanonicalDate(form, name) {
    var picker = form.querySelector('[data-stti-date-picker="' + name + '"]');
    if (picker && picker.value) return picker.value;
    return displayToIso(getCanonicalValue(form, name));
  }

  function jumpToAdvanced(tabIndex) {
    document.body.classList.add('stti-operator-advanced');
    var toggle = $('#stti-operator-advanced-toggle');
    if (toggle) toggle.textContent = 'Basit Görünüm';
    var tab = document.querySelector('.stti-editor-tab[data-tab="' + tabIndex + '"]');
    if (tab) tab.click();
    var layout = $('.stti-editor-layout');
    if (layout) layout.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('stti-editor-form');
    if (!form) return;

    document.body.classList.add('stti-operator-mode');

    var stableInput = canonical(form, 'stable_id');
    var stableId = stableInput && stableInput.value ? stableInput.value : 'YENİ';
    var sourceType = getCanonicalValue(form, 'source_type') || 'manual';
    var sourceCompleteness = getCanonicalValue(form, 'source_completeness') || 'source_minimal';
    var customerPreview = form.querySelector('.stti-editor-footer a.button-primary');
    var technicalPreview = form.querySelector('.stti-editor-footer a.button:not(.button-primary)');

    var shell = document.createElement('section');
    shell.className = 'stti-operator-shell';
    shell.innerHTML =
      '<div class="stti-operator-toolbar">' +
        '<div class="stti-operator-heading">' +
          '<span class="stti-operator-kicker">GÜNLÜK KULLANIM</span>' +
          '<h2>Tur Bilgileri</h2>' +
          '<p>Şit mantığında sade düzenleme. Teknik alanlar gerektiğinde açılır.</p>' +
        '</div>' +
        '<div class="stti-operator-toolbar-actions">' +
          (customerPreview ? '<a class="button" id="stti-operator-preview" href="' + customerPreview.href + '">Müşteri Önizleme</a>' : '') +
          '<button type="button" class="button" id="stti-operator-advanced-toggle">Gelişmiş Alanlar</button>' +
          '<button type="button" class="button button-primary" id="stti-operator-save">Değişiklikleri Kaydet</button>' +
        '</div>' +
      '</div>' +
      '<div class="stti-operator-main">' +
        '<div class="stti-operator-statusbar">' +
          '<span><b>Site ID</b><strong id="stti-op-stable"></strong></span>' +
          '<span><b>Durum</b><strong id="stti-op-editorial-chip"></strong></span>' +
          '<span><b>Takvim</b><strong id="stti-op-schedule-chip"></strong></span>' +
          '<span><b>Rezervasyon</b><strong id="stti-op-availability-chip"></strong></span>' +
          '<span><b>Kaynak</b><strong id="stti-op-source-chip"></strong></span>' +
        '</div>' +
        '<div class="stti-operator-notice" id="stti-op-source-notice"></div>' +
        '<div class="stti-operator-grid">' +
          '<label class="stti-op-field stti-op-wide"><span>Tur Adı</span><input type="text" id="stti-op-title"></label>' +
          '<label class="stti-op-field"><span>Ülke / Hedef</span><input type="text" id="stti-op-country"></label>' +
          '<label class="stti-op-field"><span>Gidiş</span><input type="date" id="stti-op-start"></label>' +
          '<label class="stti-op-field"><span>Dönüş</span><input type="date" id="stti-op-end"></label>' +
          '<label class="stti-op-field"><span>Süre</span><input type="text" id="stti-op-duration" readonly></label>' +
          '<label class="stti-op-field"><span>Ana Fiyat</span><input type="text" inputmode="decimal" id="stti-op-price"></label>' +
          '<label class="stti-op-field"><span>Para Birimi</span><select id="stti-op-currency"><option>EUR</option><option>USD</option><option>TRY</option><option>GBP</option></select></label>' +
          '<label class="stti-op-field"><span>Rezervasyon</span><select id="stti-op-availability"><option value="open">Açık</option><option value="limited">Sınırlı</option><option value="sold_out">Dolu</option><option value="on_request">Talep Üzerine</option><option value="closed">Kapalı</option></select></label>' +
          '<label class="stti-op-field"><span>Vize</span><select id="stti-op-visa"><option value="unknown">Belirsiz</option><option value="required">Gerekli</option><option value="not_required">Gerekli Değil</option><option value="conditional">Duruma Göre</option></select></label>' +
          '<label class="stti-op-field stti-op-wide"><span>İnceleme Durumu</span><select id="stti-op-editorial"><option value="draft">Taslak</option><option value="needs_review">Kontrol Bekliyor</option><option value="approved">Onaylı</option></select><small>Onaylı olmak yayınlamak değildir. Public/SEO kilitleri ayrı kalır.</small></label>' +
        '</div>' +
        '<div class="stti-operator-route-card">' +
          '<div><span>ROTA</span><strong id="stti-op-route">—</strong></div>' +
          '<button type="button" class="button" data-stti-op-jump="3">Rota Detayı</button>' +
        '</div>' +
        '<div class="stti-operator-detail-actions">' +
          '<button type="button" class="button" data-stti-op-jump="4">Gün Gün Program</button>' +
          '<button type="button" class="button" data-stti-op-jump="5">Oteller</button>' +
          '<button type="button" class="button" data-stti-op-jump="6">Ulaşım</button>' +
          '<button type="button" class="button" data-stti-op-jump="7">Fiyat Detayları</button>' +
          '<button type="button" class="button" data-stti-op-jump="8">Dahil / Hariç</button>' +
          '<button type="button" class="button" data-stti-op-jump="9">Vize Detayı</button>' +
          (technicalPreview ? '<a class="button" href="' + technicalPreview.href + '">Teknik Önizleme</a>' : '') +
        '</div>' +
      '</div>';

    form.parentNode.insertBefore(shell, form);

    var refs = {
      title: $('#stti-op-title'),
      country: $('#stti-op-country'),
      start: $('#stti-op-start'),
      end: $('#stti-op-end'),
      duration: $('#stti-op-duration'),
      price: $('#stti-op-price'),
      currency: $('#stti-op-currency'),
      availability: $('#stti-op-availability'),
      visa: $('#stti-op-visa'),
      editorial: $('#stti-op-editorial'),
      route: $('#stti-op-route'),
      stable: $('#stti-op-stable'),
      editorialChip: $('#stti-op-editorial-chip'),
      scheduleChip: $('#stti-op-schedule-chip'),
      availabilityChip: $('#stti-op-availability-chip'),
      sourceChip: $('#stti-op-source-chip'),
      sourceNotice: $('#stti-op-source-notice')
    };

    function updateDuration() {
      var nights = daysBetween(refs.start.value, refs.end.value);
      refs.duration.value = nights === null ? '—' : nights + ' Gece / ' + (nights + 1) + ' Gün';
    }

    function refreshChips() {
      refs.stable.textContent = stableId;
      refs.editorialChip.textContent = ({ draft: 'TASLAK', needs_review: 'KONTROL', approved: 'ONAYLI' })[refs.editorial.value] || refs.editorial.value.toUpperCase();
      refs.scheduleChip.textContent = (getCanonicalValue(form, 'schedule_status') || '—').toUpperCase();
      refs.availabilityChip.textContent = ({ open: 'AÇIK', limited: 'SINIRLI', sold_out: 'DOLU', on_request: 'TALEP', closed: 'KAPALI' })[refs.availability.value] || refs.availability.value.toUpperCase();
      refs.sourceChip.textContent = (sourceType + ' · ' + sourceCompleteness.replace('source_', '')).toUpperCase();
      refs.route.textContent = getCanonicalValue(form, 'route') || 'Rota bilgisi yok';

      refs.sourceNotice.className = 'stti-operator-notice';
      if (sourceCompleteness === 'source_complete') {
        refs.sourceNotice.classList.add('is-ok');
        refs.sourceNotice.textContent = 'Kaynak tam. Yine de yalnız doğrulanmış bilgileri kullanın.';
      } else {
        refs.sourceNotice.classList.add('is-warn');
        refs.sourceNotice.textContent = 'Kaynak kısmi. Eksik alanları tahmin etmeyin; bilinmeyen olarak bırakmak doğrudur.';
      }
    }

    function syncFromCanonical() {
      refs.title.value = getCanonicalValue(form, 'public_title');
      refs.country.value = getCanonicalValue(form, 'primary_country');
      refs.start.value = getCanonicalDate(form, 'start_date');
      refs.end.value = getCanonicalDate(form, 'end_date');
      refs.price.value = getCanonicalValue(form, 'price_amount');
      refs.currency.value = getCanonicalValue(form, 'currency') || 'EUR';
      refs.availability.value = getCanonicalValue(form, 'availability') || 'open';
      refs.visa.value = getCanonicalValue(form, 'visa_status') || 'unknown';
      refs.editorial.value = getCanonicalValue(form, 'editorial') || 'needs_review';
      updateDuration();
      refreshChips();
    }

    function syncToCanonical() {
      setCanonicalValue(form, 'public_title', refs.title.value);
      setCanonicalValue(form, 'primary_country', refs.country.value);
      setCanonicalDate(form, 'start_date', refs.start.value);
      setCanonicalDate(form, 'end_date', refs.end.value);
      setCanonicalValue(form, 'price_amount', refs.price.value);
      setCanonicalValue(form, 'currency', refs.currency.value);
      setCanonicalValue(form, 'availability', refs.availability.value);
      setCanonicalValue(form, 'visa_status', refs.visa.value);
      setCanonicalValue(form, 'editorial', refs.editorial.value);
    }

    [refs.title, refs.country, refs.price, refs.currency, refs.availability, refs.visa, refs.editorial].forEach(function (el) {
      el.addEventListener('input', function () {
        syncToCanonical();
        refreshChips();
      });
      el.addEventListener('change', function () {
        syncToCanonical();
        refreshChips();
      });
    });

    [refs.start, refs.end].forEach(function (el) {
      el.addEventListener('change', function () {
        syncToCanonical();
        updateDuration();
        refreshChips();
      });
    });

    document.querySelectorAll('[data-stti-op-jump]').forEach(function (button) {
      button.addEventListener('click', function () {
        syncToCanonical();
        jumpToAdvanced(parseInt(button.getAttribute('data-stti-op-jump'), 10));
      });
    });

    var toggle = $('#stti-operator-advanced-toggle');
    toggle.addEventListener('click', function () {
      var opening = !document.body.classList.contains('stti-operator-advanced');
      if (opening) syncToCanonical();
      document.body.classList.toggle('stti-operator-advanced', opening);
      toggle.textContent = opening ? 'Basit Görünüm' : 'Gelişmiş Alanlar';
      if (!opening) {
        syncFromCanonical();
        shell.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });

    $('#stti-operator-save').addEventListener('click', function () {
      syncToCanonical();
      if (refs.editorial.value === 'approved' && sourceCompleteness !== 'source_complete') {
        var proceed = window.confirm('Kaynak kısmi. Eksik bilgiler bilinmeyen olarak kalacak. Bu turu yine de ONAYLI olarak kaydetmek istiyor musunuz?');
        if (!proceed) return;
      }
      if (typeof form.requestSubmit === 'function') form.requestSubmit();
      else form.submit();
    });

    form.addEventListener('submit', function () {
      syncToCanonical();
    });

    var topTitle = $('.stti-topbar h1');
    var topDescription = $('.stti-topbar p');
    if (topTitle) topTitle.textContent = 'Tur Düzenle';
    if (topDescription) topDescription.textContent = 'Günlük kullanım için sade görünüm. Teknik alanlar gerektiğinde açılır.';

    var navItems = document.querySelectorAll('.stti-nav-item');
    navItems.forEach(function (item) {
      if (item.textContent.indexOf('Create / Edit Tour') !== -1) {
        var label = item.querySelector('span:last-child');
        if (label) label.textContent = 'Tur Düzenle';
      }
    });

    syncFromCanonical();
  });
})();
