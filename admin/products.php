<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/helpers.php';
require_once __DIR__.'/../inc/admin_ui.php';
admin_header('Products','products');
require __DIR__.'/products_inner.php';
admin_footer();
