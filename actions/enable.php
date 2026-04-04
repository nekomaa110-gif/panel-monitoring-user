<?php
require "../core/auth.php";
require "../config/db.php";

$user   = $_GET['user'] ?? '';
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

if ($user == "") {
    die("username kosong");
}

// cari profile lama dari metadata voucher.status
$restoreGroup = '';
$stmtMeta = $conn->prepare("
    SELECT status
    FROM voucher
    WHERE username=?
      AND status LIKE 'PREV_GROUP:%'
    ORDER BY id DESC
    LIMIT 1
");
$stmtMeta->bind_param("s", $user);
$stmtMeta->execute();
$resMeta = $stmtMeta->get_result();
if ($rowMeta = $resMeta->fetch_assoc()) {
    $meta = $rowMeta['status'] ?? '';
    if (strpos($meta, 'PREV_GROUP:') === 0) {
        $restoreGroup = substr($meta, strlen('PREV_GROUP:'));
    }
}

// fallback: pilih group aktif default jika metadata tidak ada
if (empty($restoreGroup)) {
    $stmtFallback = $conn->prepare("
        SELECT groupname
        FROM (
            SELECT DISTINCT groupname FROM radgroupcheck
            UNION
            SELECT DISTINCT groupname FROM radgroupreply
        ) g
        WHERE groupname <> 'daloRADIUS-Disabled-Users'
        ORDER BY groupname
        LIMIT 1
    ");
    $stmtFallback->execute();
    $resFallback = $stmtFallback->get_result();
    if ($rowFallback = $resFallback->fetch_assoc()) {
        $restoreGroup = $rowFallback['groupname'] ?? '';
    }
}

// fallback terakhir
if (empty($restoreGroup)) {
    $restoreGroup = 'Radius-Member';
}

/* hapus group lama */
$stmtDelete = $conn->prepare("
    DELETE FROM radusergroup
    WHERE BINARY username=?
");
$stmtDelete->bind_param("s", $user);
$stmtDelete->execute();

/* masukkan kembali ke profile aktif */
$stmtEnable = $conn->prepare("
    INSERT INTO radusergroup (username,groupname,priority)
    VALUES (?, ?, 0)
");
$stmtEnable->bind_param("ss", $user, $restoreGroup);
$stmtEnable->execute();

/* bersihkan metadata agar tidak stale */
$stmtClearMeta = $conn->prepare("
    UPDATE voucher
    SET status=NULL
    WHERE username=?
      AND status LIKE 'PREV_GROUP:%'
");
$stmtClearMeta->bind_param("s", $user);
$stmtClearMeta->execute();

/* kembali ke halaman sebelumnya */
header("Location: ../users.php?search=" . urlencode($search) . "&filter=" . urlencode($filter));
exit;
