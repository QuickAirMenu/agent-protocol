<?php
// zoe-dashboard/setup_admin.php
// Usage: php setup_admin.php --username=admin --password=YourPassword --name="اسمك"

require_once __DIR__ . '/includes/db.php';

$opts = getopt('', ['username:', 'password:', 'name:']);

$username = $opts['username'] ?? null;
$password = $opts['password'] ?? null;
$fullName = $opts['name'] ?? 'Admin';

if (!$username || !$password) {
    echo "Usage: php setup_admin.php --username=admin --password=YourPassword --name=\"Your Name\"\n";
    exit(1);
}

if (strlen($password) < 8) {
    echo "Password must be at least 8 characters\n";
    exit(1);
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    echo "User '$username' already exists\n";
    exit(0);
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$pdo->prepare("INSERT INTO users (username, password_hash, full_name, role) VALUES (?, ?, ?, 'admin')")
    ->execute([$username, $hash, $fullName]);

echo "Admin user '$username' created successfully\n";
