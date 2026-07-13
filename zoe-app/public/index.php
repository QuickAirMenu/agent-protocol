<?php
// zoe-app/public/index.php — Bot Status Page
require_once __DIR__ . '/../includes/db.php';

$stats = [
    'users'  => $pdo->query("SELECT COUNT(*) FROM users WHERE telegram_id IS NOT NULL")->fetchColumn(),
    'tasks'  => $pdo->query("SELECT COUNT(*) FROM tasks WHERE status='pending'")->fetchColumn(),
    'messages' => $pdo->query("SELECT COUNT(*) FROM chat_history WHERE source='telegram'")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Zoe Bot — Status</title>
<link href="https://fonts.googleapis.com/css2?family=Rakkas&family=Cairo:wght@400;700&display=swap" rel="stylesheet">
<style>
:root{--night:#0f1923;--night2:#1a2d42;--paper:#f0ead6;--gold:#d4a843;--green:#27ae60;--line:rgba(240,234,214,0.12);}
*{box-sizing:border-box;margin:0;padding:0;}
body{background:var(--night);color:var(--paper);font-family:'Cairo',sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;}
.box{background:var(--night2);border:1px solid var(--line);border-radius:20px;padding:40px;text-align:center;max-width:400px;width:100%;}
.icon{width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--gold),var(--green));display:inline-flex;align-items:center;justify-content:center;font-size:36px;color:var(--night);font-weight:bold;font-family:'Rakkas',serif;}
h1{font-family:'Rakkas',serif;font-size:28px;margin:16px 0 4px;}
.sub{opacity:0.5;font-size:14px;}
.stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:24px;}
.stat{padding:12px;}
.stat-num{font-size:28px;color:var(--gold);font-family:'Rakkas',serif;}
.stat-label{font-size:12px;opacity:0.5;margin-top:4px;}
.status{margin-top:20px;padding:8px 16px;border-radius:999px;background:rgba(39,174,96,0.15);color:var(--green);font-size:13px;display:inline-flex;align-items:center;gap:6px;}
.dot{width:8px;height:8px;border-radius:50%;background:var(--green);animation:pulse 2s infinite;}
@keyframes pulse{0%,100%{opacity:1;}50%{opacity:0.4;}}
</style>
</head>
<body>
<div class="box">
  <div class="icon">Z</div>
  <h1>Zoe Assistant</h1>
  <div class="sub">بوت تيليغرام ذكي</div>
  <div class="status"><span class="dot"></span> نشط ويعمل</div>
  <div class="stats">
    <div class="stat"><div class="stat-num"><?= $stats['users'] ?></div><div class="stat-label">مستخدم</div></div>
    <div class="stat"><div class="stat-num"><?= $stats['tasks'] ?></div><div class="stat-label">مهمة</div></div>
    <div class="stat"><div class="stat-num"><?= $stats['messages'] ?></div><div class="stat-label">رسالة</div></div>
  </div>
</div>
</body>
</html>
