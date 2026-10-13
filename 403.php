<?php
/**
 * Mehtab Garments — 403 Forbidden
 */

declare(strict_types=1);

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/config/app.php';
    require_once __DIR__ . '/includes/functions.php';
}

http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Access Denied — Mehtab Garments</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="<?= asset_url('/assets/css/style.css') ?>">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1.5rem;">
  <?php renderIconSprite(); ?>

  <div class="auth-card" style="text-align: center; max-width: 480px; margin: auto; padding: 2.5rem 2rem;">
    <div style="width: 80px; height: 80px; margin: 0 auto 1.5rem; border-radius: 50%; background: #FEE2E2; display: flex; align-items: center; justify-content: center; color: #DC2626; box-shadow: 0 8px 24px rgba(220, 38, 38, 0.15);">
      <span style="display: inline-flex; transform: scale(1.6);"><?= icon('shield') ?></span>
    </div>

    <div style="font-family: var(--font-serif); font-size: 3.5rem; font-weight: 700; color: #BE123C; line-height: 1; margin-bottom: 0.75rem;">
      403
    </div>

    <h1 style="font-size: 1.5rem; margin-bottom: 0.75rem; color: var(--color-black);">Access Restricted</h1>
    
    <p style="color: var(--color-text-muted); font-size: 0.95rem; margin-bottom: 2rem; line-height: 1.5;">
      You do not have administrative clearance to access this resource or perform this action.
    </p>

    <a href="<?= APP_URL ?>/index.php" class="btn btn-blue btn-primary" style="width: 100%; justify-content: center;">
      <?= icon('tag') ?>
      <span>Return to Catalog</span>
    </a>
  </div>

</body>
</html>
