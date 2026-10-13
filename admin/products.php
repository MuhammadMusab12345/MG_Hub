<?php
/**
 * Mehtab Garments — Admin Products Management
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';

requireAdmin();

$db = getDb();

// Fetch all products across all users
$stmt = $db->query('
    SELECT id, user_id, share_token, name, design_number, retail_price,
           main_image_path, status, created_at
    FROM products
    ORDER BY created_at DESC
');
$products = $stmt->fetchAll();

$pageTitle = 'All Products (Admin)';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="container" style="padding-top: 1rem;">
  
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 12px;">
    <div>
      <h1 style="margin: 0;">Catalog Products</h1>
      <p style="color: var(--color-text-muted); font-size: 0.95rem; margin-top: 4px;">Manage and audit all system products</p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
      <a href="<?= APP_URL ?>/add-product.php" class="btn btn-primary">
        <?= icon('plus') ?>
        <span>Add Product</span>
      </a>
      <a href="<?= APP_URL ?>/admin/index.php" class="btn btn-secondary">
        <?= icon('shield') ?>
        <span>Admin Overview</span>
      </a>
    </div>
  </div>

  <?php if (empty($products)): ?>
    <div class="empty-state">
      <h2 class="empty-state-title">No products found</h2>
      <p class="empty-state-desc">The catalog has no products yet.</p>
    </div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th style="width: 70px;">Image</th>
            <th>Name</th>
            <!-- Strict Privacy: Owner shown ONLY as "User #ID" -->
            <th>Owner</th>
            <th>Design #</th>
            <th>Retail Price</th>
            <th>Status</th>
            <th>Created</th>
            <th style="text-align: right;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($products as $prod): 
            $shareUrl = getShareUrl($prod['share_token']);
          ?>
            <tr>
              <!-- Thumbnail -->
              <td>
                <div style="width: 48px; height: 60px; border-radius: var(--radius-sm); overflow: hidden; background: #eee; border: 1px solid var(--color-border);">
                  <?php if (!empty($prod['main_image_path']) && file_exists(ROOT_PATH . '/' . $prod['main_image_path'])): ?>
                    <img src="<?= APP_URL . '/' . e($prod['main_image_path']) ?>" alt="Thumbnail" style="width: 100%; height: 100%; object-fit: cover;">
                  <?php else: ?>
                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 0.65rem; color: #94A3B8;">None</div>
                  <?php endif; ?>
                </div>
              </td>

              <!-- Name -->
              <td>
                <strong>
                  <a href="<?= APP_URL ?>/product-details.php?id=<?= (int)$prod['id'] ?>">
                    <?= !empty($prod['name']) ? e($prod['name']) : '<em style="color:#94a3b8;">(Untitled Product)</em>' ?>
                  </a>
                </strong>
              </td>

              <!-- Owner: strictly "User #ID" -->
              <td>
                <span class="badge" style="background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1;">
                  User #<?= (int)$prod['user_id'] ?>
                </span>
              </td>

              <!-- Design Number -->
              <td>
                <?= !empty($prod['design_number']) ? e($prod['design_number']) : '—' ?>
              </td>

              <!-- Retail Price -->
              <td>
                <?= ($prod['retail_price'] !== null && $prod['retail_price'] !== '') ? formatPrice($prod['retail_price']) : '—' ?>
              </td>

              <!-- Status -->
              <td>
                <span class="badge <?= ($prod['status'] === 'published') ? 'badge-published' : 'badge-draft' ?>">
                  <?= e(ucfirst($prod['status'])) ?>
                </span>
              </td>

              <!-- Created -->
              <td>
                <?= date('M j, Y', strtotime($prod['created_at'])) ?>
              </td>

              <!-- Actions -->
              <td style="text-align: right; white-space: nowrap;">
                <div style="display: inline-flex; gap: 6px; align-items: center;">
                  
                  <a href="<?= APP_URL ?>/product-details.php?id=<?= (int)$prod['id'] ?>" class="btn btn-blue btn-sm" title="View details">
                    <?= icon('eye') ?>
                    <span>View</span>
                  </a>

                  <a href="<?= APP_URL ?>/edit-product.php?id=<?= (int)$prod['id'] ?>" class="btn btn-blue btn-sm" title="Edit product">
                    <?= icon('edit') ?>
                    <span>Edit</span>
                  </a>

                  <!-- Copy Link -->
                  <button 
                    type="button" 
                    class="btn btn-gray btn-secondary btn-sm js-copy-link" 
                    data-url="<?= e($shareUrl) ?>"
                    title="Copy Share Link"
                  >
                    <?= icon('link') ?>
                    <span>Link</span>
                  </button>

                  <!-- Toggle Status (POST) -->
                  <form action="<?= APP_URL ?>/toggle-status.php" method="POST" style="display: inline;">
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$prod['id'] ?>">
                    <input type="hidden" name="return_url" value="<?= APP_URL ?>/admin/products.php">
                    <button type="submit" class="btn <?= ($prod['status'] === 'published') ? 'btn-gray btn-secondary' : 'btn-blue' ?> btn-sm" title="Toggle status">
                      <?= ($prod['status'] === 'published') ? icon('draft') . '<span>Unpublish</span>' : icon('publish') . '<span>Publish</span>' ?>
                    </button>
                  </form>

                  <!-- Delete (POST with Confirmation) -->
                  <form 
                    action="<?= APP_URL ?>/delete-product.php" 
                    method="POST" 
                    style="display: inline;" 
                    class="js-confirm-action" 
                    data-confirm-message="Admin Action: Delete this product and all associated image files?"
                  >
                    <?= csrfField() ?>
                    <input type="hidden" name="id" value="<?= (int)$prod['id'] ?>">
                    <input type="hidden" name="return_url" value="<?= APP_URL ?>/admin/products.php">
                    <button type="submit" class="btn btn-crimson btn-danger btn-sm" title="Delete product">
                      <?= icon('trash') ?>
                      <span>Delete</span>
                    </button>
                  </form>

                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
