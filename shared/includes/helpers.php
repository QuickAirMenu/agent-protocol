<?php
// shared/includes/helpers.php

function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never {
    header("Location: $url");
    exit;
}

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function formatDate(?string $date): string {
    if (!$date) return '—';
    return date('Y-m-d H:i', strtotime($date));
}

function timeAgo(string $datetime): string {
    $now = new DateTime();
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->y > 0) return $diff->y . ' سنة';
    if ($diff->m > 0) return $diff->m . ' شهر';
    if ($diff->d > 0) return $diff->d . ' يوم';
    if ($diff->h > 0) return $diff->h . ' ساعة';
    if ($diff->i > 0) return $diff->i . ' دقيقة';
    return 'الآن';
}

function priorityLabel(string $p): string {
    return match($p) { 'low' => 'منخفضة', 'medium' => 'متوسطة', 'high' => 'عالية', 'urgent' => 'عاجلة', default => $p };
}

function priorityColor(string $p): string {
    return match($p) { 'low' => '#27ae60', 'medium' => '#2980b9', 'high' => '#d4a843', 'urgent' => '#c0392b', default => '#999' };
}

function statusLabel(string $s): string {
    return match($s) { 'pending' => 'معلقة', 'in_progress' => 'جارية', 'done' => 'منجزة', 'cancelled' => 'ملغاة', default => $s };
}

function statusColor(string $s): string {
    return match($s) { 'pending' => '#d4a843', 'in_progress' => '#2980b9', 'done' => '#27ae60', 'cancelled' => '#7f8c8d', default => '#999' };
}
