<?php
$page_title = 'Hatchery & Fingerling';
$page_sub   = 'Track breeding activities, hatching performance and fingerling transfers.';
require_once '../db/connection.php';
require_once 'sidebar.php';

$success = '';
$error   = '';

// ── DELETES ───────────────────────────────────────────────────
if (isset($_GET['delete_hatchery'])) {
    $pdo->prepare("DELETE FROM hatchery_records WHERE id = ?")->execute([(int)$_GET['delete_hatchery']]);
    header('Location: hatchery.php?deleted=1'); exit;
}
if (isset($_GET['delete_transfer'])) {
    $pdo->prepare("DELETE FROM fingerling_transfers WHERE id = ?")->execute([(int)$_GET['delete_transfer']]);
    header('Location: hatchery.php?transfer_deleted=1'); exit;
}
if (isset($_GET['deleted']))          $success = 'Hatchery record deleted.';
if (isset($_GET['transfer_deleted'])) $success = 'Fingerling transfer deleted.';

// ── FETCH ─────────────────────────────────────────────────────
$hatchery_records = $pdo->query("SELECT * FROM hatchery_records ORDER BY record_date DESC")->fetchAll(PDO::FETCH_ASSOC);

$transfers = $pdo->query("
    SELECT ft.*, hr.record_date AS hatchery_date, hr.eggs_hatched
    FROM fingerling_transfers ft
    JOIN hatchery_records hr ON ft.hatchery_id = hr.id
    ORDER BY ft.transfer_date DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
    .tabs { display:flex; gap:0; border-bottom:2px solid #e4e4e4; margin-bottom:24px; }
    .tab-btn { padding:10px 22px; font-family:inherit; font-size:12px; font-weight:600; letter-spacing:0.06em; color:#aaa; background:none; border:none; border-bottom:2px solid transparent; margin-bottom:-2px; cursor:pointer; transition:color 0.15s,border-color 0.15s; display:flex; align-items:center; gap:7px; }
    .tab-btn:hover { color:#555; }
    .tab-btn.active { color:#111; border-bottom-color:#111; }
    .tab-panel { display:none; }
    .tab-panel.active { display:block; }

    .toolbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; gap:12px; flex-wrap:wrap; }
    .count-label { font-size:11px; color:#aaa; }

    .alert { padding:10px 14px; font-size:12px; margin-bottom:16px; border-left:3px solid; display:flex; align-items:center; gap:8px; }
    .alert.success { background:#f0fdf4; color:#16a34a; border-color:#16a34a; }
    .alert.error   { background:#fef2f2; color:#b91c1c; border-color:#b91c1c; }

    .table-wrap { background:#fff; border:1px solid #e4e4e4; overflow-x:auto; }
    table { width:100%; border-collapse:collapse; font-size:13px; }
    thead tr { border-bottom:1px solid #e4e4e4; background:#f9f9f9; }
    th { padding:11px 16px; text-align:left; font-size:10px; font-weight:700; letter-spacing:0.14em; text-transform:uppercase; color:#aaa; white-space:nowrap; }
    tbody tr { border-bottom:1px solid #f0f0f0; transition:background 0.1s; }
    tbody tr:last-child { border-bottom:none; }
    tbody tr:hover { background:#fafafa; }
    td { padding:12px 16px; color:#444; vertical-align:middle; }

    .val-bold { font-weight:700; color:#111; }
    .rate-badge { display:inline-block; font-size:11px; font-weight:700; padding:3px 9px; border-radius:2px; }
    .rate-high { background:#f0fdf4; color:#16a34a; }
    .rate-mid  { background:#fefce8; color:#ca8a04; }
    .rate-low  { background:#fef2f2; color:#b91c1c; }
    .pond-badge { display:inline-block; font-size:10px; font-weight:600; background:#f0f0f0; color:#555; padding:3px 8px; border-radius:2px; }
    .avail-pill { display:inline-block; font-size:10px; font-weight:600; padding:2px 8px; border-radius:20px; background:#eff6ff; color:#2563eb; }

    .empty-state { text-align:center; padding:50px 20px; color:#ccc; background:#fff; border:1px solid #e4e4e4; }
    .empty-state i { font-size:32px; margin-bottom:10px; display:block; }
    .empty-state p { font-size:13px; }

    .btn-icon-sm { width:26px; height:26px; display:flex; align-items:center; justify-content:center; border:1px solid #e4e4e4; background:#fff; cursor:pointer; font-size:11px; color:#888; transition:background 0.15s,color 0.15s; }
    .btn-icon-sm.danger:hover { background:#b91c1c; color:#fff; border-color:#b91c1c; }
</style>

<?php if ($success): ?>
    <div class="alert success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>

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
    </div>
    <?php if (empty($hatchery_records)): ?>
        <div class="empty-state"><i class="fa-solid fa-egg"></i><p>No hatchery records yet. Staff can add records from their portal.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>#</th><th>Date</th><th>Females</th>
                        <th>Eggs Produced</th><th>Eggs Hatched</th>
                        <th>Survival Rate</th><th>Available Fingerlings</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hatchery_records as $i => $r):
                        $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity),0) FROM fingerling_transfers WHERE hatchery_id = ?");
                        $stmt->execute([$r['id']]);
                        $transferred = (int)$stmt->fetchColumn();
                        $remaining   = $r['eggs_hatched'] - $transferred;
                        $rate        = (float)$r['survival_rate'];
                        $rateClass   = $rate >= 75 ? 'rate-high' : ($rate >= 50 ? 'rate-mid' : 'rate-low');
                    ?>
                    <tr>
                        <td style="color:#bbb;font-size:12px;"><?= $i+1 ?></td>
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
                        <td>
                            <button class="btn-icon-sm danger" title="Delete"
                                onclick="if(confirm('Delete this hatchery record? All related transfers will also be deleted.')) window.location='hatchery.php?delete_hatchery=<?= $r['id'] ?>'">
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

<!-- TAB 2: TRANSFERS -->
<div class="tab-panel" id="tab-transfers">
    <div class="toolbar">
        <span class="count-label"><?= count($transfers) ?> transfer<?= count($transfers) !== 1 ? 's' : '' ?></span>
    </div>
    <?php if (empty($transfers)): ?>
        <div class="empty-state"><i class="fa-solid fa-fish"></i><p>No fingerling transfers yet.</p></div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>#</th><th>Transfer Date</th><th>Hatchery Batch</th><th>Pond</th><th>Qty Transferred</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach ($transfers as $i => $t): ?>
                    <tr>
                        <td style="color:#bbb;font-size:12px;"><?= $i+1 ?></td>
                        <td><?= date('M d, Y', strtotime($t['transfer_date'])) ?></td>
                        <td style="font-size:12px;color:#888;">
                            Batch <?= date('M d, Y', strtotime($t['hatchery_date'])) ?>
                            <span style="color:#bbb;">(<?= number_format($t['eggs_hatched']) ?> hatched)</span>
                        </td>
                        <td><span class="pond-badge"><?= htmlspecialchars($t['pond_destination']) ?></span></td>
                        <td class="val-bold"><?= number_format($t['quantity']) ?> pcs</td>
                        <td>
                            <button class="btn-icon-sm danger" title="Delete"
                                onclick="if(confirm('Delete this transfer record?')) window.location='hatchery.php?delete_transfer=<?= $t['id'] ?>'">
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

<script>
    function switchTab(tab, btn) {
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('tab-' + tab).classList.add('active');
        btn.classList.add('active');
    }
</script>

<?php require_once 'sidebar_end.php'; ?>