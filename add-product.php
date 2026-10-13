<?php
/**
 * Mehtab Garments — Add New Product
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

$db = getDb();
$errors = [];

// Form field defaults
$data = [
    'name'              => '',
    'design_number'     => '',
    'retail_price'      => '',
    'fabric_price'      => '',
    'description'       => '',
    'fabric1_name'      => '',
    'fabric1_details'   => '',
    'fabric1_cost'      => '',
    'fabric2_name'      => '',
    'fabric2_details'   => '',
    'fabric2_cost'      => '',
    'inner_back_cost'   => '',
    'embroidery_cost'   => '',
    'neck_rate'         => '',
    'cutwork_cost'      => '',
    'cutting_cost'      => '',
    'stitching_cost'    => '',
    'press_cost'        => '',
    'elastic_cost'      => '',
    'thread_cost'       => '',
    'poly_bag_cost'     => '',
    'pico_cost'         => '',
    'private_details'   => '',
    'status'            => 'draft',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();

    // Collect posted values
    foreach ($data as $key => $val) {
        if ($key === 'status') {
            $data[$key] = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
        } else {
            $data[$key] = trim($_POST[$key] ?? '');
        }
    }

    // Numeric field validation
    $numericFields = [
        'retail_price'    => 'Retail Price',
        'fabric_price'    => 'Fabric Price',
        'fabric1_cost'    => 'Fabric 1 Cost',
        'fabric2_cost'    => 'Fabric 2 Cost',
        'inner_back_cost' => 'Inner / Back Cost',
        'embroidery_cost' => 'Embroidery Cost',
        'neck_rate'       => 'Neck Rate',
        'cutwork_cost'    => 'Cutwork Cost',
        'cutting_cost'    => 'Cutting Cost',
        'stitching_cost'  => 'Stitching Cost',
        'press_cost'      => 'Press Cost',
        'elastic_cost'    => 'Elastic Cost',
        'thread_cost'     => 'Thread Cost',
        'poly_bag_cost'   => 'Poly Bag Cost',
        'pico_cost'       => 'Pico Cost',
    ];

    $parsedDecimals = [];
    foreach ($numericFields as $fieldKey => $fieldLabel) {
        $rawVal = $data[$fieldKey];
        $parsed = parseOptionalDecimal($rawVal);
        if ($parsed === false) {
            $errors[] = "{$fieldLabel} must be a valid non-negative number.";
        } else {
            $parsedDecimals[$fieldKey] = $parsed;
        }
    }

    $hasMainUpload = isset($_FILES['main_image']) && $_FILES['main_image']['error'] !== UPLOAD_ERR_NO_FILE;
    $hasAddlUpload = isset($_FILES['additional_images']) && !empty($_FILES['additional_images']['name'][0]);

    // Check if the form is completely empty
    $allTextEmpty = true;
    foreach ($data as $k => $v) {
        if ($k !== 'status' && $v !== '') {
            $allTextEmpty = false;
            break;
        }
    }

    if ($allTextEmpty && !$hasMainUpload && !$hasAddlUpload) {
        $errors[] = 'Please enter at least one detail or add an image.';
    }

    // Process file uploads if no prior errors
    $uploadedMainPath = null;
    $uploadedAddlPaths = [];

    if (empty($errors)) {
        if ($hasMainUpload) {
            $mainRes = handleSingleUpload($_FILES['main_image']);
            if ($mainRes['success']) {
                $uploadedMainPath = $mainRes['path'];
            } else {
                $errors[] = 'Main image error: ' . $mainRes['error'];
            }
        }

        if ($hasAddlUpload && empty($errors)) {
            $multiRes = handleMultipleUploads($_FILES['additional_images']);
            if ($multiRes['success']) {
                $uploadedAddlPaths = $multiRes['paths'];
            } else {
                $errors = array_merge($errors, $multiRes['errors']);
            }
        }
    }

    // If any error occurred, clean up newly uploaded files
    if (!empty($errors)) {
        if ($uploadedMainPath) {
            safeDeleteImage($uploadedMainPath);
        }
        foreach ($uploadedAddlPaths as $p) {
            safeDeleteImage($p);
        }
    } else {
        // Save to database
        try {
            $shareToken = generateShareToken();
            $userId = currentUserId();

            $insertSql = '
                INSERT INTO products (
                    share_token, user_id, name, design_number, retail_price, fabric_price,
                    description, private_details, fabric1_name, fabric1_details, fabric1_cost,
                    fabric2_name, fabric2_details, fabric2_cost, inner_back_cost, embroidery_cost,
                    neck_rate, cutwork_cost, cutting_cost, stitching_cost, press_cost,
                    elastic_cost, thread_cost, poly_bag_cost, pico_cost, main_image_path,
                    status, created_at, updated_at
                ) VALUES (
                    :share_token, :user_id, :name, :design_number, :retail_price, :fabric_price,
                    :description, :private_details, :fabric1_name, :fabric1_details, :fabric1_cost,
                    :fabric2_name, :fabric2_details, :fabric2_cost, :inner_back_cost, :embroidery_cost,
                    :neck_rate, :cutwork_cost, :cutting_cost, :stitching_cost, :press_cost,
                    :elastic_cost, :thread_cost, :poly_bag_cost, :pico_cost, :main_image_path,
                    :status, NOW(), NOW()
                )
            ';

            $stmt = $db->prepare($insertSql);
            $stmt->execute([
                ':share_token'      => $shareToken,
                ':user_id'          => $userId,
                ':name'             => ($data['name'] !== '') ? $data['name'] : null,
                ':design_number'    => ($data['design_number'] !== '') ? $data['design_number'] : null,
                ':retail_price'     => $parsedDecimals['retail_price'],
                ':fabric_price'     => $parsedDecimals['fabric_price'],
                ':description'      => ($data['description'] !== '') ? $data['description'] : null,
                ':private_details'  => ($data['private_details'] !== '') ? $data['private_details'] : null,
                ':fabric1_name'     => ($data['fabric1_name'] !== '') ? $data['fabric1_name'] : null,
                ':fabric1_details'  => ($data['fabric1_details'] !== '') ? $data['fabric1_details'] : null,
                ':fabric1_cost'     => $parsedDecimals['fabric1_cost'],
                ':fabric2_name'     => ($data['fabric2_name'] !== '') ? $data['fabric2_name'] : null,
                ':fabric2_details'  => ($data['fabric2_details'] !== '') ? $data['fabric2_details'] : null,
                ':fabric2_cost'     => $parsedDecimals['fabric2_cost'],
                ':inner_back_cost'  => $parsedDecimals['inner_back_cost'],
                ':embroidery_cost'  => $parsedDecimals['embroidery_cost'],
                ':neck_rate'        => $parsedDecimals['neck_rate'],
                ':cutwork_cost'     => $parsedDecimals['cutwork_cost'],
                ':cutting_cost'     => $parsedDecimals['cutting_cost'],
                ':stitching_cost'   => $parsedDecimals['stitching_cost'],
                ':press_cost'       => $parsedDecimals['press_cost'],
                ':elastic_cost'     => $parsedDecimals['elastic_cost'],
                ':thread_cost'      => $parsedDecimals['thread_cost'],
                ':poly_bag_cost'    => $parsedDecimals['poly_bag_cost'],
                ':pico_cost'        => $parsedDecimals['pico_cost'],
                ':main_image_path'  => $uploadedMainPath,
                ':status'           => $data['status'],
            ]);

            $newProductId = (int)$db->lastInsertId();

            // Insert additional images
            if (!empty($uploadedAddlPaths)) {
                $imgStmt = $db->prepare('
                    INSERT INTO product_images (product_id, image_path, sort_order, created_at)
                    VALUES (:pid, :path, :sort, NOW())
                ');
                foreach ($uploadedAddlPaths as $idx => $path) {
                    $imgStmt->execute([
                        ':pid'  => $newProductId,
                        ':path' => $path,
                        ':sort' => $idx,
                    ]);
                }
            }

            setFlash('success', 'Product created successfully.');
            redirect(APP_URL . '/admin/products.php');
        } catch (Exception $e) {
            error_log('Error creating product: ' . $e->getMessage());
            $errors[] = 'A database error occurred while creating the product.';
            if ($uploadedMainPath) {
                safeDeleteImage($uploadedMainPath);
            }
            foreach ($uploadedAddlPaths as $p) {
                safeDeleteImage($p);
            }
        }
    }
}

$pageTitle = 'Add New Product';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 900px; padding-top: 1rem;">
  
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
    <h1>Add New Product</h1>
    <a href="<?= APP_URL ?>/dashboard.php" class="btn btn-ghost">Cancel</a>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-error" role="alert">
      <ul style="padding-left: 1.25rem; margin: 0;">
        <?php foreach ($errors as $err): ?>
          <li><?= e($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form action="<?= APP_URL ?>/add-product.php" method="POST" enctype="multipart/form-data" class="form-card">
    <?= csrfField() ?>

    <!-- SECTION: BASIC DETAILS (Fields 1-5 in exact order) -->
    <div class="form-section">
      <h2 class="form-section-title">General Information</h2>
      <p class="form-section-desc">Public title, public description, and private catalog identifier.</p>

      <!-- 1. Name -->
      <div class="form-group">
        <label for="name" class="form-label">Product Name</label>
        <input type="text" id="name" name="name" class="form-control" value="<?= e($data['name']) ?>" placeholder="e.g. Royal Blue Lawn Suit">
      </div>

      <div class="form-grid">
        <!-- 2. Design Number -->
        <div class="form-group">
          <label for="design_number" class="form-label">
            <span>Design Number</span>
          </label>
          <input type="text" id="design_number" name="design_number" class="form-control" value="<?= e($data['design_number']) ?>" placeholder="e.g. MG-8821">
        </div>

        <!-- 3. Retail Price (public) -->
        <div class="form-group">
          <label for="retail_price" class="form-label">Retail Price (<?= CURRENCY_SYMBOL ?>)</label>
          <input type="text" id="retail_price" name="retail_price" class="form-control" value="<?= e($data['retail_price']) ?>" placeholder="e.g. 1250">
        </div>

        <!-- 4. Fabric Price -->
        <div class="form-group">
          <label for="fabric_price" class="form-label">
            <span>Fabric Price (<?= CURRENCY_SYMBOL ?>)</span>
          </label>
          <input type="text" id="fabric_price" name="fabric_price" class="form-control" value="<?= e($data['fabric_price']) ?>" placeholder="e.g. 750">
        </div>
      </div>

      <!-- 5. Description (public) -->
      <div class="form-group" style="margin-top: 1rem;">
        <label for="description" class="form-label">Public Description</label>
        <textarea id="description" name="description" class="form-control" rows="3" placeholder="Provide general garment details (fabric quality, season, styling advice)..."><?= e($data['description']) ?></textarea>
      </div>
    </div>

    <!-- SECTION: FABRIC 1 (Field 6: Name, Details, Cost) -->
    <div class="form-section">
      <h2 class="form-section-title">Fabric 1</h2>
      <div class="form-grid">
        <div class="form-group">
          <label for="fabric1_name" class="form-label">Fabric 1 Name</label>
          <input type="text" id="fabric1_name" name="fabric1_name" class="form-control" value="<?= e($data['fabric1_name']) ?>" placeholder="e.g. Pure Cotton">
        </div>
        <div class="form-group">
          <label for="fabric1_cost" class="form-label">
            <span>Fabric 1 Cost (<?= CURRENCY_SYMBOL ?>)</span>
          </label>
          <input type="text" id="fabric1_cost" name="fabric1_cost" class="form-control" value="<?= e($data['fabric1_cost']) ?>" placeholder="e.g. 400">
        </div>
      </div>
      <div class="form-group" style="margin-top: 0.75rem;">
        <label for="fabric1_details" class="form-label">Fabric 1 Details</label>
        <textarea id="fabric1_details" name="fabric1_details" class="form-control" rows="2" placeholder="e.g. 68/68 breathable summer weave, pre-shrunk"><?= e($data['fabric1_details']) ?></textarea>
      </div>
    </div>

    <!-- SECTION: FABRIC 2 (Field 7: Name, Details, Cost) -->
    <div class="form-section">
      <h2 class="form-section-title">Fabric 2</h2>
      <div class="form-grid">
        <div class="form-group">
          <label for="fabric2_name" class="form-label">Fabric 2 Name</label>
          <input type="text" id="fabric2_name" name="fabric2_name" class="form-control" value="<?= e($data['fabric2_name']) ?>" placeholder="e.g. Chiffon Dupatta">
        </div>
        <div class="form-group">
          <label for="fabric2_cost" class="form-label">
            <span>Fabric 2 Cost (<?= CURRENCY_SYMBOL ?>)</span>
          </label>
          <input type="text" id="fabric2_cost" name="fabric2_cost" class="form-control" value="<?= e($data['fabric2_cost']) ?>" placeholder="e.g. 250">
        </div>
      </div>
      <div class="form-group" style="margin-top: 0.75rem;">
        <label for="fabric2_details" class="form-label">Fabric 2 Details</label>
        <textarea id="fabric2_details" name="fabric2_details" class="form-control" rows="2" placeholder="e.g. 2.5 meters printed chiffon dupatta"><?= e($data['fabric2_details']) ?></textarea>
      </div>
    </div>

    <!-- SECTION: MANUFACTURING COSTS (Fields 8-18 in exact order) -->
    <div class="form-section">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
        <h2 class="form-section-title" style="margin: 0;">Manufacturing Costs</h2>
      </div>
      <p class="form-section-desc">Manufacturing and labor cost breakdown.</p>

      <div class="form-grid">
        <!-- 8. Inner / Back Cost -->
        <div class="form-group">
          <label for="inner_back_cost" class="form-label">Inner / Back Cost</label>
          <input type="text" id="inner_back_cost" name="inner_back_cost" class="form-control" value="<?= e($data['inner_back_cost']) ?>" placeholder="0.00">
        </div>

        <!-- 9. Embroidery Cost -->
        <div class="form-group">
          <label for="embroidery_cost" class="form-label">Embroidery Cost</label>
          <input type="text" id="embroidery_cost" name="embroidery_cost" class="form-control" value="<?= e($data['embroidery_cost']) ?>" placeholder="0.00">
        </div>

        <!-- 10. Neck Rate -->
        <div class="form-group">
          <label for="neck_rate" class="form-label">Neck Rate</label>
          <input type="text" id="neck_rate" name="neck_rate" class="form-control" value="<?= e($data['neck_rate']) ?>" placeholder="0.00">
        </div>

        <!-- 11. Cutwork Cost -->
        <div class="form-group">
          <label for="cutwork_cost" class="form-label">Cutwork Cost</label>
          <input type="text" id="cutwork_cost" name="cutwork_cost" class="form-control" value="<?= e($data['cutwork_cost']) ?>" placeholder="0.00">
        </div>

        <!-- 12. Cutting Cost -->
        <div class="form-group">
          <label for="cutting_cost" class="form-label">Cutting Cost</label>
          <input type="text" id="cutting_cost" name="cutting_cost" class="form-control" value="<?= e($data['cutting_cost']) ?>" placeholder="0.00">
        </div>

        <!-- 13. Stitching Cost -->
        <div class="form-group">
          <label for="stitching_cost" class="form-label">Stitching Cost</label>
          <input type="text" id="stitching_cost" name="stitching_cost" class="form-control" value="<?= e($data['stitching_cost']) ?>" placeholder="0.00">
        </div>

        <!-- 14. Press Cost -->
        <div class="form-group">
          <label for="press_cost" class="form-label">Press Cost</label>
          <input type="text" id="press_cost" name="press_cost" class="form-control" value="<?= e($data['press_cost']) ?>" placeholder="0.00">
        </div>

        <!-- 15. Elastic Cost -->
        <div class="form-group">
          <label for="elastic_cost" class="form-label">Elastic Cost</label>
          <input type="text" id="elastic_cost" name="elastic_cost" class="form-control" value="<?= e($data['elastic_cost']) ?>" placeholder="0.00">
        </div>

        <!-- 16. Thread Cost -->
        <div class="form-group">
          <label for="thread_cost" class="form-label">Thread Cost</label>
          <input type="text" id="thread_cost" name="thread_cost" class="form-control" value="<?= e($data['thread_cost']) ?>" placeholder="0.00">
        </div>

        <!-- 17. Poly Bag Cost -->
        <div class="form-group">
          <label for="poly_bag_cost" class="form-label">Poly Bag Cost</label>
          <input type="text" id="poly_bag_cost" name="poly_bag_cost" class="form-control" value="<?= e($data['poly_bag_cost']) ?>" placeholder="0.00">
        </div>

        <!-- 18. Pico Cost -->
        <div class="form-group">
          <label for="pico_cost" class="form-label">Pico Cost</label>
          <input type="text" id="pico_cost" name="pico_cost" class="form-control" value="<?= e($data['pico_cost']) ?>" placeholder="0.00">
        </div>
      </div>
    </div>

    <!-- SECTION: OTHER DETAILS (Field 19) -->
    <div class="form-section">
      <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
        <h2 class="form-section-title" style="margin: 0;">19. Other Details</h2>
      </div>
      <div class="form-group">
        <label for="private_details" class="form-label">Internal Notes / Specifications</label>
        <textarea id="private_details" name="private_details" class="form-control" rows="4" placeholder="Production instructions, vendor notes, warehouse location..."><?= e($data['private_details']) ?></textarea>
      </div>
    </div>

    <!-- SECTION: IMAGES & STATUS (Fields 20 & 21) -->
    <div class="form-section">
      <h2 class="form-section-title">20. Product Images & Gallery</h2>
      <p class="form-section-desc">Upload JPEG, PNG, or WebP images up to 8 MB each.</p>

      <div class="form-group">
        <label for="main_image_input" class="form-label">Main Product Image</label>
        <div class="form-file-box" onclick="document.getElementById('main_image_input').click();">
          <input type="file" id="main_image_input" name="main_image" accept="image/jpeg,image/png,image/webp" style="display: none;">
          <p style="margin: 0; color: var(--color-blue); font-weight: 600;">Click to select main image</p>
          <p style="margin: 4px 0 0; font-size: 0.82rem; color: var(--color-text-muted);">Recommended aspect ratio: 4:5</p>
        </div>
        <div id="main_image_preview" class="file-preview-grid"></div>
      </div>

      <div class="form-group" style="margin-top: 1.5rem;">
        <label for="additional_images_input" class="form-label">Additional Product Images (Multiple)</label>
        <div class="form-file-box" onclick="document.getElementById('additional_images_input').click();">
          <input type="file" id="additional_images_input" name="additional_images[]" multiple accept="image/jpeg,image/png,image/webp" style="display: none;">
          <p style="margin: 0; color: var(--color-blue); font-weight: 600;">Click to select additional gallery images</p>
          <p style="margin: 4px 0 0; font-size: 0.82rem; color: var(--color-text-muted);">You can select multiple photos at once</p>
        </div>
        <div id="additional_images_preview" class="file-preview-grid"></div>
      </div>

      <!-- 21. Status: Draft / Published (default Draft) -->
      <div class="form-group" style="margin-top: 1.5rem;">
        <label for="status" class="form-label">21. Product Status</label>
        <select id="status" name="status" class="form-select">
          <option value="draft" <?= ($data['status'] === 'draft') ? 'selected' : '' ?>>Draft (Hidden from public catalog)</option>
          <option value="published" <?= ($data['status'] === 'published') ? 'selected' : '' ?>>Published (Visible to all catalog visitors)</option>
        </select>
      </div>
    </div>

    <!-- Form Action Buttons -->
    <div style="display: flex; gap: 12px; margin-top: 1.5rem; justify-content: flex-end;">
      <a href="<?= APP_URL ?>/admin/products.php" class="btn btn-gray">
        <?= icon('close') ?>
        <span>Cancel</span>
      </a>
      <button type="submit" class="btn btn-blue">
        <?= icon('check') ?>
        <span>Save Product</span>
      </button>
    </div>

  </form>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
