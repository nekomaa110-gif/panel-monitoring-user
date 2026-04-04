<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// cegah cache halaman terproteksi agar tidak bisa dibuka via tombol back setelah logout
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 01 Jan 2000 00:00:00 GMT");

// cegah session fixation
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// validasi login + username
if (empty($_SESSION['login']) || empty($_SESSION['username'])) {
    session_unset();
    session_destroy();
    header("Location: /zeronet/login");
    exit;
}
?>
