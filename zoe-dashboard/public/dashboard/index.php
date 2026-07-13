<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

$user = requireLogin();
$base = getenv('APP_URL') ?: '';

$usersCount = (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$pendingTasks = (int) $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'pending'")->fetchColumn();
$doneTasks = (int) $pdo->query("SELECT COUNT(*) FROM tasks WHERE status = 'done'")->fetchColumn();
$messagesCount = (int) $pdo->query("SELECT COUNT(*) FROM chat_history")->fetchColumn();

$recentTasks = $pdo->query("
    SELECT t.id, t.title, t.status, t.created_at, u.username
    FROM tasks t
    LEFT JOIN users u ON t.user_id = u.id
    ORDER BY t.created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

$recentUsers = $pdo->query("
    SELECT id, full_name, username, role, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-num"><?= $usersCount ?></div>
        <div class="stat-label">Users</div>
    </div>
    <div class="stat-card">
        <div class="stat-num"><?= $pendingTasks ?></div>
        <div class="stat-label">Pending Tasks</div>
    </div>
    <div class="stat-card">
        <div class="stat-num"><?= $doneTasks ?></div>
        <div class="stat-label">Done Tasks</div>
    </div>
    <div class="stat-card">
        <div class="stat-num"><?= $messagesCount ?></div>
        <div class="stat-label">Messages</div>
    </div>
</div>

<div class="card">
    <div class="card-title">Recent Tasks</div>
    <?php if (empty($recentTasks)): ?>
        <p>No tasks yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentTasks as $task): ?>
                    <tr>
                        <td><?= h($task['title']) ?></td>
                        <td><span class="badge badge-<?= $task['status'] ?>"><?= ucfirst(h($task['status'])) ?></span></td>
                        <td><?= timeAgo($task['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<div class="card">
    <div class="card-title">Recent Users</div>
    <?php if (empty($recentUsers)): ?>
        <p>No users yet.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Role</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentUsers as $u): ?>
                    <tr>
                        <td><?= h($u['full_name']) ?></td>
                        <td><?= h($u['username']) ?></td>
                        <td><span class="badge badge-<?= $u['role'] ?>"><?= ucfirst(h($u['role'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

</div>
</body>
</html>
