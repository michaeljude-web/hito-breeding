<?php
$page_title = 'Staff Management';
$page_sub   = 'Manage staff accounts';
require_once '../db/connection.php';
require_once 'sidebar.php';
?>

<style>
    /* ── TOOLBAR ── */
    .toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        gap: 12px;
        flex-wrap: wrap;
    }

    .search-box {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #fff;
        border: 1px solid #e4e4e4;
        padding: 8px 14px;
        flex: 1;
        max-width: 320px;
    }

    .search-box i { color: #bbb; font-size: 13px; }

    .search-box input {
        border: none;
        outline: none;
        font-family: inherit;
        font-size: 13px;
        color: #111;
        width: 100%;
        background: transparent;
    }

    .search-box input::placeholder { color: #ccc; }

    .btn-add {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        background: #111;
        color: #fff;
        font-family: inherit;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.08em;
        border: none;
        cursor: pointer;
        transition: opacity 0.2s;
        white-space: nowrap;
    }

    .btn-add:hover { opacity: 0.82; }

    /* ── TABLE ── */
    .table-wrap {
        background: #fff;
        border: 1px solid #e4e4e4;
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    thead tr {
        border-bottom: 1px solid #e4e4e4;
        background: #f9f9f9;
    }

    th {
        padding: 12px 16px;
        text-align: left;
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #999;
        white-space: nowrap;
    }

    tbody tr {
        border-bottom: 1px solid #f0f0f0;
        transition: background 0.1s;
    }

    tbody tr:last-child { border-bottom: none; }
    tbody tr:hover { background: #fafafa; }

    td {
        padding: 13px 16px;
        color: #333;
        vertical-align: middle;
    }

    .td-name {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .staff-avatar {
        width: 32px;
        height: 32px;
        background: #111;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 12px;
        font-weight: 600;
        flex-shrink: 0;
    }

    .staff-name { font-weight: 600; color: #111; }
    .staff-username { font-size: 11px; color: #aaa; margin-top: 1px; }

    .badge-staff {
        display: inline-block;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        background: #f0f0f0;
        color: #555;
        padding: 3px 8px;
        border-radius: 2px;
    }

    .action-btns { display: flex; gap: 6px; }

    .btn-icon {
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid #e4e4e4;
        background: #fff;
        cursor: pointer;
        font-size: 12px;
        color: #888;
        transition: background 0.15s, color 0.15s;
    }

    .btn-icon:hover { background: #111; color: #fff; border-color: #111; }
    .btn-icon.danger:hover { background: #b91c1c; color: #fff; border-color: #b91c1c; }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #bbb;
    }

    .empty-state i { font-size: 36px; margin-bottom: 12px; display: block; }
    .empty-state p { font-size: 13px; }

    /* ── MODAL ── */
    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 200;
        align-items: flex-start;
        justify-content: center;
        padding: 40px 16px;
        overflow-y: auto;
    }

    .modal-overlay.show { display: flex; }

    .modal {
        background: #fff;
        width: 100%;
        max-width: 560px;
        margin: auto;
        animation: modalIn 0.25s ease both;
        flex-shrink: 0;
    }

    @keyframes modalIn {
        from { opacity: 0; transform: translateY(-16px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    .modal-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 24px;
        border-bottom: 1px solid #e4e4e4;
    }

    .modal-header h3 {
        font-size: 15px;
        font-weight: 700;
        color: #111;
    }

    .modal-header p {
        font-size: 11px;
        color: #aaa;
        margin-top: 2px;
    }

    .modal-close {
        width: 30px;
        height: 30px;
        background: #f4f4f4;
        border: none;
        cursor: pointer;
        font-size: 13px;
        color: #555;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s;
        flex-shrink: 0;
    }

    .modal-close:hover { background: #e4e4e4; }

    .modal-body { padding: 24px; }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group.full { grid-column: 1 / -1; }

    .form-group label {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #888;
    }

    .form-group input {
        border: 1px solid #e4e4e4;
        outline: none;
        font-family: inherit;
        font-size: 13px;
        color: #111;
        padding: 10px 12px;
        background: #fafafa;
        transition: border-color 0.2s, background 0.2s;
        border-radius: 0;
    }

    .form-group input:focus {
        border-color: #111;
        background: #fff;
    }

    .form-group input::placeholder { color: #ccc; }

    .modal-footer {
        padding: 16px 24px;
        border-top: 1px solid #f0f0f0;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .btn-cancel {
        padding: 10px 18px;
        background: #fff;
        border: 1.5px solid #e4e4e4;
        font-family: inherit;
        font-size: 12px;
        font-weight: 600;
        color: #555;
        cursor: pointer;
        transition: background 0.15s;
    }

    .btn-cancel:hover { background: #f4f4f4; }

    .btn-submit {
        padding: 10px 22px;
        background: #111;
        border: none;
        font-family: inherit;
        font-size: 12px;
        font-weight: 600;
        color: #fff;
        cursor: pointer;
        letter-spacing: 0.06em;
        transition: opacity 0.2s;
    }

    .btn-submit:hover { opacity: 0.82; }

    .alert {
        padding: 10px 14px;
        font-size: 12px;
        margin-bottom: 16px;
        border-left: 3px solid;
    }

    .alert.success { background: #f0fdf4; color: #16a34a; border-color: #16a34a; }
    .alert.error   { background: #fef2f2; color: #b91c1c; border-color: #b91c1c; }

    .count-label {
        font-size: 11px;
        color: #aaa;
        margin-bottom: 16px;
    }
</style>

<?php
// Handle Add Staff
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_staff') {
    $firstname  = trim($_POST['firstname'] ?? '');
    $lastname   = trim($_POST['lastname'] ?? '');
    $birthday   = trim($_POST['birthday'] ?? '');
    $address    = trim($_POST['address'] ?? '');
    $contact    = trim($_POST['contact'] ?? '');
    $username   = trim($_POST['username'] ?? '');
    $password   = trim($_POST['password'] ?? '');

    if ($firstname && $lastname && $birthday && $address && $contact && $username && $password) {
        // Check if username already exists
        $check = $pdo->prepare("SELECT id FROM staff WHERE username = ?");
        $check->execute([$username]);
        if ($check->fetch()) {
            $error = 'Username already taken. Please choose another.';
        } else {
            $stmt = $pdo->prepare("INSERT INTO staff (firstname, lastname, birthday, address, contact, username, password) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$firstname, $lastname, $birthday, $address, $contact, $username, $password]);
            $success = 'Staff account created successfully.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $del = $pdo->prepare("DELETE FROM staff WHERE id = ?");
    $del->execute([(int)$_GET['delete']]);
    header('Location: staff.php?deleted=1');
    exit;
}

if (isset($_GET['deleted'])) $success = 'Staff account deleted.';

// Fetch all staff
$search = trim($_GET['search'] ?? '');
if ($search) {
    $stmt = $pdo->prepare("SELECT *, TIMESTAMPDIFF(YEAR, birthday, CURDATE()) AS age FROM staff WHERE firstname LIKE ? OR lastname LIKE ? OR username LIKE ? ORDER BY lastname ASC");
    $like = "%$search%";
    $stmt->execute([$like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT *, TIMESTAMPDIFF(YEAR, birthday, CURDATE()) AS age FROM staff ORDER BY lastname ASC");
}
$staff_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- TOOLBAR -->
<div class="toolbar">
    <form method="GET" action="" style="display:flex;align-items:center;gap:10px;flex:1;max-width:340px;">
        <div class="search-box" style="flex:1;max-width:100%;">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="search" placeholder="Search by name or username..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <?php if ($search): ?>
            <a href="staff.php" style="font-size:11px;color:#aaa;text-decoration:none;white-space:nowrap;">Clear</a>
        <?php endif; ?>
    </form>
    <button class="btn-add" onclick="openModal()">
        <i class="fa-solid fa-user-plus"></i> Add Staff
    </button>
</div>

<!-- ALERTS -->
<?php if ($success): ?>
    <div class="alert success"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="count-label">
    <?= count($staff_list) ?> staff account<?= count($staff_list) !== 1 ? 's' : '' ?><?= $search ? " matching \"$search\"" : '' ?>
</div>

<!-- TABLE -->
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Full Name</th>
                <th>Age</th>
                <th>Address</th>
                <th>Contact</th>
                <th>Username</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($staff_list)): ?>
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            <i class="fa-solid fa-users"></i>
                            <p>No staff accounts found<?= $search ? " for \"$search\"" : '' ?>.</p>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($staff_list as $i => $s):
                    $initials = strtoupper(substr($s['firstname'], 0, 1) . substr($s['lastname'], 0, 1));
                    $fullname = htmlspecialchars($s['firstname'] . ' ' . $s['lastname']);
                ?>
                <tr>
                    <td style="color:#bbb;font-size:12px;"><?= $i + 1 ?></td>
                    <td>
                        <div class="td-name">
                            <div class="staff-avatar"><?= $initials ?></div>
                            <div>
                                <div class="staff-name"><?= $fullname ?></div>
                                <div class="staff-username">@<?= htmlspecialchars($s['username']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><?= $s['age'] ?> yrs</td>
                    <td><?= htmlspecialchars($s['address']) ?></td>
                    <td><?= htmlspecialchars($s['contact']) ?></td>
                    <td style="font-family:monospace;font-size:12px;"><?= htmlspecialchars($s['username']) ?></td>
                    <td><span class="badge-staff">Staff</span></td>
                    <td>
                        <div class="action-btns">
                            <button class="btn-icon" title="Edit" onclick="editStaff(<?= $s['id'] ?>)">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button class="btn-icon danger" title="Delete"
                                onclick="if(confirm('Delete <?= $fullname ?>?')) window.location='staff.php?delete=<?= $s['id'] ?>'">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ADD STAFF MODAL -->
<div class="modal-overlay" id="modal">
    <div class="modal">
        <div class="modal-header">
            <div>
                <h3>Add New Staff</h3>
                <p>Fill in all fields to create a staff account.</p>
            </div>
            <button class="modal-close" onclick="closeModal()">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="">
            <input type="hidden" name="action" value="add_staff">
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="firstname" placeholder="Juan" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="lastname" placeholder="Dela Cruz" required>
                    </div>
                    <div class="form-group">
                        <label>Birthday</label>
                        <input type="date" name="birthday" required>
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact" placeholder="09XXXXXXXXX" required>
                    </div>
                    <div class="form-group full">
                        <label>Address</label>
                        <input type="text" name="address" placeholder="Barangay, City, Province" required>
                    </div>
                    <div class="form-group">
                        <label>Username</label>
                        <input type="text" name="username" placeholder="juandc" required>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" placeholder="••••••••" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                <button type="submit" class="btn-submit">
                    <i class="fa-solid fa-user-plus"></i> Create Account
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal() {
        document.getElementById('modal').classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        document.getElementById('modal').classList.remove('show');
        document.body.style.overflow = '';
    }

    // Close modal on outside click
    document.getElementById('modal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    // Auto open modal if there's an error (so user doesn't lose their input)
    <?php if ($error): ?> openModal(); <?php endif; ?>

    // Search on Enter
    document.querySelector('.search-box input').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') this.closest('form').submit();
    });

    function editStaff(id) {
        // Placeholder — edit functionality to be implemented
        alert('Edit staff ID: ' + id + '\n(To be implemented)');
    }
</script>

<?php require_once 'sidebar_end.php'; ?>