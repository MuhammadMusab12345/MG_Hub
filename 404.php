<?php
/**
 * Mehtab Garments — 404 Not Found
 */

declare(strict_types=1);

if (!defined('APP_NAME')) {
    require_once __DIR__ . '/config/app.php';
    require_once __DIR__ . '/includes/functions.php';
}

http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Page Not Found — Mehtab Garments</title>
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="<?= asset_url('/assets/css/style.css') ?>">
</head>
<body style="display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1.5rem;">
  <?php renderIconSprite(); ?>

  <div class="auth-card" style="text-align: center; max-width: 480px; margin: auto; padding: 2.5rem 2rem;">
    <div style="width: 80px; height: 80px; margin: 0 auto 1.5rem; border-radius: 50%; background: #EFF6FF; display: flex; align-items: center; justify-content: center; color: var(--color-blue); box-shadow: 0 8px 24px rgba(37, 99, 235, 0.15);">
      <span style="display: inline-flex; transform: scale(1.6);"><?= icon('search') ?></span>
    </div>

    <div style="font-family: var(--font-serif); font-size: 3.5rem; font-weight: 700; color: var(--color-blue); line-height: 1; margin-bottom: 0.75rem;">
      404
    </div>

    <h1 style="font-size: 1.5rem; margin-bottom: 0.75rem; color: var(--color-black);">Item or Page Not Found</h1>
    
    <p style="color: var(--color-text-muted); font-size: 0.95rem; margin-bottom: 2rem; line-height: 1.5;">
      The page, design, or catalog token you requested does not exist or may have been archived.
    </p>

    <a href="<?= APP_URL ?>/index.php" class="btn btn-blue btn-primary" style="width: 100%; justify-content: center;">
      <?= icon('tag') ?>
      <span>Return to Catalog</span>
    </a>
  </div>

</body>
</html>
