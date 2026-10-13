# Mehtab Garments — Product Catalog Website

A brand-new, production-ready clothing catalog website built with modern PHP 8+, MySQL (PDO prepared statements), vanilla CSS3 design system, and lightweight JavaScript. Designed for local execution with XAMPP on Windows and seamless transition to production hosting.

---

## Features

- **Public Catalog**: Accessible without login. Features a brand hero, aligned toolbar with search by product name, dynamic fabric filter dropdown, sort options, and a responsive product grid.
- **Product Card Layout**: Standardized 4:5 image ratio, retail price, interactive Fabric 1 / Fabric 2 selector, description snippet, and functional Details, safe Download, and Copy Link buttons.
- **Standalone Share Page (`share.php?t=<token>`)**: Unguessable 32-character hex token displaying strictly the standalone read-only product card and image download button with zero navigation or site chrome.
- **Privacy Enforcement**: Customer names, emails, and password hashes are never rendered on public pages, navbar, dashboard, error messages, admin panels, JavaScript, or URLs.
- **Costs & Private Data**: Manufacturing cost breakdowns (Inner/Back, Embroidery, Neck Rate, Cutwork, Cutting, Stitching, Press, Elastic, Thread, Poly Bag, Pico, Fabric costs, Design number, Private notes) are only rendered on the server for confirmed owners and administrators.
- **Dotted Background**: Subtle CSS-only radial dotted background rendered across every single page.
- **Robust Security**: PDO prepared statements everywhere, strict CSRF validation on all state changes, IP-hashed login throttling, secure HTTP-only cookies, MIME-validated image uploads, and script execution disabled in upload directories.

---

## Prerequisites & Requirements

- **PHP 8.0+** with the following extensions enabled in `php.ini`:
  - `pdo_mysql`
  - `fileinfo`
  - `gd`
  - `session`
  - `mbstring`
- **MySQL 5.7+ / 8.0+** or **MariaDB 10.4+**
- **Apache Web Server** with `mod_rewrite` and `mod_headers` enabled.

---

## Step-by-Step XAMPP Setup (Windows)

### 1. Start XAMPP
- Open the **XAMPP Control Panel**.
- Start both **Apache** and **MySQL**.

### 2. Place Project in htdocs
- Place the `mehtab-garments` folder inside your XAMPP web root:
  ```
  C:\xampp\htdocs\mehtab-garments
  ```

### 3. Import Database
- Open your browser and navigate to **phpMyAdmin**:
  ```
  http://localhost/phpmyadmin
  ```
- Click on the **Import** tab.
- Choose file: `database/database.sql` from the project directory.
- Click **Import** (or **Go**). This will create the database `mehtab_garments` with all tables, keys, and indexes.

### 4. Seed Admin Accounts (One-Time Execution)
- In phpMyAdmin, click on the newly created `mehtab_garments` database on the left sidebar.
- Go to the **SQL** tab.
- Open `database/seed_admins.sql`, copy its content, paste it into the SQL query box, and click **Go**.
- **Important**: Immediately delete `database/seed_admins.sql` from the server/filesystem after running it once.
- Log in and update both seeded administrator passwords to strong, unique passwords.

### 5. Configure Environment Variables
- In the project root, ensure `.env` exists (copy from `.env.example` if needed):
  ```env
  DB_HOST=localhost
  DB_NAME=mehtab_garments
  DB_USER=root
  DB_PASS=
  APP_URL=http://localhost/mehtab-garments
  APP_ENV=development
  ```
- Adjust `DB_USER` and `DB_PASS` to match your local MySQL configuration.

### 6. Verify PHP Extensions
- Open `C:\xampp\php\php.ini` in a text editor.
- Ensure the following lines are not preceded by a semicolon (`;`):
  ```ini
  extension=pdo_mysql
  extension=fileinfo
  extension=gd
  ```
- If you made changes, restart Apache in the XAMPP Control Panel.

### 7. Access the Application
- Open your browser and visit:
  ```
  http://localhost/mehtab-garments
  ```

---

## Share Links: Localhost vs. Hosted Domain

- On **localhost**, the share URL is generated as:
  `http://localhost/mehtab-garments/share.php?t=<32-char-token>`
