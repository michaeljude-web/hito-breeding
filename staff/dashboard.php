<?php
$page_title = 'Dashboard';
$page_sub   = 'Here\'s an overview of today\'s operations.';
require_once 'navbar.php';
?>

<style>
    /* ── STATS ── */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 28px;
    }

    .stat-card {
        background: #fff;
        border: 1px solid #e2e2e6;
        padding: 18px 20px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        transition: box-shadow 0.18s;
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
        color: #9999aa;
    }

    .stat-icon {
        width: 30px;
        height: 30px;
        background: #f0f0f2;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        color: #666;
    }

    .stat-value {
        font-size: 24px;
        font-weight: 700;
        color: #111;
        letter-spacing: -0.02em;
        line-height: 1;
    }

    .stat-value small {
        font-size: 13px;
        font-weight: 400;
        color: #aaa;
    }

    .stat-sub { font-size: 11px; color: #aaa; }

    /* ── MODULE CARDS ── */
    .section-heading {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.18em;
        text-transform: uppercase;
        color: #aaa;
        margin-bottom: 14px;
    }

    .modules-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 14px;
    }

    .module-card {
        background: #fff;
        border: 1px solid #e2e2e6;
        padding: 22px;
        text-decoration: none;
        display: block;
        transition: box-shadow 0.18s, border-color 0.18s;
    }

    .module-card:hover {
        box-shadow: 0 4px 20px rgba(0,0,0,0.07);
        border-color: #c8c8d0;
    }

    .module-card:hover .mod-arrow {
        opacity: 1;
        transform: translateX(0);
    }

    .mod-top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 12px;
    }

    .mod-icon {
        width: 38px;
        height: 38px;
        background: #1a1a2e;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 15px;
        color: #fff;
    }

    .mod-arrow {
        font-size: 11px;
        color: #bbb;
        opacity: 0;
        transform: translateX(-4px);
        transition: opacity 0.2s, transform 0.2s;
    }

    .module-card h3 {
        font-size: 13px;
        font-weight: 600;
        color: #111;
        margin-bottom: 5px;
        line-height: 1.2;
    }

    .module-card p {
        font-size: 11px;
        color: #aaa;
        line-height: 1.6;
    }

    @media (max-width: 900px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .modules-grid { grid-template-columns: repeat(2, 1fr); }
    }

    @media (max-width: 540px) {
        .stats-grid { grid-template-columns: 1fr 1fr; }
        .modules-grid { grid-template-columns: 1fr; }
    }
</style>

<!-- STAT CARDS -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Feed Stock</span>
            <div class="stat-icon"><i class="fa-solid fa-bowl-food"></i></div>
        </div>
        <div class="stat-value">0 <small>kg</small></div>
        <div class="stat-sub">Remaining inventory</div>
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
            <span class="stat-label">Sales (Month)</span>
            <div class="stat-icon"><i class="fa-solid fa-peso-sign"></i></div>
        </div>
        <div class="stat-value">₱0</div>
        <div class="stat-sub">This month</div>
    </div>
    <div class="stat-card">
        <div class="stat-top">
            <span class="stat-label">Total Expenses</span>
            <div class="stat-icon"><i class="fa-solid fa-coins"></i></div>
        </div>
        <div class="stat-value">₱0</div>
        <div class="stat-sub">This month</div>
    </div>
</div>

<!-- MODULES -->
<div class="section-heading">Modules</div>
<div class="modules-grid">

    <a href="feeding.php" class="module-card">
        <div class="mod-top">
            <div class="mod-icon"><i class="fa-solid fa-bowl-food"></i></div>
            <span class="mod-arrow"><i class="fa-solid fa-arrow-right"></i></span>
        </div>
        <h3>Feeding & Monitoring</h3>
        <p>Manage feeding schedules, specify feed type, amount, frequency, and track consumption per session.</p>
    </a>

    <a href="hatchery.php" class="module-card">
        <div class="mod-top">
            <div class="mod-icon"><i class="fa-solid fa-egg"></i></div>
            <span class="mod-arrow"><i class="fa-solid fa-arrow-right"></i></span>
        </div>
        <h3>Hatchery & Fingerling</h3>
        <p>Track breeding activities, egg production, hatching performance, and fingerling transfers to grow-out ponds.</p>
    </a>

    <a href="sales.php" class="module-card">
        <div class="mod-top">
            <div class="mod-icon"><i class="fa-solid fa-boxes-stacked"></i></div>
            <span class="mod-arrow"><i class="fa-solid fa-arrow-right"></i></span>
        </div>
        <h3>Sales & Inventory</h3>
        <p>Track harvested hito sales, pricing, remaining stock levels, and monitor feed inventory.</p>
    </a>

    <a href="analytics.php" class="module-card">
        <div class="mod-top">
            <div class="mod-icon"><i class="fa-solid fa-chart-line"></i></div>
            <span class="mod-arrow"><i class="fa-solid fa-arrow-right"></i></span>
        </div>
        <h3>Analytics & Reports</h3>
        <p>Visual charts and summaries of overall operations, with printable reports.</p>
    </a>

    <a href="costs.php" class="module-card">
        <div class="mod-top">
            <div class="mod-icon"><i class="fa-solid fa-coins"></i></div>
            <span class="mod-arrow"><i class="fa-solid fa-arrow-right"></i></span>
        </div>
        <h3>Cost Tracking</h3>
        <p>Monitor all operational expenses including feed, maintenance, and labor. Auto-calculates profit from recorded sales.</p>
    </a>

</div>

<?php require_once 'navbar_end.php'; ?>