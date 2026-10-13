<?php
/**
 * Mehtab Garments — CSRF Protection
 */

declare(strict_types=1);

if (!function_exists('csrfToken')) {
    function csrfToken(): string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrfField')) {
    function csrfField(): string {
        $token = htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}

if (!function_exists('verifyCsrf')) {
    function verifyCsrf(?string $token = null): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $submitted = $token ?? $_POST['csrf_token'] ?? '';
        $stored = $_SESSION['csrf_token'] ?? '';

        if (!is_string($submitted) || $submitted === '' || !is_string($stored) || $stored === '') {
            return false;
        }

        return hash_equals($stored, $submitted);
    }
}

if (!function_exists('requireCsrf')) {
    function requireCsrf(): void {
        if (!verifyCsrf()) {
            http_response_code(403);
            require_once dirname(__DIR__) . '/403.php';
            exit;
        }
    }
}
