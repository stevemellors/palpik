<?php
declare(strict_types=1);
require_once __DIR__.'/inc/config.php';
require_once __DIR__.'/inc/helpers.php';
require_once __DIR__.'/inc/product_repo.php';
require_once __DIR__.'/inc/message_repo.php';
require_once __DIR__.'/inc/nav.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$p  = $id ? product_get($id) : null;

if (!$p) {
    http_response_code(404);
    ?><!doctype html>
    <html lang="en"><head>
      <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
      <title>Not Found • <?= h(SITE_NAME) ?></title>
      <link rel="stylesheet" href="/assets/styles.css">
      <link rel="stylesheet" href="/assets/theme-override.css">
      <script src="/assets/theme.js" defer></script>
    </head><body class="container">
      <?= site_nav() ?>
      <h1>Product not found</h1>
      <p style="color:var(--muted);margin:8px 0 16px">That product doesn't exist or was removed.</p>
      <a class="btn" href="/">← Back to shop</a>
    </body></html><?php
    exit;
}

$inStock = isset($p['stock']) && (int)$p['stock'] > 0;
$inqStatus = $_GET['inq'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="<?= h(mb_substr($p['description'] ?? $p['name'], 0, 155)) ?>">
  <title><?= h($p['name']) ?> • <?= h(SITE_NAME) ?></title>
  <link rel="stylesheet" href="/assets/styles.css">
  <link rel="stylesheet" href="/assets/theme-override.css">
  <script src="/assets/theme.js" defer></script>
</head>
<body class="container">
<?= site_nav() ?>

<p style="margin-bottom:14px;">
  <a href="<?= $p['category_id'] ? '/category.php?id='.(int)$p['category_id'] : '/' ?>">← Back</a>
</p>

<div class="product-detail">
  <!-- Image -->
  <div class="product-detail-img">
    <?php if (!empty($p['image'])): ?>
      <img src="/uploads/<?= h($p['image']) ?>" alt="<?= h($p['name']) ?>">
    <?php else: ?>
      <div class="no-img">📦</div>
    <?php endif; ?>
  </div>

  <!-- Details -->
  <div class="product-detail-info">
    <div>
      <h1><?= h($p['name']) ?></h1>
      <?php if (!empty($p['description'])): ?>
        <p class="product-detail-desc" style="margin-top:8px"><?= nl2br(h($p['description'])) ?></p>
      <?php endif; ?>
    </div>

    <div class="product-detail-price"><?= money((float)$p['price']) ?></div>

    <div>
      <?php if ($inStock): ?>
        <span class="stock-badge in">In stock (<?= (int)$p['stock'] ?> available)</span>
      <?php else: ?>
        <span class="stock-badge out">Out of stock</span>
      <?php endif; ?>
    </div>

    <?php if ($inStock): ?>
      <form method="post" action="/add_to_cart.php">
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
        <div class="qty-row">
          <input type="number" name="qty" value="1" min="1" max="<?= (int)$p['stock'] ?>" step="1" required aria-label="Quantity">
          <button class="btn acc" type="submit">Add to Cart</button>
        </div>
      </form>
    <?php endif; ?>

    <div style="display:flex;gap:8px;flex-wrap:wrap">
      <a class="btn secondary" href="/cart.php">View Cart</a>
      <a class="btn secondary" href="/">← Continue Shopping</a>
    </div>
  </div>
</div>

<!-- Inquiry -->
<div class="card" style="margin-top:24px;max-width:580px">
  <p class="kicker" style="margin-bottom:10px">Questions about this item?</p>

  <?php if ($inqStatus === 'sent'): ?>
    <p style="color:var(--success)">✓ Message sent — we'll reply to your email.</p>
  <?php elseif ($inqStatus === 'error'): ?>
    <p class="text-danger">Please enter a valid email and message.</p>
  <?php endif; ?>

  <form method="post" action="/inquiry_send.php" class="form">
    <?= csrf_field() ?>
    <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
    <label>Name <input name="name" placeholder="Optional"></label>
    <label>Email <input type="email" name="email" required placeholder="your@email.com"></label>
    <label>Message <textarea name="message" rows="4" required placeholder="Ask about availability, shipping, specs…"></textarea></label>
    <input type="text" name="company" style="display:none" tabindex="-1" autocomplete="off">
    <button class="btn" type="submit">Send Message</button>
  </form>
</div>

<footer class="site-footer">
  <p>&copy; <?= date('Y') ?> <?= h(SITE_NAME) ?>. All rights reserved.</p>
  <div class="foot-links">
    <a href="/">Home</a><span>·</span><a href="/cart.php">Cart</a><span>·</span><a href="/admin/login.php">Admin</a>
  </div>
</footer>
</body>
</html>
