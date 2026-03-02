<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hito System — Staff</title>
    <link rel="stylesheet" href="../assets/fontawesome-7/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --navy: #1a1a2e;
            --navy-light: #22223d;
            --white: #ffffff;
            --off-white: #f5f5f7;
            --gray-100: #f0f0f2;
            --gray-200: #e2e2e6;
            --gray-400: #9999aa;
            --black: #111111;
            --error: #b91c1c;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--off-white);
            color: var(--black);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* ── NAVBAR ── */
        .navbar {
            background: var(--navy);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 12px rgba(0,0,0,0.15);
        }

        .navbar-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            height: 58px;
            gap: 0;
        }

        /* Brand */
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            margin-right: 32px;
            flex-shrink: 0;
        }

        .brand-icon {
            width: 32px;
            height: 32px;
            background: rgba(255,255,255,0.12);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            color: #fff;
        }

        .brand-text {
            line-height: 1.1;
        }

        .brand-text h1 {
            font-size: 14px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.02em;
        }

        .brand-text span {
            font-size: 9px;
            color: rgba(255,255,255,0.3);
            letter-spacing: 0.12em;
            text-transform: uppercase;
        }

        /* Nav links */
        .nav-links {
            display: flex;
            align-items: center;
            gap: 2px;
            flex: 1;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 8px 14px;
            font-size: 12px;
            font-weight: 500;
            color: rgba(255,255,255,0.5);
            text-decoration: none;
            border-radius: 4px;
            transition: background 0.15s, color 0.15s;
            white-space: nowrap;
            letter-spacing: 0.01em;
        }

        .nav-link i {
            font-size: 12px;
        }

        .nav-link:hover {
            background: rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.9);
        }

        .nav-link.active {
            background: rgba(255,255,255,0.13);
            color: #fff;
            font-weight: 600;
        }

        /* Right side */
        .nav-right {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-left: auto;
            flex-shrink: 0;
        }

        .staff-chip {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .staff-avatar {
            width: 30px;
            height: 30px;
            background: rgba(255,255,255,0.12);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #fff;
        }

        .staff-name-label {
            font-size: 12px;
            font-weight: 500;
            color: rgba(255,255,255,0.7);
        }

        .nav-divider {
            width: 1px;
            height: 20px;
            background: rgba(255,255,255,0.1);
        }

        .logout-link {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: rgba(255,255,255,0.4);
            text-decoration: none;
            padding: 6px 10px;
            border-radius: 4px;
            transition: color 0.15s, background 0.15s;
        }

        .logout-link:hover {
            color: rgba(255,255,255,0.9);
            background: rgba(255,255,255,0.07);
        }

        /* Mobile hamburger */
        .hamburger {
            display: none;
            background: none;
            border: none;
            color: rgba(255,255,255,0.7);
            font-size: 18px;
            cursor: pointer;
            padding: 4px 8px;
            margin-left: auto;
        }

        /* Mobile drawer */
        .mobile-menu {
            display: none;
            flex-direction: column;
            background: var(--navy-light);
            border-top: 1px solid rgba(255,255,255,0.06);
            padding: 8px 16px 16px;
            gap: 2px;
        }

        .mobile-menu.open { display: flex; }

        .mobile-menu .nav-link {
            padding: 10px 12px;
            border-radius: 4px;
        }

        .mobile-user {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 12px 8px;
            font-size: 12px;
            color: rgba(255,255,255,0.4);
            border-top: 1px solid rgba(255,255,255,0.06);
            margin-top: 6px;
        }

        /* ── PAGE WRAP ── */
        .page-wrap {
            flex: 1;
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 32px 24px;
        }

        /* ── PAGE HEADER ── */
        .page-header {
            margin-bottom: 24px;
        }

        .page-header h2 {
            font-size: 20px;
            font-weight: 700;
            color: var(--black);
            letter-spacing: -0.01em;
        }

        .page-header p {
            font-size: 12px;
            color: var(--gray-400);
            margin-top: 3px;
        }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .nav-right { display: none; }
            .hamburger { display: flex; margin-left: auto; }
            .page-wrap { padding: 20px 16px; }
        }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <div class="navbar-inner">
        <a href="dashboard.php" class="nav-brand">
            <!-- <div class="brand-icon"><i class="fa-solid fa-fish"></i></div> -->
            <div class="brand-text">
                <!-- <h1>Hito System</h1>
                <span>Staff Portal</span> -->
            </div>
        </a>

        <div class="nav-links">
            <a href="dashboard.php" class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
            <a href="feeding.php" class="nav-link <?= $current_page === 'feeding.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-bowl-food"></i> Feeding
            </a>
            <a href="hatchery.php" class="nav-link <?= $current_page === 'hatchery.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-egg"></i> Hatchery
            </a>
            <a href="sales.php" class="nav-link <?= $current_page === 'sales.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-boxes-stacked"></i> Sales & Inventory
            </a>
            <a href="analytics.php" class="nav-link <?= $current_page === 'analytics.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-chart-line"></i> Analytics
            </a>
            <a href="costs.php" class="nav-link <?= $current_page === 'costs.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-coins"></i> Cost Tracking
            </a>
        </div>

        <div class="nav-right">
            <div class="staff-chip">
                <div class="staff-avatar"><i class="fa-solid fa-user"></i></div>
                <span class="staff-name-label">Staff</span>
            </div>
            <div class="nav-divider"></div>
            <a href="login.php" class="logout-link" title="Sign out">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>

        <button class="hamburger" onclick="toggleMobile()" aria-label="Menu">
            <i class="fa-solid fa-bars" id="ham-icon"></i>
        </button>
    </div>

    <!-- Mobile menu -->
    <div class="mobile-menu" id="mobile-menu">
        <a href="dashboard.php" class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge-high"></i> Dashboard
        </a>
        <a href="feeding.php" class="nav-link <?= $current_page === 'feeding.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-bowl-food"></i> Feeding & Monitoring
        </a>
        <a href="hatchery.php" class="nav-link <?= $current_page === 'hatchery.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-egg"></i> Hatchery & Fingerling
        </a>
        <a href="sales.php" class="nav-link <?= $current_page === 'sales.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-boxes-stacked"></i> Sales & Inventory
        </a>
        <a href="analytics.php" class="nav-link <?= $current_page === 'analytics.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-line"></i> Analytics & Reports
        </a>
        <a href="costs.php" class="nav-link <?= $current_page === 'costs.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-coins"></i> Cost Tracking
        </a>
        <div class="mobile-user">
            <i class="fa-solid fa-user"></i> Staff &nbsp;·&nbsp;
            <a href="login.php" style="color:rgba(255,255,255,0.4);text-decoration:none;">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </div>
</nav>

<!-- PAGE CONTENT WRAP -->
<div class="page-wrap">
    <!-- PAGE HEADER (set $page_title and $page_sub before including navbar.php) -->
    <div class="page-header">
        <h2><?= $page_title ?? 'Dashboard' ?></h2>
        <p><?= $page_sub ?? '' ?></p>
    </div>

    <!-- CONTENT STARTS HERE -->