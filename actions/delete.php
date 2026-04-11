<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require "../core/auth.php";
require "../config/db.php";

$user = trim($_GET['user'] ?? '');
$search = $_GET['search'] ?? "";
$filter = $_GET['filter'] ?? "";

if ($user === '') {
    $_SESSION['msg'] = ["type" => "warning", "text" => "Username tidak valid"];
    header("Location: /zeronet/users?search=" . urlencode($search) . "&filter=" . urlencode($filter));
    exit;
}

$tables = [
    'radcheck' => "DELETE FROM radcheck WHERE BINARY username=?",
    'radreply' => "DELETE FROM radreply WHERE BINARY username=?",
    'radusergroup' => "DELETE FROM radusergroup WHERE BINARY username=?",
];

$success_count = 0;
$errors = [];

foreach ($tables as $table => $sql) {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        if ($affected > 0) {
            $success_count++;
        }
        $stmt->close();
    } else {
        // Skip tables that don't exist (no error)
        error_log("Delete user $user: Table $table not found - " . $conn->error);
    }
}

if ($success_count > 0) {
    $_SESSION['msg'] = [
        "type" => "success", 
        "text" => "User '$user' berhasil dihapus dari $success_count tabel utama"
    ];
} else {
    $_SESSION['msg'] = [
        "type" => "danger", 
        "text" => "Gagal hapus user. Error: " . implode('; ', $errors)
    ];
}

header("Location: /zeronet/users?search=" . urlencode($search) . "&filter=" . urlencode($filter));
exit;