<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/helpers.php';
require_once __DIR__.'/../inc/category_repo.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['admin_email'])) { header('Location: /admin/login.php'); exit; }

$id   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$cat  = $id ? category_get($id) : ['id'=>0,'name'=>'','description'=>''];
$err  = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  csrf_verify();
  $name = trim($_POST['name'] ?? '');
  $desc = trim($_POST['description'] ?? '');
  if ($name === '') { $err = 'Name is required'; }
  if (!$err) {
    if ($id) category_update($id, $name, $desc);
    else     $id = category_create($name, $desc);
    header('Location: /admin/categories.php'); exit;
  }
}
?><!doctype html><html><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $id?'Edit':'New' ?> Category • <?= h(SITE_NAME) ?></title>
<link rel="stylesheet" href="/assets/styles.css">
<link rel="stylesheet" href="/assets/theme-override.css">
<script src="/assets/theme.js" defer></script>
</head><body class="container">
<h1><?= $id?'Edit':'New' ?> Category</h1>
<?php if ($err): ?><p class="text-danger"><?= h($err) ?></p><?php endif; ?>
<form method="post" class="form" style="max-width:560px;">
  <?= csrf_field() ?>
  <label>Name
    <input name="name" value="<?= h($cat['name']) ?>" required>
  </label>
  <label>Description
    <textarea name="description" rows="4"><?= h($cat['description'] ?? '') ?></textarea>
  </label>
  <div style="display:flex;gap:8px;margin-top:10px;">
    <button class="btn" type="submit">Save</button>
    <a class="btn" href="/admin/categories.php">Cancel</a>
  </div>
</form>
</body></html>
