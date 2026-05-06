<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/helpers.php';
require_once __DIR__.'/../inc/user_repo.php';
require_once __DIR__.'/../inc/admin_ui.php';

admin_header('Admin Users','users');
$rows = users_all();
?>
<div class="admin-actions">
  <a class="btn acc" href="/admin/user_edit.php">+ New Admin</a>
</div>

<div class="card">
  <div class="kicker">Accounts</div>
  <table class="table" style="width:100%;">
    <thead>
      <tr><th style="width:70px;">#</th><th>Name</th><th>Email</th><th style="width:120px;">Role</th><th style="width:200px;">Created</th><th style="width:220px;">Actions</th></tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><?= (int)$r['id'] ?></td>
          <td><?= h($r['name']) ?></td>
          <td><?= h($r['email']) ?></td>
          <td><?= h($r['role']) ?></td>
          <td><?= h((string)$r['created_at']) ?></td>
          <td>
            <a class="btn" href="/admin/user_edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
            <?php if ($_SESSION['admin_email'] !== $r['email']): ?>
              <form method="post" action="/admin/user_delete.php" style="display:inline;" onsubmit="return confirm('Delete this admin?')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn" type="submit">Delete</button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr><td colspan="6" class="kicker">No users found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<?php admin_footer();
