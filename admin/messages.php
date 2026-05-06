<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/helpers.php';
require_once __DIR__.'/../inc/message_repo.php';
require_once __DIR__.'/../inc/admin_ui.php';

admin_header('Messages','msgs');

$status = $_GET['status'] ?? 'open';
$rows = inquiries_list(in_array($status,['open','closed'], true)?$status:'open', 500);
?>
<div class="admin-actions">
  <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;margin-right:auto;">
    <select name="status" style="padding:10px;border-radius:10px;border:1px solid var(--line);background: var(--card); color: var(--ink);">
      <option value="open"   <?= $status==='open'?'selected':'' ?>>Open</option>
      <option value="closed" <?= $status==='closed'?'selected':'' ?>>Closed</option>
    </select>
    <button class="btn" type="submit">Filter</button>
    <a class="btn" href="/admin/messages.php">Reset</a>
  </form>
</div>

<div class="card">
  <div class="kicker">Inquiries (<?= count($rows) ?>)</div>
  <?php if (!$rows): ?>
    <p class="kicker">No inquiries.</p>
  <?php else: ?>
    <table class="table" style="width:100%;">
      <thead><tr>
        <th>#</th>
        <th>Updated</th>
        <th>Product</th>
        <th>From</th>
        <th>Last Message</th>
        <th>Open</th>
      </tr></thead>
      <tbody>
        <?php foreach ($rows as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= h($r['updated_at']) ?></td>
            <td><?= h($r['product_name']) ?></td>
            <td><?= h((($r['buyer_name']?:'')).' <'.$r['buyer_email'].'>') ?></td>
            <td><?= h(mb_strimwidth((string)$r['last_message'],0,120,'…')) ?></td>
            <td><a class="btn" href="/admin/message_view.php?id=<?= (int)$r['id'] ?>">Open</a></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<?php admin_footer();
