<?php

function adminLogFile($action, $target = null) {

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $admin  = $_SESSION['username'] ?? 'unknown';
    $ip     = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    $time   = date('Y-m-d H:i:s');
    $target = $target ?? '-';

    $log = "[$time] [$admin] $action => $target | IP:$ip\n";

    file_put_contents(__DIR__ . '/admin.log', $log, FILE_APPEND);
}