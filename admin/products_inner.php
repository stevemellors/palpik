<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/product_repo.php';
require_once __DIR__.'/../inc/category_repo.php';

$filterCat = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
$cats  = categories_all();
$items = products_all($filterCat);
?>
<div class="admin-actions">
  <a class="btn acc" href="/admin/product_edit.php">+ New Product</a>
  <a class="btn" href="/admin/categories.php">Categories</a>
</div>

<form method="get" style="margin-bottom:14px;max-width:340px;">
  <select name="category_id" onchange="this.form.submit()"
          style="width:100%;padding:9px 12px;border-radius:8px;border:1px solid var(--line);background:var(--panel);color:var(--ink);font-size:14px;">
    <option value="">All categories</option>
    <?php foreach ($cats as $c): ?>
      <option value="<?= (int)$c['id'] ?>" <?= $filterCat===(int)$c['id']?'selected':'' ?>>
        <?= h($c['name']) ?>
      </option>
    <?php endforeach; ?>
  </select>
</form>

<?php if (!$items): ?>
  <div class="card"><p class="kicker">No products yet. Click "+ New Product" to add one.</p></div>
<?php else: ?>
  <div class="card">
    <div class="table-wrap">
    <table class="table" style="width:100%;">
      <thead>
        <tr>
          <th style="width:60px;">ID</th>
          <th style="width:80px;">Image</th>
          <th>Name</th>
          <th style="width:100px;">Price</th>
          <th>Category</th>
          <th style="width:180px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $p): ?>
          <tr>
            <td><?= (int)$p['id'] ?></td>
            <td><?php if (!empty($p['image'])): ?>
              <img src="/uploads/<?= h($p['image']) ?>" alt="" style="height:50px;border-radius:6px;display:block;">
            <?php else: ?>
              <span class="kicker">—</span>
            <?php endif; ?></td>
            <td><?= h($p['name']) ?></td>
            <td><?= money((float)$p['price']) ?></td>
            <td><?= h($p['category_name'] ?? '') ?></td>
            <td style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
              <a class="btn" href="/admin/product_edit.php?id=<?= (int)$p['id'] ?>">Edit</a>
              <form method="post" action="/admin/product_delete.php" style="display:inline;" onsubmit="return confirm('Delete this product?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button class="btn danger" type="submit">Delete</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>
  </div>
<?php endif; ?>
