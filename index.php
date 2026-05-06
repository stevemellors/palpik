<?php
declare(strict_types=1);
require_once __DIR__.'/inc/config.php';
require_once __DIR__.'/inc/helpers.php';
require_once __DIR__.'/inc/db.php';
require_once __DIR__.'/inc/category_repo.php';
require_once __DIR__.'/inc/product_repo.php';
require_once __DIR__.'/inc/nav.php';

$err = '';
$cats = $featured = [];

try {
  $cats = categories_all();
  // Featured = latest 6 products
  $all  = products_all(null);
  $featured = array_slice($all, 0, 6);
} catch (Throwable $e) {
  $err = $e->getMessage();
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h(SITE_NAME) ?> • Shop</title>
  <meta name="description" content="Quality pallets, gear, and merch — shipped fast.">
  <link rel="stylesheet" href="/assets/styles.css">
  <link rel="stylesheet" href="/assets/theme-override.css">
  <script src="/assets/theme.js" defer></script>
</head>
<body class="container">
<?= site_nav() ?>

<!-- HERO -->
<section class="hero">
  <div class="hero-inner">
    <h1 class="hero-title"><?= h(SITE_NAME) ?></h1>
    <p class="hero-sub">Quality pallets, gear, and merch — shipped fast.</p>
    <div class="hero-cta">
      <a class="btn" href="<?= $cats ? '/category.php?id='.(int)$cats[0]['id'] : '/' ?>">Shop Now</a>
    </div>
  </div>
</section>

<?php if ($err): ?>
  <div class="card" style="margin-top:16px;">
    <p style="color:#ef4444;"><?= h($err) ?></p>
  </div>
<?php endif; ?>

<!-- FEATURED PRODUCTS -->
<section class="section">
  <div class="section-head">
    <h2>Featured Products</h2>
    <p class="kicker">Hand-picked bestsellers & latest drops</p>
  </div>

  <?php if (!$featured): ?>
    <div class="card"><p class="kicker">No products yet. Add some in Admin → Products.</p></div>
  <?php else: ?>
    <div class="grid prod-grid">
      <?php foreach ($featured as $p): ?>
        <a class="card product-card" href="/product.php?id=<?= (int)$p['id'] ?>">
          <div class="product-media">
            <?php if (!empty($p['image'])): ?>
              <img src="/uploads/<?= h($p['image']) ?>" alt="" class="product-img">
            <?php else: ?>
              <div class="product-placeholder"><span class="logo-dot"></span></div>
            <?php endif; ?>
          </div>
          <div class="product-body">
            <h3 class="product-name"><?= h($p['name']) ?></h3>
            <div class="product-meta">
              <span class="price"><?= money((float)$p['price']) ?></span>
              <?php if (!empty($p['category_name'])): ?>
                <span class="pill"><?= h($p['category_name']) ?></span>
              <?php endif; ?>
            </div>
            <span class="product-link">View →</span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<!-- CATEGORIES -->
<section class="section">
  <div class="section-head">
    <h2>Shop by Category</h2>
    <p class="kicker">Browse our most popular categories</p>
  </div>

  <?php if (!$cats): ?>
    <div class="card"><p class="kicker">No categories yet. Create some in Admin → Categories.</p></div>
  <?php else: ?>
    <div class="grid cat-grid">
      <?php foreach ($cats as $c): ?>
        <a class="card cat-card" href="/category.php?id=<?= (int)$c['id'] ?>">
          <div class="cat-avatar"><span class="logo-dot"></span></div>
          <h3 class="cat-name"><?= h($c['name']) ?></h3>
          <p class="cat-desc"><?= h($c['description'] ?: 'Explore products') ?></p>
          <span class="cat-link">Shop →</span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
 


<!-- FOOTER -->
<footer class="site-footer">
  <p>© <?= date('Y') ?> <?= h(SITE_NAME) ?>. All rights reserved.</p>
  <p class="foot-links">
    <a href="/">Home</a>
    <span>·</span>
    <a href="/cart.php">Cart</a>
    <span>·</span>
    <a href="/admin/login.php" class="foot-admin">Admin</a>
  </p>
</footer>
</body>
</html>
