<?php
$page_title = 'Feeding & Monitoring';
$page_sub   = 'Manage feeding schedules and track feed consumption.';
require_once '../db/connection.php';
require_once 'sidebar.php';

$success = '';
$error   = '';

// ── DELETE SCHEDULE ───────────────────────────────────────────
if (isset($_GET['delete_schedule'])) {
    $pdo->prepare("DELETE FROM feeding_schedules WHERE id = ?")->execute([(int)$_GET['delete_schedule']]);
    header('Location: feeding.php?deleted=1');
    exit;
}

// ── DELETE LOG ────────────────────────────────────────────────
if (isset($_GET['delete_log'])) {
    $pdo->prepare("DELETE FROM feed_consumption WHERE id = ?")->execute([(int)$_GET['delete_log']]);
    header('Location: feeding.php?log_deleted=1');
    exit;
}

if (isset($_GET['deleted']))     $success = 'Feeding schedule deleted.';
if (isset($_GET['log_deleted'])) $success = 'Consumption log deleted.';

// ── ADD SCHEDULE ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_schedule') {
    $pond      = trim($_POST['pond_name'] ?? '');
    $feed_type = trim($_POST['feed_type'] ?? '');
    $amount    = trim($_POST['amount_kg'] ?? '');
    $frequency = trim($_POST['frequency'] ?? '');
    $feed_time = trim($_POST['feed_time'] ?? '');

    if ($pond && $feed_type && $amount && $frequency && $feed_time) {
        $pdo->prepare("INSERT INTO feeding_schedules (pond_name, feed_type, amount_kg, frequency, feed_time, created_by) VALUES (?,?,?,?,?,?)")
            ->execute([$pond, $feed_type, $amount, $frequency, $feed_time, 0]);
        $success = 'Feeding schedule added.';
    } else {
        $error = 'Please fill in all fields.';
    }
}

