<?php
require "../config/db.php";

$user = $_GET['user'];
$search = $_GET['search'] ?? "";
$filter = $_GET['filter'] ?? "";

$conn->query("DELETE FROM radcheck WHERE username='$user'");
$conn->query("DELETE FROM radusergroup WHERE username='$user'");
$conn->query("DELETE FROM radreply WHERE username='$user'");
$conn->query("DELETE FROM radacct WHERE username='$user'");

header("Location: ../users.php?search=$search&filter=$filter");
exit;
