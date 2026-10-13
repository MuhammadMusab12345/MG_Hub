<?php
/**
 * Mehtab Garments — Administrator Authentication
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';

// Redirect if already logged in as admin
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect(APP_URL . '/admin/index.php');
    } else {
        logoutUser();
    }
}

$db = getDb();
$error = null;
$redirectTarget = $_GET['redirect'] ?? '';

// Sanitize redirect to avoid open redirect vulnerabilities
if ($redirectTarget !== '' && (str_starts_with($redirectTarget, 'http://') || str_starts_with($redirectTarget, 'https://') || str_starts_with($redirectTarget, '//'))) {
    $redirectTarget = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $redirectTarget = $_POST['redirect'] ?? '';
    if ($redirectTarget !== '' && (str_starts_with($redirectTarget, 'http://') || str_starts_with($redirectTarget, 'https://') || str_starts_with($redirectTarget, '//'))) {
        $redirectTarget = '';
    }

    // Check throttle
    if (isLoginThrottled($db)) {
        $error = 'Too many failed login attempts. Please wait 10 minutes before trying again.';
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            $error = 'Invalid login details.';
            recordLoginAttempt($db);
        } else {
            $stmt = $db->prepare('SELECT id, password_hash, role FROM users WHERE email = :email LIMIT 1');
            $stmt->execute([':email' => strtolower($email)]);
            $user = $stmt->fetch();

            // Strict security rule: Only accounts with role 'admin' are permitted to log in
            if ($user && password_verify($password, $user['password_hash']) && $user['role'] === 'admin') {
                // Successful admin login
                clearLoginAttempts($db);
                loginUser($user);

                if ($redirectTarget !== '') {
                    redirect(APP_URL . '/' . ltrim($redirectTarget, '/'));
                } else {
                    redirect(APP_URL . '/admin/index.php');
                }
            } else {
                // Generic error: never reveal account existence, role, or reason
                recordLoginAttempt($db);
                $error = 'Invalid login details.';
            }
        }
    }
}

$pageTitle = 'Admin Login';
require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-header">
      <div style="width: 56px; height: 56px; margin: 0 auto 12px; border-radius: 50%; background: var(--color-blue-light); color: var(--color-blue); display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 14px rgba(37, 99, 235, 0.15);">
        <span style="display: inline-flex; transform: scale(1.3);"><?= icon('shield') ?></span>
      </div>
      <h1>Admin Portal</h1>
      <p style="color: var(--color-text-muted); font-size: 0.95rem;">Administrative credentials required</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-error" role="alert">
        <?= icon('close') ?>
        <span><?= e($error) ?></span>
      </div>
    <?php endif; ?>

    <form action="<?= APP_URL ?>/login.php" method="POST" autocomplete="off" novalidate>
      <?= csrfField() ?>
      <input type="hidden" name="redirect" value="<?= e($redirectTarget) ?>">

      <div class="form-group">
        <label for="email" class="form-label">Email Address</label>
        <div class="form-input-wrap">
          <span class="form-input-icon"><?= icon('user') ?></span>
          <!-- Privacy rule: never echo email back into form after failed attempt -->
          <input type="email" id="email" name="email" class="form-control" required autocomplete="email" placeholder="admin@example.com">
        </div>
      </div>

      <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <div class="form-input-wrap">
          <span class="form-input-icon"><?= icon('shield') ?></span>
          <!-- Privacy rule: never pre-fill password -->
          <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password" placeholder="Enter password">
        </div>
      </div>

      <button type="submit" class="btn btn-blue" style="width: 100%; margin-top: 1.25rem; justify-content: center;">
        <?= icon('login') ?>
        <span>Sign In</span>
      </button>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
