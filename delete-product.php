<?php
/**
 * Mehtab Garments — Safe Product Deletion Handler (POST Only)
 * Admin-only operation.
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/upload.php';

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
$stmt = $db->prepare('SELECT id, user_id, main_image_path FROM products WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    abort404();
}

try {
    // 1. Fetch additional images to delete them safely from disk
    $imgStmt = $db->prepare('SELECT image_path FROM product_images WHERE product_id = :id');
    $imgStmt->execute([':id' => $productId]);
    $additionalImages = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

    // 2. Safely delete main image and additional images from disk
    if (!empty($product['main_image_path'])) {
        safeDeleteImage($product['main_image_path']);
    }
    foreach ($additionalImages as $imgPath) {
        if (!empty($imgPath)) {
            safeDeleteImage($imgPath);
        }
    }

    // 3. Delete database record (foreign keys cascade to product_images)
    $delStmt = $db->prepare('DELETE FROM products WHERE id = :id');
    $delStmt->execute([':id' => $productId]);

    setFlash('success', 'Product and associated files deleted successfully.');
} catch (Exception $e) {
    error_log('Error deleting product: ' . $e->getMessage());
    setFlash('error', 'Unable to delete the product.');
}

$returnUrl = $_POST['return_url'] ?? APP_URL . '/admin/products.php';
if (str_starts_with($returnUrl, APP_URL)) {
    redirect($returnUrl);
} else {
    redirect(APP_URL . '/admin/products.php');
}