// ── FETCH ─────────────────────────────────────────────────────
$schedules = $pdo->query("SELECT * FROM feeding_schedules ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

$logs = $pdo->query("
    SELECT fc.*, fs.pond_name, fs.feed_type, fs.amount_kg AS scheduled_kg
    FROM feed_consumption fc
    JOIN feeding_schedules fs ON fc.schedule_id = fs.id
    ORDER BY fc.session_date DESC, fc.session_time DESC
    LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .tabs { display:flex; gap:0; border-bottom:2px solid #e4e4e4; margin-bottom:24px; }

    .tab-btn {
        padding:10px 22px; font-family:inherit; font-size:12px; font-weight:600;
        letter-spacing:0.06em; color:#aaa; background:none; border:none;
        border-bottom:2px solid transparent; margin-bottom:-2px; cursor:pointer;
        transition:color 0.15s, border-color 0.15s;
        display:flex; align-items:center; gap:7px;
    }

    .tab-btn:hover { color:#555; }
    .tab-btn.active { color:#111; border-bottom-color:#111; }
    .tab-panel { display:none; }
    .tab-panel.active { display:block; }

    .toolbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; gap:12px; flex-wrap:wrap; }
    .count-label { font-size:11px; color:#aaa; }

    .btn-primary {
        display:flex; align-items:center; gap:8px;
        padding:9px 16px; background:#111; color:#fff;
        font-family:inherit; font-size:12px; font-weight:600;
        letter-spacing:0.06em; border:none; cursor:pointer; transition:opacity 0.2s;
    }
    .btn-primary:hover { opacity:0.82; }

    .alert { padding:10px 14px; font-size:12px; margin-bottom:16px; border-left:3px solid; display:flex; align-items:center; gap:8px; }
    .alert.success { background:#f0fdf4; color:#16a34a; border-color:#16a34a; }
    .alert.error   { background:#fef2f2; color:#b91c1c; border-color:#b91c1c; }

    /* Schedule cards */
    .schedule-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; }

    .schedule-card { background:#fff; border:1px solid #e4e4e4; padding:18px 20px; transition:box-shadow 0.18s; }
    .schedule-card:hover { box-shadow:0 3px 14px rgba(0,0,0,0.07); }

    .sc-top { display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:12px; }
    .sc-pond { font-size:14px; font-weight:700; color:#111; }
    .sc-time { font-size:11px; font-weight:600; color:#fff; background:#111; padding:3px 9px; border-radius:20px; white-space:nowrap; }

    .sc-details { display:flex; flex-direction:column; gap:5px; }
    .sc-row { display:flex; align-items:center; gap:7px; font-size:12px; color:#666; }
    .sc-row i { width:14px; text-align:center; font-size:11px; color:#bbb; }

    .sc-footer { margin-top:14px; padding-top:12px; border-top:1px solid #f0f0f2; display:flex; justify-content:flex-end; }

    .btn-delete {
        display:flex; align-items:center; gap:6px;
        padding:7px 12px; background:#fff; color:#b91c1c;
        border:1px solid #fca5a5; font-family:inherit; font-size:11px;
        font-weight:600; cursor:pointer; transition:background 0.15s, color 0.15s;
    }
    .btn-delete:hover { background:#b91c1c; color:#fff; border-color:#b91c1c; }

    .empty-state { text-align:center; padding:50px 20px; color:#ccc; background:#fff; border:1px solid #e4e4e4; }
    .empty-state i { font-size:32px; margin-bottom:10px; display:block; }
    .empty-state p { font-size:13px; }

    /* Table */
    .table-wrap { background:#fff; border:1px solid #e4e4e4; overflow-x:auto; }
    table { width:100%; border-collapse:collapse; font-size:13px; }
    thead tr { border-bottom:1px solid #e4e4e4; background:#f9f9f9; }
    th { padding:11px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.14em; text-transform:uppercase; color:#aaa; white-space:nowrap; }
    tbody tr { border-bottom:1px solid #f0f0f0; transition:background 0.1s; }
    tbody tr:last-child { border-bottom:none; }
    tbody tr:hover { background:#fafafa; }
    td { padding:12px 16px; color:#444; vertical-align:middle; }

    .pond-badge { display:inline-block; font-size:10px; font-weight:600; background:#f0f0f0; color:#555; padding:3px 8px; border-radius:2px; letter-spacing:0.04em; }
    .consumed-val { font-weight:700; color:#111; }
    .variance { font-size:11px; font-weight:600; padding:2px 7px; border-radius:2px; }
    .variance.ok  { background:#f0fdf4; color:#16a34a; }
    .variance.low { background:#fef2f2; color:#b91c1c; }

    .btn-icon-sm {
        width:26px; height:26px; display:flex; align-items:center; justify-content:center;
        border:1px solid #e4e4e4; background:#fff; cursor:pointer; font-size:11px; color:#888;
        transition:background 0.15s, color 0.15s;
    }
    .btn-icon-sm.danger:hover { background:#b91c1c; color:#fff; border-color:#b91c1c; }

    /* Modal */
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:200; align-items:center; justify-content:center; padding:24px; }
    .modal-overlay.show { display:flex; }
    .modal { background:#fff; width:100%; max-width:500px; animation:modalIn 0.22s ease both; }
    @keyframes modalIn { from { opacity:0; transform:translateY(-14px); } to { opacity:1; transform:translateY(0); } }

    .modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 22px; border-bottom:1px solid #e4e4e4; }
    .modal-header h3 { font-size:15px; font-weight:700; color:#111; }
    .modal-header p  { font-size:11px; color:#aaa; margin-top:2px; }
    .modal-close { width:28px; height:28px; background:#f4f4f4; border:none; cursor:pointer; font-size:12px; color:#666; display:flex; align-items:center; justify-content:center; transition:background 0.15s; }
    .modal-close:hover { background:#e4e4e4; }

    .modal-body { padding:22px; }
    .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .form-group { display:flex; flex-direction:column; gap:6px; }
    .form-group.full { grid-column:1/-1; }
    .form-group label { font-size:10px; font-weight:700; letter-spacing:0.14em; text-transform:uppercase; color:#999; }
    .form-group input, .form-group select { border:1px solid #e4e4e4; outline:none; font-family:inherit; font-size:13px; color:#111; padding:9px 11px; background:#fafafa; transition:border-color 0.2s; border-radius:0; appearance:none; }
    .form-group input:focus, .form-group select:focus { border-color:#111; background:#fff; }
    .form-group input::placeholder { color:#ccc; }

    .modal-footer { padding:14px 22px; border-top:1px solid #f0f0f0; display:flex; justify-content:flex-end; gap:10px; }
    .btn-cancel { padding:9px 16px; background:#fff; border:1.5px solid #e4e4e4; font-family:inherit; font-size:12px; font-weight:600; color:#666; cursor:pointer; transition:background 0.15s; }
    .btn-cancel:hover { background:#f4f4f4; }
    .btn-submit { padding:9px 20px; background:#111; border:none; font-family:inherit; font-size:12px; font-weight:600; color:#fff; cursor:pointer; transition:opacity 0.2s; }
    .btn-submit:hover { opacity:0.82; }

    @media (max-width:768px) { .schedule-grid { grid-template-columns:1fr; } }
</style>

<?php if ($success): ?>
    <div class="alert success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="tabs">
    <button class="tab-btn active" onclick="switchTab('schedules', this)">
        <i class="fa-solid fa-calendar-days"></i> Feeding Schedules
    </button>
    <button class="tab-btn" onclick="switchTab('logs', this)">
        <i class="fa-solid fa-clipboard-list"></i> Consumption Log
    </button>
</div>

<!-- TAB 1: SCHEDULES -->
<div class="tab-panel active" id="tab-schedules">
    <div class="toolbar">
        <span class="count-label"><?= count($schedules) ?> schedule<?= count($schedules) !== 1 ? 's' : '' ?></span>
        <button class="btn-primary" onclick="openModal('modal-schedule')">
            <i class="fa-solid fa-plus"></i> Add Schedule
        </button>
    </div>

    <?php if (empty($schedules)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-bowl-food"></i>
            <p>No feeding schedules yet.</p>
        </div>
    <?php else: ?>
        <div class="schedule-grid">
            <?php foreach ($schedules as $s): ?>
            <div class="schedule-card">
                <div class="sc-top">
                    <div class="sc-pond"><?= htmlspecialchars($s['pond_name']) ?></div>
                    <div class="sc-time"><?= date('g:i A', strtotime($s['feed_time'])) ?></div>
                </div>
                <div class="sc-details">
                    <div class="sc-row"><i class="fa-solid fa-box"></i> <?= htmlspecialchars($s['feed_type']) ?></div>
                    <div class="sc-row"><i class="fa-solid fa-weight-hanging"></i> <?= number_format($s['amount_kg'], 2) ?> kg per session</div>
                    <div class="sc-row"><i class="fa-solid fa-rotate"></i> <?= htmlspecialchars($s['frequency']) ?></div>
                </div>
                <div class="sc-footer">
                    <button class="btn-delete"
                        onclick="if(confirm('Delete schedule for <?= htmlspecialchars($s['pond_name'], ENT_QUOTES) ?>? This will also delete all its consumption logs.')) window.location='feeding.php?delete_schedule=<?= $s['id'] ?>'">
                        <i class="fa-solid fa-trash"></i> Delete
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- TAB 2: LOGS -->
<div class="tab-panel" id="tab-logs">
    <div class="toolbar">
        <span class="count-label">Last 100 entries</span>
    </div>

    <?php if (empty($logs)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-clipboard-list"></i>
            <p>No consumption logs yet.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Pond</th>
                        <th>Feed Type</th>
                        <th>Scheduled</th>
                        <th>Consumed</th>
                        <th>Variance</th>
                        <th>Notes</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log):
                        $diff = $log['consumed_kg'] - $log['scheduled_kg'];
                        $varClass = $diff >= 0 ? 'ok' : 'low';
                        $varLabel = ($diff >= 0 ? '+' : '') . number_format($diff, 2) . ' kg';
                    ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($log['session_date'])) ?></td>
                        <td><?= date('g:i A', strtotime($log['session_time'])) ?></td>
                        <td><span class="pond-badge"><?= htmlspecialchars($log['pond_name']) ?></span></td>
                        <td><?= htmlspecialchars($log['feed_type']) ?></td>
                        <td><?= number_format($log['scheduled_kg'], 2) ?> kg</td>
                        <td><span class="consumed-val"><?= number_format($log['consumed_kg'], 2) ?> kg</span></td>
                        <td><span class="variance <?= $varClass ?>"><?= $varLabel ?></span></td>
                        <td style="color:#bbb;font-size:12px;"><?= $log['notes'] ? htmlspecialchars($log['notes']) : '—' ?></td>
                        <td>
                            <button class="btn-icon-sm danger" title="Delete log"
                                onclick="if(confirm('Delete this log entry?')) window.location='feeding.php?delete_log=<?= $log['id'] ?>'">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL: ADD SCHEDULE -->
<div class="modal-overlay" id="modal-schedule">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h3>Add Feeding Schedule</h3>
                <p>Set pond, feed type, amount, frequency and time.</p>
            </div>
            <button class="modal-close" onclick="closeModal('modal-schedule')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add_schedule">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Pond Name</label>
                        <input type="text" name="pond_name" placeholder="e.g. Pond A" required>
                    </div>
                    <div class="form-group">
                        <label>Feed Type</label>
                        <input type="text" name="feed_type" placeholder="e.g. Pellet, Fry mash" required>
                    </div>
                    <div class="form-group">
                        <label>Amount (kg)</label>
                        <input type="number" name="amount_kg" placeholder="0.00" step="0.01" min="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Feed Time</label>
                        <input type="time" name="feed_time" required>
                    </div>
                    <div class="form-group full">
                        <label>Frequency</label>
                        <select name="frequency" required>
                            <option value="" disabled selected>Select frequency</option>
                            <option value="Once daily">Once daily</option>
                            <option value="Twice daily">Twice daily</option>
                            <option value="3x daily">3x daily</option>
                            <option value="Every other day">Every other day</option>
                            <option value="Weekly">Weekly</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('modal-schedule')">Cancel</button>
                <button type="submit" class="btn-submit"><i class="fa-solid fa-plus"></i> Add Schedule</button>
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

    <?php if ($error): ?> openModal('modal-schedule'); <?php endif; ?>
</script>

<?php require_once 'sidebar_end.php'; ?>