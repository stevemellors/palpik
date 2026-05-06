<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/helpers.php';
require_once __DIR__.'/../inc/user_repo.php';

if (!empty($_SESSION['admin_email'])) { header('Location: /admin/panel.php'); exit; }

$sent  = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim($_POST['email'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } else {
        $token = user_create_reset_token($email);
        // Always show the same message to avoid user enumeration
        $sent = true;
        if ($token) {
            $resetLink = SITE_URL.'/admin/reset_password.php?token='.urlencode($token);
            $subject   = 'Admin password reset — '.SITE_NAME;
            $body      = '<p>You requested a password reset.</p>'
                        .'<p><a href="'.htmlspecialchars($resetLink).'">Click here to reset your password</a></p>'
                        .'<p>This link expires in 1 hour. If you did not request this, ignore this email.</p>';
            $headers   = implode("\r\n", [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: noreply@palletpiks.com',
            ]);
            @mail($email, $subject, $body, $headers);
        }
    }
}
?><!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Forgot Password • <?= h(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/styles.css">
<link rel="stylesheet" href="/assets/theme-override.css">
<script src="/assets/theme.js" defer></script>
</head>
<body class="container">
<h1>Forgot Password</h1>
<p><a class="btn" href="/admin/login.php">← Back to login</a></p>

<?php if ($sent): ?>
  <div class="card">
    <p style="color:var(--success)">If that email belongs to an admin account, a reset link has been sent.</p>
  </div>
<?php else: ?>
  <?php if ($error): ?><p class="text-danger"><?= h($error) ?></p><?php endif; ?>
  <form method="post" class="form" style="max-width:360px;">
    <?= csrf_field() ?>
    <label>Email <input type="email" name="email" required autofocus></label>
    <button class="btn" type="submit">Send Reset Link</button>
  </form>
<?php endif; ?>
</body></html>
