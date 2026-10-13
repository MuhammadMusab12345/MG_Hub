<?php
/**
 * Mehtab Garments — Public Product Catalog (Round 2)
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

$db = getDb();

// Search, Fabric Filter, and Sort parameters
$searchQuery  = trim($_GET['q'] ?? '');
$fabricFilter = trim($_GET['fabric'] ?? '');
$sortOption   = trim($_GET['sort'] ?? 'newest');

// Fetch distinct fabric names for the toolbar dropdown
$fabricStmt = $db->query("
    SELECT DISTINCT fabric_name 
    FROM (
        SELECT fabric1_name AS fabric_name FROM products WHERE status = 'published' AND fabric1_name IS NOT NULL AND TRIM(fabric1_name) != ''
        UNION
        SELECT fabric2_name AS fabric_name FROM products WHERE status = 'published' AND fabric2_name IS NOT NULL AND TRIM(fabric2_name) != ''
    ) AS distinct_fabrics 
    ORDER BY fabric_name ASC
");
$availableFabrics = $fabricStmt->fetchAll(PDO::FETCH_COLUMN);

// Build catalog query
$whereClauses = ["status = 'published'"];
$params = [];

if ($searchQuery !== '') {
    $whereClauses[] = 'name LIKE :search';
    $params[':search'] = '%' . $searchQuery . '%';
}

if ($fabricFilter !== '') {
    $whereClauses[] = '(fabric1_name = :fabric1 OR fabric2_name = :fabric2)';
    $params[':fabric1'] = $fabricFilter;
    $params[':fabric2'] = $fabricFilter;
}

$orderBy = 'created_at DESC';
if ($sortOption === 'price_asc') {
    $orderBy = '(retail_price IS NULL), retail_price ASC, created_at DESC';
} elseif ($sortOption === 'price_desc') {
    $orderBy = '(retail_price IS NULL), retail_price DESC, created_at DESC';
}

$whereSql = implode(' AND ', $whereClauses);
$sql = "
    SELECT id, share_token, name, retail_price, description, main_image_path,
           fabric1_name, fabric1_details, fabric2_name, fabric2_details
    FROM products
    WHERE {$whereSql}
    ORDER BY {$orderBy}
";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$pageTitle = 'Product Catalog';
require_once __DIR__ . '/includes/header.php';
?>

<!-- 2. Compact Hero Strip with Soft Animated Blue Glow Orb -->
<div class="hero-strip">
  <div class="hero-glow-orb" aria-hidden="true"></div>
  <div class="container hero-content">
    <h1 class="hero-title">
      <span>Mehtab Garments</span>
      <?= icon('sparkle') ?>
    </h1>
    <p class="hero-subtitle">Curated catalog of premium stitched and unstitched collections.</p>
  </div>
</div>

<div class="container">
  <!-- 3. One Neatly Aligned Toolbar Row -->
  <div class="catalog-toolbar-wrapper">
    <form id="catalog-toolbar-form" action="<?= APP_URL ?>/index.php" method="GET" class="catalog-toolbar">
      
      <!-- "+ New" button at the left (Admins only, Blue 3D) -->
      <?php if (isAdmin()): ?>
        <a href="<?= APP_URL ?>/add-product.php" class="btn btn-blue toolbar-btn toolbar-btn-new">
          <?= icon('plus') ?>
          <span> New</span>
        </a>
      <?php endif; ?>

      <!-- Search field with embedded icon -->
      <div class="toolbar-search-wrap">
        <span class="toolbar-field-icon toolbar-search-icon" aria-hidden="true"><?= icon('search') ?></span>
        <input 
          type="text" 
          name="q" 
          class="toolbar-input toolbar-search-input" 
          placeholder="Search by product name..." 
          value="<?= e($searchQuery) ?>"
          aria-label="Search by product name"
        >
      </div>

      <!-- Tablet Flex Break Helper (splits to Row 2 on 768px-1023px) -->
      <div class="toolbar-break" aria-hidden="true"></div>

      <!-- Fabric Filter Dropdown with icon -->
      <div class="toolbar-select-wrap">
        <span class="toolbar-field-icon" aria-hidden="true"><?= icon('fabric') ?></span>
        <select name="fabric" class="toolbar-select" aria-label="Filter by fabric">
          <option value="">All Fabrics</option>
          <?php foreach ($availableFabrics as $fab): ?>
            <option value="<?= e($fab) ?>" <?= ($fabricFilter === $fab) ? 'selected' : '' ?>>
              <?= e($fab) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Sort Dropdown with icon -->
      <div class="toolbar-select-wrap">
        <span class="toolbar-field-icon" aria-hidden="true"><?= icon('sort') ?></span>
        <select name="sort" class="toolbar-select" aria-label="Sort products">
          <option value="newest" <?= ($sortOption === 'newest') ? 'selected' : '' ?>>Newest First</option>
          <option value="price_asc" <?= ($sortOption === 'price_asc') ? 'selected' : '' ?>>Price: Low to High</option>
          <option value="price_desc" <?= ($sortOption === 'price_desc') ? 'selected' : '' ?>>Price: High to Low</option>
        </select>
      </div>

      <!-- Search Button (Blue 3D) -->
      <button type="submit" class="btn btn-blue toolbar-btn toolbar-btn-search" title="Search">
        <?= icon('search') ?>
        <span>Search</span>
      </button>

      <!-- Clear/Reset Button (Gray 3D, if filters active) -->
      <?php if ($searchQuery !== '' || $fabricFilter !== ''): ?>
        <a href="<?= APP_URL ?>/index.php" class="btn btn-gray toolbar-btn toolbar-btn-clear" title="Reset all filters">
          <?= icon('close') ?>
          <span>Clear</span>
        </a>
      <?php endif; ?>

    </form>
  </div>

  <!-- 4. Responsive Product Grid -->
  <?php if (empty($products)): ?>
    <div class="empty-state">
      <div class="empty-state-icon" aria-hidden="true">
        <?= icon('search') ?>
      </div>
      <h2 class="empty-state-title">No products found</h2>
      <p class="empty-state-desc">
        <?php if ($searchQuery !== '' || $fabricFilter !== ''): ?>
          No published garments match your current search and filter criteria.
        <?php else: ?>
          There are currently no published products available in the catalog.
        <?php endif; ?>
      </p>
      <?php if ($searchQuery !== '' || $fabricFilter !== ''): ?>
        <a href="<?= APP_URL ?>/index.php" class="btn btn-blue">
          <?= icon('close') ?>
          <span>Reset Filters</span>
        </a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="product-grid">
      <?php foreach ($products as $idx => $product): 
        $hasFabric1 = !empty(trim((string)($product['fabric1_name'] ?? '')));
        $hasFabric2 = !empty(trim((string)($product['fabric2_name'] ?? '')));
        $hasFabrics = $hasFabric1 || $hasFabric2;
        $panelId = 'fabric-panel-' . (int)$product['id'] . '-' . $idx;
        $shareUrl = getShareUrl($product['share_token']);
        
        $itemLabel = !empty($product['name']) ? $product['name'] : 'product #' . $product['id'];
        $cardWaMessage = "Hello Mehtab Garments, I am interested in {$itemLabel}. {$shareUrl}";
      ?>
        <!-- REDESIGNED PRODUCT CARD (PREMIUM & DYNAMIC) -->
        <article class="product-card">
          
          <!-- 1. Product NAME at the top of the card (if it exists) -->
          <?php if (!empty(trim((string)($product['name'] ?? '')))): ?>
            <div class="card-header-title">
              <h2 class="card-title">
                <a href="<?= APP_URL ?>/product-details.php?id=<?= (int)$product['id'] ?>">
                  <?= e($product['name']) ?>
                </a>
              </h2>
            </div>
          <?php else: ?>
            <div style="height: 10px;"></div>
          <?php endif; ?>

          <!-- 2. Product image (main image), 4:5 aspect ratio, hover zoom, overlay with eye icon, shine sweep -->
          <div class="card-media">
            <?php if (!empty($product['main_image_path']) && file_exists(ROOT_PATH . '/' . $product['main_image_path'])): ?>
              <img 
                src="<?= APP_URL . '/' . e($product['main_image_path']) ?>" 
                alt="<?= !empty($product['name']) ? e($product['name']) : 'Product Image' ?>" 
                class="card-img" 
                loading="lazy"
              >
              <!-- Soft dark-blue overlay with centered translucent eye icon button -->
              <a 
                href="<?= APP_URL ?>/product-details.php?id=<?= (int)$product['id'] ?>" 
                class="card-media-overlay" 
                aria-label="View product details"
              >
                <div class="overlay-eye-btn" aria-hidden="true">
                  <?= icon('eye') ?>
                </div>
              </a>
            <?php else: ?>
              <div class="card-img-placeholder">
                <?= icon('image') ?>
                <span>No Image Available</span>
              </div>
            <?php endif; ?>
          </div>

          <!-- 3. RETAIL PRICE pill/badge (if it exists) -->
          <?php if ($product['retail_price'] !== null && $product['retail_price'] !== ''): ?>
            <div class="card-price-badge">
              <?= icon('tag') ?>
              <span><?= formatPrice($product['retail_price']) ?></span>
            </div>
          <?php endif; ?>

          <!-- 4. FABRIC DROPDOWN (Shown only if at least one fabric has data) -->
          <?php if ($hasFabrics): 
            $initialName = $hasFabric1 ? $product['fabric1_name'] : $product['fabric2_name'];
            $initialDetails = $hasFabric1 ? ($product['fabric1_details'] ?? '') : ($product['fabric2_details'] ?? '');
          ?>
            <div class="card-fabric-section">
              <div class="fabric-select-wrap">
                <span class="fabric-select-icon" aria-hidden="true"><?= icon('fabric') ?></span>
                <select 
                  class="fabric-select js-fabric-select" 
                  data-target-panel="<?= e($panelId) ?>"
                  aria-label="Fabric options"
                >
                  <?php if ($hasFabric1): ?>
                    <option 
                      value="1" 
                      data-name="<?= e($product['fabric1_name']) ?>" 
                      data-details="<?= e($product['fabric1_details'] ?? '') ?>"
                    >
                      <?= e($product['fabric1_name']) ?> (Fabric 1)
                    </option>
                  <?php endif; ?>
                  <?php if ($hasFabric2): ?>
                    <option 
                      value="2" 
                      data-name="<?= e($product['fabric2_name']) ?>" 
                      data-details="<?= e($product['fabric2_details'] ?? '') ?>"
                      <?= (!$hasFabric1) ? 'selected' : '' ?>
                    >
                      <?= e($product['fabric2_name']) ?> (Fabric 2)
                    </option>
                  <?php endif; ?>
                </select>
              </div>

              <div id="<?= e($panelId) ?>" class="fabric-details-panel">
                <span class="fabric-badge-name"><?= e($initialName) ?></span>
                <?php if (!empty(trim((string)$initialDetails))): ?>
                  <p class="fabric-desc"><?= e($initialDetails) ?></p>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- 5. Public description, clamped to ~2 lines (if it exists) -->
          <?php if (!empty(trim((string)($product['description'] ?? '')))): ?>
            <p class="card-description"><?= e($product['description']) ?></p>
          <?php endif; ?>

          <!-- 6. ACTION BUTTONS IN TIDY 2x2 GRID (EQUAL SIZE, ALIGNED) -->
          <div class="card-actions-grid product-card-actions">
            
            <!-- Row 1, Col 1: Details (BLUE) -->
            <a href="<?= APP_URL ?>/product-details.php?id=<?= (int)$product['id'] ?>" class="btn btn-blue btn-primary" title="View Details">
              <?= icon('eye') ?>
              <span>Details</span>
            </a>

            <!-- Row 1, Col 2: Download (RED) -->
            <?php if (!empty($product['main_image_path']) && file_exists(ROOT_PATH . '/' . $product['main_image_path'])): ?>
              <a href="<?= APP_URL ?>/download.php?id=<?= (int)$product['id'] ?>" class="btn btn-red btn-download" title="Download Image">
                <?= icon('download') ?>
                <span>Download</span>
              </a>
            <?php else: ?>
              <button type="button" class="btn btn-red btn-download" disabled title="No image available">
                <?= icon('download') ?>
                <span>Download</span>
              </button>
            <?php endif; ?>

            <!-- Row 2, Col 1: Copy Link (GRAY) -->
            <button 
              type="button" 
              class="btn btn-gray btn-secondary js-copy-link" 
              data-url="<?= e($shareUrl) ?>"
              title="Copy Share Link"
            >
              <?= icon('link') ?>
              <span>Copy Link</span>
            </button>

            <!-- Row 2, Col 2: WhatsApp (GREEN) -->
            <a 
              href="<?= whatsapp_link($cardWaMessage) ?>" 
              target="_blank" 
              rel="noopener noreferrer" 
              class="btn btn-green btn-whatsapp"
              title="Order or ask on WhatsApp"
            >
              <?= icon('whatsapp') ?>
              <span>WhatsApp</span>
            </a>

          </div>

        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
