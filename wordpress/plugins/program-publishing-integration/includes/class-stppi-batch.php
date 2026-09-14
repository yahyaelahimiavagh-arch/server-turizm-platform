<?php
if (!defined('ABSPATH')) { exit; }

/**
 * Full eligible migration engine for real current/upcoming Turkish Umrah programs.
 *
 * v0.3.1 invariants:
 * - STP-000036 / STP-000037 are permanently excluded;
 * - Umrah + tr_TR + scheduled + current/upcoming only;
 * - every selected record must be READY or already APPROVED before canonical writes;
 * - Step 1 performs APPROVE (where needed) + final-route PREPARE in one controlled pass;
 * - Step 2 opens the entire selected set as PUBLIC/NOINDEX only after an all-or-nothing
 *   runtime-model preflight; one stale/invalid record blocks the whole exposure switch;
 * - Hub stays OFF after every mutating action until separately re-armed after QA;
 * - indexation, sitemap and Hotel public links cannot be enabled here.
 */
final class STPPI_Batch {
    private static $fixtures = array('STP-000036','STP-000037');

    public static function init() {
        add_action('admin_post_stppi_batch_action', array(__CLASS__, 'handle'));
    }

    private static function guard() {
        if (!current_user_can('manage_options') || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            wp_die('Erişim reddedildi.', '', array('response' => 403));
        }
        check_admin_referer('stppi_batch_action', 'stppi_batch_nonce');
    }

    private static function load_pi_write_classes() {
        $dep = STPPI_Repository::dependencies();
        if (is_wp_error($dep)) { return $dep; }
        foreach (array('validator','store','audit') as $file) {
            $path = STPI_DIR . 'includes/class-stpi-' . $file . '.php';
            if (file_exists($path)) { require_once $path; }
        }
        if (!class_exists('STPI_Validator') || !class_exists('STPI_Store')) {
            return new WP_Error('stppi_batch_dependency', 'Program Intelligence write/validation classes yüklenemedi.');
        }
        return true;
    }

    private static function finish($message) {
        wp_safe_redirect(add_query_arg(array('page' => 'stppi-publishing', 'stppi_message' => $message), admin_url('admin.php')));
        exit;
    }

    private static function audit($action, $ids, $extra = array()) {
        $payload = array_merge(array(
            'at' => gmdate('c'),
            'user' => get_current_user_id(),
            'action' => $action,
            'program_ids' => array_values($ids),
        ), $extra);
        add_option('stppi_audit_' . str_replace('-', '', wp_generate_uuid4()), $payload, '', false);
    }

    private static function is_scope($id, $program) {
        if (in_array((string)$id, self::$fixtures, true)) { return false; }
        if (($program['service_type'] ?? '') !== 'umrah') { return false; }
        if (($program['locale'] ?? '') !== 'tr_TR') { return false; }
        $wf = is_array($program['workflow'] ?? null) ? $program['workflow'] : array();
        if (($wf['schedule'] ?? '') !== 'scheduled') { return false; }
        return in_array(STPPI_Repository::temporal($program), array('upcoming','in_progress'), true);
    }

    private static function sorted_rows($all) {
        $rows = array();
        foreach ((array)($all['rows'] ?? array()) as $id => $row) {
            $p = $row['program'] ?? array();
            if (!is_array($p) || !self::is_scope($id, $p)) { continue; }
            $rows[$id] = $row;
        }
        uasort($rows, function($a, $b) {
            $ad = (string)($a['program']['schedule']['start_date'] ?? '9999-99-99');
            $bd = (string)($b['program']['schedule']['start_date'] ?? '9999-99-99');
            if ($ad !== $bd) { return strcmp($ad, $bd); }
            $ar = isset($a['program']['provenance']['source_row']) && is_numeric($a['program']['provenance']['source_row']) ? (int)$a['program']['provenance']['source_row'] : PHP_INT_MAX;
            $br = isset($b['program']['provenance']['source_row']) && is_numeric($b['program']['provenance']['source_row']) ? (int)$b['program']['provenance']['source_row'] : PHP_INT_MAX;
            if ($ar !== $br) { return $ar <=> $br; }
            return strcmp((string)($a['program']['program_code'] ?? ''), (string)($b['program']['program_code'] ?? ''));
        });
        return $rows;
    }

