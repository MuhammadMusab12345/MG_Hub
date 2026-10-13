<?php
/**
 * Mehtab Garments — Safe Main Image Download Handler
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$productId) {
    abort404();
}

$db = getDb();
$stmt = $db->prepare('
    SELECT id, user_id, name, status, main_image_path 
    FROM products 
    WHERE id = :id 
    LIMIT 1
');
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    abort404();
}

// Access control: published products are public; drafts require owner or admin
if ($product['status'] !== 'published') {
    $currentId = currentUserId();
    $canAccessDraft = ($currentId !== null && ((int)$product['user_id'] === $currentId || isAdmin()));
    if (!$canAccessDraft) {
        abort404();
    }
}

if (empty($product['main_image_path'])) {
    abort404();
}

$fullPath = realpath(ROOT_PATH . DIRECTORY_SEPARATOR . $product['main_image_path']);
$allowedDir = realpath(UPLOAD_PATH);

// Directory traversal prevention
if ($fullPath === false || $allowedDir === false || !str_starts_with($fullPath, $allowedDir)) {
    abort404();
}

if (!is_file($fullPath) || !is_readable($fullPath)) {
    abort404();
}

// Generate meaningful slugified filename
$ext = pathinfo($fullPath, PATHINFO_EXTENSION);
$slug = slugify((string)($product['name'] ?? ''));
if ($slug === '' || $slug === 'product') {
    $downloadName = 'product-' . (int)$product['id'] . '.' . $ext;
} else {
    $downloadName = $slug . '.' . $ext;
}

// Detect MIME type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($fullPath) ?: 'application/octet-stream';

// Clear output buffers
if (ob_get_level()) {
    ob_end_clean();
}

// Send download headers
header('Content-Description: File Transfer');
header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $downloadName . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Content-Length: ' . filesize($fullPath));

readfile($fullPath);
exit;
