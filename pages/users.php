<?php
require __DIR__ . "/../core/auth.php";
require __DIR__ . "/../config/db.php";

$search = $_GET['search'] ?? "";
$filter = $_GET['filter'] ?? "";

/* =========================
   USER ONLINE
========================= */
$onlineUsers = [];

$online_q = $conn->query("
SELECT DISTINCT username
FROM radacct
WHERE acctstoptime IS NULL
");

while ($o = $online_q->fetch_assoc()) {
    $onlineUsers[$o['username']] = true;
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

WHERE LOWER(u.username) LIKE LOWER(?)
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