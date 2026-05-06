<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/order_repo.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['admin_email'])) { http_response_code(403); exit('Forbidden'); }

$archived = ($_GET['archived'] ?? '0') === '1';
$status   = $_GET['status'] ?? 'all';
$q        = trim($_GET['q'] ?? '');

if ($archived) {
    $rows = orders_archived_search($q, 5000);
    if ($status !== 'all') $rows = array_values(array_filter($rows, fn($r)=>($r['status']??'')===$status));
    $fname = 'archived_orders.csv';
} else {
    $rows = orders_active_search($status, $q, 5000);
    $fname = 'active_orders.csv';
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="'.$fname.'"');

$out = fopen('php://output', 'w');
fputcsv($out, ['id','created_at','name','email','total','status','archived_at']);
foreach ($rows as $r) {
    fputcsv($out, [
        $r['id'],
        $r['created_at'] ?? '',
        $r['name'] ?? '',
        $r['email'] ?? '',
        $r['total'] ?? '',
        $r['status'] ?? '',
        $r['archived_at'] ?? '',
    ]);
}
fclose($out);
exit;
