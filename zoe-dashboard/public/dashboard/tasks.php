<?php
$pageTitle = 'Tasks';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

$user = requireLogin();
$base = getenv('APP_URL') ?: '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title = trim($_POST['title'] ?? '');
        $allowedPriorities = ['low', 'medium', 'high', 'urgent'];
        $priority = in_array($_POST['priority'] ?? '', $allowedPriorities) ? $_POST['priority'] : 'medium';
        $dueDate = $_POST['due_date'] ?: null;

        if ($title !== '') {
            $stmt = $pdo->prepare("INSERT INTO tasks (user_id, title, priority, due_date, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
            $stmt->execute([$user['id'], $title, $priority, $dueDate]);
            header("Location: {$base}/dashboard/tasks.php?success=1");
            exit;
        }
    } elseif ($action === 'update_status') {
        $taskId = (int) ($_POST['task_id'] ?? 0);
        if ($taskId > 0) {
            $stmt = $pdo->prepare("UPDATE tasks SET status = 'done', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$taskId]);
        }
        header("Location: {$base}/dashboard/tasks.php?success=1");
        exit;
    } elseif ($action === 'delete') {
        $taskId = (int) ($_POST['task_id'] ?? 0);
        if ($taskId > 0) {
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
        }
        header("Location: {$base}/dashboard/tasks.php?success=1");
        exit;
    }
}

$allowedFilters = ['all', 'pending', 'done', 'urgent'];
$filter = in_array($_GET['filter'] ?? '', $allowedFilters) ? $_GET['filter'] : 'all';

$sql = "SELECT t.*, u.username FROM tasks t LEFT JOIN users u ON t.user_id = u.id";
$params = [];

if ($filter === 'urgent') {
    $sql .= " WHERE t.priority = 'urgent'";
} elseif ($filter !== 'all') {
    $sql .= " WHERE t.status = ?";
    $params[] = $filter;
}

$sql .= " ORDER BY t.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if (isset($_GET['success'])): ?>
    <div class="flash flash-success">Task updated successfully.</div>
<?php endif; ?>

<div class="page-header">
    <h1 class="page-title">Tasks</h1>
    <button class="btn btn-primary" onclick="document.getElementById('addTaskModal').classList.add('active')">Add Task</button>
</div>

<div class="filter-bar">
    <a href="<?= $base ?>/dashboard/tasks.php" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">All</a>
    <a href="<?= $base ?>/dashboard/tasks.php?filter=pending" class="filter-btn <?= $filter === 'pending' ? 'active' : '' ?>">Pending</a>
    <a href="<?= $base ?>/dashboard/tasks.php?filter=done" class="filter-btn <?= $filter === 'done' ? 'active' : '' ?>">Done</a>
    <a href="<?= $base ?>/dashboard/tasks.php?filter=urgent" class="filter-btn <?= $filter === 'urgent' ? 'active' : '' ?>">Urgent</a>
</div>

<div class="card">
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Due Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($tasks)): ?>
                <tr><td colspan="5">No tasks found.</td></tr>
            <?php else: ?>
                <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td><?= h($task['title']) ?></td>
                        <td><span class="badge badge-<?= $task['priority'] ?>"><?= ucfirst(h($task['priority'])) ?></span></td>
                        <td><span class="badge badge-<?= $task['status'] ?>"><?= ucfirst(h($task['status'])) ?></span></td>
                        <td><?= $task['due_date'] ? date('M d, Y', strtotime($task['due_date'])) : '—' ?></td>
                        <td>
                            <div class="form-row">
                                <?php if ($task['status'] !== 'done'): ?>
                                    <form method="POST">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                        <button type="submit" class="btn btn-success btn-sm">Mark Done</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this task?')">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div class="modal-overlay" id="addTaskModal">
    <div class="modal">
        <div class="card-title">Add New Task</div>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" required>
            </div>
            <div class="form-group">
                <label for="priority">Priority</label>
                <select id="priority" name="priority">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                    <option value="urgent">Urgent</option>
                </select>
            </div>
            <div class="form-group">
                <label for="due_date">Due Date</label>
                <input type="datetime-local" id="due_date" name="due_date">
            </div>
            <div class="form-row">
                <button type="submit" class="btn btn-primary">Add Task</button>
                <button type="button" class="btn btn-ghost" onclick="document.getElementById('addTaskModal').classList.remove('active')">Cancel</button>
            </div>
        </form>
    </div>
</div>

</div>
</body>
</html>
