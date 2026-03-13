<?php
$page_title = 'Hatchery & Fingerling';
$page_sub   = 'Track breeding activities and fingerling transfers.';
require_once '../db/connection.php';
require_once 'navbar.php';

$success = '';
$error   = '';
$staff_id = 1; 

$ponds = ['Pond 1', 'Pond 2', 'Pond 3', 'Pond 4'];


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_hatchery') {
    $female_count  = (int)($_POST['female_count'] ?? 0);
    $estimated_fry = (int)($_POST['estimated_fry'] ?? 0);

    if ($female_count > 0 && $estimated_fry > 0) {
        $pdo->prepare("INSERT INTO hatchery_records (female_count, estimated_fry, record_date, logged_by) VALUES (?,?,CURDATE(),?)")
            ->execute([$female_count, $estimated_fry, $staff_id]);
        $success = 'Breeding record added successfully.';
    } else {
        $error = 'Please fill in all fields correctly.';
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_transfer') {
    $hatchery_id = (int)($_POST['hatchery_id'] ?? 0);
    $pond        = trim($_POST['pond_destination'] ?? '');
    $qty         = (int)($_POST['quantity'] ?? 0);

    if ($hatchery_id && $pond && $qty > 0) {


        $dupCheck = $pdo->prepare("SELECT id FROM fingerling_transfers WHERE hatchery_id = ? AND pond_destination = ?");
        $dupCheck->execute([$hatchery_id, $pond]);
        if ($dupCheck->fetch()) {
            $error = "This batch has already been transferred to {$pond}.";
        } else {

            $stmt = $pdo->prepare("
                SELECT hr.estimated_fry - COALESCE(SUM(ft.quantity), 0) AS available
                FROM hatchery_records hr
                LEFT JOIN fingerling_transfers ft ON ft.hatchery_id = hr.id
                WHERE hr.id = ?
                GROUP BY hr.id
            ");
            $stmt->execute([$hatchery_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $available = $row ? (int)$row['available'] : 0;

            if ($qty > $available) {
                $error = "Only {$available} fingerlings remaining in this batch.";
            } else {
                $pdo->prepare("INSERT INTO fingerling_transfers (hatchery_id, transfer_date, pond_destination, quantity, logged_by) VALUES (?,CURDATE(),?,?,?)")
                    ->execute([$hatchery_id, $pond, $qty, $staff_id]);
                $success = 'Fingerling transfer logged successfully.';
            }
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}


$records = $pdo->query("
    SELECT hr.*,
           COALESCE(SUM(ft.quantity), 0) AS transferred,
           (hr.estimated_fry - COALESCE(SUM(ft.quantity), 0)) AS available
    FROM hatchery_records hr
    LEFT JOIN fingerling_transfers ft ON ft.hatchery_id = hr.id
    GROUP BY hr.id
    ORDER BY hr.record_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

$transfers = $pdo->query("
    SELECT ft.*, hr.record_date AS hatchery_date, hr.estimated_fry
    FROM fingerling_transfers ft
    JOIN hatchery_records hr ON ft.hatchery_id = hr.id
    ORDER BY ft.transfer_date DESC
")->fetchAll(PDO::FETCH_ASSOC);


$available_batches = array_filter($records, fn($r) => (int)$r['available'] > 0);


$used_combos = [];
$stmt = $pdo->query("SELECT hatchery_id, pond_destination FROM fingerling_transfers");
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $used_combos[$row['hatchery_id']][] = $row['pond_destination'];
}
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
    .btn-primary:disabled { opacity:.35; cursor:not-allowed; }

    .alert { padding:10px 14px; font-size:12px; margin-bottom:16px; border-left:3px solid; display:flex; align-items:center; gap:8px; }
    .alert.success { background:#f0fdf4; color:#16a34a; border-color:#16a34a; }
    .alert.error   { background:#fef2f2; color:#b91c1c; border-color:#b91c1c; }

    .table-wrap { background:#fff; border:1px solid #e2e2e6; overflow-x:auto; }
    table { width:100%; border-collapse:collapse; font-size:13px; }
    thead tr { border-bottom:1px solid #e2e2e6; background:#f9f9fb; }
    th { padding:11px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:#aaa; white-space:nowrap; }
    tbody tr { border-bottom:1px solid #f0f0f2; transition:background .1s; }
    tbody tr:last-child { border-bottom:none; }
    tbody tr:hover { background:#fafafa; }
    td { padding:12px 16px; color:#444; vertical-align:middle; }

    .val-bold { font-weight:700; color:#111; }
    .pond-badge { display:inline-block; font-size:10px; font-weight:600; background:#f0f0f2; color:#555; padding:3px 8px; border-radius:2px; }
    .avail-pill { display:inline-block; font-size:10px; font-weight:600; padding:2px 8px; border-radius:20px; background:#eff6ff; color:#2563eb; }
    .done-pill  { display:inline-block; font-size:10px; font-weight:600; padding:2px 8px; border-radius:20px; background:#f0f0f2; color:#aaa; }

    .empty-state { text-align:center; padding:50px 20px; color:#ccc; background:#fff; border:1px solid #e2e2e6; }
    .empty-state i { font-size:32px; margin-bottom:10px; display:block; }
    .empty-state p { font-size:13px; }

    /* Modal */
    .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:200; align-items:center; justify-content:center; padding:24px; }
    .modal-overlay.show { display:flex; }
    .modal { background:#fff; width:100%; max-width:440px; animation:modalIn .22s ease both; }
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
    .form-group input, .form-group select { border:1px solid #e2e2e6; outline:none; font-family:inherit; font-size:13px; color:#111; padding:9px 11px; background:#fafafa; transition:border-color .2s; border-radius:0; appearance:none; }
    .form-group input:focus, .form-group select:focus { border-color:#1a1a2e; background:#fff; }
    .form-group input::placeholder { color:#ccc; }
    .form-group .hint { font-size:10px; color:#bbb; }

    .info-box { grid-column:1/-1; background:#f9f9fb; border:1px solid #e2e2e6; padding:10px 14px; font-size:12px; color:#888; display:flex; align-items:center; gap:8px; }
    .info-box strong { color:#1a1a2e; }

    .avail-display { grid-column:1/-1; background:#eff6ff; border:1px solid #bfdbfe; padding:10px 14px; font-size:12px; color:#1d4ed8; display:none; align-items:center; gap:8px; }

    .modal-footer { padding:14px 22px; border-top:1px solid #f0f0f2; display:flex; justify-content:flex-end; gap:10px; }
    .btn-cancel { padding:9px 16px; background:#fff; border:1.5px solid #e2e2e6; font-family:inherit; font-size:12px; font-weight:600; color:#666; cursor:pointer; }
    .btn-cancel:hover { background:#f4f4f4; }
    .btn-submit { padding:9px 20px; background:#1a1a2e; border:none; font-family:inherit; font-size:12px; font-weight:600; color:#fff; cursor:pointer; transition:opacity .2s; }
    .btn-submit:hover { opacity:.82; }

    option:disabled { color:#bbb; }
</style>

<?php if ($success): ?>
    <div class="alert success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="tabs">
    <button class="tab-btn active" onclick="switchTab('hatchery',this)">
        <i class="fa-solid fa-egg"></i> Breeding Records
    </button>
    <button class="tab-btn" onclick="switchTab('transfers',this)">
        <i class="fa-solid fa-fish"></i> Fingerling Transfers
    </button>
</div>


<div class="tab-panel active" id="tab-hatchery">
    <div class="toolbar">
        <span class="count-label"><?= count($records) ?> record<?= count($records) !== 1 ? 's' : '' ?></span>
        <button class="btn-primary" onclick="openModal('modal-hatchery')">
            <i class="fa-solid fa-plus"></i> Add Record
        </button>
    </div>

    <?php if (empty($records)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-egg"></i>
            <p>No breeding records yet. Click "Add Record" to get started.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Females Used</th>
                        <th>Est. Fry Count</th>
                        <th>Transferred</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $i => $r): ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($r['record_date'])) ?></td>
                        <td class="val-bold"><?= number_format($r['female_count']) ?></td>
                        <td><?= number_format($r['estimated_fry']) ?> pcs</td>
                        <td><?= $r['transferred'] > 0 ? number_format($r['transferred']) . ' pcs' : '<span style="color:#ccc;font-size:12px;">None yet</span>' ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>


<div class="tab-panel" id="tab-transfers">
    <div class="toolbar">
        <span class="count-label"><?= count($transfers) ?> transfer<?= count($transfers) !== 1 ? 's' : '' ?></span>
        <button class="btn-primary" onclick="openModal('modal-transfer')"
            <?= empty($available_batches) ? 'disabled title="No available fingerlings"' : '' ?>>
            <i class="fa-solid fa-plus"></i> Log Transfer
        </button>
    </div>

    <?php if (empty($transfers)): ?>
        <div class="empty-state">
            <i class="fa-solid fa-fish"></i>
            <p>No transfers logged yet.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Batch</th>
                        <th>Pond</th>
                        <th>Qty Transferred</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transfers as $t): ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($t['transfer_date'])) ?></td>
                        <td style="font-size:12px;color:#888;">Batch <?= date('M d, Y', strtotime($t['hatchery_date'])) ?></td>
                        <td><span class="pond-badge"><?= htmlspecialchars($t['pond_destination']) ?></span></td>
                        <td class="val-bold"><?= number_format($t['quantity']) ?> pcs</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>


<div class="modal-overlay" id="modal-hatchery">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h3>Add Breeding Record</h3>
                <p>Today's date will be recorded automatically.</p>
            </div>
            <button class="modal-close" onclick="closeModal('modal-hatchery')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_hatchery">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Female Count</label>
                        <input type="number" name="female_count" id="h-female" placeholder="e.g. 5" min="1" required oninput="suggestFry()">
                        <span class="hint">Number of female hito used</span>
                    </div>
                    <div class="form-group">
                        <label>Estimated Fry Count</label>
                        <input type="number" name="estimated_fry" id="h-fry" placeholder="e.g. 5,000" min="1" required>
                        <span class="hint">Approx. surviving fry</span>
                    </div>
                    <div class="info-box">
                        <i class="fa-solid fa-circle-info"></i>
                        1 female hito ≈ <strong>1,000–3,000 fry</strong>. Use this as your reference.
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


<div class="modal-overlay" id="modal-transfer">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h3>Log Fingerling Transfer</h3>
                <p>Today's date will be recorded automatically.</p>
            </div>
            <button class="modal-close" onclick="closeModal('modal-transfer')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_transfer">
            <div class="modal-body">
                <div class="form-grid">

                    
                    <div class="form-group full">
                        <label>Hatchery Batch</label>
                        <select name="hatchery_id" id="batch-select" required onchange="onBatchChange(this)">
                            <option value="" disabled selected>Select batch</option>
                            <?php foreach ($available_batches as $b): ?>
                                <option value="<?= $b['id'] ?>"
                                    data-available="<?= $b['available'] ?>"
                                    data-date="<?= date('M d, Y', strtotime($b['record_date'])) ?>">
                                    <?= date('M d, Y', strtotime($b['record_date'])) ?> — <?= number_format($b['available']) ?> remaining
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    
                    <div class="avail-display" id="avail-display">
                        <i class="fa-solid fa-fish"></i>
                        <span id="avail-text"></span>
                    </div>

                    
                    <div class="form-group full">
                        <label>Pond Destination</label>
                        <select name="pond_destination" id="pond-select" required>
                            <option value="" disabled selected>Select pond</option>
                            <?php foreach ($ponds as $pond): ?>
                                <option value="<?= $pond ?>"><?= $pond ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span class="hint" id="pond-hint"></span>
                    </div>

                    
                    <div class="form-group full">
                        <label>Quantity (pcs)</label>
                        <input type="number" name="quantity" id="qty-input" placeholder="How many to transfer?" min="1" required>
                        <span class="hint" id="qty-hint"></span>
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

const usedCombos = <?= json_encode($used_combos) ?>;

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

function onBatchChange(sel) {
    const opt       = sel.options[sel.selectedIndex];
    const available = parseInt(opt.dataset.available);
    const batchId   = parseInt(sel.value);


    const display = document.getElementById('avail-display');
    document.getElementById('avail-text').textContent =
        number_format(available) + ' fingerlings remaining in this batch';
    display.style.display = 'flex';


    document.getElementById('qty-input').max = available;
    document.getElementById('qty-hint').textContent = 'Max: ' + number_format(available) + ' pcs';


    const usedPonds = usedCombos[batchId] || [];
    const pondSel   = document.getElementById('pond-select');

    Array.from(pondSel.options).forEach(opt => {
        if (opt.value === '') return;
        const alreadyUsed = usedPonds.includes(opt.value);
        opt.disabled = alreadyUsed;
        opt.text = alreadyUsed
            ? opt.value + ' (already transferred)'
            : opt.value;
    });


    pondSel.value = '';
    document.getElementById('pond-hint').textContent =
        usedPonds.length > 0
            ? 'Grayed out ponds already received this batch.'
            : '';
}

function suggestFry() {
    const female = parseInt(document.getElementById('h-female').value) || 0;
    const fryInput = document.getElementById('h-fry');
    if (female > 0 && !fryInput.value) {
        fryInput.placeholder = (female * 1000).toLocaleString() + '–' + (female * 3000).toLocaleString();
    }
}

function number_format(n) {
    return parseInt(n).toLocaleString();
}

<?php if ($error && ($_POST['action'] ?? '') === 'add_hatchery'): ?>openModal('modal-hatchery');<?php endif; ?>
<?php if ($error && ($_POST['action'] ?? '') === 'add_transfer'): ?>openModal('modal-transfer');<?php endif; ?>
</script>

<?php require_once 'navbar_end.php'; ?>