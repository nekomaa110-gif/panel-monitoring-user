<?php
require __DIR__ . "/../core/auth.php";
require __DIR__ . "/../config/db.php";
require __DIR__ . "/../core/admin_log.php";

$user   = trim($_GET['user'] ?? '');
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

if ($user === '') {
    die("username kosong");
}

try {
    $conn->begin_transaction();

    $restoreGroup = '';

    /* ambil metadata */
    $stmt = $conn->prepare("
        SELECT status FROM voucher
        WHERE username=?
        AND status LIKE 'PREV_GROUP:%'
        ORDER BY id DESC LIMIT 1
    ");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        $restoreGroup = str_replace('PREV_GROUP:', '', $row['status']);
    }
    $stmt->close();

    /* fallback */
    if (!$restoreGroup) {
        $restoreGroup = 'Radius-Member';
    }

    /* bersihin */
    $stmt = $conn->prepare("
        DELETE FROM radusergroup
        WHERE LOWER(username)=LOWER(?)
    ");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $stmt->close();

    /* insert */
    $stmt = $conn->prepare("
        INSERT INTO radusergroup (username, groupname, priority)
        VALUES (?, ?, 0)
    ");
    $stmt->bind_param("ss", $user, $restoreGroup);
    $stmt->execute();
    $stmt->close();

    /* clear metadata */
    $stmt = $conn->prepare("
        UPDATE voucher
        SET status=NULL
        WHERE username=?
        AND status LIKE 'PREV_GROUP:%'
    ");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    adminLogFile("ENABLE_USER", $user . " status = sucess");

} catch (Throwable $e) {
    $conn->rollback();
    adminLogFile("ENABLE_USER_FAILED", $user . " status = failed: " . $e->getMessage());
}

header("Location: /zeronet/users?search=" . urlencode($search) . "&filter=" . urlencode($filter));
exit;