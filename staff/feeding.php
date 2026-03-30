<?php
require_once 'auth.php';
$page_title = 'Feed Inventory';
$page_sub   = 'Monitor feed stock and record usage.';
require_once '../db/connection.php';
require_once 'navbar.php';

$success = '';
$error   = '';
$staff_id = $_SESSION['staff_id'];

$ponds = ['Pond 1', 'Pond 2', 'Pond 3', 'Pond 4'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'log_usage') {
    $pond       = trim($_POST['pond'] ?? '');
    $size       = $_POST['hito_size'] ?? '';       // 'small' or 'large'
    $used_grams = (float)($_POST['used_grams'] ?? 0);
    $used_kg    = $used_grams / 1000;

    // Size-specific fields
    $hito_kg    = ($size === 'large') ? (float)($_POST['hito_kg'] ?? 0) : null;

    if (!in_array($pond, $ponds)) {
        $error = 'Please select a valid pond.';
    } elseif (!in_array($size, ['small', 'large'])) {
        $error = 'Please select hito size.';
    } elseif ($size === 'large' && $hito_kg <= 0) {
        $error = 'Please enter the weight of hito in kg.';
    } elseif ($used_grams <= 0) {
        $error = 'Please enter the amount of feed used.';
    } else {
        $remaining = (float)$pdo->query("
            SELECT COALESCE(SUM(quantity_kg),0) - COALESCE((SELECT SUM(used_kg) FROM feed_usage),0)
            FROM feed_inventory
        ")->fetchColumn();

        if ($used_kg > $remaining) {
            $error = "Not enough stock. Only " . number_format($remaining * 1000, 0) . " grams remaining.";
        } else {
            $pdo->prepare("
                INSERT INTO feed_usage (used_kg, pond, hito_size, hito_kg, usage_date, logged_by)
                VALUES (?, ?, ?, ?, CURDATE(), ?)
            ")->execute([$used_kg, $pond, $size, $hito_kg, $staff_id]);
            $success = 'Feed usage recorded successfully.';
        }
    }
}

$summary = $pdo->query("
    SELECT
        COALESCE(SUM(quantity_kg), 0) AS total_in,
        COALESCE((SELECT SUM(used_kg) FROM feed_usage), 0) AS total_used,
        COALESCE(SUM(quantity_kg), 0) - COALESCE((SELECT SUM(used_kg) FROM feed_usage), 0) AS remaining
    FROM feed_inventory
")->fetch(PDO::FETCH_ASSOC);

$usage_log = $pdo->query("
    SELECT * FROM feed_usage ORDER BY usage_date DESC, created_at DESC LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);

$pct = ($summary['total_in'] > 0) ? min(100, round(($summary['remaining'] / $summary['total_in']) * 100)) : 0;
$levelClass = $pct > 40 ? '' : ($pct > 15 ? 'low' : 'critical');
?>

<style>
    .tabs { display:flex; border-bottom:2px solid #e2e2e6; margin-bottom:24px; }
    .tab-btn { padding:10px 22px; font-family:inherit; font-size:12px; font-weight:600; letter-spacing:0.06em; color:#aaa; background:none; border:none; border-bottom:2px solid transparent; margin-bottom:-2px; cursor:pointer; transition:color .15s,border-color .15s; display:flex; align-items:center; gap:7px; }
    .tab-btn:hover { color:#555; }
    .tab-btn.active { color:#1a1a2e; border-bottom-color:#1a1a2e; }
    .tab-panel { display:none; }
    .tab-panel.active { display:block; }

    .toolbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; gap:12px; flex-wrap:wrap; }
    .count-label { font-size:11px; color:#aaa; }

    .btn-primary { display:flex; align-items:center; gap:8px; padding:9px 16px; background:#1a1a2e; color:#fff; font-family:inherit; font-size:12px; font-weight:600; letter-spacing:0.06em; border:none; cursor:pointer; transition:opacity .2s; }
    .btn-primary:hover { opacity:.82; }

    .alert { padding:10px 14px; font-size:12px; margin-bottom:16px; border-left:3px solid; display:flex; align-items:center; gap:8px; }
    .alert.success { background:#f0fdf4; color:#16a34a; border-color:#16a34a; }
    .alert.error   { background:#fef2f2; color:#b91c1c; border-color:#b91c1c; }

    .overview-card { background:#fff; border:1px solid #e2e2e6; padding:24px; margin-bottom:24px; }
    .ov-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:0; }
    .ov-stat { text-align:center; padding:0 16px; border-right:1px solid #f0f0f2; }
    .ov-stat:last-child { border-right:none; }
    .ov-val { font-size:26px; font-weight:700; color:#111; line-height:1; margin-bottom:4px; }
    .ov-val small { font-size:13px; font-weight:400; color:#aaa; }
    .ov-label { font-size:10px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:#bbb; }
    .ov-remaining { color:#16a34a; }
    .ov-remaining.low { color:#ca8a04; }
    .ov-remaining.critical { color:#b91c1c; }
    .ov-bar { margin-top:20px; height:5px; background:#f0f0f2; border-radius:2px; overflow:hidden; }
    .ov-bar-fill { height:100%; background:#1a1a2e; border-radius:2px; }
    .ov-bar-fill.low { background:#ca8a04; }
    .ov-bar-fill.critical { background:#b91c1c; }

    .table-wrap { background:#fff; border:1px solid #e2e2e6; overflow-x:auto; }
    table { width:100%; border-collapse:collapse; font-size:13px; }
    thead tr { border-bottom:1px solid #e2e2e6; background:#f9f9fb; }
    th { padding:11px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:#aaa; white-space:nowrap; }
    tbody tr { border-bottom:1px solid #f0f0f2; transition:background .1s; }
    tbody tr:last-child { border-bottom:none; }
    tbody tr:hover { background:#fafafa; }
    td { padding:12px 16px; color:#444; vertical-align:middle; }
    .val-bold { font-weight:700; color:#111; }

    .badge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; font-size:10px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; border-radius:2px; }
    .badge-small { background:#eff6ff; color:#2563eb; }
    .badge-large { background:#fef9c3; color:#854d0e; }

    .empty-state { text-align:center; padding:50px 20px; color:#ccc; background:#fff; border:1px solid #e2e2e6; }
    .empty-state i { font-size:32px; margin-bottom:10px; display:block; }
    .empty-state p { font-size:13px; }

    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:200; align-items:center; justify-content:center; padding:24px; }
    .modal-overlay.show { display:flex; }
    .modal { background:#fff; width:100%; max-width:420px; animation:modalIn .22s ease both; }
    @keyframes modalIn { from{opacity:0;transform:translateY(-14px)}to{opacity:1;transform:translateY(0)} }
    .modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 22px; border-bottom:1px solid #e2e2e6; }
    .modal-header h3 { font-size:15px; font-weight:700; color:#111; }
    .modal-header p  { font-size:11px; color:#aaa; margin-top:2px; }
    .modal-close { width:28px; height:28px; background:#f0f0f2; border:none; cursor:pointer; font-size:12px; color:#666; display:flex; align-items:center; justify-content:center; }
    .modal-close:hover { background:#e2e2e6; }
    .modal-body { padding:22px; }
    .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .form-group { display:flex; flex-direction:column; gap:6px; }
    .form-group.full { grid-column:1/-1; }
    .form-group label { font-size:10px; font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:#999; }
    .form-group input,
    .form-group select { border:1px solid #e2e2e6; outline:none; font-family:inherit; font-size:13px; color:#111; padding:9px 11px; background:#fafafa; transition:border-color .2s; border-radius:0; appearance:none; -webkit-appearance:none; }
    .form-group input:focus,
    .form-group select:focus { border-color:#1a1a2e; background:#fff; }
    .form-group input::placeholder { color:#ccc; }
    .form-group input[readonly] { background:#f0f0f2; color:#555; cursor:not-allowed; border-color:#e2e2e6; }

    /* Size toggle */
    .size-toggle { display:grid; grid-template-columns:1fr 1fr; gap:0; border:1px solid #e2e2e6; }
    .size-toggle input[type=radio] { display:none; }
    .size-toggle label { padding:10px; text-align:center; font-size:11px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:#aaa; cursor:pointer; transition:background .15s,color .15s; border-right:1px solid #e2e2e6; }
    .size-toggle label:last-of-type { border-right:none; }
    .size-toggle input[type=radio]:checked + label { background:#1a1a2e; color:#fff; }

    .divider { grid-column:1/-1; border:none; border-top:1px dashed #e2e2e6; margin:4px 0; }
    .hint { font-size:10px; color:#bbb; margin-top:2px; font-weight:400; text-transform:none; letter-spacing:0; }
    .auto-badge { display:inline-block; font-size:9px; font-weight:700; letter-spacing:.08em; text-transform:uppercase; background:#f0fdf4; color:#16a34a; border:1px solid #bbf7d0; padding:1px 6px; margin-left:4px; vertical-align:middle; }

    .modal-footer { padding:14px 22px; border-top:1px solid #f0f0f2; display:flex; justify-content:flex-end; gap:10px; }
    .btn-cancel { padding:9px 16px; background:#fff; border:1.5px solid #e2e2e6; font-family:inherit; font-size:12px; font-weight:600; color:#666; cursor:pointer; }
    .btn-cancel:hover { background:#f4f4f4; }
    .btn-submit { padding:9px 20px; background:#1a1a2e; border:none; font-family:inherit; font-size:12px; font-weight:600; color:#fff; cursor:pointer; transition:opacity .2s; }
    .btn-submit:hover { opacity:.82; }
</style>

<?php if ($success): ?>
    <div class="alert success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="overview-card">
    <div class="ov-stats">
        <div class="ov-stat">
            <div class="ov-val"><?= number_format($summary['total_in'] * 1000, 0) ?><small> g</small></div>
            <div class="ov-label">Total Stock</div>
        </div>
        <div class="ov-stat">
            <div class="ov-val"><?= number_format($summary['total_used'] * 1000, 0) ?><small> g</small></div>
            <div class="ov-label">Total Used</div>
        </div>
        <div class="ov-stat">
            <div class="ov-val ov-remaining <?= $levelClass ?>"><?= number_format($summary['remaining'] * 1000, 0) ?><small> g</small></div>
            <div class="ov-label">Remaining</div>
        </div>
    </div>
    <div class="ov-bar">
        <div class="ov-bar-fill <?= $levelClass ?>" style="width:<?= $pct ?>%"></div>
    </div>
</div>

<div class="tabs">
    <button class="tab-btn active" onclick="switchTab('usage',this)">
        <i class="fa-solid fa-arrow-trend-down"></i> Usage Log
    </button>
</div>

<div class="tab-panel active" id="tab-usage">
    <div class="toolbar">
        <span class="count-label">Last 50 usage entries</span>
        <button class="btn-primary" onclick="openModal('modal-usage')">
            <i class="fa-solid fa-minus"></i> Record Usage
        </button>
    </div>

    <?php if (empty($usage_log)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-arrow-trend-down"></i>
            <p>No usage recorded yet.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Pond</th>
                        <th>Size</th>
                        <th>Feed Used</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usage_log as $u): ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($u['usage_date'])) ?></td>
                        <td class="val-bold"><?= htmlspecialchars($u['pond']) ?></td>
                        <td>
                            <?php if (($u['hito_size'] ?? '') === 'small'): ?>
                                <span class="badge badge-small"><i class="fa-solid fa-fish"></i> Small</span>
                            <?php elseif (($u['hito_size'] ?? '') === 'large'): ?>
                                <span class="badge badge-large"><i class="fa-solid fa-fish"></i> Large</span>
                            <?php else: ?>
                                <span style="color:#ccc">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="val-bold"><?= number_format($u['used_kg'] * 1000, 0) ?> g</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Record Usage Modal -->
<div class="modal-overlay" id="modal-usage">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h3>Record Feed Usage</h3>
                <p>Select pond and hito size to log feeding.</p>
            </div>
            <button class="modal-close" onclick="closeModal('modal-usage')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="log_usage">
            <div class="modal-body">
                <div class="form-grid">

                    <!-- Pond -->
                    <div class="form-group full">
                        <label>Pond</label>
                        <select name="pond" required>
                            <option value="" disabled selected>— Select Pond —</option>
                            <?php foreach ($ponds as $p): ?>
                                <option value="<?= $p ?>" <?= (($_POST['pond'] ?? '') === $p ? 'selected' : '') ?>><?= $p ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Hito Size Toggle -->
                    <div class="form-group full">
                        <label>Hito Size</label>
                        <div class="size-toggle">
                            <input type="radio" name="hito_size" id="size-small" value="small" onchange="onSizeChange()" <?= (($_POST['hito_size'] ?? '') === 'small' ? 'checked' : '') ?>>
                            <label for="size-small"><i class="fa-solid fa-fish"></i> Small</label>
                            <input type="radio" name="hito_size" id="size-large" value="large" onchange="onSizeChange()" <?= (($_POST['hito_size'] ?? '') === 'large' ? 'checked' : '') ?>>
                            <label for="size-large"><i class="fa-solid fa-fish"></i> Large (per kg)</label>
                        </div>
                    </div>

                    <hr class="divider">

                    <!-- Large: hito weight in kg → auto-fills feed used -->
                    <div class="form-group full" id="field-hito-kg" style="display:none;">
                        <label>Hito Weight (kg)</label>
                        <div class="hint">I-input ang kg ng large hito — awtomatikong mako-compute ang feed used sa grams</div>
                        <input type="number" name="hito_kg" id="input-hito-kg"
                               placeholder="e.g. 10" step="0.01" min="0.01"
                               value="<?= htmlspecialchars($_POST['hito_kg'] ?? '') ?>"
                               oninput="autoFillFeed()">
                    </div>

                    <!-- Feed Used (grams) -->
                    <!-- Small: user types grams manually -->
                    <!-- Large: readonly, auto-filled from hito kg × 1000 -->
                    <div class="form-group full">
                        <label id="label-feed">
                            Feed Used (grams)
                            <span class="auto-badge" id="auto-badge" style="display:none;">Auto</span>
                        </label>
                        <div class="hint">Max: <?= number_format($summary['remaining'] * 1000, 0) ?> g remaining in stock</div>
                        <input type="number" name="used_grams" id="input-feed"
                               placeholder="e.g. 500" step="1" min="1"
                               max="<?= $summary['remaining'] * 1000 ?>"
                               required
                               value="<?= htmlspecialchars($_POST['used_grams'] ?? '') ?>">
                    </div>

                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('modal-usage')">Cancel</button>
                <button type="submit" class="btn-submit"><i class="fa-solid fa-minus"></i> Record Usage</button>
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
    onSizeChange();
}
function closeModal(id) {
    document.getElementById(id).classList.remove('show');
    document.body.style.overflow = '';
}
document.querySelectorAll('.modal-overlay').forEach(o => {
    o.addEventListener('click', function(e) { if (e.target === this) closeModal(this.id); });
});

function autoFillFeed() {
    const hitoKg = parseFloat(document.getElementById('input-hito-kg').value) || 0;
    const feedInput = document.getElementById('input-feed');
    if (hitoKg > 0) {
        feedInput.value = Math.round(hitoKg * 1000); // 1 kg = 1000 g, live update
    } else {
        feedInput.value = '';
    }
}

function onSizeChange() {
    const size = document.querySelector('input[name="hito_size"]:checked')?.value;
    const hitoKgField = document.getElementById('field-hito-kg');
    const hitoKgInput = document.getElementById('input-hito-kg');
    const feedInput   = document.getElementById('input-feed');
    const autoBadge   = document.getElementById('auto-badge');

    if (size === 'small') {
        // Show feed input as normal manual grams entry
        hitoKgField.style.display = 'none';
        hitoKgInput.required = false;
        hitoKgInput.value = '';

        feedInput.readOnly = false;
        feedInput.placeholder = 'e.g. 100';
        feedInput.value = '';
        autoBadge.style.display = 'none';

    } else if (size === 'large') {
        // Show hito kg field; feed used becomes readonly and auto-computed
        hitoKgField.style.display = '';
        hitoKgInput.required = true;

        feedInput.readOnly = true;
        feedInput.placeholder = 'Auto-computed mula sa kg...';
        feedInput.value = '';
        autoBadge.style.display = '';

        // Re-compute if hito_kg already has a value (e.g. after validation error)
        autoFillFeed();

    } else {
        hitoKgField.style.display = 'none';
        hitoKgInput.required = false;
        hitoKgInput.value = '';
        feedInput.readOnly = false;
        feedInput.placeholder = 'e.g. 500';
        feedInput.value = '';
        autoBadge.style.display = 'none';
    }
}

<?php if ($error): ?>openModal('modal-usage');<?php endif; ?>
</script>

<?php require_once 'navbar_end.php'; ?>