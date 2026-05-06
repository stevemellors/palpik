<?php
declare(strict_types=1);
require_once __DIR__.'/../inc/config.php';
require_once __DIR__.'/../inc/db.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['admin_email'])) { header('Location: /admin/login.php'); exit; }
$dbh = db();

/* --- CONFIG --- */
const LOGO_PATH = '../assets/images/palletpikslogo.png';
$tz = 'America/New_York';

/* --- Period window --- */
$period = $_GET['period'] ?? 'week';
function windowFor($period, $tz){
  switch ($period) {
    case 'month':
      $from = (new DateTime('first day of this month', new DateTimeZone($tz)))->format('Y-m-d 00:00:00');
      $to   = (new DateTime('last day of this month 23:59:59', new DateTimeZone($tz)))->format('Y-m-d H:i:s');
      break;
    case 'year':
      $from = (new DateTime(date('Y-01-01 00:00:00'), new DateTimeZone($tz)))->format('Y-m-d H:i:s');
      $to   = (new DateTime(date('Y-12-31 23:59:59'), new DateTimeZone($tz)))->format('Y-m-d H:i:s');
      break;
    case 'custom':
      $fromRaw = preg_replace('/[^0-9T: -]/', '', $_GET['from'] ?? '');
      $toRaw   = preg_replace('/[^0-9T: -]/', '', $_GET['to']   ?? '');
      $from = $fromRaw !== '' ? str_replace('T', ' ', $fromRaw) : date('Y-m-01 00:00:00');
      $to   = $toRaw   !== '' ? str_replace('T', ' ', $toRaw)   : date('Y-m-d 23:59:59');
      break;
    case 'week':
    default:
      $monday = new DateTime('monday this week', new DateTimeZone($tz));
      $sunday = new DateTime('sunday this week 23:59:59', new DateTimeZone($tz));
      $from = $monday->format('Y-m-d 00:00:00');
      $to   = $sunday->format('Y-m-d H:i:s');
      break;
  }
  return [$from,$to];
}
list($from,$to) = windowFor($period,$tz);

/* --- Filters --- */
$cat  = isset($_GET['category']) && $_GET['category'] !== '' ? (int)$_GET['category'] : null;
$prod = isset($_GET['product'])  && $_GET['product']  !== '' ? (int)$_GET['product']  : null;
$pay  = isset($_GET['payment'])  && $_GET['payment']  !== '' ? $_GET['payment']        : null;

/* Build JOINs/WHERE once so all queries respect filters */
$joins = [];
$wheres = [];        // for filters only
$params = [':f'=>$from, ':t'=>$to];  // detail/top-products use :f,:t + filters

if ($cat !== null) {
  $joins['p'] = "JOIN products p ON p.id = v.product_id";
  $wheres[]   = "p.category_id = :cat";
  $params[':cat'] = $cat;
}
if ($prod !== null) {
  $wheres[] = "v.product_id = :prod";
  $params[':prod'] = $prod;
}
if ($pay !== null) {
  $joins['o'] = "JOIN orders o ON o.id = v.order_id";
  $wheres[]   = "o.payment_method = :pay";
  $params[':pay'] = $pay;
}

$joins_sql    = implode(" ", $joins);
$filters_sql  = $wheres ? (" AND ".implode(" AND ", $wheres)) : "";

/* Aggregates must NOT include :f/:t. Make a copy of params without them. */
$paramsAgg = $params;
unset($paramsAgg[':f'], $paramsAgg[':t']);

/* helpers */
function q($dbh, $sql, $params = []) {
  $st = $dbh->prepare($sql);
  $st->execute($params);
  return $st->fetchAll(PDO::FETCH_ASSOC);
}

/* --- Picklists --- */
$categories = [];
try { $categories = q($dbh, "SELECT id,name FROM categories ORDER BY name"); } catch(Throwable $e) {}
if (!$categories) {
  $categories = q($dbh, "SELECT DISTINCT category_id AS id, CONCAT('Category ',category_id) AS name FROM products ORDER BY id");
}
$products = q($dbh, "SELECT id,name FROM products ORDER BY name");
$payments = q($dbh, "SELECT DISTINCT payment_method pm FROM orders WHERE payment_method IS NOT NULL AND payment_method<>'' ORDER BY pm");

