<?php
/**
 * Imoratok Safaris — Protected Admin Dashboard Login
 */

declare(strict_types=1);

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax'
]);
session_start();

require_once __DIR__ . '/../config.php';

// If already logged in, redirect straight to dashboard
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string)($_POST['username'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    // Anti-brute force delay
    usleep(300000); // 300ms pause

    $valid_password = (defined('ADMIN_PASSWORD_HASH') && password_verify($password, ADMIN_PASSWORD_HASH))
                   || (defined('ADMIN_PASSWORD_RAW') && hash_equals(ADMIN_PASSWORD_RAW, $password));

    if ($username === ADMIN_USERNAME && $valid_password) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user'] = ADMIN_USERNAME;
        $_SESSION['login_time'] = time();

        header('Location: index.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — Imoratok Safaris</title>
  <link rel="icon" href="../favicon.ico">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
  <style>
    :root {
      --color-primary: #1b382b;
      --color-primary-dark: #12261d;
      --color-gold: #e5a742;
      --color-bg: #0f1d16;
      --color-card: #182e23;
      --color-text: #ffffff;
      --color-text-muted: #a3b8ad;
      --color-border: #284436;
      --color-error: #ff6b6b;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: var(--color-bg);
      color: var(--color-text);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .login-container {
      width: 100%;
      max-width: 420px;
      background: var(--color-card);
      border: 1px solid var(--color-border);
      border-radius: 12px;
      padding: 36px 32px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
    }
    .brand-header {
      text-align: center;
      margin-bottom: 28px;
    }
    .brand-title {
      font-family: 'Playfair Display', serif;
      font-size: 1.6rem;
      color: #ffffff;
      margin-bottom: 4px;
    }
    .brand-subtitle {
      font-size: 0.88rem;
      color: var(--color-gold);
      letter-spacing: 1px;
      text-transform: uppercase;
      font-weight: 600;
    }
    .form-group {
      margin-bottom: 20px;
    }
    .form-label {
      display: block;
      font-size: 0.88rem;
      font-weight: 600;
      color: var(--color-text-muted);
      margin-bottom: 8px;
    }
    .form-control {
      width: 100%;
      padding: 12px 14px;
      background: #11221a;
      border: 1px solid var(--color-border);
      border-radius: 6px;
      color: #ffffff;
      font-size: 0.95rem;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .form-control:focus {
      outline: none;
      border-color: var(--color-gold);
      box-shadow: 0 0 0 3px rgba(229, 167, 66, 0.2);
    }
    .error-alert {
      background: rgba(255, 107, 107, 0.15);
      border: 1px solid var(--color-error);
      color: var(--color-error);
      padding: 10px 14px;
      border-radius: 6px;
      font-size: 0.88rem;
      margin-bottom: 20px;
      text-align: center;
    }
    .btn-login {
      width: 100%;
      padding: 13px;
      background: var(--color-gold);
      color: #12261d;
      font-weight: 700;
      font-size: 0.95rem;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      transition: background-color 0.2s, transform 0.1s;
    }
    .btn-login:hover {
      background: #f1b34e;
    }
    .btn-login:active {
      transform: scale(0.99);
    }
    .login-footer {
      text-align: center;
      margin-top: 24px;
      font-size: 0.82rem;
      color: var(--color-text-muted);
    }
    .login-footer a {
      color: var(--color-gold);
      text-decoration: none;
    }
    .login-footer a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="brand-header">
      <h1 class="brand-title">Imoratok Safaris</h1>
      <p class="brand-subtitle">Inquiries Dashboard</p>
    </div>

    <?php if ($error): ?>
      <div class="error-alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
      <div class="form-group">
        <label for="username" class="form-label">Username</label>
        <input type="text" id="username" name="username" class="form-control" required autofocus autocomplete="username">
      </div>

      <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <input type="password" id="password" name="password" class="form-control" required autocomplete="current-password">
      </div>

      <button type="submit" class="btn-login">Sign In</button>
    </form>

    <div class="login-footer">
      <a href="../index.html">← Back to Website</a>
    </div>
  </div>
</body>
</html>
