<?php
session_start();

// 🔐 cegah session fixation (kalau belum pernah regenerate)
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// 🔐 cek login
if (!isset($_SESSION['login'])) {
    header("Location: login.php");
    exit;
}
?>
