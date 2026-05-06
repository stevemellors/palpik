<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/helpers.php';
require_once __DIR__.'/../inc/user_repo.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['admin_email'])) { header('Location: /admin/login.php'); exit; }

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$u  = $id ? user_get($id) : null;

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_verify();
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $role = ($_POST['role'] ?? 'admin') === 'customer' ? 'customer' : 'admin';
  $pwd   = trim($_POST['password'] ?? '');
  $pwd2  = trim($_POST['password2'] ?? '');

  if ($name === '') {
    $err = 'Name is required';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $err = 'Valid email required';
  } elseif ($id === 0 && $pwd === '') {
    $err = 'Password required for new admin';
  } elseif ($pwd !== '' && $pwd !== $pwd2) {
    $err = 'Passwords do not match';
  }

  if (!$err) {
    if ($id) {
      user_update($id, $name, $email, $role);
      if ($pwd !== '') user_set_password($id, $pwd);
    } else {
      $id = user_create($name, $email, $pwd, $role);
    }
    header('Location: /admin/users.php'); exit;
  }
}

$title = $id ? 'Edit User' : 'New User';
$nameVal = $u['name'] ?? '';
$emailVal = $u['email'] ?? '';
$roleVal = $u['role'] ?? 'admin';
?><!doctype html><html><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= h($title) ?> • <?= h(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/styles.css">
<link rel="stylesheet" href="/assets/theme-override.css">
<script src="/assets/theme.js" defer></script>
</head>
<body class="container">
<p><a class="btn" href="/admin/users.php">← Back</a></p>
<h1><?= h($title) ?></h1>
<?php if ($err): ?><p class="text-danger"><?= h($err) ?></p><?php endif; ?>

<form method="post" class="form" style="max-width:420px;">
  <?= csrf_field() ?>
  <label>Name
    <input name="name" value="<?= h($nameVal) ?>" required>
  </label>
  <label>Email
    <input type="email" name="email" value="<?= h($emailVal) ?>" required>
  </label>
  <label>Role
    <select name="role">
      <option value="admin" <?= $roleVal==='admin'?'selected':''; ?>>Admin</option>
      <option value="customer" <?= $roleVal==='customer'?'selected':''; ?>>Customer</option>
    </select>
  </label>
  <label>Password <?= $id ? '(leave blank to keep current)' : '' ?>
    <input type="password" name="password" <?= $id ? '' : 'required' ?>>
  </label>
  <label>Confirm Password
    <input type="password" name="password2" <?= $id ? '' : 'required' ?>>
  </label>

  <div style="display:flex;gap:8px;margin-top:10px;">
    <button class="btn" type="submit">Save</button>
    <a class="btn" href="/admin/users.php">Cancel</a>
  </div>
</form>
</body></html>
