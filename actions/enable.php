<?php
require __DIR__ . "/../core/auth.php";
require __DIR__ . "/../config/db.php";

$user   = $_GET['user'] ?? '';
$search = $_GET['search'] ?? '';
$filter = $_GET['filter'] ?? '';

if ($user == "") {
    die("username kosong");
}

/* =========================
   1. AMBIL PROFILE LAMA (METADATA)
========================= */
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
$stmtMeta->close();

/* =========================
   2. JIKA TIDAK ADA, AMBIL DARI RADUSERGROUP
========================= */
if (empty($restoreGroup)) {

    $stmtExisting = $conn->prepare("
        SELECT groupname
        FROM radusergroup
        WHERE BINARY username=?
          AND groupname <> 'nonaktif'
        ORDER BY priority ASC
        LIMIT 1
    ");
    $stmtExisting->bind_param("s", $user);
    $stmtExisting->execute();
    $resExisting = $stmtExisting->get_result();

    if ($rowExisting = $resExisting->fetch_assoc()) {
        $restoreGroup = $rowExisting['groupname'];
    }

    $stmtExisting->close();
}

/* =========================
   3. FALLBACK TERAKHIR (AMAN)
========================= */
if (empty($restoreGroup)) {
    $restoreGroup = 'Radius-Member';
}

/* =========================
   4. BERSIHKAN SEMUA GROUP USER (ANTI DUPLIKAT)
========================= */
$stmtDeleteAll = $conn->prepare("
    DELETE FROM radusergroup
    WHERE BINARY username=?
");
$stmtDeleteAll->bind_param("s", $user);
$stmtDeleteAll->execute();
$stmtDeleteAll->close();

/* =========================
   5. INSERT PROFILE YANG BENAR
========================= */
$stmtInsert = $conn->prepare("
    INSERT INTO radusergroup (username, groupname, priority)
    VALUES (?, ?, 0)
");
$stmtInsert->bind_param("ss", $user, $restoreGroup);
$stmtInsert->execute();
$stmtInsert->close();

/* =========================
   6. BERSIHKAN METADATA
========================= */
$stmtClearMeta = $conn->prepare("
    UPDATE voucher
    SET status=NULL
    WHERE username=?
      AND status LIKE 'PREV_GROUP:%'
");
$stmtClearMeta->bind_param("s", $user);
$stmtClearMeta->execute();
$stmtClearMeta->close();

/* =========================
   7. REDIRECT
========================= */
header("Location: /zeronet/users?search=" . urlencode($search) . "&filter=" . urlencode($filter));
exit;