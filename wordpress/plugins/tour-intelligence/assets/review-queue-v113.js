(function () {
  'use strict';

  function esc(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  // stti_view_url() is an HTML-context helper and may arrive through wp_localize_script
  // with ampersands already entity-escaped. Normalize it back to a raw URL before the
  // final HTML attribute escape below; otherwise `view`/`tour` become `amp;view` and
  // WordPress falls back to the Dashboard.
  function rawUrl(value) {
    return String(value == null ? '' : value)
      .replace(/&#0*38;/gi, '&')
      .replace(/&amp;/gi, '&');
  }

  function toneLabel(tone) {
    return ({ ok: 'Hazır', warn: 'Kontrol', block: 'Eksik', neutral: 'Bilgi' })[tone] || 'Bilgi';
  }

  function checkCard(check) {
    return '' +
      '<div class="stti-rq-check is-' + esc(check.tone) + '">' +
        '<div class="stti-rq-check-top"><span>' + esc(check.label) + '</span><b>' + esc(toneLabel(check.tone)) + '</b></div>' +
        '<strong>' + esc(check.state) + '</strong>' +
        '<small>' + esc(check.detail || '') + '</small>' +
      '</div>';
  }

  function list(items, emptyText) {
    if (!items || !items.length) return '<p class="stti-rq-empty-inline">' + esc(emptyText) + '</p>';
    return '<ul>' + items.map(function (item) { return '<li>' + esc(item) + '</li>'; }).join('') + '</ul>';
  }

  function itemCard(item, index) {
    var ready = !!item.approvalReady;
    var checks = (item.checks || []).map(checkCard).join('');
    var detailId = 'stti-rq-detail-' + index;
    var editUrl = rawUrl(item.editUrl);
    var previewUrl = rawUrl(item.previewUrl);

    return '' +
      '<article class="stti-rq-card ' + (ready ? 'is-ready' : 'is-blocked') + '">' +
        '<header class="stti-rq-card-head">' +
          '<div class="stti-rq-title">' +
            '<span class="stti-rq-stable">' + esc(item.stableId) + '</span>' +
            '<h3>' + esc(item.title || 'Başlıksız Tur') + '</h3>' +
            '<p>' + esc(item.country) + ' · ' + esc(String(item.schedule || '').toUpperCase()) + ' · ' + esc(String(item.availability || '').toUpperCase()) + '</p>' +
          '</div>' +
          '<div class="stti-rq-state ' + (ready ? 'is-ready' : 'is-blocked') + '">' +
            '<b>' + (ready ? 'ONAYA HAZIR' : 'EKSİKLER VAR') + '</b>' +
            '<span>' + (ready ? 'Hard blocker yok' : esc(item.hardBlockerCount) + ' blocker') + '</span>' +
          '</div>' +
        '</header>' +
        '<div class="stti-rq-check-grid">' + checks + '</div>' +
        '<div class="stti-rq-meta">' +
          '<span><b>Checksum</b>' + esc(item.checksum || '—') + '</span>' +
          '<span><b>Son güncelleme</b>' + esc(item.updatedAt || '—') + '</span>' +
          '<span><b>Uyarı</b>' + esc(item.warningCount || 0) + '</span>' +
        '</div>' +
        '<div class="stti-rq-detail" id="' + detailId + '" hidden>' +
          '<div class="stti-rq-detail-col is-blockers"><h4>Onay blockerları</h4>' + list(item.blockers, 'Hard blocker yok.') + '</div>' +
          '<div class="stti-rq-detail-col is-warnings"><h4>Uyarılar</h4>' + list(item.warnings, 'Ek uyarı yok.') + '</div>' +
        '</div>' +
        '<footer class="stti-rq-actions">' +
          '<button type="button" class="button stti-rq-toggle" aria-expanded="false" aria-controls="' + detailId + '">İncele</button>' +
          (previewUrl ? '<a class="button" href="' + esc(previewUrl) + '">Müşteri Önizleme</a>' : '') +
          '<a class="button" href="' + esc(editUrl) + '">Tur Bilgilerini Düzenle</a>' +
          (ready
            ? '<a class="button button-primary" href="' + esc(editUrl) + '">Onaylama Adımına Geç</a>'
            : '<button type="button" class="button button-primary" disabled>Onay için eksikleri tamamla</button>') +
        '</footer>' +
      '</article>';
  }

  document.addEventListener('DOMContentLoaded', function () {
    var data = window.STTI_V113_REVIEW_QUEUE;
    if (!data || !Array.isArray(data.items)) return;

    document.body.classList.add('stti-review-queue-v113');

    var main = document.querySelector('.stti-main');
    if (!main) return;

    var topTitle = document.querySelector('.stti-topbar h1');
    var topDescription = document.querySelector('.stti-topbar p');
    if (topTitle) topTitle.textContent = 'Tur İnceleme';
    if (topDescription) topDescription.textContent = 'Eksikleri burada gör. İncelemek için editöre girmen gerekmez; düzenleme gerektiğinde ayrı düğmeyi kullan.';

    var oldGrid = main.querySelector('.stti-grid.stti-grid-2');
    if (oldGrid) oldGrid.style.display = 'none';

    var readyCount = data.items.filter(function (item) { return !!item.approvalReady; }).length;
    var blockedCount = data.items.length - readyCount;

    var shell = document.createElement('section');
    shell.className = 'stti-rq-shell';
    shell.innerHTML = '' +
      '<div class="stti-rq-summary">' +
        '<div><span>İNCELEME BEKLEYEN</span><strong>' + esc(data.items.length) + '</strong></div>' +
        '<div class="is-ready"><span>ONAYA HAZIR</span><strong>' + esc(readyCount) + '</strong></div>' +
        '<div class="is-blocked"><span>EKSİĞİ OLAN</span><strong>' + esc(blockedCount) + '</strong></div>' +
        '<div><span>PUBLIC / SEO</span><strong>KAPALI</strong></div>' +
      '</div>' +
      '<div class="stti-rq-note">' +
        '<b>Çalışma kuralı:</b> Eksik bilgi tahmin edilmez. Bu ekran yalnız canonical veriyi okur; kendi başına Tour onaylamaz veya Public/SEO kilidi açmaz.' +
      '</div>' +
      '<div class="stti-rq-list">' +
        (data.items.length ? data.items.map(itemCard).join('') : '<div class="stti-rq-empty"><h3>İnceleme bekleyen tur yok</h3><p>NEEDS_REVIEW durumunda canonical Tour bulunmuyor.</p></div>') +
      '</div>';

    if (oldGrid) main.insertBefore(shell, oldGrid);
    else main.appendChild(shell);

    shell.querySelectorAll('.stti-rq-toggle').forEach(function (button) {
      button.addEventListener('click', function () {
        var id = button.getAttribute('aria-controls');
        var panel = document.getElementById(id);
        if (!panel) return;
        var opening = panel.hasAttribute('hidden');
        if (opening) panel.removeAttribute('hidden');
        else panel.setAttribute('hidden', 'hidden');
        button.setAttribute('aria-expanded', opening ? 'true' : 'false');
        button.textContent = opening ? 'İncelemeyi Kapat' : 'İncele';
      });
    });
  });
})();
