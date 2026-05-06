<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/db.php';
require_once __DIR__.'/../inc/admin_guard.php';
$dbh = db();

$fromRaw = preg_replace('/[^0-9T: -]/', '', $_GET['from'] ?? '');
$toRaw   = preg_replace('/[^0-9T: -]/', '', $_GET['to']   ?? '');
$from = $fromRaw !== '' ? str_replace('T', ' ', $fromRaw) : date('Y-m-01 00:00:00');
$to   = $toRaw   !== '' ? str_replace('T', ' ', $toRaw)   : date('Y-m-d 23:59:59');
$cat  = isset($_GET['category']) && $_GET['category'] !== '' ? (int)$_GET['category'] : null;
$prod = isset($_GET['product'])  && $_GET['product']  !== '' ? (int)$_GET['product']  : null;
$pay  = isset($_GET['payment'])  && $_GET['payment']  !== '' ? $_GET['payment']        : null;

$joins = [];
$wheres = ["v.ordered_at BETWEEN :f AND :t"];
$params = [':f'=>$from, ':t'=>$to];

if ($cat !== null) { $joins['p']="JOIN products p ON p.id=v.product_id"; $wheres[]="p.category_id=:cat"; $params[':cat']=$cat; }
if ($prod !== null){ $wheres[]="v.product_id=:prod"; $params[':prod']=$prod; }
if ($pay !== null) { $joins['o']="JOIN orders o ON o.id=v.order_id"; $wheres[]="o.payment_method=:pay"; $params[':pay']=$pay; }

$joins_sql = implode(" ", $joins);
$where_sql = "WHERE ".implode(" AND ", $wheres);

/* rich export */
$sql = "
SELECT
  v.ordered_at,
  v.order_id,
  o.name        AS customer_name,
  o.email       AS customer_email,
  CONCAT_WS(', ', o.address, CONCAT(o.city,' ',o.state,' ',o.zip)) AS customer_address,
  o.payment_method,
  o.status,
  o.subtotal, o.tax, o.shipping, o.total AS order_total,
  v.product_name,
  v.qty,
  v.unit_price,
  v.line_total
FROM v_sales_lines v
JOIN orders o ON o.id = v.order_id
$joins_sql
$where_sql
ORDER BY v.ordered_at DESC, v.order_id DESC
";

$stmt = $dbh->prepare($sql);
$stmt->execute($params);

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="palletpiks_earnings_'.date('Ymd_His').'.csv"');
$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF");
$headers = [
  'ordered_at','order_id','customer_name','customer_email','customer_address',
  'payment_method','status','subtotal','tax','shipping','order_total',
  'product_name','qty','unit_price','line_total'
];
fputcsv($out, $headers);
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
  foreach (['subtotal','tax','shipping','order_total','unit_price','line_total'] as $n) {
    if ($row[$n] !== null) $row[$n] = number_format((float)$row[$n], 2, '.', '');
  }
  fputcsv($out, $row);
}
fclose($out);
