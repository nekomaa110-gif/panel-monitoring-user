<?php
require "../core/auth.php";
require "../config/db.php";

$user   = trim($_GET['user'] ?? '');
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

if ($user === '') {
    die("username kosong");
}

try {
    $conn->begin_transaction();

    /* ambil group lama */
    $prevGroup = '';
    $stmt = $conn->prepare("
        SELECT groupname
        FROM radusergroup
        WHERE LOWER(username)=LOWER(?) 
          AND groupname <> 'nonaktif'
        ORDER BY priority ASC
        LIMIT 1
    ");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        $prevGroup = $row['groupname'];
    }
    $stmt->close();

    /* simpan metadata */
    if ($prevGroup) {
        $meta = "PREV_GROUP:" . $prevGroup;
        $stmt = $conn->prepare("
            UPDATE voucher
            SET status=?
            WHERE username=?
            AND (status IS NULL OR status='')
        ");
        $stmt->bind_param("ss", $meta, $user);
        $stmt->execute();
        $stmt->close();
    }

    /* hapus semua group */
    $stmt = $conn->prepare("
        DELETE FROM radusergroup
        WHERE LOWER(username)=LOWER(?)
    ");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $stmt->close();

    /* insert nonaktif */
    $stmt = $conn->prepare("
        INSERT INTO radusergroup (username,groupname,priority)
        VALUES (?, 'nonaktif', 0)
    ");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

} catch (Throwable $e) {
    $conn->rollback();
}

header("Location: /zeronet/users?search=" . urlencode($search) . "&filter=" . urlencode($filter));
exit;