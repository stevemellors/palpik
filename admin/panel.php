<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/helpers.php';
require_once __DIR__.'/../inc/admin_ui.php';
require_once __DIR__.'/../inc/order_repo.php';

admin_header('Dashboard', 'panel');

/* Simple KPIs */
$orders = orders_all(6); // latest 6 for the table
$today = date('Y-m-d');
$todayTotal = 0.0; $orderCount = 0;
foreach ($orders as $o) {
  if (strpos((string)$o['created_at'], $today) === 0) {
    $todayTotal += (float)$o['total']; $orderCount++;
  }
}
?>
<div class="admin-cards">
  <div class="kpi">
    <div class="kicker">Today’s Orders</div>
    <div class="v"><?= (int)$orderCount ?></div>
  </div>
  <div class="kpi">
    <div class="kicker">Today’s Revenue</div>
    <div class="v"><?= money($todayTotal) ?></div>
  </div>
  <div class="kpi">
    <div class="kicker">Latest Order Total</div>
    <div class="v"><?= isset($orders[0]) ? money((float)$orders[0]['total']) : '$0.00' ?></div>
  </div>
</div>

<div class="admin-actions">
  <a class="btn acc" href="/admin/products.php">Manage Products</a>
  <a class="btn" href="/admin/product_edit.php">+ New Product</a>
  <a class="btn" href="/admin/categories.php">Categories</a>
  <a class="btn" href="/admin/orders.php">Orders</a>
  <a class="btn" href="/admin/users.php">Admin Users</a>
</div>

<div class="card">
  <div class="kicker">Recent Orders</div>
  <?php if (!$orders): ?>
    <p class="kicker">No recent orders.</p>
  <?php else: ?>
    <table class="table" style="width:100%;">
      <thead><tr>
        <th style="width:80px;">#</th>
        <th>When</th>
        <th>Name</th>
        <th>Email</th>
        <th style="width:120px;">Total</th>
        <th style="width:120px;">Status</th>
        <th style="width:140px;">View</th>
      </tr></thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td><?= (int)$o['id'] ?></td>
          <td><?= h($o['created_at']) ?></td>
          <td><?= h($o['name']) ?></td>
          <td><?= h($o['email']) ?></td>
          <td><?= money((float)$o['total']) ?></td>
          <td><?= h($o['status']) ?></td>
          <td><a class="btn" href="/admin/order_view.php?id=<?= (int)$o['id'] ?>">Open</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<?php admin_footer();
