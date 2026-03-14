<?php
require_once 'auth.php';
$page_title = 'Sales & Orders';
$page_sub   = 'Record customer orders for harvested hito.';
require_once '../db/connection.php';
require_once 'navbar.php';

$success = '';
$error   = '';
$staff_id = $_SESSION['staff_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_order') {
    $customer    = trim($_POST['customer_name'] ?? '') ?: null;
    $quantity_kg = (float)($_POST['quantity_kg'] ?? 0);
    $price_per_kg = (float)($_POST['price_per_kg'] ?? 0);

    if ($quantity_kg > 0 && $price_per_kg > 0) {
        $pdo->prepare("INSERT INTO orders (customer_name, quantity_kg, price_per_kg, order_date, logged_by) VALUES (?,?,?,CURDATE(),?)")
            ->execute([$customer, $quantity_kg, $price_per_kg, $staff_id]);
        $success = 'Order recorded successfully.';
    } else {
        $error = 'Please fill in all required fields.';
    }
}

$orders = $pdo->query("
    SELECT o.*, CONCAT(s.firstname, ' ', s.lastname) AS staff_name
    FROM orders o
    JOIN staff s ON o.logged_by = s.id
    ORDER BY o.order_date DESC, o.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$summary = $pdo->query("
    SELECT
        COUNT(*) AS total_orders,
        COALESCE(SUM(quantity_kg), 0) AS total_kg,
        COALESCE(SUM(total_price), 0) AS total_revenue
    FROM orders
")->fetch(PDO::FETCH_ASSOC);
?>

<style>
    .summary-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:24px; }
    .summary-card { background:#fff; border:1px solid #e2e2e6; padding:20px 22px; }
    .sc-val { font-size:24px; font-weight:700; color:#111; line-height:1; margin-bottom:4px; }
    .sc-val small { font-size:12px; font-weight:400; color:#aaa; }
    .sc-label { font-size:10px; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:#bbb; }

    .toolbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:16px; gap:12px; flex-wrap:wrap; }
    .count-label { font-size:11px; color:#aaa; }

    .btn-primary { display:flex; align-items:center; gap:8px; padding:9px 16px; background:#1a1a2e; color:#fff; font-family:inherit; font-size:12px; font-weight:600; letter-spacing:0.06em; border:none; cursor:pointer; transition:opacity .2s; }
    .btn-primary:hover { opacity:.82; }

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

    .customer-cell { font-weight:600; color:#111; }
    .no-customer { color:#ccc; font-size:12px; font-style:italic; }
    .revenue { font-weight:700; color:#16a34a; }

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
    .modal-body { padding:16px 22px; }
    .form-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; }
    .form-group { display:flex; flex-direction:column; gap:4px; }
    .form-group.full { grid-column:1/-1; }
    .form-group label { font-size:10px; font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:#999; }
    .form-group input { border:1px solid #e2e2e6; outline:none; font-family:inherit; font-size:13px; color:#111; padding:6px 10px; background:#fafafa; transition:border-color .2s; border-radius:0; }
    .form-group input:focus { border-color:#1a1a2e; background:#fff; }
    .form-group input::placeholder { color:#ccc; }
    .form-group .hint { font-size:10px; color:#bbb; }
    .total-preview { grid-column:1/-1; background:#f9f9fb; border:1px solid #e2e2e6; padding:10px 14px; font-size:12px; color:#555; display:flex; align-items:center; justify-content:space-between; }
    .total-preview strong { color:#111; font-size:15px; }
    .optional-tag { font-size:9px; color:#bbb; font-weight:400; text-transform:none; letter-spacing:0; margin-left:4px; }
    .modal-footer { padding:14px 22px; border-top:1px solid #f0f0f2; display:flex; justify-content:flex-end; gap:10px; }
    .btn-cancel { padding:9px 16px; background:#fff; border:1.5px solid #e2e2e6; font-family:inherit; font-size:12px; font-weight:600; color:#666; cursor:pointer; }
    .btn-cancel:hover { background:#f4f4f4; }
    .btn-submit { padding:9px 20px; background:#1a1a2e; border:none; font-family:inherit; font-size:12px; font-weight:600; color:#fff; cursor:pointer; transition:opacity .2s; }
    .btn-submit:hover { opacity:.82; }

    @media(max-width:600px) { .summary-grid { grid-template-columns:1fr; } }
</style>

<?php if ($success): ?>
    <div class="alert success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="summary-grid">
    <div class="summary-card">
        <div class="sc-val"><?= number_format($summary['total_orders']) ?></div>
        <div class="sc-label">Total Orders</div>
    </div>
    <div class="summary-card">
        <div class="sc-val"><?= number_format($summary['total_kg'], 1) ?><small> kg</small></div>
        <div class="sc-label">Total Sold</div>
    </div>
    <div class="summary-card">
        <div class="sc-val">₱<?= number_format($summary['total_revenue'], 2) ?></div>
        <div class="sc-label">Total Revenue</div>
    </div>
</div>

<div class="toolbar">
    <span class="count-label"><?= count($orders) ?> order<?= count($orders) !== 1 ? 's' : '' ?></span>
    <button class="btn-primary" onclick="openModal('modal-order')">
        <i class="fa-solid fa-plus"></i> Add Order
    </button>
</div>

<?php if (empty($orders)): ?>
    <div class="empty-state">
        <i class="fa-solid fa-cart-shopping"></i>
        <p>No orders yet. Click "Add Order" to get started.</p>
    </div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Quantity</th>
                    <th>Price / kg</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                <tr>
                    <td><?= date('M d, Y', strtotime($o['order_date'])) ?></td>
                    <td>
                        <?php if ($o['customer_name']): ?>
                            <span class="customer-cell"><?= htmlspecialchars($o['customer_name']) ?></span>
                        <?php else: ?>
                            <span class="no-customer">— walk-in —</span>
                        <?php endif; ?>
                    </td>
                    <td class="val-bold"><?= number_format($o['quantity_kg'], 2) ?> kg</td>
                    <td>₱<?= number_format($o['price_per_kg'], 2) ?></td>
                    <td class="revenue">₱<?= number_format($o['total_price'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<div class="modal-overlay" id="modal-order">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h3>Add Order</h3>
            
            </div>

        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_order">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group full">
                        <label>Customer Name <span class="optional-tag">(optional)</span></label>
                        <input type="text" name="customer_name" placeholder="">
                    </div>
                    <div class="form-group">
                        <label>Quantity</label>
                        <input type="number" name="quantity_kg" id="o-qty" placeholder="0.00" step="0.01" min="0.01" required oninput="updateTotal()" style="max-width:120px;">
                    </div>
                    <div class="form-group">
                        <label>Price</label>
                        <input type="number" name="price_per_kg" id="o-price" placeholder="0.00" step="0.01" min="0.01" required oninput="updateTotal()">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal('modal-order')">Cancel</button>
                <button type="submit" class="btn-submit"><i class="fa-solid fa-plus"></i> Save Order</button>
            </div>
        </form>
    </div>
</div>

<script>
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
function updateTotal() {
    const qty   = parseFloat(document.getElementById('o-qty').value) || 0;
    const price = parseFloat(document.getElementById('o-price').value) || 0;
    document.getElementById('total-preview').textContent = '₱' + (qty * price).toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});
}
<?php if ($error): ?>openModal('modal-order');<?php endif; ?>
</script>

<?php require_once 'navbar_end.php'; ?>