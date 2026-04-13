<?php
require __DIR__ . "/../core/auth.php";

$logFile = __DIR__ . "/../core/admin.log";
$logs = [];

if (file_exists($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $logs = array_reverse($lines);
}

$pageTitle = 'Admin Log';
$navTitle  = 'Admin Log';

include __DIR__ . "/../views/admin_log.view.php";