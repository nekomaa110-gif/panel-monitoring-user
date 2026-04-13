<?php

function adminLogFile($action, $user = '-', $status = '-') {

    // pastikan session jalan
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // ambil data
    $admin = strtoupper($_SESSION['username'] ?? 'UNKNOWN');
    $ip    = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
    $time  = date('Y-m-d H:i:s');

    // rapihin input
    $action = strtoupper(trim(str_replace('_', ' ', $action)));
    $user   = trim($user);
    $status = strtoupper(trim($status));

    // validasi biar ga sampah
    if ($action === '') $action = '-';
    if ($user === '')   $user   = '-';
    if ($status === '') $status = '-';

    // format log (rapi & konsisten)
    $log = "$time | $admin | $action | $user | $status | $ip\n";

    // path file
    $file = __DIR__ . '/admin.log';

    // tulis log (aman)
    file_put_contents($file, $log, FILE_APPEND | LOCK_EX);
}