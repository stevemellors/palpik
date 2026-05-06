<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/category_repo.php';

$cats = categories_all();
?>
<div class="admin-actions">
  <a class="btn acc" href="/admin/category_edit.php">+ New Category</a>
</div>

<?php if (!$cats): ?>
  <div class="card"><p class="kicker">No categories yet. Click "+ New Category" to add one.</p></div>
<?php else: ?>
  <div class="card">
    <div class="table-wrap">
    <table class="table" style="width:100%;">
      <thead>
        <tr>
          <th style="width:60px;">ID</th>
          <th>Name</th>
          <th>Description</th>
          <th style="width:180px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($cats as $c): ?>
          <tr>
            <td><?= (int)$c['id'] ?></td>
            <td><?= h($c['name']) ?></td>
            <td><?= h($c['description'] ?? '') ?></td>
            <td style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
              <a class="btn" href="/admin/category_edit.php?id=<?= (int)$c['id'] ?>">Edit</a>
              <form method="post" action="/admin/category_delete.php" style="display:inline;" onsubmit="return confirm('Delete this category?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
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
