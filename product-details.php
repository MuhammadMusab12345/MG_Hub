<?php
/**
 * Mehtab Garments — Product Details Page (Round 3)
 * Fully public read-only view of product & manufacturing details.
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
$stmt = $db->prepare('SELECT * FROM products WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    abort404();
}

// Access rule: Only admins can preview drafts; all public visitors require published status
if ($product['status'] !== 'published' && !isAdmin()) {
    abort404();
}

// Fetch additional gallery images
$imgStmt = $db->prepare('
    SELECT image_path 
    FROM product_images 
    WHERE product_id = :id 
    ORDER BY sort_order ASC, id ASC
');
$imgStmt->execute([':id' => $productId]);
$additionalImages = $imgStmt->fetchAll(PDO::FETCH_COLUMN);

// Compile all gallery images
$galleryImages = [];
if (!empty($product['main_image_path']) && file_exists(ROOT_PATH . '/' . $product['main_image_path'])) {
    $galleryImages[] = $product['main_image_path'];
}
foreach ($additionalImages as $addPath) {
    if (!empty($addPath) && file_exists(ROOT_PATH . '/' . $addPath)) {
        $galleryImages[] = $addPath;
    }
}

$hasFabric1 = !empty(trim((string)($product['fabric1_name'] ?? '')));
$hasFabric2 = !empty(trim((string)($product['fabric2_name'] ?? '')));
$hasFabrics = $hasFabric1 || $hasFabric2;
$panelId = 'details-fabric-panel';
$shareUrl = getShareUrl($product['share_token']);

$itemLabel = !empty($product['name']) ? $product['name'] : 'product #' . $product['id'];
$detailsWaMessage = "Hello Mehtab Garments, I am interested in {$itemLabel}. {$shareUrl}";

// Manufacturing & Production details array (only populated fields shown)
$manufacturingFields = [];
if (!empty(trim((string)($product['design_number'] ?? '')))) {
    $manufacturingFields['Design Number'] = $product['design_number'];
}
if ($product['fabric_price'] !== null && $product['fabric_price'] !== '') {
    $manufacturingFields['Fabric Price'] = formatPrice($product['fabric_price']);
}
if ($product['fabric1_cost'] !== null && $product['fabric1_cost'] !== '') {
    $manufacturingFields['Fabric 1 Cost'] = formatPrice($product['fabric1_cost']);
}
if ($product['fabric2_cost'] !== null && $product['fabric2_cost'] !== '') {
    $manufacturingFields['Fabric 2 Cost'] = formatPrice($product['fabric2_cost']);
}
if ($product['inner_back_cost'] !== null && $product['inner_back_cost'] !== '') {
    $manufacturingFields['Inner / Back Cost'] = formatPrice($product['inner_back_cost']);
}
if ($product['embroidery_cost'] !== null && $product['embroidery_cost'] !== '') {
    $manufacturingFields['Embroidery Cost'] = formatPrice($product['embroidery_cost']);
}
if ($product['neck_rate'] !== null && $product['neck_rate'] !== '') {
    $manufacturingFields['Neck Rate'] = formatPrice($product['neck_rate']);
}
if ($product['cutwork_cost'] !== null && $product['cutwork_cost'] !== '') {
    $manufacturingFields['Cutwork Cost'] = formatPrice($product['cutwork_cost']);
}
if ($product['cutting_cost'] !== null && $product['cutting_cost'] !== '') {
    $manufacturingFields['Cutting Cost'] = formatPrice($product['cutting_cost']);
}
if ($product['stitching_cost'] !== null && $product['stitching_cost'] !== '') {
    $manufacturingFields['Stitching Cost'] = formatPrice($product['stitching_cost']);
}
if ($product['press_cost'] !== null && $product['press_cost'] !== '') {
    $manufacturingFields['Press Cost'] = formatPrice($product['press_cost']);
}
if ($product['elastic_cost'] !== null && $product['elastic_cost'] !== '') {
    $manufacturingFields['Elastic Cost'] = formatPrice($product['elastic_cost']);
}
if ($product['thread_cost'] !== null && $product['thread_cost'] !== '') {
    $manufacturingFields['Thread Cost'] = formatPrice($product['thread_cost']);
}
if ($product['poly_bag_cost'] !== null && $product['poly_bag_cost'] !== '') {
    $manufacturingFields['Poly Bag Cost'] = formatPrice($product['poly_bag_cost']);
}
if ($product['pico_cost'] !== null && $product['pico_cost'] !== '') {
    $manufacturingFields['Pico Cost'] = formatPrice($product['pico_cost']);
}

$hasOtherDetails = !empty(trim((string)($product['private_details'] ?? '')));

$pageTitle = !empty($product['name']) ? $product['name'] : 'Product Details';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top: 1.25rem;">
  
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 10px;">
    <a href="<?= APP_URL ?>/index.php" class="btn btn-secondary btn-sm">
      <?= icon('tag') ?>
      <span>&larr; Back to Catalog</span>
    </a>
    <?php if (isAdmin()): ?>
      <div style="display: flex; gap: 8px; align-items: center;">
        <span class="badge <?= ($product['status'] === 'published') ? 'badge-published' : 'badge-draft' ?>">
          <?= ($product['status'] === 'published') ? icon('publish') : icon('draft') ?>
          <span><?= e(ucfirst($product['status'])) ?></span>
        </span>
        <a href="<?= APP_URL ?>/edit-product.php?id=<?= (int)$product['id'] ?>" class="btn btn-blue btn-sm">
          <?= icon('edit') ?>
          <span>Edit Product</span>
        </a>
      </div>
    <?php endif; ?>
  </div>

  <div class="details-layout">
    
    <!-- LEFT: Image Gallery with Magnifier Zoom -->
    <div class="gallery-container">
      <div class="gallery-main-frame" title="Hover to zoom">
        <?php if (!empty($galleryImages)): ?>
          <img 
            id="details-main-img" 
            src="<?= APP_URL . '/' . e($galleryImages[0]) ?>" 
            alt="<?= !empty($product['name']) ? e($product['name']) : 'Garment Image' ?>"
          >
        <?php else: ?>
          <div class="card-img-placeholder" style="height: 100%;">
            <?= icon('image') ?>
            <span>No Image Available</span>
          </div>
        <?php endif; ?>
      </div>

      <!-- Thumbnails Switcher -->
      <?php if (count($galleryImages) > 1): ?>
        <div class="gallery-thumbs">
          <?php foreach ($galleryImages as $index => $imgPath): ?>
            <button 
              type="button" 
              class="gallery-thumb-btn <?= ($index === 0) ? 'active' : '' ?>" 
              onclick="document.getElementById('details-main-img').src='<?= APP_URL . '/' . e($imgPath) ?>'; document.querySelectorAll('.gallery-thumb-btn').forEach(b => b.classList.remove('active')); this.classList.add('active');"
              aria-label="View image <?= $index + 1 ?>"
            >
              <img src="<?= APP_URL . '/' . e($imgPath) ?>" alt="Thumbnail <?= $index + 1 ?>">
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- RIGHT: Product Details Info -->
    <div>
      <div class="details-info-card">
        
        <!-- Name (if exists) -->
        <?php if (!empty(trim((string)($product['name'] ?? '')))): ?>
          <h1 class="details-title"><?= e($product['name']) ?></h1>
        <?php endif; ?>

        <!-- Retail Price Badge (if exists) -->
        <?php if ($product['retail_price'] !== null && $product['retail_price'] !== ''): ?>
          <div class="details-price-badge">
            <?= icon('tag') ?>
            <span><?= formatPrice($product['retail_price']) ?></span>
          </div>
        <?php endif; ?>

        <!-- Fabric Dropdown (if at least one fabric has data) -->
        <?php if ($hasFabrics): 
          $initialName = $hasFabric1 ? $product['fabric1_name'] : $product['fabric2_name'];
          $initialDetails = $hasFabric1 ? ($product['fabric1_details'] ?? '') : ($product['fabric2_details'] ?? '');
          $initialCost = $hasFabric1 
            ? (($product['fabric1_cost'] !== null && $product['fabric1_cost'] !== '') ? formatPrice($product['fabric1_cost']) : '') 
            : (($product['fabric2_cost'] !== null && $product['fabric2_cost'] !== '') ? formatPrice($product['fabric2_cost']) : '');
        ?>
          <div class="card-fabric-section" style="margin-bottom: 1.5rem;">
            <label for="fabric_selector" class="details-section-label">
              <?= icon('fabric') ?>
              <span>Fabric Options</span>
            </label>
            <div class="fabric-select-wrap">
              <span class="fabric-select-icon" aria-hidden="true"><?= icon('fabric') ?></span>
              <select 
                id="fabric_selector"
                class="fabric-select js-fabric-select" 
                data-target-panel="<?= e($panelId) ?>"
                aria-label="Fabric options"
              >
                <?php if ($hasFabric1): 
                  $f1CostFormatted = ($product['fabric1_cost'] !== null && $product['fabric1_cost'] !== '') ? formatPrice($product['fabric1_cost']) : '';
                ?>
                  <option 
                    value="1" 
                    data-name="<?= e($product['fabric1_name']) ?>" 
                    data-details="<?= e($product['fabric1_details'] ?? '') ?>"
                    data-cost="<?= e($f1CostFormatted) ?>"
                  >
                    <?= e($product['fabric1_name']) ?> (Fabric 1)
                  </option>
                <?php endif; ?>
                <?php if ($hasFabric2): 
                  $f2CostFormatted = ($product['fabric2_cost'] !== null && $product['fabric2_cost'] !== '') ? formatPrice($product['fabric2_cost']) : '';
                ?>
                  <option 
                    value="2" 
                    data-name="<?= e($product['fabric2_name']) ?>" 
                    data-details="<?= e($product['fabric2_details'] ?? '') ?>"
                    data-cost="<?= e($f2CostFormatted) ?>"
                    <?= (!$hasFabric1) ? 'selected' : '' ?>
                  >
                    <?= e($product['fabric2_name']) ?> (Fabric 2)
                  </option>
                <?php endif; ?>
              </select>
            </div>

            <div id="<?= e($panelId) ?>" class="fabric-details-panel">
              <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3px;">
                <span class="fabric-badge-name" style="margin: 0;"><?= e($initialName) ?></span>
                <span class="fabric-cost badge" style="background: #DBEAFE; color: var(--color-blue-deep); font-size: 0.78rem; <?= empty($initialCost) ? 'display:none;' : '' ?>">
                  <?= e($initialCost) ?>
                </span>
              </div>
              <p class="fabric-desc" style="<?= empty(trim((string)$initialDetails)) ? 'display:none;' : '' ?>">
                <?= e($initialDetails) ?>
              </p>
            </div>
          </div>
        <?php endif; ?>

        <!-- Public Description (if exists) -->
        <?php if (!empty(trim((string)($product['description'] ?? '')))): ?>
          <div class="details-section-box">
            <div class="details-section-label">
              <?= icon('sparkle') ?>
              <span>Product Description</span>
            </div>
            <div class="details-text"><?= e($product['description']) ?></div>
          </div>
        <?php endif; ?>

        <!-- Action Buttons: Download (RED), Copy Link (GRAY), WhatsApp (GREEN) -->
        <div class="details-actions-cluster">
          
          <!-- Download (RED 3D) -->
          <?php if (!empty($product['main_image_path']) && file_exists(ROOT_PATH . '/' . $product['main_image_path'])): ?>
            <a href="<?= APP_URL ?>/download.php?id=<?= (int)$product['id'] ?>" class="btn btn-red btn-download" title="Download Image">
              <?= icon('download') ?>
              <span>Download Image</span>
            </a>
          <?php else: ?>
            <button type="button" class="btn btn-red btn-download" disabled title="No image available">
              <?= icon('download') ?>
              <span>Download Image</span>
            </button>
          <?php endif; ?>

          <!-- Copy Link (GRAY 3D) -->
          <button 
            type="button" 
            class="btn btn-gray btn-secondary js-copy-link" 
            data-url="<?= e($shareUrl) ?>" 
            title="Copy shareable link"
          >
            <?= icon('link') ?>
            <span>Copy Link</span>
          </button>

          <!-- WhatsApp (GREEN 3D) -->
          <a 
            href="<?= whatsapp_link($detailsWaMessage) ?>" 
            target="_blank" 
            rel="noopener noreferrer" 
            class="btn btn-green btn-whatsapp"
            title="Order or ask on WhatsApp"
          >
            <?= icon('whatsapp') ?>
            <span>Order on WhatsApp</span>
          </a>

        </div>

      </div>

      <!-- MANUFACTURING & PRODUCTION DETAILS (Visible to all visitors, strictly read-only) -->
      <?php if (!empty($manufacturingFields) || $hasOtherDetails): ?>
        <div class="manufacturing-panel private-panel" style="margin-top: 1.5rem;">
          <div class="manufacturing-panel-header private-panel-header">
            <h2 style="font-size: 1.25rem; margin: 0; color: var(--color-black); display: flex; align-items: center; gap: 8px;">
              <?= icon('fabric') ?>
              <span>Manufacturing Details</span>
            </h2>
          </div>

          <?php if (!empty($manufacturingFields)): ?>
            <div class="manufacturing-costs-grid private-costs-grid">
              <?php foreach ($manufacturingFields as $label => $val): ?>
                <div class="manufacturing-cost-item private-cost-item">
                  <span class="manufacturing-cost-label private-cost-label"><?= e($label) ?></span>
                  <span class="manufacturing-cost-val private-cost-val"><?= e($val) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if ($hasOtherDetails): ?>
            <div style="margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--color-border);">
              <span class="manufacturing-cost-label private-cost-label" style="margin-bottom: 6px; font-weight: 700; color: var(--color-text-muted);">Other Details & Specifications</span>
              <div class="details-text" style="color: var(--color-black);"><?= e($product['private_details']) ?></div>
            </div>
          <?php endif; ?>

        </div>
      <?php endif; ?>

    </div>

  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
