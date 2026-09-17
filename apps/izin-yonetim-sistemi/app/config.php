<?php

declare(strict_types=1);

function load_app_config(): array
{
    static $config = null;

    if (is_array($config)) {
        return $config;
    }

    $customPath = getenv('IZIN_CONFIG_FILE') ?: null;
    $documentRoot = $_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__, 2);
    $defaultPath = dirname(rtrim((string) $documentRoot, '/\\')) . '/izin-private/config.php';
    $configPath = $customPath ?: $defaultPath;

    if (!is_file($configPath) || !is_readable($configPath)) {
        throw new RuntimeException('Uygulama yapılandırma dosyası bulunamadı.');
    }

    $loaded = require $configPath;

    if (!is_array($loaded) || !isset($loaded['app'], $loaded['db'])) {
        throw new RuntimeException('Uygulama yapılandırması geçersiz.');
    }

    $config = $loaded;
    return $config;
}

function app_config(?string $section = null): mixed
{
    $config = load_app_config();

    if ($section === null) {
        return $config;
    }

    return $config[$section] ?? null;
}
