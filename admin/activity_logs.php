<?php
$page_title = 'Activity Logs';
$page_sub   = '';
require_once '../db/connection.php';
require_once 'sidebar.php';

$filter_date = trim($_GET['date'] ?? '');
$filter_type = trim($_GET['type'] ?? '');

$logs = [];

if ($filter_type !== 'order') {
    $sql = "
        SELECT
            'feed' AS log_type,
            fu.usage_date AS log_date,
            fu.created_at,
            ROUND(fu.used_kg * 1000) AS used_grams,
            NULL AS quantity_kg,
            NULL AS price_per_kg,
            NULL AS total_price,
            NULL AS customer_name,
            CONCAT(s.firstname, ' ', s.lastname) AS staff_name
        FROM feed_usage fu
        JOIN staff s ON fu.logged_by = s.id
    ";
    if ($filter_date) $sql .= " WHERE fu.usage_date = " . $pdo->quote($filter_date);
    $feed_logs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $logs = array_merge($logs, $feed_logs);
}

if ($filter_type !== 'feed') {
    $sql = "
        SELECT
            'order' AS log_type,
            o.order_date AS log_date,
            o.created_at,
            NULL AS used_grams,
            o.quantity_kg,
            o.price_per_kg,
            o.total_price,
            o.customer_name,
            CONCAT(s.firstname, ' ', s.lastname) AS staff_name
        FROM orders o
        JOIN staff s ON o.logged_by = s.id
    ";
    if ($filter_date) $sql .= " WHERE o.order_date = " . $pdo->quote($filter_date);
    $order_logs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    $logs = array_merge($logs, $order_logs);
}

