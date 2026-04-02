<?php
require "../auth.php";
require "../config/db.php";

$user = $_GET['user'];
$search = $_GET['search'] ?? "";
$filter = $_GET['filter'] ?? "";

$conn->query("DELETE FROM radcheck WHERE BINARY username='$user'");
$conn->query("DELETE FROM radusergroup WHERE BINARY username='$user'");
$conn->query("DELETE FROM radreply WHERE BINARY username='$user'");
$conn->query("DELETE FROM radacct WHERE BINARY username='$user'");

header("Location: ../users.php?search=$search&filter=$filter");
exit;
