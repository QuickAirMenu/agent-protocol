<?php
$pageTitle = 'المباريات';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';

$base = getenv('APP_URL') ?: '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        verifyCsrf();
        $competition = trim($_POST['competition'] ?? '');
        $teamHome = trim($_POST['team_home'] ?? '');
        $teamAway = trim($_POST['team_away'] ?? '');
        $matchDate = $_POST['match_date'] ?? '';
        $status = $_POST['status'] ?? 'scheduled';
        $scoreHome = (int)($_POST['score_home'] ?? 0);
        $scoreAway = (int)($_POST['score_away'] ?? 0);
        if ($competition && $teamHome && $teamAway && $matchDate) {
            $stmt = $pdo->prepare('INSERT INTO matches (competition, team_home, team_away, match_date, status, score_home, score_away) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$competition, $teamHome, $teamAway, $matchDate, $status, $scoreHome, $scoreAway]);
            setFlash('تمت إضافة المباراة بنجاح', 'success');
        }
    } elseif ($action === 'delete') {
        verifyCsrf();
        $id = (int)($_POST['id'] ?? 0);
        if ($id) {
            $stmt = $pdo->prepare('DELETE FROM matches WHERE id = ?');
            $stmt->execute([$id]);
            setFlash('تم حذف المباراة', 'success');
        }
    }
    redirect($base . '/dashboard/matches.php');
}

$filter = $_GET['filter'] ?? 'all';
$where = '';
if ($filter === 'today') {
    $where = ' WHERE DATE(match_date) = CURDATE()';
} elseif ($filter === 'live') {
    $where = " WHERE status = 'live'";
} elseif ($filter === 'finished') {
    $where = " WHERE status = 'finished'";
}
$matches = $pdo->query("SELECT * FROM matches $where ORDER BY match_date DESC")->fetchAll();

$statusLabels = ['scheduled' => 'قادمة', 'live' => 'مباشر', 'finished' => 'انتهت'];
$statusClasses = ['scheduled' => 'badge-gold', 'live' => 'badge-red', 'finished' => 'badge-success'];

?>

<div class="page-header">
    <h1 class="page-title">المباريات</h1>
    <button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('active')">إضافة مباراة</button>
</div>

<?php flashMessage(); ?>

<div class="filter-bar">
    <a href="<?= $base ?>/dashboard/matches.php?filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">الكل</a>
    <a href="<?= $base ?>/dashboard/matches.php?filter=today" class="filter-btn <?= $filter === 'today' ? 'active' : '' ?>">اليوم</a>
    <a href="<?= $base ?>/dashboard/matches.php?filter=live" class="filter-btn <?= $filter === 'live' ? 'active' : '' ?>">مباشر</a>
    <a href="<?= $base ?>/dashboard/matches.php?filter=finished" class="filter-btn <?= $filter === 'finished' ? 'active' : '' ?>">انتهت</a>
</div>

<table>
    <thead>
        <tr>
            <th>البطولة</th>
            <th>الفريقين</th>
            <th>النتيجة</th>
            <th>التاريخ</th>
            <th>الحالة</th>
            <th>إجراءات</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($matches)): ?>
            <tr><td colspan="6">لا توجد مباريات</td></tr>
        <?php else: ?>
            <?php foreach ($matches as $m): ?>
                <tr>
                    <td><?= htmlspecialchars($m['competition']) ?></td>
                    <td><?= htmlspecialchars($m['team_home']) ?> vs <?= htmlspecialchars($m['team_away']) ?></td>
                    <td><?= $m['score_home'] ?> - <?= $m['score_away'] ?></td>
                    <td><?= formatDate($m['match_date']) ?></td>
                    <td><span class="badge <?= $statusClasses[$m['status']] ?? '' ?>"><?= $statusLabels[$m['status']] ?? $m['status'] ?></span></td>
                    <td>
                        <form method="POST" style="display:inline">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $m['id'] ?>">
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
        <h2>إضافة مباراة</h2>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="action" value="add">
            <div class="form-group">
                <label>البطولة</label>
                <input type="text" name="competition" class="form-control" required>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>الفريق المضيف</label>
                    <input type="text" name="team_home" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>الفريق الضيف</label>
                    <input type="text" name="team_away" class="form-control" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>التاريخ</label>
                    <input type="datetime-local" name="match_date" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>الحالة</label>
                    <select name="status" class="form-control">
                        <option value="scheduled">قادمة</option>
                        <option value="live">مباشر</option>
                        <option value="finished">انتهت</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>نتيجة المضيف</label>
                    <input type="number" name="score_home" class="form-control" min="0" value="0">
                </div>
                <div class="form-group">
                    <label>نتيجة الضيف</label>
                    <input type="number" name="score_away" class="form-control" min="0" value="0">
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
