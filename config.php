<?php
require_once __DIR__ . '/site_config.php';

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    return $protocol . $host . BASE_PATH;
}

function getAssetUrl($path = '') {
    $path = ltrim($path, '/');
    return getBaseUrl() . '/adminlte/' . $path;
}

function setBaseUrl($url) {
    return getBaseUrl();
}

// Database connection is already included via site_config.php
// No need to include conn.php here since it's included in site_config.php
