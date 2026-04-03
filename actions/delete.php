<?php
require "../auth.php";
require "../config/db.php";

$user = trim($_GET['user'] ?? '');
$search = $_GET['search'] ?? "";
$filter = $_GET['filter'] ?? "";

if ($user === '') {
    header("Location: ../users.php?search=" . urlencode($search) . "&filter=" . urlencode($filter));
    exit;
}

$queries = [
    "DELETE FROM radacct WHERE BINARY username=?",
    "DELETE FROM radpostauth WHERE BINARY username=?",
    "DELETE FROM radcheck WHERE BINARY username=?",
    "DELETE FROM radreply WHERE BINARY username=?",
    "DELETE FROM radusergroup WHERE BINARY username=?",
    "DELETE FROM userbillinfo WHERE BINARY username=?",
    "DELETE FROM userinfo WHERE BINARY username=?"
    // Optional logging placeholder: tambahkan audit delete user di sini jika dibutuhkan
];

foreach ($queries as $sql) {
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed (delete user): " . $conn->error);
    }

    $stmt->bind_param("s", $user);
    $stmt->execute();
    $stmt->close();
}

header("Location: ../users.php?search=" . urlencode($search) . "&filter=" . urlencode($filter));
exit;
