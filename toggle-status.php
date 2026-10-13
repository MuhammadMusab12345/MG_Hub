<?php
/**
 * Mehtab Garments — Toggle Product Status (POST Only)
 * Admin-only operation.
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';

// Strict security: Administrator authorization required
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    abort403();
}

requireCsrf();

$productId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$productId) {
    abort404();
}

$db = getDb();
$stmt = $db->prepare('SELECT id, user_id, status FROM products WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    abort404();
}

$newStatus = ($product['status'] === 'published') ? 'draft' : 'published';

try {
    $updateStmt = $db->prepare('UPDATE products SET status = :status, updated_at = NOW() WHERE id = :id');
    $updateStmt->execute([':status' => $newStatus, ':id' => $productId]);

    $label = ($newStatus === 'published') ? 'published' : 'moved to drafts';
    setFlash('success', "Product successfully {$label}.");
} catch (Exception $e) {
    error_log('Error updating status: ' . $e->getMessage());
    setFlash('error', 'Unable to update product status.');
}

$returnUrl = $_POST['return_url'] ?? APP_URL . '/admin/products.php';
if (str_starts_with($returnUrl, APP_URL)) {
    redirect($returnUrl);
} else {
    redirect(APP_URL . '/admin/products.php');
}
