<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/helpers.php';
require_once __DIR__.'/../inc/user_repo.php';

if (!empty($_SESSION['admin_email'])) { header('Location: /admin/panel.php'); exit; }

$token = trim($_GET['token'] ?? '');
$user  = $token !== '' ? user_verify_reset_token($token) : null;
$error = '';
$done  = false;

if ($user && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $pwd  = $_POST['password']  ?? '';
    $pwd2 = $_POST['password2'] ?? '';
    if (strlen($pwd) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($pwd !== $pwd2) {
        $error = 'Passwords do not match.';
    } else {
        user_consume_reset_token((int)$user['id'], $pwd);
        $done = true;
    }
}
?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reset Password • <?= h(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/styles.css">
<link rel="stylesheet" href="/assets/theme-override.css">
<script src="/assets/theme.js" defer></script>
</head>
<body class="container">
<h1>Reset Password</h1>

<?php if ($done): ?>
  <div class="card">
    <p style="color:var(--success)">Password updated. <a href="/admin/login.php">Sign in →</a></p>
  </div>
<?php elseif (!$user): ?>
  <div class="card">
    <p class="text-danger">This reset link is invalid or has expired.</p>
    <p><a class="btn" href="/admin/forgot_password.php">Request a new link</a></p>
  </div>
<?php else: ?>
  <?php if ($error): ?><p class="text-danger"><?= h($error) ?></p><?php endif; ?>
  <form method="post" class="form" style="max-width:360px;">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= h($token) ?>">
    <label>New Password <input type="password" name="password" minlength="8" required autofocus></label>
    <label>Confirm Password <input type="password" name="password2" required></label>
    <button class="btn" type="submit">Set New Password</button>
  </form>
<?php endif; ?>
</body></html>
