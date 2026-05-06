<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/helpers.php';
require_once __DIR__.'/../inc/message_repo.php';
require_once __DIR__.'/../inc/admin_ui.php';

admin_header('Message Thread','msgs');

$id = (int)($_GET['id'] ?? 0);
$inq = $id ? inquiry_get($id) : null;
if (!$inq) { ?>
  <div class="card"><p>Inquiry not found.</p><p><a class="btn" href="/admin/messages.php">← Back</a></p></div>
<?php admin_footer(); exit; }

$msgs = inquiry_messages($id);
?>
<div class="admin-actions">
  <a class="btn" href="/admin/messages.php">← All Messages</a>
  <?php if ($inq['status']==='open'): ?>
    <form method="post" action="/admin/message_close.php" style="display:inline;">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$id ?>">
      <button class="btn" type="submit">Close Thread</button>
    </form>
  <?php else: ?>
    <form method="post" action="/admin/message_open.php" style="display:inline;">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$id ?>">
      <button class="btn" type="submit">Reopen</button>
    </form>
  <?php endif; ?>
</div>

<div class="card">
  <div class="kicker">About</div>
  <p>
    <strong>Product:</strong> <?= h($inq['product_name']) ?><br>
    <strong>From:</strong> <?= h((($inq['buyer_name']?:'')).' <'.$inq['buyer_email'].'>') ?><br>
    <strong>Status:</strong> <?= h($inq['status']) ?><br>
    <strong>Created:</strong> <?= h($inq['created_at']) ?> · <strong>Updated:</strong> <?= h($inq['updated_at']) ?>
  </p>
</div>

<div class="card" style="margin-top:12px;">
  <div class="kicker">Conversation</div>
  <div style="display:flex;flex-direction:column;gap:10px;">
    <?php foreach ($msgs as $m): ?>
      <div style="border:1px solid var(--line);border-radius:10px;padding:10px;background: var(--card);">
        <div class="kicker"><?= h($m['sender']) ?> · <?= h($m['created_at']) ?></div>
        <div><?= nl2br(h($m['body'])) ?></div>
      </div>
    <?php endforeach; ?>
    <?php if (!$msgs): ?><p class="kicker">No messages yet.</p><?php endif; ?>
  </div>
</div>

<?php if ($inq['status']==='open'): ?>
<div class="card" style="margin-top:12px;">
  <div class="kicker">Reply</div>
  <form method="post" action="/admin/message_reply.php" class="form" style="max-width:640px;">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$id ?>">
    <label>Message
      <textarea name="body" rows="4" required></textarea>
    </label>
    <button class="btn" type="submit">Send Reply (emails buyer)</button>
  </form>
</div>
<?php endif; ?>
<?php admin_footer();
