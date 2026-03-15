<?php
require "../config/db.php";

$user = $_GET['user'] ?? '';

if($user==""){
die("User tidak ditemukan");
}

/* hapus group lama */
$conn->query("DELETE FROM radusergroup WHERE username='$user'");

/* masukkan ke group disable */
$conn->query("INSERT INTO radusergroup (username,groupname,priority)
VALUES ('$user','daloRADIUS-Disabled-Users',0)");

header("Location: ../users.php");
exit;
?>
