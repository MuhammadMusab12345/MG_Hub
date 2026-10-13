<?php
/**
 * Mehtab Garments — Common Footer (Round 2)
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/functions.php';
?>
</main>

<footer class="site-footer">
  <div class="container">
    
    <!-- WhatsApp Footer Card -->
    <div class="footer-whatsapp-card">
      <div class="footer-whatsapp-text">
        <div class="footer-whatsapp-title">
          <?= icon('phone') ?>
          <span>Direct Assistance: <?= e(WHATSAPP_DISPLAY) ?></span>
        </div>
        <p class="footer-whatsapp-desc">Order or ask on WhatsApp — fast response from our catalog desk.</p>
      </div>
      <a 
        href="<?= whatsapp_link('Hello Mehtab Garments, I would like to order or ask about your clothing catalog.') ?>" 
        target="_blank" 
        rel="noopener noreferrer" 
        class="btn btn-green"
      >
        <?= icon('whatsapp') ?>
        <span>Chat on WhatsApp</span>
      </a>
    </div>

    <p class="footer-copy">&copy; <?= date('Y') ?> Mehtab Garments. All rights reserved.</p>
  </div>
</footer>

<script src="<?= asset_url('assets/js/app.js') ?>"></script>
</body>
</html>
