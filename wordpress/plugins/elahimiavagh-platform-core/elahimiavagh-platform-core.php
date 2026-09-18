<?php
/**
 * Plugin Name: Elahimiavagh Platform Core
 * Description: Central module registry and health inventory for reusable Elahimiavagh operational plugins.
 * Version: 0.1.0
 * Author: elahimiavagh.com
 * Author URI: https://elahimiavagh.com
 * Requires PHP: 8.1
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ELAHI_PLATFORM_CORE_VERSION', '0.1.0');

function elahi_platform_normalize_module(string $moduleId, array $module): array
{
    $moduleId = sanitize_key($moduleId);

    return [
        'id' => $moduleId,
        'name' => sanitize_text_field((string) ($module['name'] ?? $moduleId)),
        'version' => sanitize_text_field((string) ($module['version'] ?? 'unknown')),
        'type' => sanitize_key((string) ($module['type'] ?? 'module')),
        'health' => in_array((string) ($module['health'] ?? 'unknown'), ['healthy', 'warning', 'error', 'disabled', 'unknown'], true)
            ? (string) $module['health']
            : 'unknown',
        'health_detail' => sanitize_text_field((string) ($module['health_detail'] ?? '')),
        'source_of_truth' => sanitize_text_field((string) ($module['source_of_truth'] ?? '')),
        'schema_version' => sanitize_text_field((string) ($module['schema_version'] ?? '')),
        'integration_state' => sanitize_text_field((string) ($module['integration_state'] ?? '')),
    ];
}

function elahi_platform_modules(): array
{
    $modules = [
        'platform_core' => [
            'name' => 'Elahimiavagh Platform Core',
            'version' => ELAHI_PLATFORM_CORE_VERSION,
            'type' => 'platform',
            'health' => 'healthy',
            'health_detail' => 'Registry runtime loaded.',
            'source_of_truth' => 'Module registry',
            'schema_version' => 'n/a',
            'integration_state' => 'active',
        ],
    ];

    /**
     * Modules register by adding entries keyed by stable module id.
     *
     * A module should compute health from its own authoritative state and must
     * not expose secrets through health_detail.
     */
    $modules = apply_filters('elahi_platform_modules', $modules);

    $normalized = [];
    foreach ($modules as $moduleId => $module) {
        if (!is_array($module)) {
            continue;
        }

        $normalizedModule = elahi_platform_normalize_module((string) $moduleId, $module);
        if ($normalizedModule['id'] === '') {
            continue;
        }
        $normalized[$normalizedModule['id']] = $normalizedModule;
    }

    ksort($normalized);
    return $normalized;
}

function elahi_platform_plugin_inventory(): array
{
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $active = (array) get_option('active_plugins', []);
    $networkActive = is_multisite() ? array_keys((array) get_site_option('active_sitewide_plugins', [])) : [];
    $activeLookup = array_fill_keys(array_merge($active, $networkActive), true);
    $inventory = [];

    foreach (get_plugins() as $pluginFile => $headers) {
        $name = (string) ($headers['Name'] ?? '');
        $author = wp_strip_all_tags((string) ($headers['Author'] ?? ''));

        $isManagedFamily = stripos($name, 'Server Turizm') !== false
            || stripos($name, 'Elahimiavagh') !== false
            || stripos($author, 'elahimiavagh') !== false;

        if (!$isManagedFamily) {
            continue;
        }

        $inventory[] = [
            'file' => $pluginFile,
            'name' => sanitize_text_field($name),
            'version' => sanitize_text_field((string) ($headers['Version'] ?? 'unknown')),
            'author' => sanitize_text_field($author),
            'active' => isset($activeLookup[$pluginFile]),
        ];
    }

    usort($inventory, static function (array $a, array $b): int {
        if ($a['active'] !== $b['active']) {
            return $a['active'] ? -1 : 1;
        }
        return strcasecmp((string) $a['name'], (string) $b['name']);
    });

    return $inventory;
}

