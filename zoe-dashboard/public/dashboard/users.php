<?php
$pageTitle = 'User Management';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

requireAdmin();
$base = getenv('APP_URL') ?: '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $fullName = trim($_POST['full_name'] ?? '');
            $role = $_POST['role'] ?? 'viewer';

            if ($username === '' || $password === '' || $fullName === '') {
                flash('All fields are required.', 'error');
                break;
            }

            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                flash('Username already exists.', 'error');
                break;
            }

            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, active) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $fullName, $role]);
            flash('User created successfully.', 'success');
            break;

        case 'update_role':
            $userId = (int)($_POST['user_id'] ?? 0);
            $newRole = $_POST['role'] ?? 'viewer';
            if ($userId && in_array($newRole, ['admin', 'editor', 'viewer'])) {
                $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
                $stmt->execute([$newRole, $userId]);
                flash('Role updated.', 'success');
            }
            break;

        case 'toggle_active':
            $userId = (int)($_POST['user_id'] ?? 0);
            if ($userId) {
                $stmt = $pdo->prepare("UPDATE users SET active = NOT active WHERE id = ?");
                $stmt->execute([$userId]);
                flash('User status toggled.', 'success');
            }
            break;

        case 'reset_password':
            $userId = (int)($_POST['user_id'] ?? 0);
            $newPassword = $_POST['new_password'] ?? '';
            if ($userId && strlen($newPassword) >= 6) {
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
                flash('Password reset successfully.', 'success');
            } else {
                flash('Password must be at least 6 characters.', 'error');
            }
            break;

        case 'delete':
            $userId = (int)($_POST['user_id'] ?? 0);
            if ($userId === $_SESSION['user_id']) {
                flash('You cannot delete your own account.', 'error');
                break;
            }
            if ($userId) {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                flash('User deleted.', 'success');
            }
            break;
    }

    header('Location: ' . $base . '/dashboard/users.php');
    exit;
}

$usersStmt = $pdo->query("SELECT id, username, full_name, role, active, last_login FROM users ORDER BY id ASC");
$users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-header">
    <h1 class="page-title">User Management</h1>
    <button class="btn btn-primary" onclick="document.getElementById('addUserModal').classList.add('active')">Add User</button>
</div>

<table>
    <thead>
        <tr>
            <th>Username</th>
            <th>Full Name</th>
            <th>Role</th>
            <th>Status</th>
            <th>Last Login</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?= htmlspecialchars($u['username']) ?></td>
            <td><?= htmlspecialchars($u['full_name']) ?></td>
            <td>
                <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="update_role">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <select name="role" onchange="this.form.submit()" class="form-group">
                        <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="editor" <?= $u['role'] === 'editor' ? 'selected' : '' ?>>Editor</option>
                        <option value="viewer" <?= $u['role'] === 'viewer' ? 'selected' : '' ?>>Viewer</option>
                    </select>
                </form>
            </td>
            <td>
                <form method="POST" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="badge <?= $u['active'] ? 'badge-success' : 'badge-error' ?>">
                        <?= $u['active'] ? 'Active' : 'Inactive' ?>
                    </button>
                </form>
            </td>
            <td><?= $u['last_login'] ? date('M j, Y g:i A', strtotime($u['last_login'])) : 'Never' ?></td>
            <td>
                <button class="btn btn-ghost btn-sm" onclick="openPasswordModal(<?= $u['id'] ?>)">Reset Password</button>
                <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                <form method="POST" style="display:inline" onsubmit="return confirm('Delete this user permanently?')">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="modal-overlay" id="addUserModal">
    <div class="modal">
        <h3 class="card-title">Add New User</h3>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required>
            </div>
            <div class="form-group">
                <label>Role</label>
                <select name="role">
                    <option value="viewer">Viewer</option>
                    <option value="editor">Editor</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            <div class="form-row">
                <button type="submit" class="btn btn-primary">Create</button>
                <button type="button" class="btn btn-ghost" onclick="this.closest('.modal-overlay').classList.remove('active')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="passwordModal">
    <div class="modal">
        <h3 class="card-title">Reset Password</h3>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="user_id" id="resetUserId">
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" required minlength="6">
            </div>
            <div class="form-row">
                <button type="submit" class="btn btn-primary">Reset</button>
                <button type="button" class="btn btn-ghost" onclick="this.closest('.modal-overlay').classList.remove('active')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPasswordModal(userId) {
    document.getElementById('resetUserId').value = userId;
    document.getElementById('passwordModal').classList.add('active');
}
</script>

</div>
</body>
</html>
