<?php

require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/helpers.php';

$base = getenv('APP_URL') ?: '';

if (isLoggedIn()) {
    header('Location: ' . $base . '/dashboard');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (loginUser($username, $password, $pdo)) {
        header('Location: ' . $base . '/dashboard');
        exit;
    }

    $error = 'اسم المستخدم أو كلمة المرور غير صحيحة';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zoe Assistant - تسجيل الدخول</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Rakkas&display=swap" rel="stylesheet">
    <style>
        :root {
            --night: #0f1923;
            --night2: #1a2d42;
            --paper: #f0ead6;
            --gold: #d4a843;
            --green: #27ae60;
            --red: #c0392b;
            --line: rgba(240, 234, 214, 0.12);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Cairo', sans-serif;
            background: var(--night);
            color: var(--paper);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            direction: rtl;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            padding: 20px;
        }

        .login-box {
            background: var(--night2);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 48px 36px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--gold), #b8912e);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-family: 'Rakkas', serif;
            font-size: 42px;
            color: var(--night);
            font-weight: 400;
            box-shadow: 0 4px 16px rgba(212, 168, 67, 0.3);
        }

        .title {
            font-size: 26px;
            font-weight: 700;
            color: var(--paper);
            margin-bottom: 6px;
        }

        .subtitle {
            font-size: 16px;
            color: rgba(240, 234, 214, 0.5);
            margin-bottom: 36px;
        }

        .form-group {
            margin-bottom: 18px;
            text-align: right;
        }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: rgba(240, 234, 214, 0.6);
            margin-bottom: 6px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            background: var(--night);
            border: 1px solid var(--line);
            border-radius: 10px;
            color: var(--paper);
            font-family: 'Cairo', sans-serif;
            font-size: 15px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group input:focus {
            border-color: var(--gold);
            box-shadow: 0 0 0 3px rgba(212, 168, 67, 0.15);
        }

        .form-group input::placeholder {
            color: rgba(240, 234, 214, 0.25);
        }

        .submit-btn {
            width: 100%;
            padding: 13px;
            background: linear-gradient(135deg, var(--gold), #b8912e);
            border: none;
            border-radius: 10px;
            color: var(--night);
            font-family: 'Cairo', sans-serif;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.15s;
            margin-top: 8px;
        }

        .submit-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(212, 168, 67, 0.35);
        }

        .submit-btn:active {
            transform: translateY(0);
        }

        .error-msg {
            background: rgba(192, 57, 43, 0.15);
            border: 1px solid rgba(192, 57, 43, 0.3);
            color: #e74c3c;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-box">
            <div class="logo">Z</div>
            <h1 class="title">Zoe Assistant</h1>
            <p class="subtitle">لوحة التحكم</p>

            <?php if ($error): ?>
                <div class="error-msg"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="<?= htmlspecialchars($base . '/login') ?>">
                <?= csrfField() ?>

                <div class="form-group">
                    <label for="username">اسم المستخدم</label>
                    <input type="text" id="username" name="username" required autocomplete="username" autofocus>
                </div>

                <div class="form-group">
                    <label for="password">كلمة المرور</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>

                <button type="submit" class="submit-btn">دخول</button>
            </form>
        </div>
    </div>
</body>
</html>