function elahi_platform_register_admin_menu(): void
{
    add_menu_page(
        'Elahimiavagh Platform',
        'Platform',
        'manage_options',
        'elahi-platform',
        'elahi_platform_render_admin',
        'dashicons-screenoptions',
        2
    );
}
add_action('admin_menu', 'elahi_platform_register_admin_menu');

function elahi_platform_health_label(string $health): string
{
    return match ($health) {
        'healthy' => 'Healthy',
        'warning' => 'Warning',
        'error' => 'Error',
        'disabled' => 'Disabled',
        default => 'Unknown',
    };
}

function elahi_platform_render_admin(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $modules = elahi_platform_modules();
    $plugins = elahi_platform_plugin_inventory();
    $healthy = count(array_filter($modules, static fn(array $m): bool => $m['health'] === 'healthy'));
    $attention = count($modules) - $healthy;
    ?>
    <div class="wrap">
        <h1>Elahimiavagh Operations Platform</h1>
        <p>
            Bu ekran modül sürümleri, çalışma durumu ve ilgili eklenti envanteri için merkezi,
            salt-okunur görünüm sağlar. Kaynak sistemlerin verisini değiştirmez.
        </p>

        <div style="display:flex;gap:12px;flex-wrap:wrap;margin:18px 0">
            <div class="card" style="min-width:180px"><strong><?php echo esc_html((string) count($modules)); ?></strong><br>Registered Modules</div>
            <div class="card" style="min-width:180px"><strong><?php echo esc_html((string) $healthy); ?></strong><br>Healthy</div>
            <div class="card" style="min-width:180px"><strong><?php echo esc_html((string) $attention); ?></strong><br>Needs Attention</div>
            <div class="card" style="min-width:180px"><strong><?php echo esc_html((string) count($plugins)); ?></strong><br>Managed Plugins</div>
        </div>

        <h2>Module Registry</h2>
        <table class="widefat striped">
            <thead>
            <tr>
                <th>Module</th>
                <th>Version</th>
                <th>Health</th>
                <th>Schema</th>
                <th>Source of Truth</th>
                <th>Integration</th>
                <th>Detail</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($modules as $module): ?>
                <tr>
                    <td><strong><?php echo esc_html($module['name']); ?></strong><br><code><?php echo esc_html($module['id']); ?></code></td>
                    <td><?php echo esc_html($module['version']); ?></td>
                    <td><?php echo esc_html(elahi_platform_health_label($module['health'])); ?></td>
                    <td><?php echo esc_html($module['schema_version'] ?: '—'); ?></td>
                    <td><?php echo esc_html($module['source_of_truth'] ?: '—'); ?></td>
                    <td><?php echo esc_html($module['integration_state'] ?: '—'); ?></td>
                    <td><?php echo esc_html($module['health_detail'] ?: '—'); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h2 style="margin-top:28px">Plugin Inventory</h2>
        <table class="widefat striped">
            <thead>
            <tr><th>Plugin</th><th>Version</th><th>State</th><th>Author</th><th>File</th></tr>
            </thead>
            <tbody>
            <?php if ($plugins === []): ?>
                <tr><td colspan="5">Managed plugin bulunamadı.</td></tr>
            <?php endif; ?>
            <?php foreach ($plugins as $plugin): ?>
                <tr>
                    <td><strong><?php echo esc_html($plugin['name']); ?></strong></td>
                    <td><?php echo esc_html($plugin['version']); ?></td>
                    <td><?php echo $plugin['active'] ? 'Active' : 'Inactive'; ?></td>
                    <td><?php echo esc_html($plugin['author'] ?: '—'); ?></td>
                    <td><code><?php echo esc_html($plugin['file']); ?></code></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <p style="margin-top:22px">
            <a href="https://elahimiavagh.com" target="_blank" rel="noopener noreferrer">
                Powered by elahimiavagh.com
            </a>
        </p>
    </div>
    <?php
}
