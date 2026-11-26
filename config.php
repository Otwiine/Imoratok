<?php
/**
 * Imoratok Safaris — Core Configuration
 * Centralized settings for Form Submissions, Cloudflare Turnstile, and Admin Dashboard.
 */

// Prevent direct script execution if accessed directly
if (basename($_SERVER['PHP_SELF']) === 'config.php') {
    http_response_code(403);
    exit('Direct access not permitted.');
}

// --------------------------------------------------------------------------
// 1. Cloudflare Turnstile API Keys
// Obtain these from Cloudflare Dashboard -> Turnstile -> Add Site
// --------------------------------------------------------------------------
define('TURNSTILE_SITE_KEY', '0x4AAAAAAGUNYS9RggtNDIPE');     // Public Turnstile Site Key
define('TURNSTILE_SECRET_KEY', '0x4AAAAAAGUNYfWYMAt2MT3qTlw5AUi5Ags'); // Secret Turnstile Secret Key

// --------------------------------------------------------------------------
// 2. Notification Email & Webmail Settings
// --------------------------------------------------------------------------
define('NOTIFY_EMAIL_TO', 'info@imoratoksafaris.com');
define('NOTIFY_EMAIL_FROM', 'no-reply@imoratoksafaris.com');
define('EMAIL_NOTIFICATIONS_ENABLED', true);
define('WEBMAIL_LOGIN_URL', 'https://imoratoksafaris.com/webmail');

// --------------------------------------------------------------------------
// 3. Admin Dashboard Authentication
// Username: admin
// --------------------------------------------------------------------------
define('ADMIN_USERNAME', 'admin');
// Primary password hash and direct constant for timing-safe authentication
define('ADMIN_PASSWORD_HASH', '$2y$10$wK1q7e8zV2X9H4Yl3J7O0OPQ5a6b7c8d9e0f1g2h3i4j5k6l7m8n9'); 
define('ADMIN_PASSWORD_RAW', 'OJO@imoratok26');

// --------------------------------------------------------------------------
// 4. Database Configuration (SQLite)
// Stored in the data/ directory, protected by .htaccess from web downloads.
// --------------------------------------------------------------------------
define('DB_PATH', __DIR__ . '/data/inquiries.db');

/**
 * Helper to get a secure PDO SQLite database connection.
 * Automatically initializes tables on first run.
 *
 * @return PDO
 */
function get_db_connection(): PDO {
    $dir = dirname(DB_PATH);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Initialize inquiries table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS inquiries (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            name TEXT NOT NULL,
            email TEXT NOT NULL,
            message TEXT NOT NULL,
            ip_address TEXT DEFAULT '',
            user_agent TEXT DEFAULT '',
            status TEXT DEFAULT 'unread' -- 'unread' or 'read'
        )
    ");

    return $pdo;
}
