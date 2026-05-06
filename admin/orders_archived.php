<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/helpers.php';
require_once __DIR__.'/../inc/order_repo.php';
require_once __DIR__.'/../inc/admin_ui.php';

admin_header('Archived Orders','orders');

$status = $_GET['status'] ?? 'all';
$q = trim($_GET['q'] ?? '');

$rows = orders_archived_search($q, 1000); // includes all statuses; we'll show a status filter client-side
if ($status !== 'all') {
  $rows = array_values(array_filter($rows, fn($r)=>($r['status']??'')===$status));
}
?>
<div class="admin-actions">
  <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;margin-right:auto;">
    <select name="status" style="padding:10px;border-radius:10px;border:1px solid var(--line);background: var(--card); color: var(--ink);">
      <?php foreach (['all','pending','paid','shipped','completed','canceled'] as $opt): ?>
        <option value="<?= h($opt) ?>" <?= $status===$opt?'selected':'' ?>><?= ucfirst($opt) ?></option>
      <?php endforeach; ?>
    </select>
    <input name="q" placeholder="Search by #, email, name" value="<?= h($q) ?>" style="padding:10px;border-radius:10px;border:1px solid var(--line);background: var(--card); color: var(--ink);min-width:260px;">
    <button class="btn" type="submit">Filter</button>
    <a class="btn" href="/admin/orders_archived.php">Reset</a>
  </form>

  <a class="btn" href="/admin/orders.php">Active Orders</a>
  <a class="btn" href="/admin/export_orders_csv.php?archived=1&status=<?= urlencode($status) ?>&q=<?= urlencode($q) ?>">Export CSV</a>
  <a class="btn" href="/admin/export_archived_pdf.php?status=<?= urlencode($status) ?>&q=<?= urlencode($q) ?>">Export PDF</a>
</div>

<div class="card">
  <div class="kicker">Archived (<?= count($rows) ?>)</div>
  <?php if (!$rows): ?>
    <p class="kicker">No archived orders match.</p>
  <?php else: ?>
    <table class="table" style="width:100%;">
      <thead><tr>
        <th style="width:80px;">#</th>
        <th>When</th>
        <th>Name</th>
        <th>Email</th>
        <th style="width:120px;">Total</th>
        <th style="width:120px;">Status</th>
        <th style="width:160px;">Archived</th>
        <th style="width:140px;">View</th>
      </tr></thead>
      <tbody>
        <?php foreach ($rows as $o): ?>
          <tr>
            <td><?= (int)$o['id'] ?></td>
            <td><?= h($o['created_at']) ?></td>
            <td><?= h($o['name']) ?></td>
            <td><?= h($o['email']) ?></td>
            <td><?= money((float)$o['total']) ?></td>
            <td><?= h($o['status']) ?></td>
            <td><?= h((string)$o['archived_at']) ?></td>
            <td><a class="btn" href="/admin/order_view.php?id=<?= (int)$o['id'] ?>">Open</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<?php admin_footer();
