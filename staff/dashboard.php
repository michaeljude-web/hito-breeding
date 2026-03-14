<?php
require_once 'auth.php';
require_once '../db/connection.php';
require_once 'navbar.php';

$staff_id   = $_SESSION['staff_id'];
$staff_name = $_SESSION['staff_name'];
$today      = date('Y-m-d');

$feed_summary = $pdo->query("
    SELECT
        COALESCE(SUM(quantity_kg), 0) - COALESCE((SELECT SUM(used_kg) FROM feed_usage), 0) AS remaining,
        COALESCE(SUM(quantity_kg), 0) AS total_in
    FROM feed_inventory
")->fetch(PDO::FETCH_ASSOC);

$feed_remaining = (float)$feed_summary['remaining'];
$feed_total     = (float)$feed_summary['total_in'];
$feed_pct       = $feed_total > 0 ? min(100, round(($feed_remaining / $feed_total) * 100)) : 0;
$feed_level     = $feed_pct > 40 ? 'ok' : ($feed_pct > 15 ? 'low' : 'critical');

$today_usage = (float)$pdo->query("
    SELECT COALESCE(SUM(used_kg), 0) FROM feed_usage WHERE usage_date = '{$today}'
")->fetchColumn();

$today_orders = $pdo->query("
    SELECT COUNT(*) AS cnt, COALESCE(SUM(total_price), 0) AS revenue, COALESCE(SUM(quantity_kg), 0) AS kg
    FROM orders WHERE order_date = '{$today}'
")->fetch(PDO::FETCH_ASSOC);

$recent = $pdo->prepare("
    (
        SELECT 'feed' AS type, usage_date AS log_date, created_at,
               ROUND(used_kg * 1000) AS grams, NULL AS total_price, NULL AS customer_name, NULL AS qty_kg
        FROM feed_usage WHERE logged_by = ?
    )
    UNION ALL
    (
        SELECT 'order' AS type, order_date AS log_date, created_at,
               NULL AS grams, total_price, customer_name, quantity_kg AS qty_kg
        FROM orders WHERE logged_by = ?
    )
    ORDER BY created_at DESC
    LIMIT 6
");
$recent->execute([$staff_id, $staff_id]);
$recent_logs = $recent->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .greeting { margin-bottom:24px; }
    .greeting h3 { font-size:18px; font-weight:700; color:#111; }
    .greeting p  { font-size:12px; color:#aaa; margin-top:3px; }

    .stat-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:24px; }

    .stat-card { background:#fff; border:1px solid #e2e2e6; padding:20px; }
    .stat-icon { width:34px; height:34px; background:#f0f0f2; display:flex; align-items:center; justify-content:center; font-size:14px; color:#888; margin-bottom:14px; }
    .stat-val { font-size:22px; font-weight:700; color:#111; line-height:1; margin-bottom:3px; }
    .stat-val small { font-size:12px; font-weight:400; color:#aaa; }
    .stat-label { font-size:10px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:#bbb; }
    .stat-val.ok { color:#16a34a; }
    .stat-val.low { color:#ca8a04; }
    .stat-val.critical { color:#b91c1c; }

    .feed-bar { margin-top:12px; height:3px; background:#f0f0f2; border-radius:2px; overflow:hidden; }
    .feed-bar-fill { height:100%; background:#1a1a2e; border-radius:2px; }
    .feed-bar-fill.low { background:#ca8a04; }
    .feed-bar-fill.critical { background:#b91c1c; }

    .section-title { font-size:11px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:#bbb; margin-bottom:12px; }

    .activity-list { background:#fff; border:1px solid #e2e2e6; }
    .activity-item { display:flex; align-items:center; gap:14px; padding:13px 16px; border-bottom:1px solid #f0f0f2; }
    .activity-item:last-child { border-bottom:none; }

    .act-icon { width:32px; height:32px; background:#f0f0f2; display:flex; align-items:center; justify-content:center; font-size:12px; color:#888; border-radius:50%; flex-shrink:0; }
    .act-icon.order { background:#1a1a2e; color:#fff; }

    .act-body { flex:1; min-width:0; }
    .act-title { font-size:13px; font-weight:600; color:#111; }
    .act-sub { font-size:11px; color:#aaa; margin-top:2px; }

    .act-time { font-size:11px; color:#ccc; white-space:nowrap; }

    .empty-activity { text-align:center; padding:30px; color:#ccc; background:#fff; border:1px solid #e2e2e6; font-size:13px; }

    @media(max-width:768px) { .stat-grid { grid-template-columns:1fr 1fr; } }
    @media(max-width:480px) { .stat-grid { grid-template-columns:1fr; } }
</style>



<div class="stat-grid">
    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
        <div class="stat-val <?= $feed_level ?>"><?= number_format($feed_remaining * 1000, 0) ?><small> g</small></div>
        <div class="stat-label">Feed Remaining</div>
        <div class="feed-bar">
            <div class="feed-bar-fill <?= $feed_level === 'ok' ? '' : $feed_level ?>" style="width:<?= $feed_pct ?>%"></div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-bowl-food"></i></div>
        <div class="stat-val"><?= number_format($today_usage * 1000, 0) ?><small> g</small></div>
        <div class="stat-label">Feed Used Today</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-cart-shopping"></i></div>
        <div class="stat-val"><?= number_format($today_orders['cnt']) ?></div>
        <div class="stat-label">Orders Today</div>
    </div>

    <div class="stat-card">
        <div class="stat-icon"><i class="fa-solid fa-peso-sign"></i></div>
        <div class="stat-val">₱<?= number_format($today_orders['revenue'], 2) ?></div>
        <div class="stat-label">Revenue Today</div>
    </div>
</div>

<div class="section-title">Your Recent Activity</div>

<?php if (empty($recent_logs)): ?>
    <div class="empty-activity">No activity recorded yet.</div>
<?php else: ?>
    <div class="activity-list">
        <?php foreach ($recent_logs as $log): ?>
        <div class="activity-item">
            <div class="act-icon <?= $log['type'] === 'order' ? 'order' : '' ?>">
                <i class="fa-solid <?= $log['type'] === 'feed' ? 'fa-bowl-food' : 'fa-cart-shopping' ?>"></i>
            </div>
            <div class="act-body">
                <?php if ($log['type'] === 'feed'): ?>
                    <div class="act-title">Recorded Feed Usage</div>
                    <div class="act-sub"><?= number_format($log['grams']) ?> g used</div>
                <?php else: ?>
                    <div class="act-title">Customer Order</div>
                    <div class="act-sub">
                        <?= $log['customer_name'] ? htmlspecialchars($log['customer_name']) : 'Walk-in' ?>
                        · <?= number_format($log['qty_kg'], 2) ?> kg
                        · <strong style="color:#16a34a;">₱<?= number_format($log['total_price'], 2) ?></strong>
                    </div>
                <?php endif; ?>
            </div>
            <div class="act-time"><?= date('M d, h:i A', strtotime($log['created_at'])) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'navbar_end.php'; ?>