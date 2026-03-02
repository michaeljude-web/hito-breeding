<?php
$page_title = 'Dashboard';
$page_sub   = 'Welcome back, Admin';
require_once 'sidebar.php';
?>

<style>
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 28px;
    }

    .stat-card {
        background: #fff;
        border: 1px solid #e4e4e4;
        padding: 20px 22px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        transition: box-shadow 0.2s;
    }

    .stat-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.06); }

    .stat-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .stat-label {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: #999;
    }

    .stat-icon {
        width: 32px;
        height: 32px;
        background: #f4f4f4;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        color: #555;
    }

    .stat-value {
        font-size: 26px;
        font-weight: 700;
        color: #111;
        letter-spacing: -0.02em;
        line-height: 1;
    }

    .stat-sub { font-size: 11px; color: #999; }

    .section-heading {
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.16em;
        text-transform: uppercase;
        color: #999;
        margin-bottom: 14px;
    }

    .modules-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 28px;
    }

    .module-card {
        background: #fff;
        border: 1px solid #e4e4e4;
        padding: 24px;
        cursor: pointer;
        transition: box-shadow 0.2s, border-color 0.2s;
        text-decoration: none;
        display: block;
    }

    .module-card:hover {
        box-shadow: 0 4px 20px rgba(0,0,0,0.07);
        border-color: #c8c8c8;
    }

    .module-card:hover .module-arrow {
        opacity: 1;
        transform: translateX(0);
    }

    .module-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 14px;
    }

    .module-icon-wrap {
        width: 42px;
        height: 42px;
        background: #111;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        color: #fff;
        border-radius: 6px;
    }

    .module-arrow {
        font-size: 12px;
        color: #999;
        opacity: 0;
        transform: translateX(-4px);
        transition: opacity 0.2s, transform 0.2s;
    }

    .module-card h3 {
        font-size: 14px;
        font-weight: 600;
        color: #111;
        margin-bottom: 6px;
        line-height: 1.2;
    }

    .module-card p {
        font-size: 12px;
        color: #999;
        line-height: 1.6;
    }

    .actions-row {
        display: flex;
        gap: 10px;
        margin-bottom: 28px;
    }

    .action-btn {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        font-family: inherit;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.06em;
        cursor: pointer;
        border: none;
        text-decoration: none;
        transition: opacity 0.2s;
    }

    .action-btn.primary { background: #111; color: #fff; }
    .action-btn.secondary { background: #fff; color: #111; border: 1.5px solid #e4e4e4; }
    .action-btn:hover { opacity: 0.8; }

    @media (max-width: 1024px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .modules-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 600px) {
        .stats-grid { grid-template-columns: 1fr 1fr; }
        .modules-grid { grid-template-columns: 1fr; }
    }
</style>

<!-- Quick Actions -->
<div class="actions-row">
    <a href="staff.php" class="action-btn primary">
        <i class="fa-solid fa-user-plus"></i> Add Staff
    </a>
    <a href="analytics.php" class="action-btn secondary">
        <i class="fa-solid fa-print"></i> Print Report
    </a>
    <a href="#" class="action-btn secondary">
        <i class="fa-solid fa-file-export"></i> Export Data
    </a>
</div>

<!-- Stat Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Total Staff</span>
            <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
        </div>
        <div class="stat-value">0</div>
        <div class="stat-sub">Active accounts</div>
    </div>
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Fingerlings</span>
            <div class="stat-icon"><i class="fa-solid fa-fish"></i></div>
        </div>
        <div class="stat-value">0</div>
        <div class="stat-sub">Current stock</div>
    </div>
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Feed Stock</span>
            <div class="stat-icon"><i class="fa-solid fa-bowl-food"></i></div>
        </div>
        <div class="stat-value">0 <span style="font-size:14px;font-weight:400;color:#999">kg</span></div>
        <div class="stat-sub">Remaining inventory</div>
    </div>
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Sales (Month)</span>
            <div class="stat-icon"><i class="fa-solid fa-peso-sign"></i></div>
        </div>
        <div class="stat-value">₱0</div>
        <div class="stat-sub">This month</div>
    </div>
</div>

<!-- Modules -->
<div class="section-heading">System Modules</div>
<div class="modules-grid">

    <a href="feeding.php" class="module-card">
        <div class="module-top">
            <div class="module-icon-wrap"><i class="fa-solid fa-bowl-food"></i></div>
            <span class="module-arrow"><i class="fa-solid fa-arrow-right"></i></span>
        </div>
        <h3>Feeding & Monitoring</h3>
        <p>Manage feeding schedules, specify feed type, amount, frequency, and track consumption per session.</p>
    </a>

    <a href="hatchery.php" class="module-card">
        <div class="module-top">
            <div class="module-icon-wrap"><i class="fa-solid fa-egg"></i></div>
            <span class="module-arrow"><i class="fa-solid fa-arrow-right"></i></span>
        </div>
        <h3>Hatchery & Fingerling</h3>
        <p>Track breeding activities, egg production, hatching performance, and fingerling transfers to grow-out ponds.</p>
    </a>

    <a href="sales.php" class="module-card">
        <div class="module-top">
            <div class="module-icon-wrap"><i class="fa-solid fa-boxes-stacked"></i></div>
            <span class="module-arrow"><i class="fa-solid fa-arrow-right"></i></span>
        </div>
        <h3>Sales & Inventory</h3>
        <p>Track harvested hito sales, pricing, remaining stock levels, and monitor feed inventory.</p>
    </a>

    <a href="analytics.php" class="module-card">
        <div class="module-top">
            <div class="module-icon-wrap"><i class="fa-solid fa-chart-line"></i></div>
            <span class="module-arrow"><i class="fa-solid fa-arrow-right"></i></span>
        </div>
        <h3>Analytics & Reports</h3>
        <p>Visual charts and operation summaries with printable reports for all system data.</p>
    </a>

    <a href="costs.php" class="module-card">
        <div class="module-top">
            <div class="module-icon-wrap"><i class="fa-solid fa-coins"></i></div>
            <span class="module-arrow"><i class="fa-solid fa-arrow-right"></i></span>
        </div>
        <h3>Cost Tracking</h3>
        <p>Monitor operational expenses including feed, maintenance, and labor. Auto-calculates profit from recorded sales.</p>
    </a>

    <a href="staff.php" class="module-card">
        <div class="module-top">
            <div class="module-icon-wrap"><i class="fa-solid fa-users"></i></div>
            <span class="module-arrow"><i class="fa-solid fa-arrow-right"></i></span>
        </div>
        <h3>Staff Management</h3>
        <p>Add and manage staff accounts with access to system features and operations.</p>
    </a>

</div>

<?php require_once 'sidebar_end.php'; ?>