<?php
/**
 * Mehtab Garments — Core Utility Functions
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';

function e(?string $value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function formatPrice($amount): string {
    if ($amount === null || $amount === '') {
        return '';
    }
    $num = (float)$amount;
    // If integer value, show without trailing zeros, else show 2 decimals
    if (floor($num) == $num) {
        return CURRENCY_SYMBOL . ' ' . number_format($num, 0);
    }
    return CURRENCY_SYMBOL . ' ' . number_format($num, 2);
}

function setFlash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_' . $type] = $message;
}

function getFlash(string $type): ?string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $key = 'flash_' . $type;
    if (isset($_SESSION[$key])) {
        $msg = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $msg;
    }
    return null;
}

function slugify(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text) ?: $text;
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    return $text !== '' ? $text : 'product';
}

function generateShareToken(): string {
    return bin2hex(random_bytes(16)); // 32 hex characters
}

function getShareUrl(string $token): string {
    return APP_URL . '/share.php?t=' . urlencode($token);
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function abort404(): void {
    http_response_code(404);
    require dirname(__DIR__) . '/404.php';
    exit;
}

function abort403(): void {
    http_response_code(403);
    require dirname(__DIR__) . '/403.php';
    exit;
}

/**
 * Validates and sanitizes optional decimal/price/cost fields.
 * Returns:
 *   - null if empty
 *   - float/string if valid non-negative number
 *   - false if invalid or negative
 */
function parseOptionalDecimal(?string $val) {
    if ($val === null) {
        return null;
    }
    $trimmed = trim($val);
    if ($trimmed === '') {
        return null;
    }
    // Remove any user-typed thousands commas
    $cleaned = str_replace(',', '', $trimmed);
    if (!is_numeric($cleaned)) {
        return false;
    }
    $num = (float)$cleaned;
    if ($num < 0) {
        return false;
    }
    return number_format($num, 2, '.', '');
}

/**
 * Builds standard WhatsApp chat link with prefilled message.
 */
function whatsapp_link(string $message): string {
    return 'https://wa.me/' . WHATSAPP_NUMBER . '?text=' . rawurlencode($message);
}

/**
 * Generates versioned asset URL for cache busting.
 */
function asset_url(string $relativePath): string {
    $cleanPath = ltrim($relativePath, '/');
    $filePath = ROOT_PATH . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $cleanPath);
    $ver = file_exists($filePath) ? (string)filemtime($filePath) : '1.0';
    return APP_URL . '/' . $cleanPath . '?v=' . $ver;
}

