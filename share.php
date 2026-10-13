<?php
/**
 * Mehtab Garments — Complete Standalone Read-Only Product Share Page (Round 3)
 * Full public read-only view of product & manufacturing details.
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$token = trim($_GET['t'] ?? '');
if ($token === '' || strlen($token) !== 32 || !ctype_xdigit($token)) {
    abort404();
}

$db = getDb();
// STRICT RULE: Only PUBLISHED products can be opened via share link.
$stmt = $db->prepare('
    SELECT *
    FROM products
    WHERE share_token = :token AND status = \'published\'
    LIMIT 1
');
$stmt->execute([':token' => $token]);
$product = $stmt->fetch();

if (!$product) {
    abort404();
}

// Fetch additional gallery images
$imgStmt = $db->prepare('
    SELECT image_path 
    FROM product_images 
    WHERE product_id = :id 
    ORDER BY sort_order ASC, id ASC
');
$imgStmt->execute([':id' => $product['id']]);
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
$panelId = 'share-fabric-panel';
$shareUrl = getShareUrl($product['share_token']);

$itemLabel = !empty($product['name']) ? $product['name'] : 'product #' . $product['id'];
$shareWaMessage = "Hello Mehtab Garments, I am interested in {$itemLabel}. {$shareUrl}";

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= !empty($product['name']) ? e($product['name']) . ' — Product' : 'Product' ?></title>
  <!-- Disallow search indexation for share page -->
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="<?= asset_url('assets/css/style.css') ?>">
</head>
<body class="share-page-container">

  <?php renderIconSprite(); ?>

  <div class="share-card-wrapper" style="width: 100%; max-width: 640px; margin: 0 auto;">
    <!-- Standalone Read-Only Product Card: No navbar, no footer, no owner data, no links to site -->
    <article class="product-card" style="box-shadow: var(--shadow-xl); padding: clamp(1.25rem, 3vw, 2rem); border-radius: var(--radius-card);">
      
      <!-- 1. Product Name at top (if exists) -->
      <?php if (!empty(trim((string)($product['name'] ?? '')))): ?>
        <h1 class="card-title" style="font-size: 1.6rem; margin-bottom: 14px; white-space: normal; line-height: 1.3;">
          <?= e($product['name']) ?>
        </h1>
      <?php endif; ?>

      <!-- 2. Product Image Gallery (Main Image 4:5 + Additional Thumbnails + Hover Zoom) -->
      <div class="gallery-container" style="margin-bottom: 18px;">
        <div class="gallery-main-frame" title="Hover to zoom">
          <?php if (!empty($galleryImages)): ?>
            <img 
              id="share-main-img" 
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

        <?php if (count($galleryImages) > 1): ?>
          <div class="gallery-thumbs" style="gap: 8px;">
            <?php foreach ($galleryImages as $index => $imgPath): ?>
              <button 
                type="button" 
                class="gallery-thumb-btn <?= ($index === 0) ? 'active' : '' ?>" 
                onclick="document.getElementById('share-main-img').src='<?= APP_URL . '/' . e($imgPath) ?>'; document.querySelectorAll('.gallery-thumb-btn').forEach(b => b.classList.remove('active')); this.classList.add('active');"
                aria-label="View image <?= $index + 1 ?>"
                style="width: 64px; height: 64px;"
              >
                <img src="<?= APP_URL . '/' . e($imgPath) ?>" alt="Thumbnail <?= $index + 1 ?>">
              </button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- 3. RETAIL PRICE pill/badge (if exists) -->
      <?php if ($product['retail_price'] !== null && $product['retail_price'] !== ''): ?>
        <div class="card-price-badge" style="font-size: 1.4rem; margin-bottom: 16px;">
          <?= icon('tag') ?>
          <span><?= formatPrice($product['retail_price']) ?></span>
        </div>
      <?php endif; ?>

      <!-- 4. FABRIC SELECTOR (Shown only if at least one fabric has data) -->
      <?php if ($hasFabrics): 
        $initialName = $hasFabric1 ? $product['fabric1_name'] : $product['fabric2_name'];
        $initialDetails = $hasFabric1 ? ($product['fabric1_details'] ?? '') : ($product['fabric2_details'] ?? '');
        $initialCost = $hasFabric1 
          ? (($product['fabric1_cost'] !== null && $product['fabric1_cost'] !== '') ? formatPrice($product['fabric1_cost']) : '') 
          : (($product['fabric2_cost'] !== null && $product['fabric2_cost'] !== '') ? formatPrice($product['fabric2_cost']) : '');
      ?>
        <div class="card-fabric-section" style="margin-bottom: 18px;">
          <label for="share_fabric_select" class="details-section-label">
            <?= icon('fabric') ?>
            <span>Fabric Details</span>
          </label>
          <div class="fabric-select-wrap">
            <span class="fabric-select-icon" aria-hidden="true"><?= icon('fabric') ?></span>
            <select 
              id="share_fabric_select"
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

      <!-- 5. Full Public Description (Not Clamped) -->
      <?php if (!empty(trim((string)($product['description'] ?? '')))): ?>
        <div style="margin-bottom: 20px; padding: 14px 0; border-top: 1px solid var(--color-border); border-bottom: 1px solid var(--color-border);">
          <div class="details-section-label">
            <?= icon('sparkle') ?>
            <span>Description</span>
          </div>
          <div class="details-text" style="white-space: pre-line; color: #334155; font-size: 0.94rem;">
            <?= e($product['description']) ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- 6. Manufacturing & Production Details (Fully visible, clean responsive grid) -->
      <?php if (!empty($manufacturingFields) || $hasOtherDetails): ?>
        <div class="manufacturing-panel private-panel" style="margin-bottom: 20px; background: #F8FAFC; border: 1px solid var(--color-border); border-radius: var(--radius-md); padding: 16px;">
          <div class="manufacturing-panel-header private-panel-header" style="margin-bottom: 12px; padding-bottom: 8px; border-bottom: 1px solid var(--color-border);">
            <h2 style="font-size: 1.15rem; margin: 0; color: var(--color-black); display: flex; align-items: center; gap: 8px;">
              <?= icon('fabric') ?>
              <span>Manufacturing Details</span>
            </h2>
          </div>

          <?php if (!empty($manufacturingFields)): ?>
            <div class="manufacturing-costs-grid private-costs-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px;">
              <?php foreach ($manufacturingFields as $label => $val): ?>
                <div class="manufacturing-cost-item private-cost-item" style="background: #FFF; padding: 8px 10px; border-radius: var(--radius-sm); border: 1px solid var(--color-border);">
                  <span class="manufacturing-cost-label private-cost-label" style="font-size: 0.75rem; color: var(--color-text-muted); display: block;"><?= e($label) ?></span>
                  <span class="manufacturing-cost-val private-cost-val" style="font-size: 0.95rem; font-weight: 700; color: var(--color-black);"><?= e($val) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php if ($hasOtherDetails): ?>
            <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid var(--color-border);">
              <span class="manufacturing-cost-label private-cost-label" style="font-size: 0.75rem; font-weight: 700; color: var(--color-text-muted); display: block; margin-bottom: 4px;">Other Details</span>
              <div class="details-text" style="color: var(--color-black); font-size: 0.88rem;"><?= e($product['private_details']) ?></div>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- 7. Action Buttons: Download (RED), Copy Link (GRAY), WhatsApp (GREEN) -->
      <div style="display: flex; flex-direction: column; gap: 10px; margin-top: auto; padding-top: 6px;">
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
          <!-- Download (RED 3D) -->
          <?php if (!empty($product['main_image_path']) && file_exists(ROOT_PATH . '/' . $product['main_image_path'])): ?>
            <a href="<?= APP_URL ?>/download.php?id=<?= (int)$product['id'] ?>" class="btn btn-red btn-download" title="Download Image">
              <?= icon('download') ?>
              <span>Download</span>
            </a>
          <?php else: ?>
            <button type="button" class="btn btn-red btn-download" disabled>
              <?= icon('download') ?>
              <span>Download</span>
            </button>
          <?php endif; ?>

          <!-- Copy Link (GRAY 3D) -->
          <button 
            type="button" 
            class="btn btn-gray btn-secondary js-copy-link" 
            data-url="<?= e($shareUrl) ?>" 
            title="Copy Share Link"
          >
            <?= icon('link') ?>
            <span>Copy Link</span>
          </button>
        </div>

        <!-- WhatsApp (GREEN 3D) -->
        <a 
          href="<?= whatsapp_link($shareWaMessage) ?>" 
          target="_blank" 
          rel="noopener noreferrer" 
          class="btn btn-green btn-whatsapp"
          style="width: 100%;"
          title="Order or ask on WhatsApp"
        >
          <?= icon('whatsapp') ?>
          <span>Order on WhatsApp</span>
        </a>

      </div>

    </article>
  </div>

  <script src="<?= asset_url('assets/js/app.js') ?>"></script>
</body>
</html>
