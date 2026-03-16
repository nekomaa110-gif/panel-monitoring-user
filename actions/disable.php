<?php
require "../config/db.php";
$user   = $_GET['user'] ?? '';
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

if ($user == "") {
    die("username kosong");
}

/* hapus group lama */
$conn->query("
        DELETE FROM radusergroup
        WHERE username='$user'
    ");

/* masukkan ke group disabled */
$conn->query("
        INSERT INTO radusergroup (username,groupname,priority)
        VALUES ('$user','daloRADIUS-Disabled-Users',0)
    ");

header("Location: ../users.php?search=$search&filter=$filter");
exit;
