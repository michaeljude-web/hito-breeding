<?php
$page_title = 'Hatchery & Fingerling';
$page_sub   = 'Track breeding activities, hatching performance and fingerling transfers.';
require_once '../db/connection.php';
require_once 'navbar.php';

$success = '';
$error   = '';
$staff_id = 1; // replace with $_SESSION['staff_id'] when ready

// ── ADD HATCHERY RECORD ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_hatchery') {
    $date         = trim($_POST['record_date'] ?? '');
    $female_count = (int)($_POST['female_count'] ?? 0);
    $eggs_prod    = (int)($_POST['eggs_produced'] ?? 0);
    $eggs_hatch   = (int)($_POST['eggs_hatched'] ?? 0);

    if ($date && $female_count > 0 && $eggs_prod > 0 && $eggs_hatch >= 0) {
        if ($eggs_hatch > $eggs_prod) {
            $error = 'Eggs hatched cannot exceed eggs produced.';
        } else {
            $pdo->prepare("INSERT INTO hatchery_records (record_date, female_count, eggs_produced, eggs_hatched, logged_by) VALUES (?,?,?,?,?)")
                ->execute([$date, $female_count, $eggs_prod, $eggs_hatch, $staff_id]);
            $success = 'Hatchery record added successfully.';
        }
    } else {
        $error = 'Please fill in all fields correctly.';
    }
}

// ── ADD FINGERLING TRANSFER ───────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_transfer') {
    $date        = trim($_POST['transfer_date'] ?? '');
    $hatchery_id = (int)($_POST['hatchery_id'] ?? 0);
    $pond        = trim($_POST['pond_destination'] ?? '');
    $qty         = (int)($_POST['quantity'] ?? 0);

    if ($date && $hatchery_id && $pond && $qty > 0) {
        $pdo->prepare("INSERT INTO fingerling_transfers (transfer_date, hatchery_id, pond_destination, quantity, logged_by) VALUES (?,?,?,?,?)")
            ->execute([$date, $hatchery_id, $pond, $qty, $staff_id]);
        $success = 'Fingerling transfer logged successfully.';
    } else {
        $error = 'Please fill in all fields correctly.';
    }
}

// ── FETCH ─────────────────────────────────────────────────────
$hatchery_records = $pdo->query("SELECT * FROM hatchery_records ORDER BY record_date DESC")->fetchAll(PDO::FETCH_ASSOC);

