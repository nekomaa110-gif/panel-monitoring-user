<?php
require "../config/db.php";
$user = $_GET['user'] ?? '';

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

header("Location: users.php");
exit;