- This link works on the local computer and devices on the same local area network (by replacing `localhost` with your computer's LAN IP).
- When deploying to production hosting:
  - Update `APP_URL` in `.env` to your production domain (e.g., `https://catalog.mehtabgarments.com`).
  - All "Copy Link" buttons will automatically generate the public domain URL.
  - Existing share tokens remain identical and uninterrupted.

---

## Changing Currency Display

The currency symbol is defined in exactly one place:
- File: `config/app.php`
- Constant:
  ```php
  define('CURRENCY_SYMBOL', 'Rs.');
  ```
- To change to another currency (e.g., `$`, `PKR`, `AED`), edit this constant. Every price and cost display throughout the entire application updates instantly.

---

## Round 2 Customizations & Design System

### 1. WhatsApp Configuration (Single Source of Truth)
The business WhatsApp contact is configured in **`config/app.php`**:
```php
define('WHATSAPP_NUMBER', '923228127038');    // International format without '+' or leading zero
define('WHATSAPP_DISPLAY', '0322 8127038');   // Friendly local display format
```
- **Helper function**: `whatsapp_link($message)` in `includes/functions.php` automatically encodes prefilled messages and links to `https://wa.me/923228127038?text=...`.
- Buttons are embedded on the navbar (desktop + mobile compact), footer card, every product card (prefilled with product title and share token URL), product details page, and standalone share page.

### 2. Colored 3D Button System (CSS Variables)
Buttons use a raised 3D edge, hover lift, press-down click, and shine sweep defined in `assets/css/style.css`:
- **Blue** (`.btn-primary`): Details, Save Product, Login, Sign Up, Search.
- **Red** (`.btn-download`): Image download actions.
- **Gray** (`.btn-secondary`): Copy Link, Cancel, Admin overview.
- **Green** (`.btn-whatsapp`): Direct WhatsApp chat actions with gentle pulse.
- **Deep Crimson** (`.btn-danger`): Delete products and users (visually distinct from Download).

CSS variables can be customized in `:root`:
```css
--btn-primary-gradient: linear-gradient(180deg, #3B82F6 0%, #2563EB 50%, #1D4ED8 100%);
--btn-download-gradient: linear-gradient(180deg, #EF4444 0%, #DC2626 50%, #B91C1C 100%);
--btn-secondary-gradient: linear-gradient(180deg, #94A3B8 0%, #64748B 50%, #475569 100%);
--btn-whatsapp-gradient: linear-gradient(180deg, #25D366 0%, #16A34A 50%, #128C7E 100%);
--btn-danger-gradient: linear-gradient(180deg, #E11D48 0%, #BE123C 50%, #881337 100%);
```

### 3. Offline SVG Icon Helper
Icons are loaded locally with zero CDN dependencies via `includes/icons.php`:
- Render sprite in header/body: `renderIconSprite();`
- Render icon anywhere: `<?= icon('eye', 'custom-class') ?>`
- 24 built-in icons: `eye`, `download`, `link`, `check`, `whatsapp`, `search`, `filter`, `sort`, `plus`, `user`, `logout`, `login`, `shield`, `image`, `fabric`, `tag`, `trash`, `edit`, `publish`, `draft`, `menu`, `close`, `chevron-down`, `phone`, `sparkle`.

### 4. Browser Cache & Hard Refresh
- CSS and JS assets use automatic cache-busting query strings `?v=<timestamp>` via `asset_url('/assets/css/style.css')`.
- If styling changes are not immediately visible in your browser, perform a **hard refresh**:
  - **Windows / Linux**: `Ctrl + F5` or `Ctrl + Shift + R`
  - **macOS**: `Cmd + Shift + R`

---

## Troubleshooting

### 1. Upload Size Limits
If uploads fail with large photos:
- Check `upload_max_filesize` and `post_max_size` in `C:\xampp\php\php.ini`.
- Recommended settings:
  ```ini
  upload_max_filesize = 10M
  post_max_size = 12M
  memory_limit = 128M
  ```
- Restart Apache after modifying `php.ini`.

### 2. Session Issues or Premature Logout
- Ensure cookies are allowed in your browser.
- Verify that `C:\xampp\tmp` exists and is writable by Apache.

### 3. File Permissions
- Ensure the web server has write permissions to:
  `uploads/products/`

---

## Testing Checklist

- [x] Landing page loads without authentication.
- [x] Product toolbar controls (+ New, Search, Fabric Filter, Sort) are aligned and functional.
- [x] Product card displays Name at top, 4:5 image, Retail Price, Fabric dropdown, Description, Details, safe Download, and Copy Link buttons.
- [x] Fabric 1 / Fabric 2 dropdown updates name and details in real time.
- [x] Copy Link copies the token URL to clipboard and displays toast confirmation.
- [x] Safe download handler streams the image with slugified filename without login.
- [x] User registration creates role 'user' without exposing names or emails.
- [x] Login throttle triggers after 5 failed attempts from the same IP within 10 minutes.
- [x] Private costs panel only renders for product owner or admin.
- [x] Admin panel allows management without leaking user names, emails, or password hashes.
- [x] Dotted background is active across all pages, including error pages.
- [x] Mobile menu collapses into accessible toggle button.