    private static function approval_gate($program) {
        $editorial = (string)($program['workflow']['editorial'] ?? '');
        if ($editorial === 'approved') {
            return array('state' => 'approved', 'label' => 'APPROVED', 'detail' => '');
        }
        if (!in_array($editorial, array('draft','needs_review'), true)) {
            return array('state' => 'blocked', 'label' => 'BLOCKED', 'detail' => 'Editorial state: ' . $editorial);
        }
        $dep = self::load_pi_write_classes();
        if (is_wp_error($dep)) {
            return array('state' => 'blocked', 'label' => 'DEPENDENCY', 'detail' => $dep->get_error_message());
        }
        $candidate = $program;
        $candidate['workflow']['editorial'] = 'approved';
        $report = STPI_Validator::validate_batch(array(
            'schema_version' => STPI_SCHEMA_VERSION,
            'source' => array('type' => 'wordpress', 'mode' => 'partial'),
            'programs' => array($candidate),
        ));
        $errors = (array)($report['errors'] ?? array());
        $gate = (string)($report['programs'][0]['publish_gate'] ?? '');
        if (!$errors && $gate === 'READY') {
            return array('state' => 'ready', 'label' => 'READY', 'detail' => 'Validation + verification + hotel gates pass');
        }
        $detail = $errors ? (string)($errors[0]['message'] ?? 'Validation error') : ('Publish gate: ' . ($gate ?: 'UNKNOWN'));
        return array('state' => 'blocked', 'label' => 'BLOCKED', 'detail' => $detail);
    }

    private static function selected_ids($all) {
        $raw = isset($_POST['program_ids']) ? (array)wp_unslash($_POST['program_ids']) : array();
        $ids = array();
        foreach ($raw as $id) {
            $id = sanitize_text_field($id);
            if (!preg_match('/^STP-[0-9]{6}$/D', $id) || in_array($id, self::$fixtures, true)) { continue; }
            if (isset($ids[$id])) { continue; }
            $row = $all['rows'][$id] ?? null;
            if (!is_array($row) || !self::is_scope($id, $row['program'] ?? array())) { continue; }
            $ids[$id] = true;
        }
        $ids = array_keys($ids);
        if (!$ids) {
            return new WP_Error('stppi_batch_empty', 'En az bir gerçek APPROVED/READY güncel veya yaklaşan Umre programı seçin.');
        }
        return $ids;
    }

    private static function prepare_one($id, $row, &$registry, &$claimed_slugs) {
        $p = $row['program'] ?? array();
        $wf = $p['workflow'] ?? array();
        if (($wf['editorial'] ?? '') !== 'approved' || ($wf['schedule'] ?? '') !== 'scheduled' || !in_array(STPPI_Repository::temporal($p), array('upcoming','in_progress'), true)) {
            return new WP_Error('stppi_not_route_ready', 'APPROVED + SCHEDULED + CURRENT/UPCOMING gerekli.');
        }
        $base = (isset($registry[$id]) && is_array($registry[$id])) ? $registry[$id] : array(
            'id' => $id,
            'slug' => '',
            'mode' => 'prepared',
            'seo' => true,
            'post_id' => 0,
            'hash' => '',
            'hotel_hash' => ''
        );
        if (empty($base['slug'])) { $base['slug'] = STPPI_Repository::suggested_slug($p); }
        $slug = (string)$base['slug'];
        if (!STPPI_Renderer::valid_slug($slug)) { return new WP_Error('stppi_bad_slug', 'Geçersiz final slug.'); }
        if (isset($claimed_slugs[$slug]) && $claimed_slugs[$slug] !== $id) {
            return new WP_Error('stppi_slug_collision', 'Final adres çakışması: ' . $slug);
        }
        foreach ($registry as $other => $r) {
            if ($other !== $id && is_array($r) && (string)($r['slug'] ?? '') === $slug) {
                return new WP_Error('stppi_slug_collision', 'Final adres başka Program tarafından kullanılıyor: ' . $slug);
            }
        }
        $m = STPPI_Renderer::model($base, false);
        if (is_wp_error($m)) { return $m; }
        $prior_mode = (string)($base['mode'] ?? 'prepared');
        $base['post_id'] = $m['post_id'];
        $base['hash'] = $m['hash'];
        $base['hotel_hash'] = $m['hotel_hash'];
        // Incremental-safe: refresh accepted live routes in place; new routes remain PREPARED.
        $base['mode'] = in_array($prior_mode, array('public_noindex','indexable'), true) ? $prior_mode : 'prepared';
        $base['seo'] = true;
        $base['review_token'] = wp_generate_uuid4();
        $base['prepared_at'] = gmdate('c');
        $base['full_migration'] = true;
        $registry[$id] = $base;
        $claimed_slugs[$slug] = $id;
        return $base;
    }

