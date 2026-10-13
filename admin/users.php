<?php
/**
 * Mehtab Garments — Admin Users Management
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/csrf.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/upload.php';

requireAdmin();

$db = getDb();
$currentAdminId = currentUserId();

// Handle deletion of normal users
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    $deleteUserId = filter_input(INPUT_POST, 'delete_user_id', FILTER_VALIDATE_INT);

    if (!$deleteUserId) {
        setFlash('error', 'Invalid user ID.');
        redirect(APP_URL . '/admin/users.php');
    }

    // Protection: Admins can never delete themselves
    if ($deleteUserId === $currentAdminId) {
        setFlash('error', 'You cannot delete your own admin account.');
        redirect(APP_URL . '/admin/users.php');
    }

    // Fetch user to check role
    $uStmt = $db->prepare('SELECT id, role FROM users WHERE id = :id LIMIT 1');
    $uStmt->execute([':id' => $deleteUserId]);
    $targetUser = $uStmt->fetch();

    if (!$targetUser) {
        setFlash('error', 'User not found.');
        redirect(APP_URL . '/admin/users.php');
    }

    // Protection: Only normal users can be deleted
    if ($targetUser['role'] === 'admin') {
        setFlash('error', 'Admin accounts cannot be deleted through this interface.');
        redirect(APP_URL . '/admin/users.php');
    }

    try {
        // 1. Clean up all images associated with this user's products
        $imgMainStmt = $db->prepare('SELECT main_image_path FROM products WHERE user_id = :uid');
        $imgMainStmt->execute([':uid' => $deleteUserId]);
        $mainImages = $imgMainStmt->fetchAll(PDO::FETCH_COLUMN);

        $imgAddlStmt = $db->prepare('
            SELECT pi.image_path 
            FROM product_images pi
            INNER JOIN products p ON pi.product_id = p.id
            WHERE p.user_id = :uid
        ');
        $imgAddlStmt->execute([':uid' => $deleteUserId]);
        $addlImages = $imgAddlStmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($mainImages as $path) {
            if (!empty($path)) safeDeleteImage($path);
        }
        foreach ($addlImages as $path) {
            if (!empty($path)) safeDeleteImage($path);
        }

        // 2. Delete the user (Foreign key ON DELETE CASCADE deletes products and product_images)
        $delStmt = $db->prepare('DELETE FROM users WHERE id = :id AND role = \'user\'');
        $delStmt->execute([':id' => $deleteUserId]);

        setFlash('success', "User #{$deleteUserId} and all their catalog products have been deleted.");
    } catch (Exception $e) {
        error_log('Error deleting user: ' . $e->getMessage());
        setFlash('error', 'An error occurred while deleting the user account.');
    }

    redirect(APP_URL . '/admin/users.php');
}

// Fetch all users with their product count.
// STRICT PRIVACY RULE: Show ONLY "User #ID", role, product count, joined date. NO name, NO email, NO password hash!
$query = '
    SELECT 
        u.id, 
        u.role, 
        u.created_at, 
        COUNT(p.id) AS product_count
    FROM users u
    LEFT JOIN products p ON u.id = p.user_id
    GROUP BY u.id, u.role, u.created_at
    ORDER BY u.id ASC
';
$users = $db->query($query)->fetchAll();

$pageTitle = 'Manage Users (Admin)';
require_once dirname(__DIR__) . '/includes/header.php';
?>

<div class="container" style="padding-top: 1rem;">
  
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 12px;">
    <div>
      <h1 style="margin: 0;">User Accounts</h1>
      <p style="color: var(--color-text-muted); font-size: 0.95rem; margin-top: 4px;">System account directory</p>
    </div>
    <a href="<?= APP_URL ?>/admin/index.php" class="btn btn-secondary">
      <?= icon('shield') ?>
      <span>Admin Overview</span>
    </a>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <!-- Strict Privacy: NO name, NO email, NO password hash -->
          <th>User Identifier</th>
          <th>Role</th>
          <th>Catalog Products</th>
          <th>Registered Date</th>
          <th style="text-align: right;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $user): ?>
          <tr>
            <td>
              <strong>User #<?= (int)$user['id'] ?></strong>
              <?php if ((int)$user['id'] === $currentAdminId): ?>
                <span style="font-size: 0.8rem; color: var(--color-blue); margin-left: 6px;">(You)</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ($user['role'] === 'admin'): ?>
                <span class="badge" style="background: #DBEAFE; color: var(--color-blue-deep); border: 1px solid #BFDBFE;">
                  Admin
                </span>
              <?php else: ?>
                <span class="badge" style="background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1;">
                  User
                </span>
              <?php endif; ?>
            </td>
            <td>
              <?= (int)$user['product_count'] ?> products
            </td>
            <td>
              <?= date('M j, Y', strtotime($user['created_at'])) ?>
            </td>
            <td style="text-align: right;">
              <?php if ($user['role'] !== 'admin'): ?>
                <form 
                  action="<?= APP_URL ?>/admin/users.php" 
                  method="POST" 
                  style="display: inline;" 
                  class="js-confirm-action" 
                  data-confirm-message="Are you sure you want to delete User #<?= (int)$user['id'] ?>? All products and uploaded files belonging to this user will be permanently deleted."
                >
                  <?= csrfField() ?>
                  <input type="hidden" name="delete_user_id" value="<?= (int)$user['id'] ?>">
                  <button type="submit" class="btn btn-crimson btn-danger btn-sm">
                    <?= icon('trash') ?>
                    <span>Delete User</span>
                  </button>
                </form>
              <?php else: ?>
                <span style="font-size: 0.85rem; color: var(--color-text-muted);">Protected</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
