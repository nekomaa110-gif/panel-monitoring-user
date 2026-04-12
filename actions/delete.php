<?php
require "../core/auth.php";
require "../config/db.php";
require "../core/admin_log.php";

$user = trim($_GET['user'] ?? '');
$search = $_GET['search'] ?? "";
$filter = $_GET['filter'] ?? "";

if ($user === '') {
    $_SESSION['msg'] = ["type" => "warning", "text" => "Username tidak valid"];
    header("Location: /zeronet/users?search=" . urlencode($search) . "&filter=" . urlencode($filter));
    exit;
}

try {
    $conn->begin_transaction();

    $tables = [
        "DELETE FROM radcheck WHERE LOWER(username)=LOWER(?)",
        "DELETE FROM radreply WHERE LOWER(username)=LOWER(?)",
        "DELETE FROM radusergroup WHERE LOWER(username)=LOWER(?)",
    ];

    foreach ($tables as $sql) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();
    adminLogFile("DELETE_USER",
    $user . " status = sucess");
    
    $_SESSION['msg'] = [
        "type" => "success",
        "text" => "User berhasil dihapus"
    ];

} catch (Throwable $e) {

    $conn->rollback();

    adminLogFile("DELETE_USER_FAILED",
    $user . " status = failed: " . $e->getMessage());

    $_SESSION['msg'] = [
        "type" => "danger",
        "text" => "Gagal menghapus user: " . $e->getMessage()
    ];
}

header("Location: /zeronet/users?search=" . urlencode($search) . "&filter=" . urlencode($filter));
exit;