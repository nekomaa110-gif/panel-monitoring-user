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

/* masukkan kembali ke profile aktif */
$conn->query("
INSERT INTO radusergroup (username,groupname,priority)
VALUES ('$user','Radius-Member',0)
");

/* kembali ke halaman sebelumnya */
header("Location: ../users.php?search=$search&filter=$filter");
exit;
