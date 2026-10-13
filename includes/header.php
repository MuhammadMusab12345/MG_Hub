<?php
/**
 * Mehtab Garments — Common Header (Round 2)
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$pageTitle = isset($pageTitle) ? $pageTitle . ' — ' . APP_NAME : APP_NAME . ' — Premium Clothing Catalog';
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$defaultWaMsg = 'Hello Mehtab Garments, I would like to inquire about your clothing catalog.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?></title>
  <meta name="description" content="Mehtab Garments — Curated catalog of premium stitched and unstitched collections.">
  <link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
  <?php if (file_exists(ROOT_PATH . '/assets/images/logo.png')): ?>
    <link rel="icon" type="image/png" href="<?= APP_URL ?>/assets/images/logo.png">
  <?php endif; ?>
</head>
<body>

<?php renderIconSprite(); ?>

<header class="site-header">
  <div class="container header-inner">
    <!-- Brand / Logo (Left) -->
    <a href="<?= APP_URL ?>/index.php" class="brand-link" aria-label="Mehtab Garments Home">
      <?php if (file_exists(ROOT_PATH . '/assets/images/logo.png')): ?>
        <img src="<?= APP_URL ?>/assets/images/logo.png" alt="Mehtab Garments Logo" class="brand-logo-img">
      <?php else: ?>
        <div class="brand-logo-fallback" aria-hidden="true">MG</div>
      <?php endif; ?>
      <span>Mehtab Garments</span>
    </a>

    <!-- Right Cluster: WhatsApp + Menu -->
    <div class="nav-right-cluster">
      
      <!-- Mobile WhatsApp Button (Always visible on mobile next to hamburger) -->
      <a 
        href="<?= whatsapp_link($defaultWaMsg) ?>" 
        target="_blank" 
        rel="noopener noreferrer" 
        class="btn btn-green btn-whatsapp nav-whatsapp-mobile navbar-btn-whatsapp"
        aria-label="Order or chat on WhatsApp"
        title="Chat on WhatsApp"
      >
        <?= icon('whatsapp') ?>
      </a>

      <!-- Desktop WhatsApp Button (Pulsing green glow attention effect) -->
      <a 
        href="<?= whatsapp_link($defaultWaMsg) ?>" 
        target="_blank" 
        rel="noopener noreferrer" 
        class="btn btn-green btn-whatsapp btn-whatsapp-pulse nav-whatsapp-desktop navbar-btn-whatsapp"
        aria-label="Order or chat on WhatsApp"
      >
        <?= icon('whatsapp') ?>
        <span>WhatsApp</span>
      </a>

      <!-- Mobile Toggle Button -->
      <button class="nav-toggle" aria-label="Toggle navigation menu" aria-expanded="false">
        <?= icon('menu') ?>
      </button>

      <!-- Navigation Menu (Right) - NEVER shows user's name or email -->
      <nav>
        <ul class="nav-menu">
          <li>
            <a href="<?= APP_URL ?>/index.php" class="nav-link <?= ($currentScript === 'index.php') ? 'active' : '' ?>">
              <?= icon('tag') ?>
              <span>Catalog</span>
            </a>
          </li>
          <?php if (isAdmin()): ?>
            <li>
              <a href="<?= APP_URL ?>/add-product.php" class="btn btn-blue btn-sm <?= ($currentScript === 'add-product.php') ? 'active' : '' ?>">
                <?= icon('plus') ?>
                <span>+ New</span>
              </a>
            </li>
            <li>
              <a href="<?= APP_URL ?>/admin/index.php" class="btn btn-dark btn-sm <?= (str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin/')) ? 'active' : '' ?>">
                <?= icon('shield') ?>
                <span>Admin</span>
              </a>
            </li>
            <li>
              <a href="<?= APP_URL ?>/logout.php" class="btn btn-slate btn-sm">
                <?= icon('logout') ?>
                <span>Logout</span>
              </a>
            </li>
          <?php else: ?>
            <li>
              <a href="<?= APP_URL ?>/login.php" class="btn btn-blue btn-sm <?= ($currentScript === 'login.php') ? 'active' : '' ?>">
                <?= icon('login') ?>
                <span>Login</span>
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
  </div>
</header>

<main class="site-main">
  <div class="container" style="margin-top: 1rem;">
    <?php if ($flashSuccess = getFlash('success')): ?>
      <div class="alert alert-success" role="alert">
        <?= icon('check') ?>
        <span><?= e($flashSuccess) ?></span>
      </div>
    <?php endif; ?>

    <?php if ($flashError = getFlash('error')): ?>
      <div class="alert alert-error" role="alert">
        <?= icon('close') ?>
        <span><?= e($flashError) ?></span>
      </div>
    <?php endif; ?>

    <?php if ($flashInfo = getFlash('info')): ?>
      <div class="alert alert-info" role="alert">
        <?= icon('tag') ?>
        <span><?= e($flashInfo) ?></span>
      </div>
    <?php endif; ?>
  </div>
