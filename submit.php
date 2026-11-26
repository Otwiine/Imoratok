<?php
/**
 * Imoratok Safaris — Safari Inquiry Submission Handler
 * Verifies Cloudflare Turnstile token, saves inquiry to SQLite, and sends email notification.
 */

declare(strict_types=1);

ob_start();
ini_set('display_errors', '0');
error_reporting(E_ALL);

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

require_once __DIR__ . '/config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    send_response(false, 'Method Not Allowed. Only POST submissions are accepted.');
}

// --------------------------------------------------------------------------
// 1. Honeypot Spam Check
// --------------------------------------------------------------------------
if (!empty($_POST['botcheck'])) {
    // Silent drop for automated bots filling hidden field
    send_response(true, 'Inquiry received.');
}

// --------------------------------------------------------------------------
// 2. Cloudflare Turnstile Verification
// --------------------------------------------------------------------------
$turnstile_response = $_POST['cf-turnstile-response'] ?? '';
$client_ip = $_SERVER['HTTP_CF_CONNECTING_IP'] 
          ?? $_SERVER['HTTP_X_FORWARDED_FOR'] 
          ?? $_SERVER['REMOTE_ADDR'] 
          ?? '';

// If X-Forwarded-For contains multiple IPs, grab the first client IP
if (strpos($client_ip, ',') !== false) {
    $client_ip = trim(explode(',', $client_ip)[0]);
}

if (empty($turnstile_response)) {
    http_response_code(400);
    send_response(false, 'Security verification required. Please complete the security check.');
}

$turnstile_verified = verify_turnstile($turnstile_response, $client_ip);
if (!$turnstile_verified) {
    http_response_code(403);
    send_response(false, 'Security verification failed. Please refresh the page and try again.');
}

// --------------------------------------------------------------------------
// 3. Input Validation & Sanitization
// --------------------------------------------------------------------------
$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

if ($name === '' || $email === '' || $message === '') {
    http_response_code(400);
    send_response(false, 'Please fill in all required fields (Name, Email, and Message).');
}

if (mb_strlen($name) > 150) {
    http_response_code(400);
    send_response(false, 'Name exceeds the maximum allowed length.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254) {
    http_response_code(400);
    send_response(false, 'Please provide a valid email address.');
}

if (mb_strlen($message) > 5000) {
    http_response_code(400);
    send_response(false, 'Message exceeds the maximum allowed length (5000 characters).');
}

$user_agent = trim((string)($_SERVER['HTTP_USER_AGENT'] ?? ''));

// --------------------------------------------------------------------------
// 4. Save Submission to SQLite Database
// --------------------------------------------------------------------------
try {
    $pdo = get_db_connection();
    $stmt = $pdo->prepare("
        INSERT INTO inquiries (created_at, name, email, message, ip_address, user_agent, status)
        VALUES (datetime('now'), :name, :email, :message, :ip, :ua, 'unread')
    ");
    $stmt->execute([
        ':name' => $name,
        ':email' => $email,
        ':message' => $message,
        ':ip' => $client_ip,
        ':ua' => mb_substr($user_agent, 0, 255)
    ]);
} catch (\Throwable $e) {
    error_log('Database error saving inquiry: ' . $e->getMessage());
    http_response_code(500);
    send_response(false, 'A server error occurred while saving your inquiry. Please try again or email us directly.');
}

// --------------------------------------------------------------------------
// 5. Send Email Notification
// --------------------------------------------------------------------------
if (defined('EMAIL_NOTIFICATIONS_ENABLED') && EMAIL_NOTIFICATIONS_ENABLED) {
    send_notification_email($name, $email, $message, $client_ip);
}

// --------------------------------------------------------------------------
// 6. Return Success Response
// --------------------------------------------------------------------------
send_response(true, '✓ Thank you! Your safari inquiry has been received. Our team will review your details and respond shortly.');

// ==========================================================================
// Helper Functions
// ==========================================================================

/**
 * Verify Cloudflare Turnstile token via siteverify endpoint.
 */
function verify_turnstile(string $response_token, string $remote_ip): bool {
    // If user has not replaced placeholder key yet, allow in dev/staging mode with notice in logs
    if (TURNSTILE_SECRET_KEY === '0x4AAAAAAAE-EXAMPLE_SECRET_KEY') {
        error_log('Notice: Cloudflare Turnstile secret key is still set to placeholder in config.php.');
        return true;
    }

    $verify_url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $post_data = http_build_query([
        'secret' => TURNSTILE_SECRET_KEY,
        'response' => $response_token,
        'remoteip' => $remote_ip
    ]);

    $result_json = null;

    if (function_exists('curl_init')) {
        $ch = curl_init($verify_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        $result_json = curl_exec($ch);
        curl_close($ch);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $post_data,
                'timeout' => 10
            ]
        ]);
        $result_json = @file_get_contents($verify_url, false, $context);
    }

    if (!$result_json) {
        error_log('Turnstile verification failed: could not reach Cloudflare API.');
        return false;
    }

    $data = json_decode($result_json, true);
    return !empty($data['success']);
}

/**
 * Send an email notification for the new inquiry.
 */
function send_notification_email(string $name, string $email, string $message, string $ip): bool {
    // If running on localhost without local sendmail/postfix, skip mail() to prevent timeouts
    $host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
    if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
        error_log('Localhost test detected: inquiry saved to SQLite, skipping mail().');
        return true;
    }

    $to = NOTIFY_EMAIL_TO;
    $subject = "New Safari Inquiry from {$name} — Imoratok Safaris";

    $email_body = "You have received a new safari inquiry from the website.\n\n"
                . "====================================================\n"
                . "Guest Name: {$name}\n"
                . "Guest Email: {$email}\n"
                . "Received: " . date('Y-m-d H:i:s T') . "\n"
                . "IP Address: {$ip}\n"
                . "====================================================\n\n"
                . "Dream Safari Details / Message:\n"
                . "{$message}\n\n"
                . "----------------------------------------------------\n"
                . "View all submissions in your protected admin dashboard:\n"
                . (isset($_SERVER['HTTP_HOST']) ? 'https://' . $_SERVER['HTTP_HOST'] . '/admin/' : 'Website Admin');

    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $headers[] = 'From: ' . NOTIFY_EMAIL_FROM;
    $headers[] = 'Reply-To: ' . $email;
    $headers[] = 'X-Mailer: PHP/' . phpversion();

    return @mail($to, $subject, $email_body, implode("\r\n", $headers));
}

/**
 * Deliver JSON response or HTML fallback.
 */
function send_response(bool $success, string $message): void {
    // Clear any preceding output buffer so nothing corrupts JSON
    while (ob_get_level()) {
        ob_end_clean();
    }

    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => $success,
        'message' => $message
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}
