<?php
require __DIR__ . "/../core/auth.php";
require __DIR__ . "/../config/db.php";

$search = trim($_GET['search'] ?? "");
$filter = $_GET['filter'] ?? "";

/* =========================
   USER ONLINE (SOURCE OF TRUTH)
========================= */
$onlineUsers = [];

$online_q = $conn->query("
SELECT DISTINCT ra.username
FROM radacct ra
WHERE ra.acctstoptime IS NULL

AND NOT EXISTS (
    SELECT 1 FROM radusergroup rg
    WHERE rg.username = ra.username
    AND rg.groupname = 'nonaktif'
)

AND NOT EXISTS (
    SELECT 1 FROM radcheck rc
    WHERE rc.username = ra.username
    AND rc.attribute = 'Expiration'
    AND STR_TO_DATE(rc.value,'%d %b %Y %H:%i:%s') <= NOW()
)
");

if ($online_q) {
    while ($o = $online_q->fetch_assoc()) {
        $onlineUsers[$o['username']] = true;
    }
}

/* =========================
   COUNT ONLINE (SYNC DASHBOARD)
========================= */
$onlineCount = 0;

$count_q = $conn->query("
SELECT COUNT(DISTINCT ra.username) as total
FROM radacct ra
WHERE ra.acctstoptime IS NULL

AND NOT EXISTS (
    SELECT 1 FROM radusergroup rg
    WHERE rg.username = ra.username
    AND rg.groupname = 'nonaktif'
)

AND NOT EXISTS (
    SELECT 1 FROM radcheck rc
    WHERE rc.username = ra.username
    AND rc.attribute = 'Expiration'
    AND STR_TO_DATE(rc.value,'%d %b %Y %H:%i:%s') <= NOW()
)
");

if ($count_q) {
    $row = $count_q->fetch_assoc();
    $onlineCount = $row['total'] ?? 0;
}

/* =========================
   AMBIL DATA USER
========================= */
$stmtUsers = $conn->prepare("
SELECT
    u.username,

    MAX(CASE 
        WHEN rc.attribute='Cleartext-Password' 
        THEN rc.value 
    END) AS password,

    MAX(CASE 
        WHEN rc.attribute='Expiration' 
        THEN rc.value 
    END) AS expiration,

    rg.groupname AS profile

FROM
(
    SELECT username FROM radcheck
    UNION
    SELECT username FROM radusergroup
) u

LEFT JOIN radcheck rc 
    ON rc.username = u.username

LEFT JOIN (
    SELECT r1.username, r1.groupname
    FROM radusergroup r1
    INNER JOIN (
        SELECT username, MIN(priority) AS min_priority
        FROM radusergroup
        GROUP BY username
    ) r2 
    ON r1.username = r2.username
    AND r1.priority = r2.min_priority
) rg 
    ON rg.username = u.username

WHERE u.username LIKE ?
  AND u.username NOT LIKE '5K%'

GROUP BY u.username
ORDER BY u.username
");

$searchLike = '%' . $search . '%';
$stmtUsers->bind_param("s", $searchLike);
$stmtUsers->execute();
$q = $stmtUsers->get_result();

/* =========================
   VIEW
========================= */
$pageTitle = 'Pelanggan';
$navTitle  = 'Pelanggan';

include __DIR__ . "/../views/users.view.php";