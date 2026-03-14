<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hito</title>
    <link rel="stylesheet" href="../assets/fontawesome-7/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --sidebar-w: 240px;
            --black: #111111;
            --gray-400: #999;
            --gray-200: #e4e4e4;
            --gray-100: #f4f4f4;
            --white: #ffffff;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--gray-100);
            color: var(--black);
            display: flex;
            min-height: 100vh;
        }

        /* ── SIDEBAR ── */
        .sidebar {
            width: var(--sidebar-w);
            min-height: 100vh;
            background: var(--black);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            z-index: 100;
            transition: transform 0.3s ease;
        }

        .sidebar-brand {
            padding: 28px 24px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }

        .logo-row {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-icon {
            width: 34px;
            height: 34px;
            background: var(--white);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            color: var(--black);
            flex-shrink: 0;
        }

        .brand-text h1 {
            font-size: 15px;
            font-weight: 700;
            color: var(--white);
            letter-spacing: 0.02em;
            line-height: 1.1;
        }

        .brand-text span {
            font-size: 10px;
            color: rgba(255,255,255,0.35);
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .sidebar-section {
            padding: 16px 0 4px;
        }

        .section-label {
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: rgba(255,255,255,0.25);
            padding: 0 24px;
            margin-bottom: 4px;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 24px;
            font-size: 13px;
            font-weight: 400;
            color: rgba(255,255,255,0.55);
            text-decoration: none;
            border-left: 3px solid transparent;
            transition: background 0.15s, color 0.15s, border-color 0.15s;
        }

        .nav-item i {
            width: 16px;
            text-align: center;
            font-size: 13px;
            flex-shrink: 0;
        }

        .nav-item:hover {
            background: rgba(255,255,255,0.06);
            color: rgba(255,255,255,0.9);
            border-left-color: rgba(255,255,255,0.2);
        }

        .nav-item.active {
            background: rgba(255,255,255,0.08);
            color: var(--white);
            border-left-color: var(--white);
            font-weight: 600;
        }

        .sidebar-footer {
            margin-top: auto;
            padding: 16px 24px;
            border-top: 1px solid rgba(255,255,255,0.07);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .avatar {
            width: 32px;
            height: 32px;
            background: rgba(255,255,255,0.12);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            color: var(--white);
            flex-shrink: 0;
        }

        .admin-info h4 {
            font-size: 12px;
            font-weight: 600;
            color: var(--white);
        }

        .admin-info span {
            font-size: 10px;
            color: rgba(255,255,255,0.35);
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .logout-btn {
            margin-left: auto;
            color: rgba(255,255,255,0.3);
            font-size: 13px;
            cursor: pointer;
            transition: color 0.2s;
            background: none;
            border: none;
            padding: 4px;
        }

        .logout-btn:hover { color: rgba(255,255,255,0.85); }

        /* ── MAIN WRAPPER ── */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ── TOPBAR ── */
        .topbar {
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            padding: 0 32px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }

        .topbar-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .topbar-left h2 {
            font-size: 16px;
            font-weight: 600;
            color: var(--black);
        }

        .topbar-left p {
            font-size: 11px;
            color: var(--gray-400);
            margin-top: 1px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .topbar-date {
            font-size: 11px;
            color: var(--gray-400);
            letter-spacing: 0.04em;
        }

        .badge-admin {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            background: var(--black);
            color: var(--white);
            padding: 4px 10px;
            border-radius: 2px;
        }

        /* ── CONTENT AREA ── */
        .content {
            padding: 32px;
            flex: 1;
        }

        /* ── HAMBURGER (mobile) ── */
        .hamburger {
            display: none;
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: var(--black);
            padding: 4px;
        }

        .overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            z-index: 90;
        }

        .overlay.show { display: block; }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; }
            .hamburger { display: flex; }
            .content { padding: 20px; }
        }
    </style>
</head>
<body>

<div class="overlay" id="overlay" onclick="toggleSidebar()"></div>

<aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        <div class="logo-row">
            <div class="brand-icon"><i class="fa-solid fa-user"></i></div>
            <div class="brand-text">
                <h1>ADMINISTRATOR</h1>
                <span>Admin Panel</span>
            </div>
        </div>
    </div>

    <div class="sidebar-section">
        <div class="section-label">Overview</div>
        <a href="dashboard.php" class="nav-item <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge-high"></i> Dashboard
        </a>
    </div>

    <div class="sidebar-section">
        <div class="section-label">Management</div>
        <a href="inventory.php" class="nav-item <?= $current_page === 'feeding.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-bowl-food"></i> Feed Monitoring
        </a>

        <a href="sales.php" class="nav-item <?= $current_page === 'sales.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-boxes-stacked"></i> Sales & Inventory
        </a>
        <a href="costs.php" class="nav-item <?= $current_page === 'costs.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-coins"></i> Cost Tracking
        </a>
        <a href="activity_logs.php" class="nav-item <?= $current_page === 'activity_logs.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-clock-rotate-left"></i> Activity Logs
    </a>
    </div>

    <div class="sidebar-section">
        <div class="section-label">Reports</div>
        <a href="analytics.php" class="nav-item <?= $current_page === 'analytics.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-line"></i> Analytics & Reports
        </a>
    </div>

    <div class="sidebar-section">
        <div class="section-label">Admin</div>
        <a href="staff.php" class="nav-item <?= $current_page === 'staff.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-users"></i> Staff Management
        </a>
        
    </div>

    <div class="sidebar-footer">
        <div class="avatar"><i class="fa-solid fa-user"></i></div>
        <div class="admin-info">
            <h4>Admin</h4>
            <span>Administrator</span>
        </div>
        <button class="logout-btn" onclick="window.location='login.php'" title="Logout">
            <i class="fa-solid fa-right-from-bracket"></i>
        </button>
    </div>

</aside>

<div class="main">
    <header class="topbar">
        <div class="topbar-left">
            <button class="hamburger" onclick="toggleSidebar()">
                <i class="fa-solid fa-bars"></i>
            </button>
            <div>
                <h2><?= $page_title ?? 'Dashboard' ?></h2>
                <p><?= $page_sub ?? 'Welcome back, Admin' ?></p>
            </div>
        </div>
        <div class="topbar-right">
            <span class="topbar-date" id="topbar-date"></span>
            <span class="badge-admin">Admin</span>
        </div>
    </header>

    <div class="content">
        <!-- PAGE CONTENT STARTS HERE -->