<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/helpers.php';
require_once __DIR__.'/../inc/order_repo.php';
require_once __DIR__.'/../inc/admin_ui.php';

admin_header('Order Detail','orders');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$ord = $id ? order_get($id) : null;
if (!$ord) { http_response_code(404); ?>
<div class="card"><h1>Order not found</h1><p><a class="btn" href="/admin/orders.php">← Back</a></p></div>
<?php admin_footer(); exit; }

$items = order_items($id);
?>
<div class="admin-actions">
  <a class="btn" href="/admin/orders.php">← Orders</a>
  <?php if (empty($ord['archived_at'])): ?>
    <form method="post" action="/admin/order_archive.php" style="display:inline;">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$id ?>">
      <button class="btn acc" type="submit" onclick="return confirm('Mark as shipped and archive this order?')">
        Mark Shipped & Archive
      </button>
    </form>
  <?php else: ?>
    <span class="kicker">Archived on <?= h((string)$ord['archived_at']) ?></span>
    <form method="post" action="/admin/order_unarchive.php" style="display:inline;margin-left:8px;">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$id ?>">
      <button class="btn" type="submit" onclick="return confirm('Unarchive this order?')">Unarchive</button>
    </form>
  <?php endif; ?>
</div>

<div class="card">
  <div class="kicker">Customer</div>
  <p>
    <strong><?= h($ord['name']) ?></strong><br>
    <?= h($ord['email']) ?><br>
    <?= h($ord['address']) ?><br>
    <?= h($ord['city']) ?>, <?= h($ord['state']) ?> <?= h($ord['zip']) ?>
  </p>
</div>

<div class="card" style="margin-top:12px;">
  <div class="kicker">Items</div>
  <table class="table" style="width:100%;">
    <thead><tr><th>Item</th><th style="width:80px;">Qty</th><th style="width:120px;">Price</th></tr></thead>
    <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= h($it['name']) ?></td>
          <td><?= (int)$it['qty'] ?></td>
          <td><?= money((float)$it['price']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <p style="margin-top:8px;">
    <strong>Subtotal:</strong> <?= money((float)$ord['subtotal']) ?><br>
    <strong>Shipping:</strong> <?= money((float)$ord['shipping']) ?><br>
    <strong>Tax:</strong> <?= money((float)$ord['tax']) ?><br>
    <strong>Total:</strong> <?= money((float)$ord['total']) ?><br>
    <strong>Status:</strong> <?= h($ord['status']) ?>
    <?php if (!empty($ord['archived_at'])): ?><br><strong>Archived:</strong> <?= h((string)$ord['archived_at']) ?><?php endif; ?>
  </p>
</div>
<?php admin_footer();
