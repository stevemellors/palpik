<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/helpers.php';
require_once __DIR__.'/../inc/user_repo.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!empty($_SESSION['admin_email'])) { header('Location: /admin/panel.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $pwd   = trim($_POST['password'] ?? '');
  if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $pwd === '') {
    $error = 'Enter a valid email and password.';
  } else {
    if (user_verify_admin_login($email, $pwd)) {
      session_regenerate_id(true);
      $_SESSION['admin_email'] = $email;
      header('Location: /admin/panel.php'); exit;
    } else {
      $error = 'Invalid credentials or not an admin.';
    }
  }
}
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login • <?= h(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/styles.css">
<link rel="stylesheet" href="/assets/theme-override.css">
<script src="/assets/theme.js" defer></script>
</head>
<body class="container">
<h1>Admin Login</h1>
<?php if ($error): ?><p class="text-danger"><?= h($error) ?></p><?php endif; ?>
<form method="post" class="form" style="max-width:360px;">
  <label>Email <input type="email" name="email" required></label>
  <label>Password <input type="password" name="password" required></label>
  <button class="btn" type="submit">Sign in</button>
</form>
<p style="margin-top:10px;"><a class="btn" href="/">← Storefront</a></p>
</body></html>
