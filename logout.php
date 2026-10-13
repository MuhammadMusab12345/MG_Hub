<?php
/**
 * Mehtab Garments — User Logout
 */

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

logoutUser();
setFlash('info', 'You have been logged out.');
redirect(APP_URL . '/login.php');
