<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/order_repo.php';
require_once __DIR__.'/../vendor/autoload.php';

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['admin_email'])) { http_response_code(403); exit('Forbidden'); }

use Dompdf\Dompdf;

$status = $_GET['status'] ?? 'all';
$q      = trim($_GET['q'] ?? '');

$rows = orders_archived_search($q, 2000);
if ($status !== 'all') $rows = array_values(array_filter($rows, fn($r)=>($r['status']??'')===$status));

$htmlRows = '';
foreach ($rows as $r) {
  $htmlRows .= '<tr>'.
    '<td style="padding:6px;border:1px solid #ddd;">'.(int)$r['id'].'</td>'.
    '<td style="padding:6px;border:1px solid #ddd;">'.htmlspecialchars((string)$r['created_at']).'</td>'.
    '<td style="padding:6px;border:1px solid #ddd;">'.htmlspecialchars((string)$r['name']).'</td>'.
    '<td style="padding:6px;border:1px solid #ddd;">'.htmlspecialchars((string)$r['email']).'</td>'.
    '<td style="padding:6px;border:1px solid #ddd;text-align:right;">$'.number_format((float)$r['total'],2).'</td>'.
    '<td style="padding:6px;border:1px solid #ddd;">'.htmlspecialchars((string)$r['status']).'</td>'.
    '<td style="padding:6px;border:1px solid #ddd;">'.htmlspecialchars((string)($r['archived_at'] ?? '')).'</td>'.
  '</tr>';
}

$html = '<!doctype html><html><head><meta charset="utf-8"><style>
body{font-family:DejaVu Sans,Arial,Helvetica,sans-serif;font-size:12px;color:#111}
h1{font-size:18px;margin:0 0 8px}
table{border-collapse:collapse;width:100%}
th{background:#f5f5f5;text-align:left}
</style></head><body>
<h1>Archived Orders</h1>
<p>Filters: status='.htmlspecialchars($status).', q="'.htmlspecialchars($q).'"</p>
<table>
<thead><tr>
<th style="padding:6px;border:1px solid #ddd;">#</th>
<th style="padding:6px;border:1px solid #ddd;">When</th>
<th style="padding:6px;border:1px solid #ddd;">Name</th>
<th style="padding:6px;border:1px solid #ddd;">Email</th>
<th style="padding:6px;border:1px solid #ddd;">Total</th>
<th style="padding:6px;border:1px solid #ddd;">Status</th>
<th style="padding:6px;border:1px solid #ddd;">Archived</th>
</tr></thead>
<tbody>'.$htmlRows.'</tbody>
</table>
</body></html>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('archived_orders.pdf', ['Attachment' => true]);
exit;
