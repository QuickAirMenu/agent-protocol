<?php
// shared/includes/logger.php

function zoeLog(string $level, string $message, array $context = []): void {
    $logDir = getenv('LOG_PATH') ?: dirname(__DIR__, 2) . '/logs';
    if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
    
    $log = sprintf(
        "[%s] [%s] %s %s\n",
        date('Y-m-d H:i:s'),
        strtoupper($level),
        $message,
        $context ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
    );
    
    @error_log($log, 3, $logDir . '/zoe.log');
}
