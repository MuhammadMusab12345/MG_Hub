/**
 * Mehtab Garments — Core Client Scripts (Round 2)
 */

document.addEventListener('DOMContentLoaded', () => {
  initMobileNav();
  initNavbarScrollEffect();
  initCardIntersectionObserver();
  initCopyLinkButtons();
  initFabricDropdowns();
  initImageUploadPreviews();
  initToolbarAutoSubmit();
  initConfirmDialogs();
  initImageMagnifier();
});

/* ----------------------------------------------------------
   Mobile Navigation Toggle
   ---------------------------------------------------------- */
function initMobileNav() {
  const toggleBtn = document.querySelector('.nav-toggle');
  const navMenu = document.querySelector('.nav-menu');

  if (!toggleBtn || !navMenu) return;

  toggleBtn.addEventListener('click', () => {
    const isExpanded = toggleBtn.getAttribute('aria-expanded') === 'true';
    toggleBtn.setAttribute('aria-expanded', String(!isExpanded));
    navMenu.classList.toggle('open');
  });

  // Close on Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && navMenu.classList.contains('open')) {
      navMenu.classList.remove('open');
      toggleBtn.setAttribute('aria-expanded', 'false');
      toggleBtn.focus();
    }
  });

  // Close when clicking outside
  document.addEventListener('click', (e) => {
    if (navMenu.classList.contains('open') && !navMenu.contains(e.target) && !toggleBtn.contains(e.target)) {
      navMenu.classList.remove('open');
      toggleBtn.setAttribute('aria-expanded', 'false');
    }
  });
}

/* ----------------------------------------------------------
   Navbar Scroll Elevation Effect
   ---------------------------------------------------------- */
function initNavbarScrollEffect() {
  const header = document.querySelector('.site-header');
  if (!header) return;

  const onScroll = () => {
    if (window.scrollY > 15) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  };

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
}

/* ----------------------------------------------------------
   Staggered Fade-Up Entrance Animation for Cards
   ---------------------------------------------------------- */
function initCardIntersectionObserver() {
  const cards = document.querySelectorAll('.product-card');
  if (!cards.length) return;

  if (!('IntersectionObserver' in window)) {
    // Fallback: cards stay immediately visible
    return;
  }

  const observer = new IntersectionObserver(
    (entries, obs) => {
      entries.forEach((entry, idx) => {
        if (entry.isIntersecting) {
          setTimeout(() => {
            entry.target.classList.add('reveal-in');
          }, idx * 60);
          obs.unobserve(entry.target);
        }
      });
    },
    { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
  );

  cards.forEach((card) => {
    card.classList.add('reveal-init');
    observer.observe(card);
  });
}

/* ----------------------------------------------------------
   Copy Link with Toast Confirmation & Fallback
   ---------------------------------------------------------- */
function initCopyLinkButtons() {
  const copyButtons = document.querySelectorAll('.js-copy-link');

  copyButtons.forEach((btn) => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const urlToCopy = btn.getAttribute('data-url');
      if (!urlToCopy) return;

      let success = false;

      // Modern Clipboard API
      if (navigator.clipboard && window.isSecureContext) {
        try {
          await navigator.clipboard.writeText(urlToCopy);
          success = true;
        } catch (err) {
          success = false;
        }
      }

      // Fallback for older browsers or non-HTTPS localhost
      if (!success) {
        try {
          const tempInput = document.createElement('textarea');
          tempInput.value = urlToCopy;
          tempInput.style.position = 'fixed';
          tempInput.style.left = '-9999px';
          tempInput.style.top = '-9999px';
          document.body.appendChild(tempInput);
          tempInput.focus();
          tempInput.select();
          success = document.execCommand('copy');
          document.body.removeChild(tempInput);
        } catch (err) {
          success = false;
        }
      }

      if (success) {
        showToast('Link copied to clipboard!');
      } else {
        window.prompt('Copy this link:', urlToCopy);
      }
    });
  });
}

/* ----------------------------------------------------------
   Toast Notification System (With Check Icon)
   ---------------------------------------------------------- */
function showToast(message) {
  let toast = document.getElementById('app-toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'app-toast';
    toast.className = 'toast-notice';
    document.body.appendChild(toast);
  }

  toast.innerHTML = `
    <svg class="app-icon" aria-hidden="true" style="width: 1.25em; height: 1.25em; color: var(--btn-green-start);">
      <use href="#icon-check"></use>
    </svg>
    <span>${escapeHtml(message)}</span>
  `;

  toast.classList.add('show');

  if (window._toastTimeout) {
    clearTimeout(window._toastTimeout);
  }

  window._toastTimeout = setTimeout(() => {
    toast.classList.remove('show');
  }, 2600);
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.innerText = str;
  return div.innerHTML;
}

