<?php
declare(strict_types=1);
require_once __DIR__.'/helpers.php';
require_once __DIR__.'/cart.php';
require_once __DIR__.'/category_repo.php';

function site_nav(string $active=''): string {
    $count = cart_count();
    $badge = $count > 0 ? '<span class="badge">'.$count.'</span>' : '';
    $cats  = categories_all();
    $q     = h(trim($_GET['q'] ?? ''));

    $catLinks = '';
    foreach ($cats as $c) {
        $catLinks .= '<a href="/category.php?id='.(int)$c['id'].'">'.h($c['name']).'</a>';
    }

    return <<<HTML
<header class="site-header">
  <a class="brand" href="/"><span class="logo-dot"></span><strong>Pallet Picks</strong></a>
  <button class="nav-toggle" type="button" aria-label="Toggle menu" aria-expanded="false" aria-controls="site-nav">
    <span></span><span></span><span></span>
  </button>
  <nav class="nav" id="site-nav">
    {$catLinks}
    <form class="nav-search" action="/search.php" method="get" role="search">
      <input type="search" name="q" value="{$q}" placeholder="Search…" aria-label="Search products">
      <button type="submit" aria-label="Go">&#128269;</button>
    </form>
    <a href="/cart.php">Cart{$badge}</a>
  </nav>
</header>
HTML;
}
