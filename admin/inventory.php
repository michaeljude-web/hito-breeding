<?php
$page_title = 'Feed Inventory';
$page_sub   = 'Manage feed stock, pricing and monitor usage.';
require_once '../db/connection.php';
require_once 'sidebar.php';

$success = '';
$error   = '';

// Delete stock record
if (isset($_GET['delete_stock'])) {
    $pdo->prepare("DELETE FROM feed_inventory WHERE id = ?")->execute([(int)$_GET['delete_stock']]);
    header('Location: inventory.php?deleted=1'); exit;
}
if (isset($_GET['deleted'])) $success = 'Feed stock record deleted.';

// Add stock
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_stock') {
    $quantity_kg  = (float)($_POST['quantity_kg'] ?? 0);
    $total_price  = (float)($_POST['total_price'] ?? 0);
    $price_per_kg = ($quantity_kg > 0) ? $total_price / $quantity_kg : 0;

    if ($quantity_kg > 0 && $total_price > 0) {
        $pdo->prepare("INSERT INTO feed_inventory (quantity_kg, price_per_kg, date_added, added_by) VALUES (?,?,CURDATE(),0)")
            ->execute([$quantity_kg, $price_per_kg]);
        $success = 'Feed stock added successfully.';
    } else {
        $error = 'Please fill in all fields correctly.';
    }
}

