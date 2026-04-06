<?php
require "../core/auth.php";
require "../config/db.php";

$user   = $_GET['user'] ?? '';
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

if ($user == "") {
    die("username kosong");
}

// ambil profile aktif terakhir user (selain group disabled)
$prevGroup = '';
$stmtPrev = $conn->prepare("
    SELECT groupname
    FROM radusergroup
    WHERE BINARY username=? AND groupname <> 'nonaktif'
    ORDER BY priority ASC
    LIMIT 1
");
$stmtPrev->bind_param("s", $user);
$stmtPrev->execute();
$resPrev = $stmtPrev->get_result();
if ($rowPrev = $resPrev->fetch_assoc()) {
    $prevGroup = $rowPrev['groupname'] ?? '';
}

// simpan metadata previous group ke voucher terbaru (jika user berasal dari voucher)
if (!empty($prevGroup)) {
    $statusMeta = "PREV_GROUP:" . $prevGroup;
    $stmtSaveMeta = $conn->prepare("
        UPDATE voucher
        SET status=?
        WHERE username=?
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmtSaveMeta->bind_param("ss", $statusMeta, $user);
    $stmtSaveMeta->execute();
}

/* hapus group lama */
$stmtDelete = $conn->prepare("
    DELETE FROM radusergroup
    WHERE BINARY username=?
");
$stmtDelete->bind_param("s", $user);
$stmtDelete->execute();

/* masukkan ke group disabled */
$stmtDisable = $conn->prepare("
    INSERT INTO radusergroup (username,groupname,priority)
    VALUES (?, 'nonaktif', 0)
");
$stmtDisable->bind_param("s", $user);
$stmtDisable->execute();

header("Location: /zeronet/users?search=" . urlencode($search) . "&filter=" . urlencode($filter));
exit;
