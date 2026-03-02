<?php
require_once '../db/connection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ? AND password = ? LIMIT 1");
        $stmt->execute([$username, $password]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin) {
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Sign In</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #f2f2f2;
            --white: #ffffff;
            --border: #d9d9d9;
            --text: #1a1a1a;
            --muted: #999999;
            --black: #111111;
            --error: #b91c1c;
        }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .container {
            display: flex;
            width: 820px;
            min-height: 480px;
            background: var(--white);
            box-shadow: 0 2px 40px rgba(0,0,0,0.08);
            animation: fadeUp 0.45s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Left decorative panel */
        .panel {
            width: 260px;
            flex-shrink: 0;
            background: var(--black);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 40px 36px;
            position: relative;
            overflow: hidden;
        }

        .panel-lines {
            position: absolute;
            inset: 0;
        }

        .panel-lines::before,
        .panel-lines::after {
            content: '';
            position: absolute;
            border: 1px solid rgba(255,255,255,0.06);
        }

        .panel-lines::before {
            width: 260px;
            height: 260px;
            border-radius: 50%;
            top: -80px;
            right: -80px;
        }

        .panel-lines::after {
            width: 160px;
            height: 160px;
            border-radius: 50%;
            bottom: 60px;
            left: -60px;
        }

        .panel-logo {
            position: relative;
            z-index: 1;
        }

        .panel-logo .dot-row {
            display: flex;
            gap: 5px;
            margin-bottom: 20px;
        }

        .dot-row span {
            display: block;
            width: 5px;
            height: 5px;
            background: rgba(255,255,255,0.25);
            border-radius: 50%;
        }

        .dot-row span:first-child {
            background: rgba(255,255,255,0.9);
        }

        .panel-logo h2 {
            font-size: 18px;
            font-weight: 600;
            color: #fff;
            letter-spacing: 0.04em;
            margin-bottom: 6px;
        }

        .panel-logo p {
            font-size: 11px;
            color: rgba(255,255,255,0.3);
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        /* Right form panel */
        .form-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 56px 52px;
        }

        .form-header {
            margin-bottom: 36px;
        }

        .form-header .eyebrow {
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 10px;
        }

        .form-header h1 {
            font-size: 28px;
            font-weight: 700;
            color: var(--black);
            letter-spacing: -0.02em;
            line-height: 1;
        }

        .field {
            margin-bottom: 22px;
        }

        label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--muted);
            margin-bottom: 8px;
        }

        input {
            width: 100%;
            background: var(--bg);
            border: 1.5px solid var(--border);
            color: var(--text);
            font-family: inherit;
            font-size: 14px;
            padding: 11px 14px;
            outline: none;
            border-radius: 0;
            transition: border-color 0.2s;
        }

        input:focus {
            border-color: var(--black);
            background: var(--white);
        }

        input::placeholder { color: #ccc; }

        .error-msg {
            font-size: 12px;
            color: var(--error);
            background: #fef2f2;
            border-left: 3px solid var(--error);
            padding: 9px 12px;
            margin-bottom: 22px;
            letter-spacing: 0.02em;
        }

        .btn {
            width: 100%;
            background: var(--black);
            color: #fff;
            font-family: inherit;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            border: none;
            padding: 14px;
            cursor: pointer;
            border-radius: 0;
            margin-top: 4px;
            transition: opacity 0.2s, transform 0.1s;
        }

        .btn:hover { opacity: 0.82; }
        .btn:active { transform: scale(0.99); }

        .form-footer {
            margin-top: 28px;
            font-size: 11px;
            color: var(--muted);
            letter-spacing: 0.04em;
        }

        @media (max-width: 600px) {
            .container { flex-direction: column; width: 100%; min-height: 100vh; box-shadow: none; }
            .panel { width: 100%; min-height: 140px; justify-content: flex-end; }
            .form-panel { padding: 36px 28px; }
        }
    </style>
</head>
<body>

<div class="container">

    <div class="panel">
        <div class="panel-lines"></div>
        <div class="panel-logo">
            <div class="dot-row">
                <span></span><span></span><span></span>
            </div>
            <h2>Hito Breeding</h2>
            <p>Administration</p>
        </div>
    </div>

    <div class="form-panel">
        <div class="form-header">
            <div class="eyebrow">Admin Portal</div>
            <h1>Sign In</h1>
        </div>

        <?php if ($error): ?>
            <div class="error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="field">
                <label for="username">Username</label>
                <input type="text" id="username" name="username"
                    placeholder="Enter username"
                    value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                    required autocomplete="username">
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input type="password" id="password" name="password"
                    placeholder="Enter password"
                    required autocomplete="current-password">
            </div>
            <button class="btn" type="submit">Sign In &rarr;</button>
        </form>

        <div class="form-footer">&copy; <?= date('Y') ?> Hito Breeding. All rights reserved.</div>
    </div>

</div>

</body>
</html>