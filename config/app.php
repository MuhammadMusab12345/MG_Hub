<?php
/**
 * Mehtab Garments — Application Configuration
 */

declare(strict_types=1);

require_once __DIR__ . '/env.php';

// Safe error handling: never leak technical errors to public visitors
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
ini_set('log_errors', '1');

// Secure Session Configuration
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    $isHttps = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443) ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    );

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

// Application Constants
if (!defined('APP_NAME')) {
    define('APP_NAME', 'Mehtab Garments');
}

if (!defined('APP_ENV')) {
    define('APP_ENV', env('APP_ENV', 'development'));
}

if (!defined('APP_URL')) {
    define('APP_URL', rtrim(env('APP_URL', 'http://localhost/mehtab-garments'), '/'));
}

// Keep the currency symbol in exactly one config constant
if (!defined('CURRENCY_SYMBOL')) {
    define('CURRENCY_SYMBOL', 'Rs.');
}

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', ROOT_PATH . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'products');
}

if (!defined('UPLOAD_URL')) {
    define('UPLOAD_URL', APP_URL . '/uploads/products');
}

if (!defined('MAX_UPLOAD_SIZE')) {
    define('MAX_UPLOAD_SIZE', 8 * 1024 * 1024); // 8 MB limit
}

// Business WhatsApp Configuration
if (!defined('WHATSAPP_NUMBER')) {
    define('WHATSAPP_NUMBER', '923228127038');
}

if (!defined('WHATSAPP_DISPLAY')) {
    define('WHATSAPP_DISPLAY', '0322 8127038');
}

// Global SVG Icons
require_once dirname(__DIR__) . '/includes/icons.php';

