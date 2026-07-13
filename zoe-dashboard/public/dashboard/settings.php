<?php
$pageTitle = 'Settings';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

requireAdmin();
$base = getenv('APP_URL') ?: '';
$envPath = dirname(__DIR__, 2) . '/.env';

function loadEnv($path) {
    $env = [];
    if (file_exists($path)) {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                [$key, $value] = explode('=', $line, 2);
                $env[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
            }
        }
    }
    return $env;
}

function saveEnv($path, $env) {
    $lines = [];
    foreach ($env as $key => $value) {
        $lines[] = $key . '="' . addslashes($value) . '"';
    }
    file_put_contents($path, implode("\n", $lines) . "\n");
}

$env = loadEnv($envPath);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'bot_settings':
            $env['BOT_TOKEN'] = trim($_POST['bot_token'] ?? '');
            $env['DEEPSEEK_KEY'] = trim($_POST['deepseek_key'] ?? '');
            $env['ADMIN_CHAT_ID'] = trim($_POST['admin_chat_id'] ?? '');
            saveEnv($envPath, $env);
            flash('Bot settings saved.', 'success');
            break;

        case 'update_profile':
            $fullName = trim($_POST['full_name'] ?? '');
            if ($fullName !== '') {
                $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
                $stmt->execute([$fullName, $_SESSION['user_id']]);
                $_SESSION['full_name'] = $fullName;
                flash('Profile updated.', 'success');
            } else {
                flash('Full name cannot be empty.', 'error');
            }
            break;

        case 'change_password':
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($currentPassword, $user['password'])) {
                flash('Current password is incorrect.', 'error');
                break;
            }

            if ($newPassword !== $confirmPassword) {
                flash('New passwords do not match.', 'error');
                break;
            }

            if (strlen($newPassword) < 6) {
                flash('Password must be at least 6 characters.', 'error');
                break;
            }

            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $_SESSION['user_id']]);
            flash('Password changed successfully.', 'success');
            break;
    }

    header('Location: ' . $base . '/dashboard/settings.php');
    exit;
}

$currentUser = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
$currentUser->execute([$_SESSION['user_id']]);
$currentUser = $currentUser->fetch(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <h1 class="page-title">Settings</h1>
</div>

<div class="card">
    <h3 class="card-title">Bot Settings</h3>
    <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="bot_settings">
        <div class="form-group">
            <label>Bot Token</label>
            <input type="text" name="bot_token" value="<?= htmlspecialchars($env['BOT_TOKEN'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>DeepSeek API Key</label>
            <input type="password" name="deepseek_key" value="<?= htmlspecialchars($env['DEEPSEEK_KEY'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Admin Chat ID</label>
            <input type="text" name="admin_chat_id" value="<?= htmlspecialchars($env['ADMIN_CHAT_ID'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-primary">Save Bot Settings</button>
    </form>
</div>

<div class="card">
    <h3 class="card-title">Profile</h3>
    <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="update_profile">
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="full_name" value="<?= htmlspecialchars($currentUser['full_name'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-primary">Update Profile</button>
    </form>
</div>

<div class="card">
    <h3 class="card-title">Change Password</h3>
    <form method="POST">
        <?= csrfField() ?>
        <input type="hidden" name="action" value="change_password">
        <div class="form-group">
            <label>Current Password</label>
            <input type="password" name="current_password" required>
        </div>
        <div class="form-group">
            <label>New Password</label>
            <input type="password" name="new_password" required minlength="6">
        </div>
        <div class="form-group">
            <label>Confirm New Password</label>
            <input type="password" name="confirm_password" required minlength="6">
        </div>
        <button type="submit" class="btn btn-primary">Change Password</button>
    </form>
</div>

</div>
</body>
</html>
