<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Basic settings fallback.
 */
function stai_get_setting($key, $default = '') {
    $options = get_option('stai_settings', []);

    if (is_array($options) && array_key_exists($key, $options)) {
        return $options[$key];
    }

    return $default;
}

function stai_get_bool_setting($key, $default = true) {
    $value = stai_get_setting($key, $default ? '1' : '0');

    return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
}

function stai_get_int_setting($key, $default = 0, $min = 0, $max = 999999) {
    $value = (int) stai_get_setting($key, $default);

    if ($value < $min) {
        return $min;
    }

    if ($value > $max) {
        return $max;
    }

    return $value;
}
