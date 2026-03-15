<?php
require "db.php";

$user = $_GET['user'] ?? '';

if($user==""){
die("username kosong");
}

/* hapus group lama */
$conn->query("
DELETE FROM radusergroup
WHERE username='$user'
");

/* masukkan ke group disable */
$conn->query("
INSERT INTO radusergroup (username,groupname,priority)
VALUES ('$user','daloRADIUS-Disabled-Users',0)
");

/* kembali ke halaman user */
header("Location: users.php");
exit;

?>
