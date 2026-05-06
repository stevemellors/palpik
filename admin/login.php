<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/helpers.php';
require_once __DIR__.'/../inc/user_repo.php';

if (!empty($_SESSION['admin_email'])) { header('Location: /admin/panel.php'); exit; }

const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCKOUT_SECONDS = 900; // 15 minutes

$error = '';
$lockedOut = false;

if (!empty($_SESSION['login_lockout_until']) && time() < $_SESSION['login_lockout_until']) {
    $remaining = (int)ceil(($_SESSION['login_lockout_until'] - time()) / 60);
    $error = "Too many failed attempts. Try again in {$remaining} minute(s).";
    $lockedOut = true;
}

if (!$lockedOut && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $pwd   = trim($_POST['password'] ?? '');
  if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $pwd === '') {
    $error = 'Enter a valid email and password.';
  } else {
    if (user_verify_admin_login($email, $pwd)) {
      $_SESSION['login_attempts'] = 0;
      unset($_SESSION['login_lockout_until']);
      session_regenerate_id(true);
      $_SESSION['admin_email'] = $email;
      header('Location: /admin/panel.php'); exit;
    } else {
      $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
      if ($_SESSION['login_attempts'] >= LOGIN_MAX_ATTEMPTS) {
          $_SESSION['login_lockout_until'] = time() + LOGIN_LOCKOUT_SECONDS;
          $_SESSION['login_attempts'] = 0;
          $error = 'Too many failed attempts. Try again in 15 minutes.';
      } else {
          $remaining = LOGIN_MAX_ATTEMPTS - $_SESSION['login_attempts'];
          $error = "Invalid credentials or not an admin. {$remaining} attempt(s) remaining.";
      }
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
<p style="margin-top:10px;">
  <a class="btn" href="/">← Storefront</a>
  <a href="/admin/forgot_password.php" style="margin-left:10px;font-size:13px;">Forgot password?</a>
</p>
</body></html>