usort($logs, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
?>

<style>
    .toolbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; gap:12px; flex-wrap:wrap; }
    .count-label { font-size:11px; color:#aaa; }

    .filters { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }
    .filters label { font-size:11px; font-weight:600; color:#aaa; letter-spacing:.06em; text-transform:uppercase; }

    .filter-wrap input[type=date], .type-select {
        border:1px solid #e4e4e4;
        background:#fafafa;
        font-family:inherit;
        font-size:12px;
        color:#111;
        padding:7px 10px;
        outline:none;
        border-radius:0;
        cursor:pointer;
        transition:border-color .2s;
        appearance:none;
    }
    .filter-wrap input[type=date]:focus, .type-select:focus { border-color:#111; background:#fff; }

    .btn-filter { padding:7px 14px; background:#111; color:#fff; font-family:inherit; font-size:11px; font-weight:600; letter-spacing:.06em; border:none; cursor:pointer; transition:opacity .2s; }
    .btn-filter:hover { opacity:.8; }

    .btn-clear { padding:7px 12px; background:#fff; color:#888; font-family:inherit; font-size:11px; font-weight:600; border:1px solid #e4e4e4; cursor:pointer; text-decoration:none; display:flex; align-items:center; gap:5px; transition:background .15s; }
    .btn-clear:hover { background:#f4f4f4; }

    .filter-active { display:inline-flex; align-items:center; gap:6px; background:#f0f0f0; color:#555; font-size:11px; font-weight:600; padding:4px 10px; border-radius:20px; }

    .table-wrap { background:#fff; border:1px solid #e4e4e4; overflow-x:auto; }
    table { width:100%; border-collapse:collapse; font-size:13px; }
    thead tr { border-bottom:1px solid #e4e4e4; background:#f9f9f9; }
    th { padding:11px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:#aaa; white-space:nowrap; }
    tbody tr { border-bottom:1px solid #f0f0f0; transition:background .1s; }
    tbody tr:last-child { border-bottom:none; }
    tbody tr:hover { background:#fafafa; }
    td { padding:12px 16px; color:#444; vertical-align:middle; }

    .staff-cell { display:flex; align-items:center; gap:10px; }
    .staff-init { width:30px; height:30px; background:#111; color:#fff; font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; border-radius:50%; flex-shrink:0; }
    .staff-full { font-weight:600; color:#111; font-size:13px; }

    .date-cell { font-size:13px; color:#444; }
    .time-cell { font-size:11px; color:#bbb; margin-top:2px; }

    .badge-feed { display:inline-flex; align-items:center; gap:6px; background:#f0f0f0; color:#444; font-size:11px; font-weight:600; padding:4px 10px; border-radius:2px; }
    .badge-feed i { color:#888; font-size:10px; }
    .badge-order { display:inline-flex; align-items:center; gap:6px; background:#f0f0f0; color:#444; font-size:11px; font-weight:600; padding:4px 10px; border-radius:2px; }
    .badge-order i { color:#888; font-size:10px; }

    .detail-cell { font-size:12px; color:#555; }
    .detail-cell strong { color:#111; font-weight:600; }
    .no-customer { color:#bbb; font-size:11px; font-style:italic; }

    .grams-val { font-weight:700; color:#555; }
    .revenue-val { font-weight:700; color:#16a34a; }

    .empty-state { text-align:center; padding:50px 20px; color:#ccc; background:#fff; border:1px solid #e4e4e4; }
    .empty-state i { font-size:32px; margin-bottom:10px; display:block; }
    .empty-state p { font-size:13px; }
</style>

<div class="toolbar">
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
        <span class="count-label"><?= count($logs) ?> record<?= count($logs) !== 1 ? 's' : '' ?></span>
        <?php if ($filter_date): ?>
            <span class="filter-active"><i class="fa-solid fa-calendar-day"></i> <?= date('M d, Y', strtotime($filter_date)) ?></span>
        <?php endif; ?>
        <?php if ($filter_type === 'feed'): ?>
            <span class="filter-active"><i class="fa-solid fa-bowl-food"></i> Feed Usage only</span>
        <?php elseif ($filter_type === 'order'): ?>
            <span class="filter-active"><i class="fa-solid fa-cart-shopping"></i> Orders only</span>
        <?php endif; ?>
    </div>

    <form method="GET" action="" class="filters">
        <label>Filter</label>
        <input type="date" name="date" value="<?= htmlspecialchars($filter_date) ?>">
        <select name="type" class="type-select">
            <option value="" <?= !$filter_type ? 'selected' : '' ?>>All Actions</option>
            <option value="feed" <?= $filter_type === 'feed' ? 'selected' : '' ?>>Record Feed Usage</option>
            <option value="order" <?= $filter_type === 'order' ? 'selected' : '' ?>>Customer Order</option>
        </select>
        <button type="submit" class="btn-filter"><i class="fa-solid fa-magnifying-glass"></i> Filter</button>
        <?php if ($filter_date || $filter_type): ?>
            <a href="activity_logs.php" class="btn-clear"><i class="fa-solid fa-xmark"></i> Clear</a>
        <?php endif; ?>
    </form>
</div>

<?php if (empty($logs)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-clock-rotate-left"></i>
        <p><?= ($filter_date || $filter_type) ? 'No records found for this filter.' : 'No activity logged yet.' ?></p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Staff</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log):
                    $parts    = explode(' ', $log['staff_name']);
                    $initials = strtoupper(substr($parts[0], 0, 1) . substr($parts[1] ?? '', 0, 1));
                ?>
                <tr>
                    <td>
                        <div class="staff-cell">
                            <div class="staff-init"><?= $initials ?></div>
                            <span class="staff-full"><?= htmlspecialchars($log['staff_name']) ?></span>
                        </div>
                    </td>
                    <td>
                        <?php if ($log['log_type'] === 'feed'): ?>
                            <span class="badge-feed"><i class="fa-solid fa-bowl-food"></i> Record Feed Usage</span>
                        <?php else: ?>
                            <span class="badge-order"><i class="fa-solid fa-cart-shopping"></i> Customer Order</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($log['log_type'] === 'feed'): ?>
                            <span class="grams-val"><?= number_format($log['used_grams']) ?> g used</span>
                        <?php else: ?>
                            <div class="detail-cell">
                                <?php if ($log['customer_name']): ?>
                                    <strong><?= htmlspecialchars($log['customer_name']) ?></strong> —
                                <?php else: ?>
                                    <span class="no-customer">Walk-in</span> —
                                <?php endif; ?>
                                <?= number_format($log['quantity_kg'], 2) ?> kg ·
                                <span class="revenue-val">₱<?= number_format($log['total_price'], 2) ?></span>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="date-cell"><?= date('M d, Y', strtotime($log['log_date'])) ?></div>
                        <div class="time-cell"><?= date('h:i A', strtotime($log['created_at'])) ?></div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once 'sidebar_end.php'; ?>