    public static function handle() {
        self::guard();
        $all = STPPI_Repository::all_rows();
        if (is_wp_error($all)) { self::finish($all->get_error_message()); }
        $ids = self::selected_ids($all);
        if (is_wp_error($ids)) { self::finish($ids->get_error_message()); }
        $action = sanitize_key(wp_unslash($_POST['batch_action'] ?? ''));
        if (!in_array($action, array('approve_prepare_all','open_noindex'), true)) {
            self::finish('Geçersiz batch işlemi.');
        }
        if (($_POST['confirm_batch'] ?? '') !== '1') {
            self::finish('Seçili tüm uygun Programlar için açık onay gerekli.');
        }

        if ($action === 'approve_prepare_all') {
            // v0.4.8 transactional incremental flow: NEVER shut accepted public gates before preflight.
            // Candidate registry is built in memory and written only after every selected Program validates.
            $dep = self::load_pi_write_classes();
            if (is_wp_error($dep)) { self::finish($dep->get_error_message()); }

            // Full preflight: every selected row must be READY or already APPROVED.
            $preflight_errors = array();
            foreach ($ids as $id) {
                $row = $all['rows'][$id] ?? null;
                if (!is_array($row)) { $preflight_errors[$id] = 'Program bulunamadı.'; continue; }
                $gate = self::approval_gate($row['program'] ?? array());
                if (!in_array($gate['state'], array('ready','approved'), true)) {
                    $preflight_errors[$id] = $gate['label'] . ($gate['detail'] ? ': ' . $gate['detail'] : '');
                }
            }
            if ($preflight_errors) {
                self::audit('full_migration_preflight_blocked', $ids, array('errors' => $preflight_errors));
                self::finish('TOPLU MIGRATION başlamadı. En az bir seçili Program READY/APPROVED değil. BLOCKED satırları düzeltin; hiçbir public gate açılmadı.');
            }

            $approved_now = 0;
            $already_approved = 0;
            $approval_failed = array();
            foreach ($ids as $id) {
                $row = $all['rows'][$id];
                $editorial = (string)($row['program']['workflow']['editorial'] ?? '');
                if ($editorial === 'approved') { $already_approved++; continue; }
                $result = STPI_Store::transition((int)$row['post_id'], 'approve');
                if (is_wp_error($result)) { $approval_failed[$id] = $result->get_error_message(); }
                else { $approved_now++; }
            }
            if ($approval_failed) {
                self::audit('full_migration_approval_partial', $ids, array(
                    'approved_now' => $approved_now,
                    'already_approved' => $already_approved,
                    'failed' => $approval_failed,
                ));
                self::finish('Approval sırasında ' . count($approval_failed) . ' kayıt başarısız oldu. Sistem fail-closed kaldı. Başarılı approval’lar canonical olarak korunur; hatalı kayıtları düzeltip işlemi tekrar çalıştırın.');
            }

            // Re-read canonical Program rows after approvals changed hashes.
            $all = STPPI_Repository::all_rows();
            if (is_wp_error($all)) { self::finish($all->get_error_message()); }

            $registry = STPPI_Repository::registry();
            $candidate_registry = $registry;
            $claimed = array();
            foreach ($candidate_registry as $rid => $cfg) {
                if (is_array($cfg) && !empty($cfg['slug'])) { $claimed[(string)$cfg['slug']] = (string)$rid; }
            }

            $prepared = 0;
            $prepare_failed = array();
            foreach ($ids as $id) {
                if (!isset($all['rows'][$id])) { $prepare_failed[$id] = 'Program bulunamadı.'; continue; }
                $result = self::prepare_one($id, $all['rows'][$id], $candidate_registry, $claimed);
                if (is_wp_error($result)) { $prepare_failed[$id] = $result->get_error_message(); }
                else { $prepared++; }
            }
            if ($prepare_failed) {
                self::audit('full_migration_prepare_blocked', $ids, array(
                    'approved_now' => $approved_now,
                    'already_approved' => $already_approved,
                    'prepare_errors' => $prepare_failed,
                ));
                $first = array_slice($prepare_failed, 0, 5, true);
                $parts = array(); foreach ($first as $pid => $err) { $parts[] = $pid . ': ' . $err; }
                self::finish('Route PREPARE durdu; canlı runtime DEĞİŞMEDİ. Hata: ' . implode(' | ', $parts));
            }

            STPPI_Repository::save_registry($candidate_registry);
            self::audit('full_migration_approve_prepare_complete', $ids, array(
                'approved_now' => $approved_now,
                'already_approved' => $already_approved,
                'prepared' => $prepared,
            ));
            self::finish('Step 1 tamamlandı. Yeni approved: ' . $approved_now . '; zaten approved: ' . $already_approved . '; doğrulanan/yenilenen route: ' . $prepared . '. Mevcut canlı route/Hub durumu korunmuştur.');
        }

        // Step 2: open the full selected set as NOINDEX only after every model validates.
        if (($_POST['confirm_public_noindex'] ?? '') !== '1') {
            self::finish('Toplu NOINDEX açılışı için ikinci açık onay gerekli.');
        }
        $registry = STPPI_Repository::registry();
        $validated = array();
        $errors = array();
        foreach ($ids as $id) {
            $cfg = $registry[$id] ?? null;
            if (!is_array($cfg) || !in_array((string)($cfg['mode'] ?? ''), array('prepared','public_noindex'), true)) {
                $errors[$id] = 'Önce final route PREPARED olmalı.';
                continue;
            }
            $m = STPPI_Renderer::model($cfg, true);
            if (is_wp_error($m)) { $errors[$id] = $m->get_error_message(); }
            else { $validated[$id] = true; }
        }
        if ($errors || count($validated) !== count($ids)) {
            self::audit('full_migration_noindex_blocked', $ids, array('errors' => $errors));
            self::finish('TOPLU NOINDEX açılmadı. En az bir Program stale/invalid. Hiçbir seçili route açılmadı; Review/Preview ile hatayı düzeltin.');
        }

        // v0.4.8 additive switch: selected routes open, existing accepted routes are NEVER demoted.
        // Hub / Hotel public relation states are preserved exactly; indexation/sitemap locks remain unchanged.
        $hub_before = stppi_hub_bridge_enabled();
        $hotel_before = stppi_hotel_links_enabled();
        foreach ($ids as $id) {
            $registry[$id]['mode'] = 'public_noindex';
            $registry[$id]['opened_at'] = gmdate('c');
            $registry[$id]['full_migration'] = true;
        }
        STPPI_Repository::save_registry($registry);
        update_option('stppi_public_master', true, false);
        update_option('stppi_hub_bridge_enabled', $hub_before, false);
        update_option('stppi_hotel_links_enabled', $hotel_before, false);
        self::audit('incremental_public_noindex_opened', $ids, array('count' => count($ids), 'hub_preserved' => $hub_before, 'hotel_preserved' => $hotel_before));
        self::finish(count($ids) . ' seçili route PUBLIC/NOINDEX açıldı; mevcut diğer canlı route’lar ve Hub/Hotel runtime durumu korundu.');
    }