$transfers = $pdo->query("
    SELECT ft.*, hr.record_date AS hatchery_date, hr.eggs_hatched
    FROM fingerling_transfers ft
    JOIN hatchery_records hr ON ft.hatchery_id = hr.id
    ORDER BY ft.transfer_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// For transfer dropdown — only hatchery records that still have available fingerlings
$available = $pdo->query("
    SELECT hr.id, hr.record_date, hr.eggs_hatched,
           COALESCE(SUM(ft.quantity), 0) AS already_transferred,
           (hr.eggs_hatched - COALESCE(SUM(ft.quantity), 0)) AS available
    FROM hatchery_records hr
    LEFT JOIN fingerling_transfers ft ON ft.hatchery_id = hr.id
    GROUP BY hr.id
    HAVING available > 0
    ORDER BY hr.record_date DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    /* TABS */
    .tabs { display:flex; gap:0; border-bottom:2px solid #e2e2e6; margin-bottom:24px; }
    .tab-btn {
        padding:10px 22px; font-family:inherit; font-size:12px; font-weight:600;
        letter-spacing:0.06em; color:#aaa; background:none; border:none;
        border-bottom:2px solid transparent; margin-bottom:-2px; cursor:pointer;
        transition:color 0.15s, border-color 0.15s; display:flex; align-items:center; gap:7px;
    }
    .tab-btn:hover { color:#555; }
    .tab-btn.active { color:#1a1a2e; border-bottom-color:#1a1a2e; }
    .tab-panel { display:none; }
    .tab-panel.active { display:block; }

    /* TOOLBAR */
    .toolbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; gap:12px; flex-wrap:wrap; }
    .count-label { font-size:11px; color:#aaa; }

    .btn-primary {
        display:flex; align-items:center; gap:8px; padding:9px 16px;
        background:#1a1a2e; color:#fff; font-family:inherit; font-size:12px;
        font-weight:600; letter-spacing:0.06em; border:none; cursor:pointer; transition:opacity 0.2s;
    }
    .btn-primary:hover { opacity:0.82; }

    /* ALERT */
    .alert { padding:10px 14px; font-size:12px; margin-bottom:16px; border-left:3px solid; display:flex; align-items:center; gap:8px; }
    .alert.success { background:#f0fdf4; color:#16a34a; border-color:#16a34a; }
    .alert.error   { background:#fef2f2; color:#b91c1c; border-color:#b91c1c; }

    /* TABLE */
    .table-wrap { background:#fff; border:1px solid #e2e2e6; overflow-x:auto; margin-bottom:8px; }
    table { width:100%; border-collapse:collapse; font-size:13px; }
    thead tr { border-bottom:1px solid #e2e2e6; background:#f9f9fb; }
    th { padding:11px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.14em; text-transform:uppercase; color:#aaa; white-space:nowrap; }
    tbody tr { border-bottom:1px solid #f0f0f2; transition:background 0.1s; }
    tbody tr:last-child { border-bottom:none; }
    tbody tr:hover { background:#fafafa; }
    td { padding:12px 16px; color:#444; vertical-align:middle; }

    .val-bold { font-weight:700; color:#111; }

    /* Survival rate badge */
    .rate-badge {
        display:inline-block; font-size:11px; font-weight:700;
        padding:3px 9px; border-radius:2px;
    }
    .rate-high { background:#f0fdf4; color:#16a34a; }
    .rate-mid  { background:#fefce8; color:#ca8a04; }
    .rate-low  { background:#fef2f2; color:#b91c1c; }

    .pond-badge { display:inline-block; font-size:10px; font-weight:600; background:#f0f0f2; color:#555; padding:3px 8px; border-radius:2px; }

    .avail-pill {
        display:inline-block; font-size:10px; font-weight:600;
        padding:2px 8px; border-radius:20px;
        background:#eff6ff; color:#2563eb;
    }

    /* EMPTY */
    .empty-state { text-align:center; padding:50px 20px; color:#ccc; background:#fff; border:1px solid #e2e2e6; }
    .empty-state i { font-size:32px; margin-bottom:10px; display:block; }
    .empty-state p { font-size:13px; }

    /* MODAL */
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:200; align-items:center; justify-content:center; padding:24px; }
    .modal-overlay.show { display:flex; }
    .modal { background:#fff; width:100%; max-width:480px; animation:modalIn 0.22s ease both; }
    @keyframes modalIn { from { opacity:0; transform:translateY(-14px); } to { opacity:1; transform:translateY(0); } }

    .modal-header { display:flex; align-items:center; justify-content:space-between; padding:18px 22px; border-bottom:1px solid #e2e2e6; }
    .modal-header h3 { font-size:15px; font-weight:700; color:#111; }
    .modal-header p  { font-size:11px; color:#aaa; margin-top:2px; }
    .modal-close { width:28px; height:28px; background:#f0f0f2; border:none; cursor:pointer; font-size:12px; color:#666; display:flex; align-items:center; justify-content:center; transition:background 0.15s; }
    .modal-close:hover { background:#e2e2e6; }

    .modal-body { padding:22px; }
    .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .form-group { display:flex; flex-direction:column; gap:6px; }
    .form-group.full { grid-column:1/-1; }
    .form-group label { font-size:10px; font-weight:700; letter-spacing:0.14em; text-transform:uppercase; color:#999; }
    .form-group input, .form-group select {
        border:1px solid #e2e2e6; outline:none; font-family:inherit; font-size:13px;
        color:#111; padding:9px 11px; background:#fafafa;
        transition:border-color 0.2s; border-radius:0; appearance:none;
    }
    .form-group input:focus, .form-group select:focus { border-color:#1a1a2e; background:#fff; }
    .form-group input::placeholder { color:#ccc; }

    .survival-preview {
        grid-column:1/-1; background:#f0f0f2; padding:10px 14px;
        font-size:12px; color:#555; display:flex; align-items:center; gap:8px;
    }
    .survival-preview span { font-weight:700; color:#111; font-size:14px; }

    .modal-footer { padding:14px 22px; border-top:1px solid #f0f0f2; display:flex; justify-content:flex-end; gap:10px; }
    .btn-cancel { padding:9px 16px; background:#fff; border:1.5px solid #e2e2e6; font-family:inherit; font-size:12px; font-weight:600; color:#666; cursor:pointer; }
    .btn-cancel:hover { background:#f4f4f4; }
    .btn-submit { padding:9px 20px; background:#1a1a2e; border:none; font-family:inherit; font-size:12px; font-weight:600; color:#fff; cursor:pointer; transition:opacity 0.2s; }
    .btn-submit:hover { opacity:0.82; }
</style>

<?php if ($success): ?>
    <div class="alert success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- TABS -->
<div class="tabs">
    <button class="tab-btn active" onclick="switchTab('hatchery', this)">
        <i class="fa-solid fa-egg"></i> Hatchery Records
    </button>
    <button class="tab-btn" onclick="switchTab('transfers', this)">
        <i class="fa-solid fa-fish"></i> Fingerling Transfers
    </button>
</div>

<!-- TAB 1: HATCHERY RECORDS -->
<div class="tab-panel active" id="tab-hatchery">
    <div class="toolbar">
        <span class="count-label"><?= count($hatchery_records) ?> record<?= count($hatchery_records) !== 1 ? 's' : '' ?></span>
        <button class="btn-primary" onclick="openModal('modal-hatchery')">
            <i class="fa-solid fa-plus"></i> Add Record
        </button>
    </div>

    <?php if (empty($hatchery_records)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-egg"></i>
            <p>No hatchery records yet. Click "Add Record" to log a breeding session.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Females Used</th>
                        <th>Eggs Produced</th>
                        <th>Eggs Hatched</th>
                        <th>Survival Rate</th>
                        <th>Fingerlings Available</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hatchery_records as $i => $r):
                        // Compute remaining fingerlings
                        $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM fingerling_transfers WHERE hatchery_id = ?");
                        $stmt->execute([$r['id']]);
                        $transferred = (int)$stmt->fetchColumn();
                        $remaining   = $r['eggs_hatched'] - $transferred;

                        $rate = (float)$r['survival_rate'];
                        $rateClass = $rate >= 75 ? 'rate-high' : ($rate >= 50 ? 'rate-mid' : 'rate-low');
                    ?>
                    <tr>
                        <td style="color:#bbb;font-size:12px;"><?= $i + 1 ?></td>
                        <td><?= date('M d, Y', strtotime($r['record_date'])) ?></td>
                        <td class="val-bold"><?= number_format($r['female_count']) ?></td>
                        <td><?= number_format($r['eggs_produced']) ?></td>
                        <td><?= number_format($r['eggs_hatched']) ?></td>
                        <td><span class="rate-badge <?= $rateClass ?>"><?= $rate ?>%</span></td>
                        <td>
                            <?php if ($remaining > 0): ?>
                                <span class="avail-pill"><?= number_format($remaining) ?> available</span>
                            <?php else: ?>
                                <span style="font-size:11px;color:#ccc;">All transferred</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- TAB 2: FINGERLING TRANSFERS -->
<div class="tab-panel" id="tab-transfers">
    <div class="toolbar">
        <span class="count-label"><?= count($transfers) ?> transfer<?= count($transfers) !== 1 ? 's' : '' ?></span>
        <button class="btn-primary" onclick="openModal('modal-transfer')"
            <?= empty($available) ? 'disabled title="No available fingerlings to transfer"' : '' ?>>
            <i class="fa-solid fa-plus"></i> Log Transfer
        </button>
    </div>

    <?php if (empty($available) && empty($transfers)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-fish"></i>
            <p>No fingerlings available yet. Add a hatchery record first.</p>
        </div>
    <?php elseif (empty($transfers)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-fish"></i>
            <p>No transfers logged yet. Click "Log Transfer" to record a fingerling transfer.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Transfer Date</th>
                        <th>Hatchery Batch</th>
                        <th>Pond Destination</th>
                        <th>Qty Transferred</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transfers as $i => $t): ?>
                    <tr>
                        <td style="color:#bbb;font-size:12px;"><?= $i + 1 ?></td>
                        <td><?= date('M d, Y', strtotime($t['transfer_date'])) ?></td>
                        <td style="font-size:12px;color:#888;">
                            Batch <?= date('M d, Y', strtotime($t['hatchery_date'])) ?>
                            <span style="color:#bbb;">(<?= number_format($t['eggs_hatched']) ?> hatched)</span>
                        </td>
                        <td><span class="pond-badge"><?= htmlspecialchars($t['pond_destination']) ?></span></td>
                        <td class="val-bold"><?= number_format($t['quantity']) ?> pcs</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- MODAL: ADD HATCHERY RECORD -->
<div class="modal-overlay" id="modal-hatchery">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h3>Add Hatchery Record</h3>
                <p>Log a new breeding session.</p>
            </div>
            <button class="modal-close" onclick="closeModal('modal-hatchery')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add_hatchery">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Date of Breeding</label>
                        <input type="date" name="record_date" id="h-date" required>
                    </div>
                    <div class="form-group">
                        <label>Female Count</label>
                        <input type="number" name="female_count" id="h-female" placeholder="e.g. 5" min="1" required>
                    </div>
                    <div class="form-group">
                        <label>Eggs Produced</label>
                        <input type="number" name="eggs_produced" id="h-eggs-prod" placeholder="e.g. 2000" min="1" required oninput="updateSurvival()">
                    </div>
                    <div class="form-group full">
                        <label>Eggs Hatched</label>
                        <input type="number" name="eggs_hatched" id="h-eggs-hatch" placeholder="e.g. 1800" min="0" required oninput="updateSurvival()">
                    </div>
                    <div class="survival-preview">
                        <i class="fa-solid fa-chart-simple" style="color:#aaa;"></i>
                        Survival Rate: <span id="survival-display">—</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('modal-hatchery')">Cancel</button>
                <button type="submit" class="btn-submit"><i class="fa-solid fa-plus"></i> Save Record</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: LOG TRANSFER -->
<div class="modal-overlay" id="modal-transfer">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h3>Log Fingerling Transfer</h3>
                <p>Record fingerlings transferred to a grow-out pond.</p>
            </div>
            <button class="modal-close" onclick="closeModal('modal-transfer')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST" action="">
            <input type="hidden" name="action" value="add_transfer">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Transfer Date</label>
                        <input type="date" name="transfer_date" required>
                    </div>
                    <div class="form-group full">
                        <label>Hatchery Batch <span style="font-weight:400;color:#bbb;text-transform:none;letter-spacing:0;">(source)</span></label>
                        <select name="hatchery_id" id="hatchery-select" required onchange="updateAvailable(this)">
                            <option value="" disabled selected>Select batch</option>
                            <?php foreach ($available as $a): ?>
                                <option value="<?= $a['id'] ?>" data-available="<?= $a['available'] ?>">
                                    <?= date('M d, Y', strtotime($a['record_date'])) ?> — <?= number_format($a['available']) ?> available
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Pond Destination</label>
                        <input type="text" name="pond_destination" placeholder="e.g. Pond A" required>
                    </div>
                    <div class="form-group">
                        <label>Quantity <span id="avail-hint" style="font-weight:400;color:#bbb;text-transform:none;letter-spacing:0;"></span></label>
                        <input type="number" name="quantity" id="qty-input" placeholder="pcs" min="1" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('modal-transfer')">Cancel</button>
                <button type="submit" class="btn-submit"><i class="fa-solid fa-fish"></i> Log Transfer</button>
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

    // Live survival rate preview
    function updateSurvival() {
        const prod  = parseFloat(document.getElementById('h-eggs-prod').value) || 0;
        const hatch = parseFloat(document.getElementById('h-eggs-hatch').value) || 0;
        const el    = document.getElementById('survival-display');
        if (prod > 0 && hatch >= 0) {
            const rate = ((hatch / prod) * 100).toFixed(2);
            el.textContent = rate + '%';
            el.style.color = rate >= 75 ? '#16a34a' : rate >= 50 ? '#ca8a04' : '#b91c1c';
        } else {
            el.textContent = '—';
            el.style.color = '#111';
        }
    }

    // Show max available on transfer modal
    function updateAvailable(sel) {
        const opt = sel.options[sel.selectedIndex];
        const avail = opt.dataset.available;
        const hint  = document.getElementById('avail-hint');
        const qtyIn = document.getElementById('qty-input');
        if (avail) {
            hint.textContent = '— max: ' + parseInt(avail).toLocaleString() + ' pcs';
            qtyIn.max = avail;
        }
    }

    // Pre-fill today's date
    const today = new Date().toISOString().split('T')[0];
    const dateInputs = document.querySelectorAll('input[type="date"]');
    dateInputs.forEach(d => { if (!d.value) d.value = today; });

    // Re-open modal on error
    <?php if ($error && ($_POST['action'] ?? '') === 'add_hatchery'): ?>openModal('modal-hatchery');<?php endif; ?>
    <?php if ($error && ($_POST['action'] ?? '') === 'add_transfer'): ?>openModal('modal-transfer');<?php endif; ?>
</script>

<?php require_once 'navbar_end.php'; ?>