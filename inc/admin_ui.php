<?php
declare(strict_types=1);
require_once __DIR__.'/helpers.php';
require_once __DIR__.'/message_repo.php';
$msgOpen = inquiries_count('open');


function admin_header(string $title='Dashboard', string $active='panel'): void {
  if (session_status() !== PHP_SESSION_ACTIVE) session_start();
  if (empty($_SESSION['admin_email'])) { header('Location: /admin/login.php'); exit; }
  $me = $_SESSION['admin_email'] ?? '';

  $nav = [
    'panel'      => ['label' => 'Overview', 'href' => '/admin/panel.php'],
    'orders'     => ['label' => 'Orders',   'href' => '/admin/orders.php'],
    'products'   => ['label' => 'Products', 'href' => '/admin/products.php'],
    'categories' => ['label' => 'Categories','href' => '/admin/categories.php'],
    'users'      => ['label' => 'Admin Users','href' => '/admin/users.php'],
    'reports'    => ['label' => 'Reports',    'href' => '/admin/reports.php'],
    'msgs'       => ['label' => 'Messages ('.(int)$msgOpen.')', 'href' => '/admin/messages.php'],
 ];

  echo "<!doctype html><html lang='en'><head>
  <meta charset='utf-8'><meta name='viewport' content='width=device-width, initial-scale=1'>
  <title>".h($title)." • Admin • ".h(SITE_NAME)."</title>
  <script src='/assets/admin-theme-mount.js' defer></script>
  <script src='/assets/theme.js' defer></script>
  <link rel='stylesheet' href='/assets/admin.css'>
    <link rel='stylesheet' href='/assets/styles.css'>
    <link rel='stylesheet' href='/assets/css/theme-vars.css?v=1'>
  <link rel='stylesheet' href='/assets/theme-override.css'>
</head><body class='admin-shell'>

  <aside class='admin-sidebar'>
    <a class='brand' href='/admin/panel.php'><span class='logo-dot'></span><strong>Admin</strong></a>

    <nav class='admin-nav'>";
  foreach ($nav as $key => $item) {
    $cls = $active === $key ? 'active' : '';
    echo "<a class='$cls' href='".h($item['href'])."'>".h($item['label'])."</a>";
  }
  echo "</nav>
    <div class='admin-sidefoot'>
      <a class='sm' href='/'>&larr; Storefront</a>
      <a class='sm' href='/admin/logout.php'>Logout</a>
    </div>
  </aside>

  <header class='admin-topbar'>
    <div class='ttl'>".h($title)."</div>
    <div class='who'>".h($me)."</div>
  </header>

  <main class='admin-main'>
  ";
}

function admin_footer(): void {
  echo "</main></body></html>";
}
