<?php
/**
 * Mehtab Garments — Authentication & Access Control
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    if (!isLoggedIn() || empty($_SESSION['user_id'])) {
        return false;
    }

    // Strict security rule: Verify role against database on every request
    try {
        $db = getDb();
        $stmt = $db->prepare('SELECT role FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => (int)$_SESSION['user_id']]);
        $role = $stmt->fetchColumn();
        if ($role === 'admin') {
            $_SESSION['role'] = 'admin';
            return true;
        }
    } catch (Exception $e) {
        error_log('Admin check error: ' . $e->getMessage());
    }

    return false;
}

function currentUserId(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function currentUserRole(): ?string {
    return $_SESSION['role'] ?? null;
}

function requireLogin(?string $redirect = null): void {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'Please log in to continue.';
        $target = 'login.php';
        if ($redirect !== null && $redirect !== '') {
            $target .= '?redirect=' . urlencode($redirect);
        }
        header('Location: ' . APP_URL . '/' . $target);
        exit;
    }
}

function requireAdmin(): void {
    if (!isLoggedIn()) {
        $_SESSION['flash_error'] = 'Please log in with administrator credentials.';
        header('Location: ' . APP_URL . '/login.php');
        exit;
    }
    if (!isAdmin()) {
        http_response_code(403);
        require_once dirname(__DIR__) . '/403.php';
        exit;
    }
}

function loginUser(array $user): void {
    session_regenerate_id(true);
    // Strict privacy rule: store ONLY user ID and role in session.
    // Never store name, email, or credentials in session.
    $_SESSION['user_id'] = (int)$user['id'];
    $_SESSION['role']    = (string)$user['role'];
}

function logoutUser(): void {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    session_destroy();
    session_start();
    session_regenerate_id(true);
}

function getClientIp(): string {
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        return trim($ips[0]);
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

function getIpHash(): string {
    return hash('sha256', getClientIp());
}

function isLoginThrottled(PDO $db): bool {
    $ipHash = getIpHash();
    
    // Purge records older than 1 day to keep table lean
    try {
        $db->query("DELETE FROM login_attempts WHERE attempted_at < (NOW() - INTERVAL 1 DAY)");
    } catch (Exception $e) {
        // Silently continue if purge fails
    }

    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM login_attempts 
        WHERE ip_hash = :ip_hash 
          AND attempted_at >= (NOW() - INTERVAL 10 MINUTE)
    ");
    $stmt->execute([':ip_hash' => $ipHash]);
    $count = (int)$stmt->fetchColumn();

    return $count >= 5;
}

function recordLoginAttempt(PDO $db): void {
    $ipHash = getIpHash();
    $stmt = $db->prepare("
        INSERT INTO login_attempts (ip_hash, attempted_at)
        VALUES (:ip_hash, NOW())
    ");
    $stmt->execute([':ip_hash' => $ipHash]);
}

function clearLoginAttempts(PDO $db): void {
    $ipHash = getIpHash();
    $stmt = $db->prepare("DELETE FROM login_attempts WHERE ip_hash = :ip_hash");
    $stmt->execute([':ip_hash' => $ipHash]);
}
