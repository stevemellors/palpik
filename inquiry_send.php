<?php
declare(strict_types=1);
require_once __DIR__.'/inc/helpers.php';
require_once __DIR__.'/inc/product_repo.php';
require_once __DIR__.'/inc/message_repo.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}
csrf_verify();

$productId = (int)($_POST['product_id'] ?? 0);
$name      = trim($_POST['name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$body      = trim($_POST['message'] ?? '');
$honeypot  = trim($_POST['company'] ?? ''); // hidden anti-bot

if ($honeypot !== '') { header('Location: /'); exit; }

if ($productId <= 0 || !filter_var($email, FILTER_VALIDATE_EMAIL) || $body === '') {
    flash_set('inq', 'error');
    header('Location: /product.php?id='.$productId);
    exit;
}

$p = product_get($productId);
if (!$p) { header('Location: /?missing=product'); exit; }

try {
    $inqId = inquiry_find_or_create($productId, $email, ($name ?: null), $body);
} catch (Throwable $e) {
    flash_set('inq', 'error');
    header('Location: /product.php?id='.$productId);
    exit;
}

/* Send notify email */
$subject = 'New Product Inquiry: '.$p['name'].' (Inquiry #'.$inqId.')';
$html = '<p><strong>Product:</strong> '.h($p['name']).'</p>'.
        '<p><strong>From:</strong> '.h($name).' &lt;'.h($email).'&gt;</p>'.
        '<p><strong>Message:</strong><br>'.nl2br(h($body)).'</p>'.
        '<p><a href="'.h(SITE_URL).'/admin/message_view.php?id='.$inqId.'">Open in Admin</a></p>';
$headers = [
  'MIME-Version: 1.0',
  'Content-type: text/html; charset=UTF-8',
  'From: noreply@palletpiks.com'
];
@mail('palletpiks@gmail.com', $subject, $html, implode("\r\n", $headers));

flash_set('inq', 'sent');
header('Location: /product.php?id='.$productId);
exit;
