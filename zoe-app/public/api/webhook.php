<?php
// zoe-app/public/api/webhook.php

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/telegram.php';
require_once __DIR__ . '/../../includes/deepseek.php';
require_once dirname(__DIR__, 2) . '/shared/includes/helpers.php';

// ✅ م٤ — تحقق من مصدر تيليغرام
$secret = getenv('TELEGRAM_SECRET_TOKEN');
if ($secret) {
    $incoming = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
    if (!hash_equals($secret, $incoming)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

$update = json_decode(file_get_contents('php://input'), true);
if (!$update) {
    http_response_code(400);
    exit;
}

$tg = getTelegram();
$ai = getDeepSeek();

// Callback Queries
if (isset($update['callback_query'])) {
    $cb     = $update['callback_query'];
    $chatId = $cb['message']['chat']['id'];
    $data   = $cb['data'];

    if ($data === 'tasks_list')       handleShowTasks($chatId, $pdo, $tg);
    elseif ($data === 'tasks_add')    handleTaskAdd($chatId, $pdo, $tg);
    elseif (str_starts_with($data, 'task_done_')) {
        $taskId = (int) substr($data, 10);
        handleTaskDone($taskId, $chatId, $pdo, $tg);
    }

    $tg->answerCallback($cb['id']);
    exit;
}

// Regular Message
$message = $update['message'] ?? $update['edited_message'] ?? null;
if (!$message) exit;

$chatId    = $message['chat']['id'];
$text      = trim($message['text'] ?? '');
$from      = $message['from'];
$userId    = $from['id'];
$username  = $from['username'] ?? '';
$firstName = $from['first_name'] ?? '';

$dbUserId = ensureUser($userId, $username, $firstName, $pdo);

// ✅ م٥ — state من DB
$state = getUserState($userId, $pdo);

if ($state === 'waiting_task_title') {
    handleNewTask($chatId, $dbUserId, $text, $pdo, $tg);
    clearUserState($userId, $pdo);
    exit;
}

// Rate Limiting — ت١
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM chat_history WHERE user_id = ? AND created_at > NOW() - INTERVAL 60 SECOND"
);
$stmt->execute([$dbUserId]);
if ($stmt->fetchColumn() > 15) {
    $tg->sendMessage($chatId, '⏳ أرسلت كثيراً، انتظر دقيقة.');
    exit;
}

// Commands
if (str_starts_with($text, '/')) {
    $parts   = explode(' ', $text, 2);
    $command = strtolower($parts[0]);
    $args    = trim($parts[1] ?? '');

    match (true) {
        $command === '/start'  => handleStart($chatId, $firstName, $tg),
        $command === '/task'   => handleTask($chatId, $dbUserId, $args, $pdo, $tg),
        $command === '/tasks'  => handleShowTasks($chatId, $pdo, $tg),
        $command === '/remind' => handleRemind($chatId, $dbUserId, $args, $pdo, $tg),
        $command === '/match'  => handleMatches($chatId, $pdo, $tg),
        $command === '/ask'    => handleAsk($chatId, $args, $ai, $tg),
        $command === '/help'   => handleHelp($chatId, $tg),
        default                => $tg->sendMessage($chatId, '❓ أمر غير معروف. اكتب /help'),
    };
    exit;
}

// Free text → AI
if (!empty($text)) {
    $tg->sendTyping($chatId);
    $response = $ai->withHistory($dbUserId, $text, $pdo);
    $tg->sendMessage($chatId, $response ? "🤖 $response" : '❌ تعذّر الرد، حاول مرة أخرى.');
}

// ─── دوال المساعدة ──────────────────────────────────────

function ensureUser(int $tgId, string $username, string $firstName, PDO $pdo): int {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE telegram_id = ?");
    $stmt->execute([$tgId]);
    $user = $stmt->fetch();
    if ($user) return $user['id'];

    $stmt = $pdo->prepare("INSERT INTO users (telegram_id, username, full_name) VALUES (?, ?, ?)");
    $stmt->execute([$tgId, $username, $firstName]);
    return (int) $pdo->lastInsertId();
}

function getUserState(int $tgId, PDO $pdo): string {
    $stmt = $pdo->prepare("SELECT bot_state FROM users WHERE telegram_id = ?");
    $stmt->execute([$tgId]);
    return $stmt->fetchColumn() ?: 'idle';
}

function setUserState(int $tgId, string $state, PDO $pdo): void {
    $pdo->prepare("UPDATE users SET bot_state = ? WHERE telegram_id = ?")->execute([$state, $tgId]);
}

function clearUserState(int $tgId, PDO $pdo): void {
    setUserState($tgId, 'idle', $pdo);
}

function handleStart(int $chatId, string $name, TelegramBot $tg): void {
    $tg->sendMessage($chatId,
        "👋 مرحباً <b>$name</b>! أنا <b>Zoe</b>، مساعدك الذكي\n\n" .
        "الأوامر المتاحة:\n" .
        "/task — إضافة مهمة جديدة\n" .
        "/tasks — عرض مهامي\n" .
        "/remind — ضبط تذكير\n" .
        "/match — مباريات اليوم\n" .
        "/ask [سؤال] — سؤال ذكي\n" .
        "/help — المساعدة\n\n" .
        "💬 أو فقط اكتب أي شيء وأنا أجاوبك!"
    );
}

function handleTask(int $chatId, int $dbUserId, string $args, PDO $pdo, TelegramBot $tg): void {
    if (empty($args)) {
        $tg->sendMessage($chatId, "📝 أرسل المهمة:\n<code>/task شراء الهدايا</code>");
        return;
    }
    $pdo->prepare("INSERT INTO tasks (user_id, title) VALUES (?, ?)")->execute([$dbUserId, $args]);
    $tg->sendMessage($chatId, "✅ تمت إضافة: <b>" . e($args) . "</b>");
}

function handleNewTask(int $chatId, int $dbUserId, string $title, PDO $pdo, TelegramBot $tg): void {
    $pdo->prepare("INSERT INTO tasks (user_id, title) VALUES (?, ?)")->execute([$dbUserId, $title]);
    $tg->sendMessage($chatId, "✅ تمت إضافة: <b>" . e($title) . "</b>");
}

function handleTaskAdd(int $chatId, PDO $pdo, TelegramBot $tg): void {
    $tg->sendMessage($chatId, "📝 أرسل المهمة الجديدة:");
    // نحتاج telegram_id لجلب user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE telegram_id = ?");
    $stmt->execute([$chatId]);
    $user = $stmt->fetch();
    if ($user) {
        setUserState($chatId, 'waiting_task_title', $pdo);
    }
}

function handleShowTasks(int $chatId, PDO $pdo, TelegramBot $tg): void {
    $stmt = $pdo->prepare(
        "SELECT t.id, t.title FROM tasks t
         JOIN users u ON t.user_id = u.id
         WHERE u.telegram_id = ? AND t.status = 'pending'
         ORDER BY FIELD(t.priority,'urgent','high','medium','low'), t.created_at DESC
         LIMIT 10"
    );
    $stmt->execute([$chatId]);
    $tasks = $stmt->fetchAll();

    if (empty($tasks)) {
        $tg->sendMessage($chatId, "📝 لا توجد مهام معلقة. أضف واحدة بـ /task");
        return;
    }

    $text    = "📋 <b>مهامي المعلقة:</b>\n\n";
    $buttons = [];
    foreach ($tasks as $i => $task) {
        $n     = $i + 1;
        $text .= "$n. " . e($task['title']) . "\n";
        $buttons[] = [['text' => "✅ $n", 'callback_data' => "task_done_{$task['id']}"]];
    }
    $tg->sendKeyboard($chatId, $text, $buttons);
}

function handleTaskDone(int $taskId, int $chatId, PDO $pdo, TelegramBot $tg): void {
    $pdo->prepare(
        "UPDATE tasks SET status='done', completed_at=NOW()
         WHERE id=? AND user_id=(SELECT id FROM users WHERE telegram_id=? LIMIT 1)"
    )->execute([$taskId, $chatId]);
    $tg->sendMessage($chatId, "✅ أحسنت! تم إنجاز المهمة.");
    handleShowTasks($chatId, $pdo, $tg);
}

function handleRemind(int $chatId, int $dbUserId, string $args, PDO $pdo, TelegramBot $tg): void {
    if (empty($args)) {
        $tg->sendMessage($chatId,
            "⏰ صيغة التذكير:\n" .
            "<code>/remind 30m اشرب الماء</code>\n" .
            "<code>/remind 2h اجتماع مهم</code>\n" .
            "<code>/remind 1d موعد الدكتور</code>\n\n" .
            "الوحدات: m = دقيقة، h = ساعة، d = يوم"
        );
        return;
    }

    if (!preg_match('/^(\d+)([mhd])\s+(.+)$/u', $args, $m)) {
        $tg->sendMessage($chatId, "❌ صيغة خاطئة. مثال: <code>/remind 30m اشرب الماء</code>");
        return;
    }

    $multipliers = ['m' => 60, 'h' => 3600, 'd' => 86400];
    $remindAt    = date('Y-m-d H:i:s', time() + ((int)$m[1] * $multipliers[$m[2]]));
    $msg         = $m[3];

    $pdo->prepare("INSERT INTO reminders (user_id, message, remind_at) VALUES (?, ?, ?)")
        ->execute([$dbUserId, $msg, $remindAt]);

    $label = ['m' => 'دقيقة', 'h' => 'ساعة', 'd' => 'يوم'];
    $tg->sendMessage($chatId, "⏰ تم! سأذكّرك بـ <b>" . e($msg) . "</b> بعد {$m[1]} {$label[$m[2]]}");
}

function handleMatches(int $chatId, PDO $pdo, TelegramBot $tg): void {
    $stmt = $pdo->prepare("SELECT * FROM matches WHERE DATE(match_date) = CURDATE() ORDER BY match_date");
    $stmt->execute();
    $matches = $stmt->fetchAll();

    if (empty($matches)) {
        $tg->sendMessage($chatId, "⚽ لا توجد مباريات مجدولة اليوم.");
        return;
    }

    $text = "⚽ <b>مباريات اليوم:</b>\n\n";
    foreach ($matches as $match) {
        if ($match['status'] === 'finished') {
            $score = "{$match['score_home']} - {$match['score_away']}";
            $text .= "🏁 {$match['team_home']} <b>$score</b> {$match['team_away']}\n";
        } elseif ($match['status'] === 'live') {
            $text .= "🔴 مباشر: {$match['team_home']} vs {$match['team_away']}\n";
        } else {
            $time  = date('H:i', strtotime($match['match_date']));
            $text .= "⏰ $time — {$match['team_home']} vs {$match['team_away']}\n";
        }
    }
    $tg->sendMessage($chatId, $text);
}

function handleAsk(int $chatId, string $args, DeepSeekClient $ai, TelegramBot $tg): void {
    if (empty($args)) {
        $tg->sendMessage($chatId, "🤖 أرسل سؤالك:\n<code>/ask ما هي عاصمة فرنسا؟</code>");
        return;
    }
    $tg->sendTyping($chatId);
    $response = $ai->simple($args);
    $tg->sendMessage($chatId, $response ? "🤖 $response" : "❌ تعذّر الرد، حاول مرة أخرى.");
}

function handleHelp(int $chatId, TelegramBot $tg): void {
    $tg->sendMessage($chatId,
        "📋 <b>قائمة الأوامر:</b>\n\n" .
        "🎯 <b>المهام:</b>\n" .
        "/task [مهمة] — إضافة مهمة\n" .
        "/tasks — عرض المهام المعلقة\n\n" .
        "⏰ <b>التذكيرات:</b>\n" .
        "/remind [وقت] [رسالة] — ضبط تذكير\n" .
        "مثال: <code>/remind 30m اشرب الماء</code>\n\n" .
        "⚽ <b>المباريات:</b>\n" .
        "/match — مباريات اليوم\n\n" .
        "🤖 <b>الذكاء الاصطناعي:</b>\n" .
        "/ask [سؤال] — سؤال ذكي\n\n" .
        "💬 يمكنك أيضاً الكتابة مباشرة دون أوامر!"
    );
}
