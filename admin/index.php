<?php
/**
 * Mehtab Garments — Admin Dashboard Overview (Round 3)
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/functions.php';

// Strict access control: Admins only
requireAdmin();

$db = getDb();

// Statistics
$totalUsers = (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalProducts = (int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn();
$publishedProducts = (int)$db->query("SELECT COUNT(*) FROM products WHERE status = 'published'")->fetchColumn();
$draftProducts = (int)$db->query("SELECT COUNT(*) FROM products WHERE status = 'draft'")->fetchColumn();

$pageTitle = 'Admin Dashboard';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="container" style="padding-top: 1rem;">
  
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 12px;">
    <div>
      <h1 style="margin: 0; display: flex; align-items: center; gap: 10px;">
        <span style="color: var(--color-blue);"><?= icon('shield') ?></span>
        <span>Administration</span>
      </h1>
      <p style="color: var(--color-text-muted); font-size: 0.95rem; margin-top: 4px;">System Overview & Statistics</p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
      <a href="<?= APP_URL ?>/add-product.php" class="btn btn-blue">
        <?= icon('plus') ?>
        <span>Add Product</span>
      </a>
      <a href="<?= APP_URL ?>/admin/products.php" class="btn btn-secondary">
        <?= icon('tag') ?>
        <span>Manage Products</span>
      </a>
      <a href="<?= APP_URL ?>/admin/users.php" class="btn btn-secondary">
        <?= icon('user') ?>
        <span>Manage Users</span>
      </a>
    </div>
  </div>

  <!-- Statistics Grid -->
  <div class="stats-grid">
    <div class="stat-card">
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
        <span class="stat-label">Total Users</span>
        <span style="color: var(--color-blue); opacity: 0.8;"><?= icon('user') ?></span>
      </div>
      <span class="stat-value"><?= $totalUsers ?></span>
    </div>
    <div class="stat-card">
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
        <span class="stat-label">Total Products</span>
        <span style="color: var(--color-blue); opacity: 0.8;"><?= icon('tag') ?></span>
      </div>
      <span class="stat-value"><?= $totalProducts ?></span>
    </div>
    <div class="stat-card">
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
        <span class="stat-label">Published</span>
        <span style="color: var(--color-success); opacity: 0.8;"><?= icon('publish') ?></span>
      </div>
      <span class="stat-value" style="color: var(--color-success);"><?= $publishedProducts ?></span>
    </div>
    <div class="stat-card">
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
        <span class="stat-label">Drafts</span>
        <span style="color: #D97706; opacity: 0.8;"><?= icon('draft') ?></span>
      </div>
      <span class="stat-value" style="color: #D97706;"><?= $draftProducts ?></span>
    </div>
  </div>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 1.5rem; margin-top: 2rem;">
    
    <div class="form-card" style="margin-bottom: 0;">
      <h2 style="font-size: 1.3rem; margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
        <span style="color: var(--color-blue);"><?= icon('tag') ?></span>
        <span>Catalog Management</span>
      </h2>
      <p style="color: var(--color-text-muted); font-size: 0.92rem; margin-bottom: 1.25rem;">
        Add, audit, edit, toggle visibility, and delete products across the entire clothing catalog.
      </p>
      <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="<?= APP_URL ?>/add-product.php" class="btn btn-blue">
          <?= icon('plus') ?>
          <span>Add Product</span>
        </a>
        <a href="<?= APP_URL ?>/admin/products.php" class="btn btn-secondary">
          <?= icon('eye') ?>
          <span>Go to Products (<?= $totalProducts ?>)</span>
        </a>
      </div>
    </div>

    <div class="form-card" style="margin-bottom: 0;">
      <h2 style="font-size: 1.3rem; margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
        <span style="color: var(--color-blue);"><?= icon('user') ?></span>
        <span>Account Directory</span>
      </h2>
      <p style="color: var(--color-text-muted); font-size: 0.92rem; margin-bottom: 1.25rem;">
        View system account IDs, assigned roles, product activity counts, and registration dates.
      </p>
      <a href="<?= APP_URL ?>/admin/users.php" class="btn btn-secondary">
        <?= icon('user') ?>
        <span>Go to Users (<?= $totalUsers ?>)</span>
      </a>
    </div>

  </div>

</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
