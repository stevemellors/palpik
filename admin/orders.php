<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/helpers.php';
require_once __DIR__.'/../inc/order_repo.php';
require_once __DIR__.'/../inc/admin_ui.php';

admin_header('Orders','orders');

$status = $_GET['status'] ?? 'all'; // all|pending|paid|shipped|completed|canceled
$q = trim($_GET['q'] ?? '');

$orders = orders_active_search($status, $q, 500);
?>
<div class="admin-actions">
  <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;margin-right:auto;">
    <select name="status" style="padding:10px;border-radius:10px;border:1px solid var(--line);background: var(--card); color: var(--ink);">
      <?php foreach (['all','pending','paid','shipped','completed','canceled'] as $opt): ?>
        <option value="<?= h($opt) ?>" <?= $status===$opt?'selected':'' ?>><?= ucfirst($opt) ?></option>
      <?php endforeach; ?>
    </select>
    <input name="q" placeholder="Search by #, email, name" value="<?= h($q) ?>" style="padding:10px;border-radius:10px;border:1px solid var(--line);background: var(--card); color: var(--ink);flex:1;min-width:160px;">
    <button class="btn" type="submit">Filter</button>
    <a class="btn" href="/admin/orders.php">Reset</a>
  </form>

  <a class="btn" href="/admin/orders_archived.php">Archived Orders</a>
  <a class="btn" href="/admin/export_orders_csv.php?archived=0&status=<?= urlencode($status) ?>&q=<?= urlencode($q) ?>">Export CSV</a>
</div>

<div class="card">
  <div class="kicker">Active Orders (<?= count($orders) ?>)</div>
  <?php if (!$orders): ?>
    <p class="kicker">No orders match.</p>
  <?php else: ?>
  <form method="post" action="/admin/orders_bulk_archive.php">
    <?= csrf_field() ?>
    <table class="table" style="width:100%;">
      <thead><tr>
        <th style="width:36px;"><input type="checkbox" onclick="document.querySelectorAll('.chk').forEach(c=>c.checked=this.checked)"></th>
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
            <td><input class="chk" type="checkbox" name="ids[]" value="<?= (int)$o['id'] ?>"></td>
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
    <div style="display:flex;gap:8px;margin-top:10px;">
      <button class="btn acc" type="submit" onclick="return confirm('Archive selected as shipped?')">Archive Selected (Shipped)</button>
    </div>
  </form>
  <?php endif; ?>
</div>
<?php admin_footer();
