<?php
session_start();

require_once '../db/connection.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $stmt = $pdo->prepare("SELECT * FROM staff WHERE username = ? AND password = ? LIMIT 1");
        $stmt->execute([$username, $password]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($staff) {
            $_SESSION['staff_id']   = $staff['id'];
            $_SESSION['staff_name'] = $staff['firstname'] . ' ' . $staff['lastname'];
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
    <title>Staff Login</title>
    <link rel="stylesheet" href="../assets/fontawesome-7/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --white: #ffffff;
            --off-white: #f6f6f6;
            --light: #e8e8e8;
            --mid: #aaaaaa;
            --black: #111111;
            --navy: #1a1a2e;
            --error: #b91c1c;
        }

        body {
            background: var(--off-white);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .wrapper {
            display: flex;
            width: 780px;
            min-height: 460px;
            background: var(--white);
            box-shadow: 0 2px 40px rgba(0,0,0,0.08);
            animation: fadeUp 0.4s ease both;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .left {
            width: 220px;
            flex-shrink: 0;
            background: var(--navy);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 36px 28px;
            position: relative;
            overflow: hidden;
        }

        .left::before {
            content: '';
            position: absolute;
            width: 220px; height: 220px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.05);
            top: -60px; right: -80px;
        }

        .left::after {
            content: '';
            position: absolute;
            width: 140px; height: 140px;
            border-radius: 50%;
            border: 1px solid rgba(255,255,255,0.04);
            bottom: 30px; left: -50px;
        }

        .left-top { position: relative; z-index: 1; }

        .fish-icon {
            width: 40px; height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #fff;
            margin-bottom: 20px;
        }

        .left-top h2 {
            font-size: 17px;
            font-weight: 700;
            color: #fff;
            line-height: 1.3;
            margin-bottom: 6px;
        }

        .left-top p {
            font-size: 11px;
            color: rgba(255,255,255,0.3);
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        .left-bottom {
            position: relative;
            z-index: 1;
            font-size: 10px;
            color: rgba(255,255,255,0.2);
            letter-spacing: 0.06em;
        }

        .right {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px 44px;
        }

        .form-wrap { width: 100%; max-width: 300px; }

        .eyebrow {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            color: var(--mid);
            margin-bottom: 10px;
        }

        .eyebrow span {
            display: block;
            width: 20px; height: 1px;
            background: var(--light);
        }

        h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--black);
            letter-spacing: -0.02em;
            margin-bottom: 32px;
        }

        .field { margin-bottom: 20px; }

        label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            color: var(--mid);
            margin-bottom: 8px;
        }

        .input-wrap {
            display: flex;
            align-items: center;
            border: 1px solid var(--light);
            background: var(--off-white);
            transition: border-color 0.2s, background 0.2s;
        }

        .input-wrap:focus-within {
            border-color: var(--black);
            background: var(--white);
        }

        .input-wrap i {
            padding: 0 12px;
            font-size: 13px;
            color: #ccc;
            flex-shrink: 0;
        }

        .input-wrap input {
            flex: 1;
            border: none;
            outline: none;
            background: transparent;
            font-family: inherit;
            font-size: 14px;
            color: var(--black);
            padding: 11px 12px 11px 0;
        }

        .input-wrap input::placeholder { color: #ddd; }

        .error-msg {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: var(--error);
            background: #fef2f2;
            border-left: 3px solid var(--error);
            padding: 9px 12px;
            margin-bottom: 20px;
        }

        .btn {
            width: 100%;
            background: var(--navy);
            color: #fff;
            font-family: inherit;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            border: none;
            padding: 14px;
            cursor: pointer;
            margin-top: 8px;
            transition: opacity 0.2s, transform 0.1s;
        }

        .btn:hover { opacity: 0.85; }
        .btn:active { transform: scale(0.99); }

        @media (max-width: 580px) {
            .wrapper { flex-direction: column; width: 100%; min-height: 100vh; box-shadow: none; }
            .left { width: 100%; min-height: 130px; justify-content: flex-end; padding: 24px; }
            .right { padding: 32px 24px; }
        }
    </style>
</head>
<body>

<div class="wrapper">
    <div class="left">
        <div class="left-top">
            <div class="fish-icon"><i class="fa-solid fa-fish"></i></div>
            <h2>LRS Hito Farm</h2>
            <p>Staff Portal</p>
        </div>
        <div class="left-bottom">&copy; <?= date('Y') ?> Hito System</div>
    </div>

    <div class="right">
        <div class="form-wrap">
            <div class="eyebrow"><span></span> Staff Access</div>
            <h1>Sign In</h1>

            <?php if ($error): ?>
                <div class="error-msg">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="field">
                    <label for="username">Username</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-user"></i>
                        <input type="text" id="username" name="username"
                            placeholder="Enter username"
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            required autocomplete="username">
                    </div>
                </div>

                <div class="field">
                    <label for="password">Password</label>
                    <div class="input-wrap">
                        <i class="fa-solid fa-lock"></i>
                        <input type="password" id="password" name="password"
                            placeholder="Enter password"
                            required autocomplete="current-password">
                    </div>
                </div>

                <button class="btn" type="submit">
                    <i class="fa-solid fa-right-to-bracket"></i> &nbsp;Sign In
                </button>
            </form>
        </div>
    </div>
</div>

</body>
</html>