/* ----------------------------------------------------------
   Fabric 1 / Fabric 2 Dropdown Switcher
   ---------------------------------------------------------- */
function initFabricDropdowns() {
  const fabricSelects = document.querySelectorAll('.js-fabric-select');

  fabricSelects.forEach((select) => {
    const targetPanelId = select.getAttribute('data-target-panel');
    const panel = targetPanelId ? document.getElementById(targetPanelId) : null;
    if (!panel) return;

    select.addEventListener('change', () => {
      const selectedOption = select.options[select.selectedIndex];
      const name = selectedOption.getAttribute('data-name') || '';
      const details = selectedOption.getAttribute('data-details') || '';
      const cost = selectedOption.getAttribute('data-cost') || '';

      const nameEl = panel.querySelector('.fabric-badge-name');
      const descEl = panel.querySelector('.fabric-desc');
      const costEl = panel.querySelector('.fabric-cost');

      if (nameEl) nameEl.textContent = name;
      if (descEl) {
        descEl.textContent = details;
        descEl.style.display = details ? 'block' : 'none';
      }
      if (costEl) {
        costEl.textContent = cost ? (cost) : '';
        costEl.style.display = cost ? 'inline-block' : 'none';
      }
    });
  });
}

/* ----------------------------------------------------------
   Live Image Upload Previews
   ---------------------------------------------------------- */
function initImageUploadPreviews() {
  const mainInput = document.getElementById('main_image_input');
  const mainPreview = document.getElementById('main_image_preview');

  if (mainInput && mainPreview) {
    mainInput.addEventListener('change', () => {
      mainPreview.innerHTML = '';
      if (mainInput.files && mainInput.files[0]) {
        const file = mainInput.files[0];
        const reader = new FileReader();
        reader.onload = (e) => {
          mainPreview.innerHTML = `
            <div class="file-preview-item" style="width: 140px; height: 175px;">
              <img src="${e.target.result}" alt="Main image preview" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
          `;
        };
        reader.readAsDataURL(file);
      }
    });
  }

  const multiInput = document.getElementById('additional_images_input');
  const multiPreview = document.getElementById('additional_images_preview');

  if (multiInput && multiPreview) {
    multiInput.addEventListener('change', () => {
      multiPreview.innerHTML = '';
      if (multiInput.files) {
        Array.from(multiInput.files).forEach((file) => {
          const reader = new FileReader();
          reader.onload = (e) => {
            const div = document.createElement('div');
            div.className = 'file-preview-item';
            div.innerHTML = `<img src="${e.target.result}" alt="Additional preview">`;
            multiPreview.appendChild(div);
          };
          reader.readAsDataURL(file);
        });
      }
    });
  }
}

/* ----------------------------------------------------------
   Toolbar Auto-Submit on Filter / Sort Change
   ---------------------------------------------------------- */
function initToolbarAutoSubmit() {
  const toolbarForm = document.getElementById('catalog-toolbar-form');
  if (!toolbarForm) return;

  const selects = toolbarForm.querySelectorAll('select');
  selects.forEach((select) => {
    select.addEventListener('change', () => {
      toolbarForm.submit();
    });
  });
}

/* ----------------------------------------------------------
   Delete & Destructive Action Confirmations
   ---------------------------------------------------------- */
function initConfirmDialogs() {
  document.querySelectorAll('.js-confirm-action').forEach((el) => {
    el.addEventListener('submit', (e) => {
      const promptText = el.getAttribute('data-confirm-message') || 'Are you sure you want to proceed?';
      if (!window.confirm(promptText)) {
        e.preventDefault();
      }
    });
  });
}

/* ----------------------------------------------------------
   Desktop Gallery Image Magnifier Zoom
   ---------------------------------------------------------- */
function initImageMagnifier() {
  const frames = document.querySelectorAll('.gallery-main-frame');

  frames.forEach((frame) => {
    const img = frame.querySelector('img');
    if (!img) return;

    frame.addEventListener('mousemove', (e) => {
      const rect = frame.getBoundingClientRect();
      const x = ((e.clientX - rect.left) / rect.width) * 100;
      const y = ((e.clientY - rect.top) / rect.height) * 100;
      img.style.transformOrigin = `${x}% ${y}%`;
    });

    frame.addEventListener('mouseleave', () => {
      img.style.transformOrigin = 'center center';
    });
  });
}
