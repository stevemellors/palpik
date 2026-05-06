<?php
declare(strict_types=1);
require_once __DIR__.'/inc/config.php';
require_once __DIR__.'/inc/helpers.php';
require_once __DIR__.'/inc/category_repo.php';
require_once __DIR__.'/inc/product_repo.php';
require_once __DIR__.'/inc/nav.php';

$id  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$cat = $id ? category_get($id) : null;

if (!$cat) {
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
      <h1>Category not found</h1>
      <a class="btn" href="/">← Back to shop</a>
    </body></html><?php
    exit;
}

$perPage = 12;
$page    = max(1, (int)($_GET['page'] ?? 1));
$total   = products_count_by_category($id);
$pages   = max(1, (int)ceil($total / $perPage));
$page    = min($page, $pages);
$items   = products_by_category_paged($id, $perPage, ($page - 1) * $perPage);
$allCats = categories_all();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($cat['name']) ?> • <?= h(SITE_NAME) ?></title>
  <link rel="stylesheet" href="/assets/styles.css">
  <link rel="stylesheet" href="/assets/theme-override.css">
  <script src="/assets/theme.js" defer></script>
</head>
<body class="container">
<?= site_nav() ?>

<div style="display:flex;align-items:baseline;gap:12px;margin-bottom:6px;flex-wrap:wrap">
  <h1 style="font-size:22px;font-weight:800"><?= h($cat['name']) ?></h1>
  <span class="kicker"><?= count($items) ?> product<?= count($items) !== 1 ? 's' : '' ?></span>
</div>
<?php if (!empty($cat['description'])): ?>
  <p style="color:var(--muted);margin-bottom:16px;font-size:14px"><?= h($cat['description']) ?></p>
<?php endif; ?>

<!-- Other categories -->
<?php if ($allCats): ?>
  <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px">
    <a class="btn secondary" href="/">All</a>
    <?php foreach ($allCats as $c): ?>
      <a class="btn <?= (int)$c['id'] === $id ? 'acc' : 'secondary' ?>" href="/category.php?id=<?= (int)$c['id'] ?>">
        <?= h($c['name']) ?>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (!$items): ?>
  <div class="card" style="text-align:center;padding:36px">
    <p class="kicker">No products yet</p>
    <p style="color:var(--muted);margin-top:6px">Check back soon or browse another category.</p>
    <a class="btn" href="/" style="margin-top:14px">← Back to shop</a>
  </div>
<?php else: ?>
  <div class="grid prod-grid">
    <?php foreach ($items as $p): ?>
      <a class="card product-card" href="/product.php?id=<?= (int)$p['id'] ?>">
        <div class="product-media">
          <?php if (!empty($p['image'])): ?>
            <img src="/uploads/<?= h($p['image']) ?>" alt="<?= h($p['name']) ?>" class="product-img">
          <?php else: ?>
            <div class="product-placeholder">📦</div>
          <?php endif; ?>
        </div>
        <div class="product-body">
          <h3 class="product-name"><?= h($p['name']) ?></h3>
          <?php if (!empty($p['description'])): ?>
            <p style="color:var(--muted);font-size:12px;margin:0 0 6px;line-height:1.4">
              <?= h(mb_substr($p['description'], 0, 80)) ?><?= mb_strlen($p['description']) > 80 ? '…' : '' ?>
            </p>
          <?php endif; ?>
          <div class="product-meta">
            <span class="price"><?= money((float)$p['price']) ?></span>
          </div>
          <span class="product-link">View →</span>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if ($pages > 1): ?>
  <div style="display:flex;gap:6px;flex-wrap:wrap;margin:20px 0;justify-content:center;">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
      <a class="btn <?= $i === $page ? 'acc' : 'secondary' ?>"
         href="/category.php?id=<?= $id ?>&page=<?= $i ?>"><?= $i ?></a>
    <?php endfor; ?>
  </div>
<?php endif; ?>

<footer class="site-footer">
  <p>&copy; <?= date('Y') ?> <?= h(SITE_NAME) ?>. All rights reserved.</p>
  <div class="foot-links">
    <a href="/">Home</a><span>·</span><a href="/cart.php">Cart</a><span>·</span><a href="/admin/login.php">Admin</a>
  </div>
</footer>
</body>
</html>