    public static function render_panel($all, $registry) {
        if (is_wp_error($all) || !is_array($all)) { return; }
        $rows = self::sorted_rows($all);

        $gate_cache = array();
        $eligible_ids = array();
        $blocked_count = 0;
        foreach ($rows as $id => $row) {
            $gate = self::approval_gate($row['program'] ?? array());
            $gate_cache[$id] = $gate;
            if (in_array($gate['state'], array('ready','approved'), true)) { $eligible_ids[] = $id; }
            else { $blocked_count++; }
        }

        echo '<h2>Full Eligible Migration</h2>';
        echo '<div class="stppi-batch-lock"><strong>TÜM UYGUNLAR: ' . esc_html(count($eligible_ids)) . '</strong><span>Gerçek + Türkçe + scheduled + current/upcoming Umre; yalnız READY/APPROVED kayıtlar otomatik seçilir. STP-000036/37 kalıcı BLOCKED. Indexation / sitemap / Hotel public links bu ekrandan açılamaz.</span></div>';
        if ($blocked_count) {
            echo '<div class="notice notice-warning inline"><p><strong>' . esc_html($blocked_count) . ' scoped Program BLOCKED:</strong> otomatik seçilmedi. Önce Validation / Hotel / Editorial hatası çözülmeli.</p></div>';
        }
        echo '<form class="stppi-batch-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="stppi_batch_action">';
        wp_nonce_field('stppi_batch_action', 'stppi_batch_nonce');
        echo '<p><button type="button" class="button" id="stppi-select-all-eligible">Tüm uygunları seç</button> <button type="button" class="button" id="stppi-clear-all">Seçimi temizle</button> <strong id="stppi-selected-count">' . esc_html(count($eligible_ids)) . '</strong> Program seçili.</p>';
        echo '<div class="stppi-table-wrap"><table class="widefat striped stppi-batch-table"><thead><tr><th>Seç</th><th>ID / Kod</th><th>Tarih</th><th>Editorial</th><th>Approval Gate</th><th>Final Route</th></tr></thead><tbody>';
        $shown = 0;
        foreach ($rows as $id => $row) {
            $p = $row['program'];
            $wf = $p['workflow'] ?? array();
            $cfg = $registry[$id] ?? null;
            $gate = $gate_cache[$id];
            $eligible = in_array($gate['state'], array('ready','approved'), true);
            $route = $cfg ? (STPPI_ROUTE_BASE . ($cfg['slug'] ?? '') . '/') : '—';
            $mode = $cfg ? (string)($cfg['mode'] ?? '') : '';
            echo '<tr' . ($eligible ? ' class="stppi-wave-suggested"' : '') . '><td><input class="stppi-program-check" type="checkbox" name="program_ids[]" value="' . esc_attr($id) . '"' . ($eligible ? ' checked data-eligible="1"' : ' disabled') . '></td><td><code>' . esc_html($id) . '</code><br><b>' . esc_html($p['program_code'] ?? '') . '</b><br><small>' . esc_html($p['title'] ?? '') . '</small></td><td>' . esc_html(($p['schedule']['start_date'] ?? '—') . ' → ' . ($p['schedule']['end_date'] ?? '—')) . '</td><td><code>' . esc_html($wf['editorial'] ?? '') . '</code><br><small>' . esc_html($wf['availability'] ?? '') . '</small></td><td><span class="stppi-gate stppi-gate-' . esc_attr($gate['state']) . '">' . esc_html($gate['label']) . '</span>' . ($gate['detail'] ? '<br><small>' . esc_html($gate['detail']) . '</small>' : '') . '</td><td><code>' . esc_html($route) . '</code>' . ($mode ? '<br><b>' . esc_html($mode) . '</b>' : '') . '</td></tr>';
            $shown++;
        }
        if (!$shown) { echo '<tr><td colspan="6">Güncel/gelecek gerçek Umre kaydı bulunamadı.</td></tr>'; }
        echo '</tbody></table></div>';
        echo '<div class="stppi-batch-actions">';
        echo '<label><input type="checkbox" name="confirm_batch" value="1"> Seçili TÜM uygun Programları canonical approval + final route hazırlığı kapsamına aldığımı onaylıyorum.</label>';
        echo '<button class="button" type="submit" name="batch_action" value="approve_prepare_all">1 · TÜM UYGUNLARI APPROVE + PREPARE ET</button>';
        echo '<label class="stppi-batch-danger"><input type="checkbox" name="confirm_public_noindex" value="1"> Seçili tüm PREPARED route’ların internette erişilebilir fakat NOINDEX olacağını ayrıca onaylıyorum.</label>';
        echo '<button class="button button-primary" type="submit" name="batch_action" value="open_noindex">2 · TÜM PREPARED ROUTE’LARI PUBLIC / NOINDEX AÇ</button>';
        echo '</div>';
        echo '<p class="description"><strong>Akış:</strong> Step 1 önce bütün seçili kayıtları preflight eder, gerekli approval’ları yazar, canonical veriyi yeniden okur ve route’ları PREPARED yapar. Step 2 bütün modelleri yeniden doğrular; tek hata varsa hiçbir route açılmaz. v0.4.8 sürümünde mevcut canlı Hub/route durumu batch boyunca korunur; tek hata tüm canlı sistemi kapatmaz.</p>';
        echo '</form>';
        ?>
        <script>
        (function(){
            var form = document.querySelector('.stppi-batch-form');
            if (!form) return;
            var checks = Array.prototype.slice.call(form.querySelectorAll('.stppi-program-check[data-eligible="1"]'));
            var count = form.querySelector('#stppi-selected-count');
            function update(){ if(count) count.textContent = checks.filter(function(c){ return c.checked; }).length; }
            checks.forEach(function(c){ c.addEventListener('change', update); });
            var all = form.querySelector('#stppi-select-all-eligible');
            var clear = form.querySelector('#stppi-clear-all');
            if(all) all.addEventListener('click', function(){ checks.forEach(function(c){ c.checked = true; }); update(); });
            if(clear) clear.addEventListener('click', function(){ checks.forEach(function(c){ c.checked = false; }); update(); });
            update();
        })();
        </script>
        <?php
    }
}
