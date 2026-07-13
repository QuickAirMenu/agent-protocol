<?php
// zoe-dashboard/includes/layout.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';

$user = requireLogin();
$currentPage = basename($_SERVER['SCRIPT_NAME'], '.php');
$base = getenv('APP_URL') ?: '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?= csrfMeta() ?>
<title>Zoe — <?= e($pageTitle ?? 'لوحة التحكم') ?></title>
<link href="https://fonts.googleapis.com/css2?family=Rakkas&family=Cairo:wght@300;400;500;700;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base ?>/assets/css/dashboard.css">
<?php if (isset($extraCss)): ?>
<style><?= $extraCss ?></style>
<?php endif; ?>
</head>
<body>

<button class="menu-toggle" onclick="document.querySelector('.sidebar').classList.toggle('open')">☰</button>

<div class="sidebar">
    <div class="sidebar-logo">
        <div class="sidebar-icon d">Z</div>
        <div class="sidebar-title d">Zoe</div>
    </div>

    <a href="<?= $base ?>/dashboard" class="nav-item <?= $currentPage === 'index' ? 'active' : '' ?>"><span class="icon">🏠</span> الرئيسية</a>
    <a href="<?= $base ?>/dashboard/tasks.php" class="nav-item <?= $currentPage === 'tasks' ? 'active' : '' ?>"><span class="icon">📋</span> المهام</a>
    <a href="<?= $base ?>/dashboard/reminders.php" class="nav-item <?= $currentPage === 'reminders' ? 'active' : '' ?>"><span class="icon">⏰</span> التذكيرات</a>
    <a href="<?= $base ?>/dashboard/matches.php" class="nav-item <?= $currentPage === 'matches' ? 'active' : '' ?>"><span class="icon">⚽</span> المباريات</a>
    <a href="<?= $base ?>/dashboard/chat.php" class="nav-item <?= $currentPage === 'chat' ? 'active' : '' ?>"><span class="icon">🤖</span> AI Chat</a>

    <?php if ($user['role'] === 'admin'): ?>
        <div class="nav-divider"></div>
        <a href="<?= $base ?>/dashboard/users.php" class="nav-item <?= $currentPage === 'users' ? 'active' : '' ?>"><span class="icon">👥</span> المستخدمين</a>
        <a href="<?= $base ?>/dashboard/settings.php" class="nav-item <?= $currentPage === 'settings' ? 'active' : '' ?>"><span class="icon">⚙️</span> الإعدادات</a>
    <?php endif; ?>

    <div class="nav-user">
        <div class="nav-user-info">
            <div class="nav-user-avatar"><?= mb_substr($user['full_name'] ?? $user['username'], 0, 1) ?></div>
            <div>
                <div class="nav-user-name"><?= e($user['full_name'] ?? $user['username']) ?></div>
                <div class="nav-user-role"><?= match($user['role']) { 'admin' => 'مدير', 'viewer' => 'مشاهد', default => 'مستخدم' } ?></div>
            </div>
        </div>
        <a href="<?= $base ?>/logout" class="nav-logout">خروج</a>
    </div>
</div>

<div class="main">
<?php $flash = getFlash(); if ($flash): ?>
    <div class="flash flash-<?= $flash['type'] ?>"><?= e($flash['message']) ?></div>
<?php endif; ?>
