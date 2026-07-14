<?php
// zoe-app/cron/caller.php — Web-accessible cron endpoint
// Set a cron service (cron-job.org) to hit this URL every minute
// https://a-mansour.com/zoe-app/cron/caller.php?secret=zoe_cron_2026

$secret = $_GET['secret'] ?? '';
if ($secret !== 'zoe_cron_2026') {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/reminders.php';

echo json_encode([
    'status' => 'ok',
    'reminders_sent' => $count ?? 0,
    'time' => date('Y-m-d H:i:s'),
]);
