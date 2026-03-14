<?php
$page_title = 'Analytics & Reports';
$page_sub   = 'Visual summaries of overall farm operations.';
require_once '../db/connection.php';
require_once 'sidebar.php';

$date_from = $_GET['from'] ?? date('Y-m-01');
$date_to   = $_GET['to']   ?? date('Y-m-d');

$from = $pdo->quote($date_from);
$to   = $pdo->quote($date_to);

$feed_daily = $pdo->query("
    SELECT usage_date, ROUND(SUM(used_kg)*1000) AS grams
    FROM feed_usage
    WHERE usage_date BETWEEN $from AND $to
    GROUP BY usage_date ORDER BY usage_date ASC
")->fetchAll(PDO::FETCH_ASSOC);

$revenue_daily = $pdo->query("
    SELECT order_date, SUM(total_price) AS revenue, COUNT(*) AS cnt
    FROM orders
    WHERE order_date BETWEEN $from AND $to
    GROUP BY order_date ORDER BY order_date ASC
")->fetchAll(PDO::FETCH_ASSOC);

$orders_daily = $pdo->query("
    SELECT order_date, COUNT(*) AS cnt, SUM(quantity_kg) AS total_kg, SUM(total_price) AS total_rev
    FROM orders
    WHERE order_date BETWEEN $from AND $to
    GROUP BY order_date ORDER BY order_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

$top_staff = $pdo->query("
    SELECT
        CONCAT(s.firstname, ' ', s.lastname) AS staff_name,
        (SELECT COUNT(*) FROM feed_usage fu WHERE fu.logged_by = s.id AND fu.usage_date BETWEEN $from AND $to) AS feed_count,
        (SELECT COUNT(*) FROM orders o WHERE o.logged_by = s.id AND o.order_date BETWEEN $from AND $to) AS order_count
    FROM staff s
    ORDER BY (feed_count + order_count) DESC
")->fetchAll(PDO::FETCH_ASSOC);

$best_selling = $pdo->query("
    SELECT
        hito_type,
        COUNT(*) AS order_count,
        SUM(quantity_kg) AS total_kg,
        SUM(total_price) AS total_revenue
    FROM orders
    WHERE order_date BETWEEN $from AND $to
    GROUP BY hito_type
    ORDER BY total_kg DESC
")->fetchAll(PDO::FETCH_ASSOC);

$totals = $pdo->query("
    SELECT
        (SELECT COALESCE(SUM(total_price),0) FROM orders WHERE order_date BETWEEN $from AND $to) AS total_revenue,
        (SELECT COUNT(*) FROM orders WHERE order_date BETWEEN $from AND $to) AS total_orders,
        (SELECT ROUND(COALESCE(SUM(used_kg),0)*1000) FROM feed_usage WHERE usage_date BETWEEN $from AND $to) AS total_feed_g,
        (SELECT COUNT(*) FROM staff) AS total_staff
")->fetch(PDO::FETCH_ASSOC);
?>

<style>
    .toolbar { display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:24px; }

    .filter-form { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    .filter-form label { font-size:11px; font-weight:700; color:#aaa; letter-spacing:.08em; text-transform:uppercase; }
    .filter-form input[type=date] {
        border:1px solid #e2e2e6; background:#fafafa; font-family:inherit;
        font-size:12px; color:#111; padding:7px 10px; outline:none; border-radius:0;
        transition:border-color .2s; cursor:pointer;
    }
    .filter-form input[type=date]:focus { border-color:#111; background:#fff; }
    .filter-sep { font-size:11px; color:#ccc; }

    .btn-filter { padding:7px 14px; background:#111; color:#fff; font-family:inherit; font-size:11px; font-weight:600; letter-spacing:.06em; border:none; cursor:pointer; transition:opacity .2s; }
    .btn-filter:hover { opacity:.8; }
    .btn-print { display:flex; align-items:center; gap:7px; padding:7px 16px; background:#1a1a2e; color:#fff; font-family:inherit; font-size:11px; font-weight:600; letter-spacing:.06em; border:none; cursor:pointer; transition:opacity .2s; }
    .btn-print:hover { opacity:.82; }

    .range-badge { display:inline-flex; align-items:center; gap:6px; background:#f0f0f2; color:#555; font-size:11px; font-weight:600; padding:5px 12px; }

    .overview-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:28px; }
    .ov-card { background:#fff; border:1px solid #e2e2e6; padding:20px; }
    .ov-icon { width:32px; height:32px; background:#f0f0f2; display:flex; align-items:center; justify-content:center; font-size:13px; color:#888; margin-bottom:12px; }
    .ov-val { font-size:22px; font-weight:700; color:#111; line-height:1; margin-bottom:3px; }
    .ov-val small { font-size:12px; font-weight:400; color:#aaa; }
    .ov-label { font-size:10px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:#bbb; }

    .analytics-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
    .section-card { background:#fff; border:1px solid #e2e2e6; }
    .sc-head { padding:14px 18px 12px; border-bottom:1px solid #f0f0f2; display:flex; align-items:baseline; justify-content:space-between; }
    .sc-head h3 { font-size:11px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:#888; }
    .sc-head span { font-size:11px; color:#ccc; }

    table { width:100%; border-collapse:collapse; font-size:13px; }
    th { padding:10px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:#bbb; border-bottom:1px solid #f0f0f2; white-space:nowrap; }
    td { padding:11px 16px; color:#555; border-bottom:1px solid #f8f8f8; vertical-align:middle; }
    tbody tr:last-child td { border-bottom:none; }
    tbody tr:hover td { background:#fafafa; }

    .val-bold { font-weight:700; color:#111; }
    .green { color:#16a34a; font-weight:700; }
    .navy  { color:#1a1a2e; font-weight:700; }
    .badge-sm { display:inline-flex; align-items:center; gap:4px; background:#f0f0f2; color:#555; font-size:10px; font-weight:600; padding:2px 8px; border-radius:20px; }
    .rank-num { display:inline-flex; align-items:center; justify-content:center; width:22px; height:22px; background:#f0f0f2; font-size:11px; font-weight:700; color:#888; border-radius:50%; }
    .rank-num.gold { background:#1a1a2e; color:#fff; }
    .staff-init-sm { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; background:#111; color:#fff; font-size:10px; font-weight:700; border-radius:50%; margin-right:8px; }
    .bar-wrap { display:flex; align-items:center; gap:8px; }
    .mini-bar { flex:1; height:5px; background:#f0f0f2; border-radius:3px; overflow:hidden; }
    .mini-bar-fill { height:100%; background:#1a1a2e; border-radius:3px; }
    .bar-label { font-size:10px; color:#aaa; white-space:nowrap; }
    .empty-row td { text-align:center; color:#ccc; padding:28px; font-size:12px; }

    /* ── PRINT ── */
    .print-header { display:none; }
    .print-footer { display:none; }

    @media print {
        * { -webkit-print-color-adjust:exact !important; print-color-adjust:exact !important; }

        .sidebar, .sidebar-overlay, nav, .toolbar, .no-print,
        .page-header,
        .ov-icon, .mini-bar, .bar-wrap, .badge-sm, .staff-init-sm, .rank-num { display:none !important; }

        body { background:#fff !important; font-family:'Segoe UI',Tahoma,sans-serif !important; color:#111 !important; }
        .page-wrap { padding:0 !important; max-width:100% !important; margin:0 !important; }

        .print-header { display:block !important; }
        .print-footer { display:block !important; }

        .overview-grid {
            display:grid !important;
            grid-template-columns:repeat(4,1fr) !important;
            gap:8px !important;
            margin-bottom:18px !important;
        }
        .ov-card {
            border:1.5px solid #111 !important;
            padding:10px 14px !important;
            text-align:center !important;
            background:#fff !important;
        }
        .ov-val { font-size:16pt !important; font-weight:700 !important; }
        .ov-val small { font-size:9pt !important; }
        .ov-label { font-size:7pt !important; letter-spacing:.1em !important; color:#555 !important; }

        .analytics-grid {
            display:grid !important;
            grid-template-columns:1fr 1fr !important;
            gap:12px !important;
            margin-bottom:14px !important;
        }
        .section-card { border:1px solid #bbb !important; break-inside:avoid !important; background:#fff !important; }

        .sc-head { background:#1a1a2e !important; padding:7px 12px !important; border-bottom:none !important; }
        .sc-head h3 { color:#fff !important; font-size:8pt !important; letter-spacing:.1em !important; }
        .sc-head span { color:rgba(255,255,255,.5) !important; font-size:8pt !important; }

        table { width:100% !important; border-collapse:collapse !important; font-size:9pt !important; }
        th { background:#f4f4f4 !important; padding:6px 10px !important; font-size:7.5pt !important; border-bottom:1px solid #ccc !important; color:#333 !important; }
        td { padding:6px 10px !important; border-bottom:1px solid #eee !important; color:#111 !important; }
        tbody tr:last-child td { border-bottom:none !important; }
        tbody tr:nth-child(even) td { background:#fafafa !important; }
        tbody tr:hover td { background:inherit !important; }

        .green { color:#16a34a !important; }
        .navy  { color:#1a1a2e !important; }
        .val-bold { font-weight:700 !important; }
        .empty-row td { color:#aaa !important; font-style:italic !important; }

        @page { size:A4 landscape; margin:15mm 12mm; }
    }

    @media(max-width:900px) { .overview-grid { grid-template-columns:1fr 1fr; } .analytics-grid { grid-template-columns:1fr; } }
    @media(max-width:480px) { .overview-grid { grid-template-columns:1fr; } }
</style>

<!-- PRINT HEADER -->
<div class="print-header" style="margin-bottom:20px;">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;padding-bottom:12px;border-bottom:3px solid #1a1a2e;">
        <div>
            <div style="font-size:20pt;font-weight:800;color:#1a1a2e;letter-spacing:-.01em;line-height:1;">LRS Hito Farm</div>
            <div style="font-size:9pt;color:#666;margin-top:3px;letter-spacing:.04em;">OPERATIONS ANALYTICS REPORT</div>
        </div>
        <div style="text-align:right;">
            <div style="font-size:9pt;color:#888;">Report Period</div>
            <div style="font-size:11pt;font-weight:700;color:#111;"><?= date('M d, Y', strtotime($date_from)) ?> &ndash; <?= date('M d, Y', strtotime($date_to)) ?></div>
            <div style="font-size:8pt;color:#aaa;margin-top:3px;">Generated: <?= date('F d, Y \a\t h:i A') ?></div>
        </div>
    </div>
</div>

<!-- TOOLBAR -->
<div class="toolbar no-print">
    <div class="range-badge">
        <i class="fa-solid fa-calendar-range"></i>
        <?= date('M d, Y', strtotime($date_from)) ?> – <?= date('M d, Y', strtotime($date_to)) ?>
    </div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <form method="GET" class="filter-form">
            <label>From</label>
            <input type="date" name="from" value="<?= $date_from ?>">
            <span class="filter-sep">–</span>
            <label>To</label>
            <input type="date" name="to" value="<?= $date_to ?>">
            <button type="submit" class="btn-filter"><i class="fa-solid fa-magnifying-glass"></i> Apply</button>
        </form>
        <button class="btn-print" onclick="window.print()">
            <i class="fa-solid fa-print"></i> Print Report
        </button>
    </div>
</div>

<!-- OVERVIEW -->
<div class="overview-grid">
    <div class="ov-card">
        <div class="ov-icon"><i class="fa-solid fa-peso-sign"></i></div>
        <div class="ov-val">₱<?= number_format($totals['total_revenue'], 2) ?></div>
        <div class="ov-label">Total Revenue</div>
    </div>
    <div class="ov-card">
        <div class="ov-icon"><i class="fa-solid fa-cart-shopping"></i></div>
        <div class="ov-val"><?= number_format($totals['total_orders']) ?></div>
        <div class="ov-label">Total Orders</div>
    </div>
    <div class="ov-card">
        <div class="ov-icon"><i class="fa-solid fa-bowl-food"></i></div>
        <div class="ov-val"><?= number_format($totals['total_feed_g']) ?><small> g</small></div>
        <div class="ov-label">Total Feed Used</div>
    </div>
    <div class="ov-card">
        <div class="ov-icon"><i class="fa-solid fa-users"></i></div>
        <div class="ov-val"><?= $totals['total_staff'] ?></div>
        <div class="ov-label">Active Staff</div>
    </div>
</div>

<!-- ROW 1 -->
<div class="analytics-grid">
    <div class="section-card">
        <div class="sc-head">
            <h3><i class="fa-solid fa-bowl-food" style="margin-right:6px;color:#ccc"></i>Feed Usage</h3>
            <span><?= date('M d', strtotime($date_from)) ?> – <?= date('M d', strtotime($date_to)) ?></span>
        </div>
        <table>
            <thead><tr><th>Date</th><th>Amount Used</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($feed_daily)): ?>
                <tr class="empty-row"><td colspan="3">No feed usage for this period.</td></tr>
            <?php else:
                $max_f = max(array_column($feed_daily,'grams')) ?: 1;
                foreach ($feed_daily as $r): ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($r['usage_date'])) ?></td>
                    <td class="val-bold"><?= number_format($r['grams']) ?> g</td>
                    <td style="width:100px;">
                        <div class="bar-wrap">
                            <div class="mini-bar"><div class="mini-bar-fill" style="width:<?= round(($r['grams']/$max_f)*100) ?>%"></div></div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section-card">
        <div class="sc-head">
            <h3><i class="fa-solid fa-peso-sign" style="margin-right:6px;color:#ccc"></i>Revenue</h3>
            <span><?= date('M d', strtotime($date_from)) ?> – <?= date('M d', strtotime($date_to)) ?></span>
        </div>
        <table>
            <thead><tr><th>Date</th><th>Revenue</th><th>Orders</th></tr></thead>
            <tbody>
            <?php if (empty($revenue_daily)): ?>
                <tr class="empty-row"><td colspan="3">No orders for this period.</td></tr>
            <?php else: foreach ($revenue_daily as $r): ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($r['order_date'])) ?></td>
                    <td class="green">₱<?= number_format($r['revenue'], 2) ?></td>
                    <td><span class="badge-sm"><i class="fa-solid fa-cart-shopping"></i> <?= $r['cnt'] ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ROW 2 -->
<div class="analytics-grid">
    <div class="section-card">
        <div class="sc-head">
            <h3><i class="fa-solid fa-cart-shopping" style="margin-right:6px;color:#ccc"></i>Orders Summary</h3>
            <span><?= date('M d', strtotime($date_from)) ?> – <?= date('M d', strtotime($date_to)) ?></span>
        </div>
        <table>
            <thead><tr><th>Date</th><th>Orders</th><th>Qty Sold</th><th>Revenue</th></tr></thead>
            <tbody>
            <?php if (empty($orders_daily)): ?>
                <tr class="empty-row"><td colspan="4">No orders for this period.</td></tr>
            <?php else: foreach ($orders_daily as $r): ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($r['order_date'])) ?></td>
                    <td class="val-bold"><?= $r['cnt'] ?></td>
                    <td><?= number_format($r['total_kg'], 2) ?> kg</td>
                    <td class="green">₱<?= number_format($r['total_rev'], 2) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section-card">
        <div class="sc-head">
            <h3><i class="fa-solid fa-users" style="margin-right:6px;color:#ccc"></i>Top Staff</h3>
            <span>By activity count</span>
        </div>
        <table>
            <thead><tr><th>#</th><th>Staff</th><th>Feed Logs</th><th>Orders</th><th>Total</th></tr></thead>
            <tbody>
            <?php if (empty($top_staff)): ?>
                <tr class="empty-row"><td colspan="5">No staff activity yet.</td></tr>
            <?php else: foreach ($top_staff as $i => $s):
                $parts    = explode(' ', $s['staff_name']);
                $initials = strtoupper(substr($parts[0],0,1).substr($parts[1]??'',0,1));
                $total    = $s['feed_count'] + $s['order_count'];
            ?>
                <tr>
                    <td><span class="rank-num <?= $i===0 && $total>0 ? 'gold':'' ?>"><?= $i+1 ?></span></td>
                    <td>
                        <span class="staff-init-sm"><?= $initials ?></span>
                        <span style="font-weight:600;color:#111;"><?= htmlspecialchars($s['staff_name']) ?></span>
                    </td>
                    <td><?= $s['feed_count'] ?></td>
                    <td><?= $s['order_count'] ?></td>
                    <td class="navy"><?= $total ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ROW 3: Best Selling -->
<div class="analytics-grid full" style="grid-template-columns:1fr;">
    <div class="section-card">
        <div class="sc-head">
            <h3><i class="fa-solid fa-fish" style="margin-right:6px;color:#ccc"></i>Best Selling Hito</h3>
            <span><?= date('M d', strtotime($date_from)) ?> – <?= date('M d', strtotime($date_to)) ?></span>
        </div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Hito Type</th>
                    <th>Orders</th>
                    <th>Total Sold (kg)</th>
                    <th>Revenue</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($best_selling)): ?>
                <tr class="empty-row"><td colspan="6">No orders for this period.</td></tr>
            <?php else:
                $max_kg = max(array_column($best_selling, 'total_kg')) ?: 1;
                foreach ($best_selling as $i => $b): ?>
                <tr>
                    <td><span class="rank-num <?= $i === 0 ? 'gold' : '' ?>"><?= $i + 1 ?></span></td>
                    <td class="val-bold"><?= htmlspecialchars($b['hito_type']) ?></td>
                    <td><span class="badge-sm"><i class="fa-solid fa-cart-shopping"></i> <?= $b['order_count'] ?></span></td>
                    <td class="val-bold"><?= number_format($b['total_kg'], 2) ?> kg</td>
                    <td class="green">₱<?= number_format($b['total_revenue'], 2) ?></td>
                    <td style="width:140px;">
                        <div class="bar-wrap">
                            <div class="mini-bar"><div class="mini-bar-fill" style="width:<?= round(($b['total_kg'] / $max_kg) * 100) ?>%"></div></div>
                            <span class="bar-label"><?= round(($b['total_kg'] / $max_kg) * 100) ?>%</span>
                        </div>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- PRINT FOOTER -->
<div class="print-footer" style="margin-top:24px;padding-top:10px;border-top:1px solid #ccc;display:flex;justify-content:space-between;font-size:8pt;color:#aaa;">
    <span>LRS Hito Farm &mdash; Confidential</span>
    <span>Generated by Hito Farm Management System &nbsp;|&nbsp; <?= date('M d, Y h:i A') ?></span>
</div>

<?php require_once 'sidebar_end.php'; ?>