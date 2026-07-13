<?php
$pageTitle = 'التذكيرات';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

$base = getenv('APP_URL') ?: '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add' && verifyCsrf($_POST)) {
        $message = trim($_POST['message'] ?? '');
        $remindAt = $_POST['remind_at'] ?? '';
        $recurring = $_POST['recurring'] ?? 'none';
        if ($message && $remindAt) {
            $stmt = $pdo->prepare('INSERT INTO reminders (message, remind_at, recurring) VALUES (?, ?, ?)');
            $stmt->execute([$message, $remindAt, $recurring]);
            setFlash('تمت إضافة التذكير بنجاح', 'success');
        }
    } elseif ($action === 'delete' && verifyCsrf($_POST)) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $pdo->prepare('DELETE FROM reminders WHERE id = ?');
            $stmt->execute([$id]);
            setFlash('تم حذف التذكير', 'success');
        }
    }
    redirect($base . '/dashboard/reminders.php');
}

$reminders = $pdo->query('SELECT * FROM reminders ORDER BY remind_at ASC')->fetchAll();
$recurringLabels = ['none' => 'مرة', 'daily' => 'يومي', 'weekly' => 'أسبوعي', 'monthly' => 'شهري'];

renderHeader($pageTitle, $base);
?>

<div class="page-header">
    <h1 class="page-title">التذكيرات</h1>
    <button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('active')">إضافة تذكير</button>
</div>

<?php flashMessage(); ?>

<table>
    <thead>
        <tr>
            <th>الرسالة</th>
            <th>التاريخ</th>
            <th>التكرار</th>
            <th>الحالة</th>
            <th>إجراءات</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($reminders)): ?>
            <tr><td colspan="5">لا يوجد تذكيرات</td></tr>
        <?php else: ?>
            <?php foreach ($reminders as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r['message']) ?></td>
                    <td><?= formatDate($r['remind_at']) ?></td>
                    <td><span class="badge"><?= $recurringLabels[$r['recurring']] ?? 'مرة' ?></span></td>
                    <td><span class="badge <?= $r['sent'] ? 'badge-success' : '' ?>"><?= $r['sent'] ? 'مرسل' : 'معلق' ?></span></td>
                    <td>
                        <form method="POST" style="display:inline">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $r['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">حذف</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<div class="modal-overlay" id="addModal">
    <div class="modal">
        <h2>إضافة تذكير</h2>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>الرسالة</label>
                <textarea name="message" class="form-control" required></textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>التاريخ والوقت</label>
                    <input type="datetime-local" name="remind_at" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>التكرار</label>
                    <select name="recurring" class="form-control">
                        <option value="none">مرة</option>
                        <option value="daily">يومي</option>
                        <option value="weekly">أسبوعي</option>
                        <option value="monthly">شهري</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <button type="submit" class="btn btn-primary">إضافة</button>
                <button type="button" class="btn" onclick="document.getElementById('addModal').classList.remove('active')">إلغاء</button>
            </div>
        </form>
    </div>
</div>

</div>
</body>
</html>
