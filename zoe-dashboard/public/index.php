<?php

$base = getenv('APP_URL') ?: '';

header('Location: ' . $base . '/dashboard');
exit;