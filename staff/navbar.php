<?php
session_start();
if (!isset($_SESSION['staff_id'])) {
    header('Location: login.php');
    exit;
}
$current_page = basename($_SERVER['PHP_SELF']);
$staff_name = $_SESSION['staff_name'] ?? 'Staff';
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
            --off-white: #f5f5f7;
            --gray-400: #9999aa;
            --black: #111111;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--off-white);
            color: var(--black);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background: var(--navy);
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 16px rgba(0,0,0,0.18);
        }

        .navbar-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            height: 56px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2px;
            grid-column: 2;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 7px 16px;
            font-size: 12px;
            font-weight: 500;
            color: rgba(255,255,255,0.45);
            text-decoration: none;
            transition: color 0.15s;
            white-space: nowrap;
            letter-spacing: 0.02em;
            position: relative;
        }

        .nav-link i { font-size: 11px; }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 16px;
            right: 16px;
            height: 2px;
            background: #fff;
            transform: scaleX(0);
            transition: transform 0.2s;
        }

        .nav-link:hover { color: rgba(255,255,255,0.85); }
        .nav-link:hover::after { transform: scaleX(0.4); }

        .nav-link.active {
            color: #fff;
            font-weight: 600;
        }

        .nav-link.active::after { transform: scaleX(1); }

        .nav-right {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            grid-column: 3;
        }

        .staff-avatar {
            width: 28px;
            height: 28px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            color: rgba(255,255,255,0.7);
        }

        .staff-name-label {
            font-size: 12px;
            font-weight: 500;
            color: rgba(255,255,255,0.55);
        }

        .nav-divider { width: 1px; height: 18px; background: rgba(255,255,255,0.08); }

        .logout-link {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            color: rgba(255,255,255,0.3);
            text-decoration: none;
            padding: 5px 8px;
            transition: color 0.15s;
            letter-spacing: 0.04em;
        }

        .logout-link:hover { color: rgba(255,255,255,0.8); }

        .hamburger {
            display: none;
            background: none;
            border: none;
            color: rgba(255,255,255,0.6);
            font-size: 17px;
            cursor: pointer;
            padding: 4px;
            grid-column: 3;
            justify-self: end;
        }

        .mobile-menu {
            display: none;
            flex-direction: column;
            background: var(--navy-light);
            border-top: 1px solid rgba(255,255,255,0.05);
            padding: 8px 16px 14px;
            gap: 2px;
        }

        .mobile-menu.open { display: flex; }

        .mobile-menu .nav-link {
            padding: 10px 12px;
            border-radius: 4px;
        }

        .mobile-menu .nav-link::after { display: none; }
        .mobile-menu .nav-link.active { background: rgba(255,255,255,0.1); }

        .mobile-user {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 12px 4px;
            font-size: 11px;
            color: rgba(255,255,255,0.3);
            border-top: 1px solid rgba(255,255,255,0.06);
            margin-top: 6px;
        }

        .mobile-user a {
            color: rgba(255,255,255,0.3);
            text-decoration: none;
            margin-left: auto;
        }

        .page-wrap {
            flex: 1;
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 32px 24px;
        }

        .page-header { margin-bottom: 24px; }
        .page-header h2 { font-size: 20px; font-weight: 700; color: var(--black); letter-spacing: -0.01em; }
        .page-header p { font-size: 12px; color: var(--gray-400); margin-top: 3px; }

        @media (max-width: 768px) {
            .navbar-inner { grid-template-columns: 1fr auto; }
            .nav-links { display: none; }
            .nav-right { display: none; }
            .hamburger { display: flex; }
            .page-wrap { padding: 20px 16px; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="navbar-inner">
        <div></div>

        <div class="nav-links">
            <a href="dashboard.php" class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
            <a href="feeding.php" class="nav-link <?= $current_page === 'feeding.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-bowl-food"></i> Feeding
            </a>
            <a href="order.php" class="nav-link <?= $current_page === 'order.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-boxes-stacked"></i> Order
            </a>
            <a href="analytics.php" class="nav-link <?= $current_page === 'analytics.php' ? 'active' : '' ?>">
                <i class="fa-solid fa-chart-line"></i> Analytics
            </a>
        </div>

        <div class="nav-right">
            <div class="staff-avatar"><i class="fa-solid fa-user"></i></div>
            <span class="staff-name-label"><?= htmlspecialchars($staff_name) ?></span>
            <div class="nav-divider"></div>
            <a href="logout.php" class="logout-link">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>

        <button class="hamburger" onclick="toggleMobile()">
            <i class="fa-solid fa-bars" id="ham-icon"></i>
        </button>
    </div>

    <div class="mobile-menu" id="mobile-menu">
        <a href="dashboard.php" class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-gauge-high"></i> Dashboard
        </a>
        <a href="feeding.php" class="nav-link <?= $current_page === 'feeding.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-bowl-food"></i> Feeding
        </a>
        <a href="order.php" class="nav-link <?= $current_page === 'order.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-boxes-stacked"></i> Order
        </a>
        <a href="analytics.php" class="nav-link <?= $current_page === 'analytics.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-line"></i> Analytics
        </a>
        <div class="mobile-user">
            <i class="fa-solid fa-user"></i> <?= htmlspecialchars($staff_name) ?>
            <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </div>
</nav>

<div class="page-wrap">
    <div class="page-header">
        <h2><?= $page_title ?? 'Dashboard' ?></h2>
        <p><?= $page_sub ?? '' ?></p>
    </div>

<script>
function toggleMobile() {
    const menu = document.getElementById('mobile-menu');
    const icon = document.getElementById('ham-icon');
    menu.classList.toggle('open');
    icon.className = menu.classList.contains('open') ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
}
</script>