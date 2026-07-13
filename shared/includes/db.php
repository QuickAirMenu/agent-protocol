<?php
// shared/includes/db.php

require_once __DIR__ . '/logger.php';

function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (!str_contains($line, '=')) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

$envCandidates = [
    dirname(__DIR__, 2) . '/.env',
    dirname(__DIR__, 3) . '/.env',
];
foreach ($envCandidates as $envPath) {
    if (file_exists($envPath)) {
        loadEnv($envPath);
        break;
    }
}

$required = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'];
foreach ($required as $key) {
    if (getenv($key) === false || getenv($key) === '') {
        http_response_code(500);
        zoeLog('error', "Missing env: $key");
        die(json_encode(['error' => 'Server configuration error']));
    }
}

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', getenv('DB_HOST'), getenv('DB_NAME')),
        getenv('DB_USER'),
        getenv('DB_PASS'),
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    zoeLog('error', 'DB connection failed: ' . $e->getMessage());
    die(json_encode(['error' => 'Database unavailable']));
}
