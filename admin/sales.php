<?php
$page_title = 'Sales & Orders';
$page_sub   = 'View all recorded customer orders.';
require_once '../db/connection.php';
require_once 'sidebar.php';

$filter_date = trim($_GET['date'] ?? '');

$sql = "
    SELECT o.*, CONCAT(s.firstname, ' ', s.lastname) AS staff_name
    FROM orders o
    JOIN staff s ON o.logged_by = s.id
";
if ($filter_date) {
    $sql .= " WHERE o.order_date = " . $pdo->quote($filter_date);
}
$sql .= " ORDER BY o.order_date DESC, o.created_at DESC";

$orders = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$summary_sql = "SELECT COUNT(*) AS total_orders, COALESCE(SUM(quantity_kg),0) AS total_kg, COALESCE(SUM(total_price),0) AS total_revenue FROM orders";
if ($filter_date) $summary_sql .= " WHERE order_date = " . $pdo->quote($filter_date);
$summary = $pdo->query($summary_sql)->fetch(PDO::FETCH_ASSOC);
?>

<style>
    .summary-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:24px; }
    .summary-card { background:#fff; border:1px solid #e2e2e6; padding:20px 22px; }
    .sc-val { font-size:24px; font-weight:700; color:#111; line-height:1; margin-bottom:4px; }
    .sc-val small { font-size:12px; font-weight:400; color:#aaa; }
    .sc-label { font-size:10px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:#bbb; }

    .toolbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; gap:12px; flex-wrap:wrap; }
    .count-label { font-size:11px; color:#aaa; }

    .filter-wrap { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    .filter-wrap label { font-size:11px; font-weight:600; color:#aaa; letter-spacing:.06em; text-transform:uppercase; }
    .filter-wrap input[type=date] {
        border:1px solid #e2e2e6; background:#fafafa; font-family:inherit;
        font-size:12px; color:#111; padding:7px 10px; outline:none; border-radius:0;
        transition:border-color .2s; cursor:pointer;
    }
    .filter-wrap input[type=date]:focus { border-color:#111; background:#fff; }
    .btn-filter { padding:7px 14px; background:#111; color:#fff; font-family:inherit; font-size:11px; font-weight:600; letter-spacing:.06em; border:none; cursor:pointer; transition:opacity .2s; }
    .btn-filter:hover { opacity:.8; }
    .btn-clear { padding:7px 12px; background:#fff; color:#888; font-family:inherit; font-size:11px; font-weight:600; border:1px solid #e2e2e6; cursor:pointer; text-decoration:none; display:flex; align-items:center; gap:5px; transition:background .15s; }
    .btn-clear:hover { background:#f4f4f4; }
    .filter-active { display:inline-flex; align-items:center; gap:6px; background:#f0f0f2; color:#555; font-size:11px; font-weight:600; padding:4px 10px; border-radius:20px; }

    .table-wrap { background:#fff; border:1px solid #e2e2e6; overflow-x:auto; }
    table { width:100%; border-collapse:collapse; font-size:13px; }
    thead tr { border-bottom:1px solid #e2e2e6; background:#f9f9fb; }
    th { padding:11px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:#aaa; white-space:nowrap; }
    tbody tr { border-bottom:1px solid #f0f0f2; transition:background .1s; }
    tbody tr:last-child { border-bottom:none; }
    tbody tr:hover { background:#fafafa; }
    td { padding:12px 16px; color:#444; vertical-align:middle; }
    .val-bold { font-weight:700; color:#111; }
    .revenue { font-weight:700; color:#16a34a; }
    .no-customer { color:#ccc; font-size:12px; font-style:italic; }
    .customer-cell { font-weight:600; color:#111; }
    .type-badge { display:inline-block; background:#f0f0f2; color:#444; font-size:11px; font-weight:600; padding:3px 10px; border-radius:20px; white-space:nowrap; }

    .staff-cell { display:flex; align-items:center; gap:8px; }
    .staff-init { width:26px; height:26px; background:#111; color:#fff; font-size:10px; font-weight:700; display:flex; align-items:center; justify-content:center; border-radius:50%; flex-shrink:0; }

    .empty-state { text-align:center; padding:50px 20px; color:#ccc; background:#fff; border:1px solid #e2e2e6; }
    .empty-state i { font-size:32px; margin-bottom:10px; display:block; }
    .empty-state p { font-size:13px; }

    @media(max-width:600px) { .summary-grid { grid-template-columns:1fr; } }
</style>

<div class="summary-grid">
    <div class="summary-card">
        <div class="sc-val"><?= number_format($summary['total_orders']) ?></div>
        <div class="sc-label"><?= $filter_date ? 'Orders on this day' : 'Total Orders' ?></div>
    </div>
    <div class="summary-card">
        <div class="sc-val"><?= number_format($summary['total_kg'], 1) ?><small> kg</small></div>
        <div class="sc-label"><?= $filter_date ? 'Sold on this day' : 'Total Sold' ?></div>
    </div>
    <div class="summary-card">
        <div class="sc-val">₱<?= number_format($summary['total_revenue'], 2) ?></div>
        <div class="sc-label"><?= $filter_date ? 'Revenue on this day' : 'Total Revenue' ?></div>
    </div>
</div>

<div class="toolbar">
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <span class="count-label"><?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?></span>
        <?php if ($filter_date): ?>
            <span class="filter-active"><i class="fa-solid fa-calendar-day"></i> <?= date('M d, Y', strtotime($filter_date)) ?></span>
        <?php endif; ?>
    </div>

    <form method="GET" class="filter-wrap">
        <label>Filter</label>
        <input type="date" name="date" value="<?= htmlspecialchars($filter_date) ?>">
        <button type="submit" class="btn-filter"><i class="fa-solid fa-magnifying-glass"></i> Filter</button>
        <?php if ($filter_date): ?>
            <a href="sales.php" class="btn-clear"><i class="fa-solid fa-xmark"></i> Clear</a>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($orders)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-cart-shopping"></i>
        <p><?= $filter_date ? 'No orders found on this date.' : 'No orders recorded yet.' ?></p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Type</th>
                    <th>Quantity</th>
                    <th>Price / kg</th>
                    <th>Total</th>
                    <th>Recorded By</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o):
                    $parts    = explode(' ', $o['staff_name']);
                    $initials = strtoupper(substr($parts[0],0,1).substr($parts[1]??'',0,1));
                ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($o['order_date'])) ?></td>
                    <td>
                        <?php if ($o['customer_name']): ?>
                            <span class="customer-cell"><?= htmlspecialchars($o['customer_name']) ?></span>
                        <?php else: ?>
                            <span class="no-customer">N/A</span>
                        <?php endif; ?>
                    </td>
                    <td><span class="type-badge"><?= htmlspecialchars($o['hito_type']) ?></span></td>
                    <td class="val-bold"><?= number_format($o['quantity_kg'], 2) ?> kg</td>
                    <td>₱<?= number_format($o['price_per_kg'], 2) ?></td>
                    <td class="revenue">₱<?= number_format($o['total_price'], 2) ?></td>
                    <td>
                        <div class="staff-cell">
                            <div class="staff-init"><?= $initials ?></div>
                            <span><?= htmlspecialchars($o['staff_name']) ?></span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once 'sidebar_end.php'; ?>