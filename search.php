<?php
declare(strict_types=1);
require_once __DIR__.'/inc/config.php';
require_once __DIR__.'/inc/helpers.php';
require_once __DIR__.'/inc/db.php';
require_once __DIR__.'/inc/nav.php';

require_once __DIR__.'/inc/product_repo.php';

$q       = trim($_GET['q'] ?? '');
$perPage = 12;
$page    = max(1, (int)($_GET['page'] ?? 1));
$total   = 0;
$pages   = 1;
$results = [];

if ($q !== '') {
    $total   = products_search_count($q);
    $pages   = max(1, (int)ceil($total / $perPage));
    $page    = min($page, $pages);
    $results = products_search($q, $perPage, ($page - 1) * $perPage);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $q !== '' ? 'Search: '.h($q).' • ' : 'Search • ' ?><?= h(SITE_NAME) ?></title>
  <link rel="stylesheet" href="/assets/styles.css">
  <link rel="stylesheet" href="/assets/theme-override.css">
  <script src="/assets/theme.js" defer></script>
</head>
<body class="container">
<?= site_nav() ?>

<h1>Search</h1>

<form class="search-form" action="/search.php" method="get" role="search">
  <input type="search" name="q" value="<?= h($q) ?>" placeholder="Search products…" autofocus>
  <button class="btn" type="submit">Search</button>
</form>

<?php if ($q === ''): ?>
  <p class="search-empty">Enter a search term above.</p>

<?php elseif (!$results): ?>
  <p class="search-empty">No products found for "<strong><?= h($q) ?></strong>".</p>

<?php else: ?>
  <p class="kicker" style="margin-bottom:14px;"><?= $total ?> result<?= $total !== 1 ? 's' : '' ?> for "<?= h($q) ?>"</p>
  <div class="grid prod-grid">
    <?php foreach ($results as $p): ?>
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
          <div class="product-meta">
            <span class="price"><?= money((float)$p['price']) ?></span>
            <?php if (!empty($p['category_name'])): ?>
              <span class="pill"><?= h($p['category_name']) ?></span>
            <?php endif; ?>
            <?php if (isset($p['stock'])): ?>
              <span class="stock-badge <?= (int)$p['stock'] > 0 ? 'in' : 'out' ?>">
                <?= (int)$p['stock'] > 0 ? 'In stock' : 'Sold out' ?>
              </span>
            <?php endif; ?>
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
         href="/search.php?q=<?= urlencode($q) ?>&page=<?= $i ?>"><?= $i ?></a>
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