// Summary
$summary = $pdo->query("
    SELECT
        COALESCE(SUM(quantity_kg), 0) AS total_in,
        COALESCE(SUM(quantity_kg * price_per_kg), 0) / NULLIF(SUM(quantity_kg), 0) AS avg_price,
        COALESCE((SELECT SUM(used_kg) FROM feed_usage), 0) AS total_used,
        COALESCE(SUM(quantity_kg), 0) - COALESCE((SELECT SUM(used_kg) FROM feed_usage), 0) AS remaining
    FROM feed_inventory
")->fetch(PDO::FETCH_ASSOC);

// Stock-in log
$stock_log = $pdo->query("
    SELECT * FROM feed_inventory ORDER BY date_added DESC, created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Usage log — now includes hito_size and hito_kg
$usage_log = $pdo->query("
    SELECT * FROM feed_usage ORDER BY usage_date DESC, created_at DESC LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);

// Per-pond usage summary
$pond_summary = $pdo->query("
    SELECT pond, SUM(used_kg) AS total_used_kg
    FROM feed_usage
    GROUP BY pond
    ORDER BY pond
")->fetchAll(PDO::FETCH_ASSOC);

$pct = ($summary['total_in'] > 0) ? min(100, round(($summary['remaining'] / $summary['total_in']) * 100)) : 0;
$levelClass = $pct > 40 ? '' : ($pct > 15 ? 'low' : 'critical');
?>

<style>
    .tabs { display:flex; border-bottom:2px solid #e4e4e4; margin-bottom:24px; }
    .tab-btn { padding:10px 22px; font-family:inherit; font-size:12px; font-weight:600; letter-spacing:0.06em; color:#aaa; background:none; border:none; border-bottom:2px solid transparent; margin-bottom:-2px; cursor:pointer; transition:color .15s,border-color .15s; display:flex; align-items:center; gap:7px; }
    .tab-btn:hover { color:#555; }
    .tab-btn.active { color:#111; border-bottom-color:#111; }
    .tab-panel { display:none; }
    .tab-panel.active { display:block; }

    .toolbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; gap:12px; flex-wrap:wrap; }
    .count-label { font-size:11px; color:#aaa; }

    .btn-primary { display:flex; align-items:center; gap:8px; padding:9px 16px; background:#111; color:#fff; font-family:inherit; font-size:12px; font-weight:600; letter-spacing:0.06em; border:none; cursor:pointer; transition:opacity .2s; }
    .btn-primary:hover { opacity:.82; }

    .alert { padding:10px 14px; font-size:12px; margin-bottom:16px; border-left:3px solid; display:flex; align-items:center; gap:8px; }
    .alert.success { background:#f0fdf4; color:#16a34a; border-color:#16a34a; }
    .alert.error   { background:#fef2f2; color:#b91c1c; border-color:#b91c1c; }

    /* Overview */
    .overview-card { background:#fff; border:1px solid #e4e4e4; padding:24px; margin-bottom:24px; }
    .ov-stats { display:grid; grid-template-columns:repeat(4,1fr); gap:0; }
    .ov-stat { text-align:center; padding:0 16px; border-right:1px solid #f0f0f0; }
    .ov-stat:last-child { border-right:none; }
    .ov-val { font-size:26px; font-weight:700; color:#111; line-height:1; margin-bottom:4px; }
    .ov-val small { font-size:13px; font-weight:400; color:#aaa; }
    .ov-label { font-size:10px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:#bbb; }
    .ov-remaining { color:#16a34a; }
    .ov-remaining.low { color:#ca8a04; }
    .ov-remaining.critical { color:#b91c1c; }
    .ov-bar { margin-top:20px; height:5px; background:#f0f0f0; border-radius:2px; overflow:hidden; }
    .ov-bar-fill { height:100%; background:#111; border-radius:2px; transition:width .3s; }
    .ov-bar-fill.low { background:#ca8a04; }
    .ov-bar-fill.critical { background:#b91c1c; }

    /* Pond Cards */
    .pond-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:24px; }
    @media(max-width:700px){ .pond-grid { grid-template-columns:1fr 1fr; } }
    .pond-card { background:#fff; border:1px solid #e4e4e4; padding:16px 18px; }
    .pond-card-title { font-size:10px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:#bbb; margin-bottom:8px; }
    .pond-card-val { font-size:20px; font-weight:700; color:#111; }
    .pond-card-val small { font-size:12px; font-weight:400; color:#aaa; }

    /* Tables */
    .table-wrap { background:#fff; border:1px solid #e4e4e4; overflow-x:auto; }
    table { width:100%; border-collapse:collapse; font-size:13px; }
    thead tr { border-bottom:1px solid #e4e4e4; background:#f9f9f9; }
    th { padding:11px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:#aaa; white-space:nowrap; }
    tbody tr { border-bottom:1px solid #f0f0f0; transition:background .1s; }
    tbody tr:last-child { border-bottom:none; }
    tbody tr:hover { background:#fafafa; }
    td { padding:12px 16px; color:#444; vertical-align:middle; }
    .val-bold { font-weight:700; color:#111; }

    .badge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; border-radius:2px; }
    .badge-small { background:#eff6ff; color:#1d4ed8; }
    .badge-large { background:#fef3c7; color:#92400e; }
    .pond-badge { display:inline-block; font-size:10px; font-weight:600; background:#f0f0f0; color:#555; padding:3px 8px; border-radius:2px; }

    .empty-state { text-align:center; padding:50px 20px; color:#ccc; background:#fff; border:1px solid #e4e4e4; }
    .empty-state i { font-size:32px; margin-bottom:10px; display:block; }
    .empty-state p { font-size:13px; }

    .btn-icon-sm { width:26px; height:26px; display:flex; align-items:center; justify-content:center; border:1px solid #e4e4e4; background:#fff; cursor:pointer; font-size:11px; color:#888; transition:background .15s,color .15s; }
    .btn-icon-sm.danger:hover { background:#b91c1c; color:#fff; border-color:#b91c1c; }

    /* Modal */
    input {
        width: 150px;    }
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:200; align-items:center; justify-content:center; padding:24px; }
    .modal-overlay.show { display:flex; }
    .modal { background:#fff; width:100%; max-width:400px; animation:modalIn .22s ease both; }
    @keyframes modalIn { from{opacity:0;transform:translateY(-14px)}to{opacity:1;transform:translateY(0)} }
    .modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 22px; border-bottom:1px solid #e4e4e4; }
    .modal-header h3 { font-size:15px; font-weight:700; color:#111; }
    .modal-header p  { font-size:11px; color:#aaa; margin-top:2px; }
    .modal-close { width:28px; height:28px; background:#f4f4f4; border:none; cursor:pointer; font-size:12px; color:#666; display:flex; align-items:center; justify-content:center; }
    .modal-close:hover { background:#e4e4e4; }
    .modal-body { padding:22px; }
    .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .form-group { display:flex; flex-direction:column; gap:6px; }
    .form-group.full { grid-column:1/-1; }
    .form-group label { font-size:10px; font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:#999; }
    .form-group input { border:1px solid #e4e4e4; outline:none; font-family:inherit; font-size:13px; color:#111; padding:9px 11px; background:#fafafa; transition:border-color .2s; border-radius:0; }
    .form-group input:focus { border-color:#111; background:#fff; }
    .form-group input::placeholder { color:#ccc; }
    .total-preview strong { color:#111; font-size:14px; }
    .modal-footer { padding:14px 22px; border-top:1px solid #f0f0f0; display:flex; justify-content:flex-end; gap:10px; }
    .btn-cancel { padding:9px 16px; background:#fff; border:1.5px solid #e4e4e4; font-family:inherit; font-size:12px; font-weight:600; color:#666; cursor:pointer; }
    .btn-cancel:hover { background:#f4f4f4; }
    .btn-submit { padding:9px 20px; background:#111; border:none; font-family:inherit; font-size:12px; font-weight:600; color:#fff; cursor:pointer; transition:opacity .2s; }
    .btn-submit:hover { opacity:.82; }
</style>

<?php if ($success): ?>
    <div class="alert success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Overall Stock Overview -->
<div class="overview-card">
    <div class="ov-stats">
        <div class="ov-stat">
            <div class="ov-val"><?= number_format($summary['total_in'], 1) ?><small> kg</small></div>
            <div class="ov-label">Total Stock In</div>
        </div>
        <div class="ov-stat">
            <div class="ov-val"><?= number_format($summary['total_used'], 1) ?><small> kg</small></div>
            <div class="ov-label">Total Used</div>
        </div>
        <div class="ov-stat">
            <div class="ov-val ov-remaining <?= $levelClass ?>"><?= number_format($summary['remaining'], 1) ?><small> kg</small></div>
            <div class="ov-label">Remaining</div>
        </div>
    </div>
    <div class="ov-bar">
        <div class="ov-bar-fill <?= $levelClass ?>" style="width:<?= $pct ?>%"></div>
    </div>
</div>

<!-- Per-Pond Usage Summary -->
<?php
$ponds = ['Pond 1', 'Pond 2', 'Pond 3', 'Pond 4'];
$pond_map = [];
foreach ($pond_summary as $ps) { $pond_map[$ps['pond']] = $ps['total_used_kg']; }
?>
<div class="pond-grid">
    <?php foreach ($ponds as $p): ?>
    <div class="pond-card">
        <div class="pond-card-title"><?= htmlspecialchars($p) ?></div>
        <div class="pond-card-val">
            <?= number_format(($pond_map[$p] ?? 0), 2) ?><small> kg used</small>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Tabs -->
<div class="tabs">
    <button class="tab-btn active" onclick="switchTab('stocklog',this)">
        <i class="fa-solid fa-receipt"></i> Stock-In Log
    </button>
    <button class="tab-btn" onclick="switchTab('usage',this)">
        <i class="fa-solid fa-arrow-trend-down"></i> Usage Log
    </button>
</div>

<!-- Stock-In Log -->
<div class="tab-panel active" id="tab-stocklog">
    <div class="toolbar">
        <span class="count-label"><?= count($stock_log) ?> record<?= count($stock_log) !== 1 ? 's' : '' ?></span>
        <button class="btn-primary" onclick="openModal('modal-stock')">
            <i class="fa-solid fa-plus"></i> Add Stock
        </button>
    </div>

    <?php if (empty($stock_log)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-receipt"></i>
            <p>No stock records yet. Click "Add Stock" to get started.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Quantity</th>
                        <th>Total Price</th>
                        <!-- <th></th> -->
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stock_log as $s): ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($s['date_added'])) ?></td>
                        <td class="val-bold"><?= number_format($s['quantity_kg'], 2) ?> kg</td>
                        <td class="val-bold">₱<?= number_format($s['quantity_kg'] * $s['price_per_kg'], 2) ?></td>
                     
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Usage Log -->
<div class="tab-panel" id="tab-usage">
    <div class="toolbar">
        <span class="count-label">Last 100 usage entries</span>
    </div>

    <?php if (empty($usage_log)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-arrow-trend-down"></i>
            <p>No feed usage recorded yet.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Pond</th>
                        <th>Hito Size</th>
                
                        <th>Feed Used</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usage_log as $u): ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($u['usage_date'])) ?></td>
                        <td><span class="pond-badge"><?= htmlspecialchars($u['pond'] ?? '—') ?></span></td>
                        <td>
                            <?php if (($u['hito_size'] ?? '') === 'small'): ?>
                                <span class="badge badge-small"><i class="fa-solid fa-fish"></i> Small</span>
                            <?php elseif (($u['hito_size'] ?? '') === 'large'): ?>
                                <span class="badge badge-large"><i class="fa-solid fa-fish"></i> Large</span>
                            <?php else: ?>
                                <span style="color:#ccc">—</span>
                            <?php endif; ?>
                        </td>
                      
                        <td class="val-bold"><?= number_format($u['used_kg'], 2) ?> kg</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Add Stock Modal -->
<div class="modal-overlay" id="modal-stock">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h3>Add Feed Stock</h3>
                <p>Today's date will be recorded automatically.</p>
            </div>
            <button class="modal-close" onclick="closeModal('modal-stock')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_stock">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Quantity (kg)</label>
                        <input type="number" name="quantity_kg" id="m-qty" placeholder="e.g. 50" step="0.01" min="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Total Price (₱)</label>
                        <input type="number" name="total_price" id="m-price" placeholder="e.g. 2500" step="0.01" min="0.01" required>
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('modal-stock')">Cancel</button>
                <button type="submit" class="btn-submit"><i class="fa-solid fa-plus"></i> Add Stock</button>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(tab, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    btn.classList.add('active');
}
function openModal(id) {
    document.getElementById(id).classList.add('show');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.remove('show');
    document.body.style.overflow = '';
}
document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', function(e) { if (e.target === this) closeModal(this.id); });
});

<?php if ($error): ?>openModal('modal-stock');<?php endif; ?>
</script>

<?php require_once 'sidebar_end.php'; ?>