/* --- Aggregates (weekly/monthly/yearly), honoring filters --- */
$weekly  = q($dbh, "
  SELECT YEARWEEK(v.ordered_at,3) AS yw,
         DATE_FORMAT(MIN(DATE_SUB(v.ordered_at, INTERVAL WEEKDAY(v.ordered_at) DAY)), '%Y-%m-%d') AS week_start,
         SUM(v.line_total) AS revenue, SUM(v.qty) AS units
  FROM v_sales_lines v
  $joins_sql
  WHERE 1=1 $filters_sql
    AND v.ordered_at >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK)
  GROUP BY yw ORDER BY yw DESC", $paramsAgg);

$monthly = q($dbh, "
  SELECT DATE_FORMAT(v.ordered_at,'%Y-%m') AS ym,
         SUM(v.line_total) AS revenue, SUM(v.qty) AS units
  FROM v_sales_lines v
  $joins_sql
  WHERE 1=1 $filters_sql
    AND v.ordered_at >= DATE_SUB(DATE_FORMAT(CURDATE(),'%Y-%m-01'), INTERVAL 12 MONTH)
  GROUP BY ym ORDER BY ym DESC", $paramsAgg);

$yearly  = q($dbh, "
  SELECT YEAR(v.ordered_at) AS yr,
         SUM(v.line_total) AS revenue, SUM(v.qty) AS units
  FROM v_sales_lines v
  $joins_sql
  WHERE 1=1 $filters_sql
    AND v.ordered_at >= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)
  GROUP BY yr ORDER BY yr DESC", $paramsAgg);

/* --- Current window detail (honors filters) --- */
$detail  = q($dbh, "
  SELECT v.ordered_at, v.order_id, v.product_name, v.qty, v.unit_price, v.line_total
  FROM v_sales_lines v
  $joins_sql
  WHERE v.ordered_at BETWEEN :f AND :t $filters_sql
  ORDER BY v.ordered_at DESC, v.order_id DESC", $params);

/* KPIs for window */
$ordersCount = count($detail);
$unitsSum    = 0.0;
$revenueSum  = 0.0;
foreach ($detail as $d) { $unitsSum += (float)$d['qty']; $revenueSum += (float)$d['line_total']; }

/* --- Prev period for deltas --- */
$fromDT = new DateTime($from, new DateTimeZone($tz));
$toDT   = new DateTime($to,   new DateTimeZone($tz));
$seconds = max(1, $toDT->getTimestamp() - $fromDT->getTimestamp());
$prevTo   = (clone $fromDT)->modify('-1 second');
$prevFrom = (clone $fromDT)->modify("-{$seconds} seconds");

$paramsPrev = $params; // carries filters + placeholders
$paramsPrev[':f'] = $prevFrom->format('Y-m-d H:i:s');
$paramsPrev[':t'] = $prevTo->format('Y-m-d H:i:s');

$prevDetail = q($dbh, "
  SELECT v.qty, v.line_total
  FROM v_sales_lines v
  $joins_sql
  WHERE v.ordered_at BETWEEN :f AND :t $filters_sql", $paramsPrev);
$prevOrders = count($prevDetail);
$prevUnits  = 0.0; $prevRevenue = 0.0;
foreach ($prevDetail as $d) { $prevUnits += (float)$d['qty']; $prevRevenue += (float)$d['line_total']; }
function pct($now,$prev){ if ($prev<=0) return $now>0?100:0; return (($now-$prev)/$prev)*100; }
$deltaRev   = pct($revenueSum,$prevRevenue);
$deltaUnits = pct($unitsSum,$prevUnits);
$deltaOrders= pct($ordersCount,$prevOrders);

/* --- Top products (in window) --- */
$topProducts = q($dbh, "
  SELECT v.product_id, v.product_name, SUM(v.qty) AS units, SUM(v.line_total) AS revenue
  FROM v_sales_lines v
  $joins_sql
  WHERE v.ordered_at BETWEEN :f AND :t $filters_sql
  GROUP BY v.product_id, v.product_name
  ORDER BY revenue DESC
  LIMIT 10", $params);

/* --- Inventory snapshot (category/product filters only) --- */
$invWhere = [];
$invParams = [];
if ($cat !== null) { $invWhere[] = "category_id = :cat"; $invParams[':cat'] = $cat; }
if ($prod !== null){ $invWhere[] = "id = :prod";        $invParams[':prod'] = $prod; }
$invWhereSql = $invWhere ? "WHERE ".implode(" AND ", $invWhere) : "";
$inv = q($dbh, "SELECT id AS product_id, name, stock, price FROM products $invWhereSql ORDER BY name", $invParams);

/* CSV link (propagate filters) */
$csv_query = http_build_query(['from'=>$from,'to'=>$to,'category'=>$cat,'product'=>$prod,'payment'=>$pay]);
$csv_url = "/admin/reports_export.php?$csv_query";

/* Charts data */
$weekly_rev = array_reverse(array_map(fn($w)=>round((float)$w['revenue'],2), $weekly));
$weekly_units = array_reverse(array_map(fn($w)=>(int)$w['units'], $weekly));
$weekly_labels = array_reverse(array_map(fn($w)=>$w['week_start'], $weekly));
$monthly_rev = array_reverse(array_map(fn($m)=>round((float)$m['revenue'],2), $monthly));
$monthly_labels = array_reverse(array_map(fn($m)=>$m['ym'], $monthly));

/* ---------- HTML BELOW (unchanged UI with charts & PDF header) ---------- */
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Reports · PalletPiks</title>
<style>
:root{ --bg:#0f172a; --panel:#0b1223; --text:#e5e7eb; --muted:#9ca3af; --border:rgba(255,255,255,.08);
  --accent:#6366f1; --accent-2:#22d3ee; --ok:#10b981; --warn:#f59e0b; --danger:#ef4444; }
@media (prefers-color-scheme: light){
  :root{ --bg:#f8fafc; --panel:#ffffff; --text:#0f172a; --muted:#475569; --border:rgba(15,23,42,.12); }
}
*{box-sizing:border-box}
body{margin:0;background:var(--bg);color:var(--text);font:15px/1.45 system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif}
.container{max-width:1200px;margin:24px auto;padding:0 16px}
h1{font-size:28px;margin:0 0 16px;font-weight:700;letter-spacing:.2px}
.panel{background:linear-gradient(180deg,rgba(255,255,255,.02),transparent 50%) , var(--panel);
  border:1px solid var(--border);border-radius:14px;padding:14px;box-shadow:0 6px 20px rgba(0,0,0,.16)}
.grid{display:grid;gap:14px}
.grid-3{grid-template-columns:repeat(3,minmax(0,1fr))}
.grid-2{grid-template-columns:repeat(2,minmax(0,1fr))}
@media (max-width:900px){ .grid-3,.grid-2{grid-template-columns:1fr} }
.filters{display:flex;gap:8px;align-items:center;margin:8px 0 18px;flex-wrap:wrap}
select,input,button,.btn{background:var(--panel);color:var(--text);border:1px solid var(--border);padding:8px 10px;border-radius:10px}
select:focus,input:focus,button:focus{outline:2px solid var(--accent);outline-offset:1px}
.btn{cursor:pointer;text-decoration:none}
.btn-primary{background:linear-gradient(90deg,var(--accent),var(--accent-2));border-color:transparent}
.btn-primary:hover{filter:brightness(.98)}
.btn-ghost{background:transparent}
.kpis{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-bottom:14px}
.kpi{background:radial-gradient(1200px 240px at -10% -50%, rgba(99,102,241,.22), transparent 60%), var(--panel);
  border:1px solid var(--border);border-radius:14px;padding:14px;display:flex;flex-direction:column;gap:6px;box-shadow:0 6px 20px rgba(0,0,0,.16)}
.kpi .label{font-size:12px;color:var(--muted);text-transform:uppercase;letter-spacing:.12em}
.kpi .value{font-size:26px;font-weight:750}
.kpi .delta{font-size:12px}
.up{color:var(--ok)} .down{color:var(--danger)}
.section-title{margin:6px 0 10px;font-size:18px;font-weight:700}
table{width:100%;border-collapse:separate;border-spacing:0;overflow:hidden}
th,td{padding:10px 12px;border-bottom:1px solid var(--border)}
th{font-weight:600;font-size:12.5px;text-transform:uppercase;letter-spacing:.08em;color:var(--muted);position:sticky;top:0;background:var(--panel);z-index:1}
tbody tr:hover{background:rgba(255,255,255,.03)} tbody tr:nth-child(even){background:rgba(255,255,255,.015)}
.badge{display:inline-block;padding:.2rem .5rem;border-radius:999px;border:1px solid var(--border);font-size:12px;color:var(--muted)}
footer{opacity:.7;font-size:12px;margin:24px 0 8px}
.chart-wrap{display:grid;grid-template-columns:1fr 1fr; gap:14px; margin:14px 0}
@media (max-width:900px){ .chart-wrap{grid-template-columns:1fr} }
canvas{background:var(--panel); border:1px solid var(--border); border-radius:14px; padding:8px}

/* Print branding */
@media print{
  body{background:#fff;color:#000}
  .filters,.btn,.badge,canvas{display:none!important}
  .print-header{display:block!important}
  .panel{box-shadow:none;border-color:#ddd}
}
.print-header{display:none; text-align:center; margin:0 0 10px}
.print-header img{max-height:56px; display:block; margin:0 auto 6px}
.print-header .sub{font-size:12px; color:#666}
</style>
</head>
<body>
  <!-- Print header -->
  <div class="print-header">
    <img src="<?=htmlspecialchars(LOGO_PATH)?>" alt="PalletPiks logo">
    <div class="sub">PalletPiks · Sales & Inventory Report · Period: <?=htmlspecialchars($from)?> → <?=htmlspecialchars($to)?></div>
  </div>

  <div class="container">
    <h1>Reports</h1>
<p style="margin:.25rem 0 .75rem"><a href="/admin/panel.php">&larr; Back to Admin</a></p>
    <form method="get" class="filters">
      <label class="badge">Period</label>
      <select name="period" onchange="this.form.submit()">
        <option value="week"  <?=$period==='week'?'selected':''?>>This week</option>
        <option value="month" <?=$period==='month'?'selected':''?>>This month</option>
        <option value="year"  <?=$period==='year'?'selected':''?>>This year</option>
        <option value="custom"<?=$period==='custom'?'selected':''?>>Custom</option>
      </select>

      <input type="datetime-local" name="from" value="<?=htmlspecialchars(str_replace(' ','T',$from))?>">
      <input type="datetime-local" name="to"   value="<?=htmlspecialchars(str_replace(' ','T',$to))?>">

      <select name="category">
        <option value="">All categories</option>
        <?php foreach($categories as $c): ?>
          <option value="<?=$c['id']?>" <?=$cat===(int)$c['id']?'selected':''?>><?=htmlspecialchars($c['name'])?></option>
        <?php endforeach; ?>
      </select>

      <select name="product">
        <option value="">All products</option>
        <?php foreach($products as $p): ?>
          <option value="<?=$p['id']?>" <?=$prod===(int)$p['id']?'selected':''?>><?=htmlspecialchars($p['name'])?></option>
        <?php endforeach; ?>
      </select>

      <select name="payment">
        <option value="">All payments</option>
        <?php foreach($payments as $pm): $v=$pm['pm']; ?>
          <option value="<?=htmlspecialchars($v)?>" <?=$pay===$v?'selected':''?>><?=htmlspecialchars($v)?></option>
        <?php endforeach; ?>
      </select>

      <button class="btn btn-primary">Apply</button>
      <a class="btn" href="<?=$csv_url?>">Export CSV</a>
      <button class="btn btn-ghost" type="button" onclick="window.print()">Download PDF</button>
      <span class="badge">Window: <?=htmlspecialchars($from)?> → <?=htmlspecialchars($to)?></span>
    </form>

    <!-- KPI cards -->
    <?php
      $classRev = ($deltaRev>=0?'up':'down');
      $classOrd = ($deltaOrders>=0?'up':'down');
      $classUnt = ($deltaUnits>=0?'up':'down');
    ?>
    <section class="kpis">
      <div class="kpi"><span class="label">Revenue</span><span class="value">$<?=number_format($revenueSum,2)?></span><span class="delta <?=$classRev?>"><?=sprintf("%+.1f%%",$deltaRev)?> vs prev</span></div>
      <div class="kpi"><span class="label">Orders</span><span class="value"><?=number_format($ordersCount)?></span><span class="delta <?=$classOrd?>"><?=sprintf("%+.1f%%",$deltaOrders)?> vs prev</span></div>
      <div class="kpi"><span class="label">Units</span><span class="value"><?=number_format($unitsSum)?></span><span class="delta <?=$classUnt?>"><?=sprintf("%+.1f%%",$deltaUnits)?> vs prev</span></div>
      <div class="kpi"><span class="label">Filters</span><span class="delta" style="color:var(--muted)"><?=
        ($cat!==null?'Cat#'.$cat.' ':'').
        ($prod!==null? 'Prod#'.$prod.' ':'').
        ($pay!==null?  'Pay='.htmlspecialchars($pay,ENT_QUOTES,'UTF-8'):'') ?: 'None'
      ?></span></div>
    </section>

    <!-- Charts -->
    <div class="chart-wrap">
      <div>
        <div class="section-title">Revenue & Units — Weekly (last 12)</div>
        <canvas id="chartWeekly" height="220"></canvas>
      </div>
      <div>
        <div class="section-title">Revenue — Monthly (last 12)</div>
        <canvas id="chartMonthly" height="220"></canvas>
      </div>
    </div>

    <!-- Tables -->
    <div class="grid grid-3">
      <div class="panel">
        <div class="section-title">Weekly (last 12)</div>
        <div style="overflow:auto; max-height:340px">
          <table><thead><tr><th>Week start</th><th>Revenue</th><th>Units</th></tr></thead><tbody>
            <?php foreach($weekly as $w): ?>
              <tr><td><?=$w['week_start']?></td><td>$<?=number_format($w['revenue'],2)?></td><td><?=number_format($w['units'])?></td></tr>
            <?php endforeach; ?>
          </tbody></table>
        </div>
      </div>
      <div class="panel">
        <div class="section-title">Monthly (last 12)</div>
        <div style="overflow:auto; max-height:340px">
          <table><thead><tr><th>Month</th><th>Revenue</th><th>Units</th></tr></thead><tbody>
            <?php foreach($monthly as $m): ?>
              <tr><td><?=$m['ym']?></td><td>$<?=number_format($m['revenue'],2)?></td><td><?=number_format($m['units'])?></td></tr>
            <?php endforeach; ?>
          </tbody></table>
        </div>
      </div>
      <div class="panel">
        <div class="section-title">Yearly (last 5)</div>
        <div style="overflow:auto; max-height:340px">
          <table><thead><tr><th>Year</th><th>Revenue</th><th>Units</th></tr></thead><tbody>
            <?php foreach($yearly as $y): ?>
              <tr><td><?=$y['yr']?></td><td>$<?=number_format($y['revenue'],2)?></td><td><?=number_format($y['units'])?></td></tr>
            <?php endforeach; ?>
          </tbody></table>
        </div>
      </div>
    </div>

    <div style="height:14px"></div>

    <div class="grid grid-2">
      <div class="panel">
        <div class="section-title">Top Products (in period)</div>
        <table>
          <thead><tr><th>Product</th><th>Units</th><th>Revenue</th></tr></thead>
          <tbody>
          <?php foreach($topProducts as $p): ?>
            <tr><td><?=htmlspecialchars($p['product_name'])?></td><td><?=number_format($p['units'])?></td><td>$<?=number_format($p['revenue'],2)?></td></tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="panel">
        <div class="section-title">Inventory (current)</div>
        <div style="overflow:auto; max-height:420px">
          <table>
            <thead><tr><th>Product</th><th>In Stock</th><th>Price</th></tr></thead>
            <tbody>
            <?php foreach($inv as $p): ?>
              <tr><td><?=htmlspecialchars($p['name'])?></td><td><?=number_format($p['stock'])?></td><td>$<?=number_format($p['price'],2)?></td></tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div style="height:14px"></div>

    <div class="panel">
      <div class="section-title">Earnings (detail)</div>
      <div style="overflow:auto; max-height:520px">
        <table>
          <thead><tr><th>Date/Time</th><th>Order #</th><th>Product</th><th>Qty</th><th>Unit $</th><th>Line $</th></tr></thead>
          <tbody>
          <?php foreach($detail as $d): ?>
            <tr>
              <td><?=htmlspecialchars($d['ordered_at'])?></td>
              <td><a style="color:var(--accent)" href="/admin/order_view.php?id=<?=$d['order_id']?>"><?=$d['order_id']?></a></td>
              <td><?=htmlspecialchars($d['product_name'])?></td>
              <td><?=number_format($d['qty'])?></td>
              <td>$<?=number_format($d['unit_price'],2)?></td>
              <td>$<?=number_format($d['line_total'],2)?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <footer>© <?=date('Y')?> PalletPiks • Reports</footer>
  </div>

  <!-- Chart.js CDN -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
    const weeklyLabels  = <?=json_encode($weekly_labels)?>;
    const weeklyRevenue = <?=json_encode($weekly_rev)?>;
    const weeklyUnits   = <?=json_encode($weekly_units)?>;
    const monthlyLabels = <?=json_encode($monthly_labels)?>;
    const monthlyRevenue= <?=json_encode($monthly_rev)?>;

    const gridColor = getComputedStyle(document.documentElement).getPropertyValue('--border') || 'rgba(0,0,0,.1)';
    const textColor = getComputedStyle(document.documentElement).getPropertyValue('--muted') || '#6b7280';

    new Chart(document.getElementById('chartWeekly'), {
      type: 'line',
      data: {
        labels: weeklyLabels,
        datasets: [
          { label: 'Revenue', data: weeklyRevenue, borderColor: '#22d3ee', backgroundColor: 'rgba(34,211,238,.2)', tension:.35, fill:true, borderWidth:2 },
          { label: 'Units',   data: weeklyUnits,   borderColor: '#6366f1', backgroundColor: 'rgba(99,102,241,.15)', tension:.35, fill:false, borderWidth:2, yAxisID: 'y1' },
        ]
      },
      options: {
        plugins: { legend: { labels:{ color:textColor } } },
        scales: {
          x: { grid:{ color:gridColor }, ticks:{ color:textColor } },
          y: { grid:{ color:gridColor }, ticks:{ color:textColor }, title:{ display:true, text:'$' , color:textColor } },
          y1:{ position:'right', grid:{ drawOnChartArea:false }, ticks:{ color:textColor }, title:{ display:true, text:'Units', color:textColor } }
        }
      }
    });

    new Chart(document.getElementById('chartMonthly'), {
      type: 'bar',
      data: { labels: monthlyLabels, datasets: [{ label:'Revenue', data: monthlyRevenue, backgroundColor: 'rgba(99,102,241,.6)', borderColor:'#6366f1', borderWidth:1.5, borderRadius:8 }] },
      options: {
        plugins: { legend: { labels:{ color:textColor } } },
        scales: {
          x: { grid:{ display:false }, ticks:{ color:textColor } },
          y: { grid:{ color:gridColor }, ticks:{ color:textColor }, title:{ display:true, text:'$', color:textColor } }
        }
      }
    });
  </script>
</body>
</html>
