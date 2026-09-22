<?php
if (!defined('ABSPATH')) { exit; }

final class STCA_Admin {
    public static function init(): void {
        add_action('admin_menu', array(__CLASS__, 'menu'));
        add_action('admin_post_stca_simulate', array(__CLASS__, 'simulate_form'));
    }

    public static function menu(): void {
        add_menu_page(
            'Conversational Assistant',
            'ST Assistant',
            'manage_options',
            'stca-assistant',
            array(__CLASS__, 'page'),
            'dashicons-format-chat',
            29
        );
    }

    public static function page(): void {
        if (!current_user_can('manage_options')) { return; }
        $counts = STCA_Data::counts();
        $simulation = get_transient('stca_admin_sim_' . get_current_user_id());
        if ($simulation) { delete_transient('stca_admin_sim_' . get_current_user_id()); }
        ?>
        <div class="wrap">
            <h1>Server Turizm Conversational Assistant</h1>
            <p><strong>v<?php echo esc_html(STCA_VERSION); ?></strong> — canonical data read-only, Instagram fail-closed.</p>
            <table class="widefat striped" style="max-width:1000px">
                <tbody>
                    <tr><th>Instagram Master</th><td><?php echo STCA_Config::instagram_enabled() ? '<strong style="color:#008a20">ON</strong>' : '<strong style="color:#b32d2e">OFF</strong>'; ?></td></tr>
                    <tr><th>Meta secrets configured</th><td><?php echo STCA_Config::configured() ? 'YES' : 'NO'; ?></td></tr>
                    <tr><th>Graph API</th><td><?php echo esc_html(STCA_Config::graph_api_version()); ?></td></tr>
                    <tr><th>Webhook</th><td><code><?php echo esc_html(STCA_Config::webhook_url()); ?></code></td></tr>
                    <tr><th>Program Intelligence</th><td><?php echo class_exists('STPI_Store') ? 'AVAILABLE' : 'MISSING'; ?></td></tr>
                    <tr><th>Hotel Intelligence</th><td><?php echo post_type_exists('sthi_hotel') ? 'AVAILABLE' : 'MISSING'; ?></td></tr>
                    <tr><th>Tour Intelligence</th><td><?php echo function_exists('stti_get_candidates') ? 'AVAILABLE' : 'MISSING'; ?></td></tr>
                    <tr><th>Eligible Umrah</th><td><?php echo esc_html((string) $counts['umrah_customer_eligible']); ?></td></tr>
                    <tr><th>Hub-visible Tours</th><td><?php echo esc_html((string) $counts['tour_hub_visible']); ?></td></tr>
                    <tr><th>Published Hotels</th><td><?php echo esc_html((string) $counts['published_hotels']); ?></td></tr>
                </tbody>
            </table>

            <h2>Safe Simulator</h2>
            <p>Runs the exact reply engine without contacting Meta.</p>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="max-width:1000px">
                <input type="hidden" name="action" value="stca_simulate">
                <?php wp_nonce_field('stca_simulate'); ?>
                <textarea name="message" rows="4" class="large-text" placeholder="Ümre ziyareti ile ilgili bilgi alabilir miyim ekim kasım ayı gibi"><?php echo isset($_GET['stca_message']) ? esc_textarea(wp_unslash($_GET['stca_message'])) : ''; ?></textarea>
                <p><button type="submit" class="button button-primary">Simulate</button></p>
            </form>
            <?php if (is_array($simulation)) : ?>
                <h3>Reply</h3>
                <pre style="white-space:pre-wrap;background:#fff;border:1px solid #ccd0d4;padding:16px;max-width:970px"><?php echo esc_html((string) ($simulation['text'] ?? '')); ?></pre>
                <p><strong>Intent:</strong> <?php echo esc_html((string) ($simulation['intent'] ?? '')); ?> · <strong>Language:</strong> <?php echo esc_html((string) ($simulation['language'] ?? '')); ?> · <strong>Matches:</strong> <?php echo esc_html((string) count((array) ($simulation['matches'] ?? array()))); ?></p>
            <?php endif; ?>

            <h2>Recent Events</h2>
            <table class="widefat striped" style="max-width:1000px"><thead><tr><th>Time</th><th>Type</th><th>Intent</th><th>Status</th><th>Message</th></tr></thead><tbody>
            <?php foreach (STCA_Logging::recent(20) as $row) : ?>
                <tr><td><?php echo esc_html((string) $row['created_at']); ?></td><td><?php echo esc_html((string) $row['event_type']); ?></td><td><?php echo esc_html((string) $row['intent']); ?></td><td><?php echo esc_html((string) $row['status']); ?></td><td><?php echo esc_html((string) $row['message_excerpt']); ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        </div>
        <?php
    }

    public static function simulate_form(): void {
        if (!current_user_can('manage_options')) { wp_die('Unauthorized'); }
        check_admin_referer('stca_simulate');
        $message = sanitize_textarea_field((string) ($_POST['message'] ?? ''));
        if ($message !== '') {
            set_transient('stca_admin_sim_' . get_current_user_id(), STCA_Responder::reply($message), 5 * MINUTE_IN_SECONDS);
        }
        wp_safe_redirect(add_query_arg(array('page' => 'stca-assistant', 'stca_message' => rawurlencode($message)), admin_url('admin.php')));
        exit;
    }
}
