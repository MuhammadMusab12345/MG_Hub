<?php
/**
 * Mehtab Garments — Secure Image Upload Handler
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';

const ALLOWED_MIME_TYPES = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

/**
 * Validates and processes a single uploaded image.
 *
 * @param array $file The $_FILES entry (e.g. $_FILES['main_image'])
 * @return array{success: bool, path?: string, error?: string}
 */
function handleSingleUpload(array $file): array {
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['success' => false, 'error' => 'Invalid file upload parameters.'];
    }

    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'error' => 'No file uploaded.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload error (code ' . $file['error'] . ').'];
    }

    if ($file['size'] > MAX_UPLOAD_SIZE) {
        return ['success' => false, 'error' => 'File size exceeds the 8 MB maximum limit.'];
    }

    $tmpPath = $file['tmp_name'];
    if (!is_uploaded_file($tmpPath)) {
        return ['success' => false, 'error' => 'Possible file upload attack detected.'];
    }

    // Validate real MIME type via finfo
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $realMime = $finfo->file($tmpPath);

    if (!array_key_exists($realMime, ALLOWED_MIME_TYPES)) {
        return ['success' => false, 'error' => 'Only JPG, PNG, and WebP images are allowed.'];
    }

    // Sane dimension check
    $dimensions = @getimagesize($tmpPath);
    if ($dimensions === false || $dimensions[0] <= 0 || $dimensions[1] <= 0) {
        return ['success' => false, 'error' => 'The uploaded file is not a valid image.'];
    }

    if ($dimensions[0] > 10000 || $dimensions[1] > 10000) {
        return ['success' => false, 'error' => 'Image dimensions are too large. Maximum 10,000 x 10,000 pixels.'];
    }

    // Generate random unique filename
    $ext = ALLOWED_MIME_TYPES[$realMime];
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    
    if (!is_dir(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0755, true);
    }

    $destination = UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename;
    if (file_exists($destination)) {
        $filename = bin2hex(random_bytes(18)) . '.' . $ext;
        $destination = UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename;
    }

    if (!move_uploaded_file($tmpPath, $destination)) {
        return ['success' => false, 'error' => 'Failed to save the uploaded image to the server.'];
    }

    // Return relative path for database storage
    $relativePath = 'uploads/products/' . $filename;
    return ['success' => true, 'path' => $relativePath];
}

/**
 * Processes multiple uploaded files (e.g. additional images).
 *
 * @param array $files The $_FILES['additional_images'] structure
 * @return array{success: bool, paths: array<string>, errors: array<string>}
 */
function handleMultipleUploads(array $files): array {
    $paths = [];
    $errors = [];

    if (!isset($files['name']) || !is_array($files['name'])) {
        return ['success' => true, 'paths' => [], 'errors' => []];
    }

    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $single = [
            'name'     => $files['name'][$i],
            'type'     => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error'    => $files['error'][$i],
            'size'     => $files['size'][$i],
        ];

        $res = handleSingleUpload($single);
        if ($res['success']) {
            $paths[] = $res['path'];
        } else {
            $errors[] = $res['error'];
        }
    }

    return [
        'success' => count($errors) === 0,
        'paths'   => $paths,
        'errors'  => $errors,
    ];
}

/**
 * Safely deletes an image file from disk, strictly within the uploads directory.
 */
function safeDeleteImage(?string $relativePath): bool {
    if (empty($relativePath)) {
        return false;
    }

    $basename = basename($relativePath);
    if ($basename === '' || $basename === '.' || $basename === '..') {
        return false;
    }

    $fullPath = realpath(UPLOAD_PATH . DIRECTORY_SEPARATOR . $basename);
    $realUploadDir = realpath(UPLOAD_PATH);

    if ($fullPath === false || $realUploadDir === false) {
        return false;
    }

    // Path traversal check
    if (strpos($fullPath, $realUploadDir) !== 0) {
        return false;
    }

    if (file_exists($fullPath) && is_file($fullPath)) {
        return @unlink($fullPath);
    }

    return false;
}
