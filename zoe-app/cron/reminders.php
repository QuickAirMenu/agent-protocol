<?php
// zoe-app/cron/reminders.php
// Run: * * * * * php /path/to/cron/reminders.php

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/telegram.php';
require_once dirname(__DIR__, 2) . '/shared/includes/helpers.php';

$tg = getTelegram();

$stmt = $pdo->prepare("
    SELECT r.*, u.telegram_id
    FROM reminders r
    JOIN users u ON r.user_id = u.id
    WHERE r.sent = 0 AND r.remind_at <= NOW()
    LIMIT 50
");
$stmt->execute();
$reminders = $stmt->fetchAll();

$count = 0;
foreach ($reminders as $reminder) {
    $tg->sendMessage($reminder['telegram_id'], "⏰ <b>تذكير:</b>\n{$reminder['message']}");
    
    $pdo->prepare("UPDATE reminders SET sent = 1, sent_at = NOW() WHERE id = ?")
        ->execute([$reminder['id']]);
    
    if ($reminder['recurring'] !== 'none') {
        $nextTime = match($reminder['recurring']) {
            'daily'  => date('Y-m-d H:i:s', strtotime('+1 day', strtotime($reminder['remind_at']))),
            'weekly' => date('Y-m-d H:i:s', strtotime('+1 week', strtotime($reminder['remind_at']))),
            'monthly'=> date('Y-m-d H:i:s', strtotime('+1 month', strtotime($reminder['remind_at']))),
        };
        $pdo->prepare("INSERT INTO reminders (user_id, message, remind_at, recurring) VALUES (?, ?, ?, ?)")
            ->execute([$reminder['user_id'], $reminder['message'], $nextTime, $reminder['recurring']]);
    }
    $count++;
}

if ($count > 0) {
    zoeLog('info', "Sent $count reminders");
}
