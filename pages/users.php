<?php
require __DIR__ . "/../core/auth.php";
require __DIR__ . "/../config/db.php";

$search = $_GET['search'] ?? "";
$filter = $_GET['filter'] ?? "";

/* USER ONLINE */

$onlineUsers = [];

$online_q = $conn->query("
SELECT DISTINCT username
FROM radacct
WHERE acctstoptime IS NULL
");

while ($o = $online_q->fetch_assoc()) {
    $onlineUsers[$o['username']] = true;
}

/* AMBIL DATA USER */

$stmtUsers = $conn->prepare("
SELECT
u.username,
MAX(CASE WHEN rc.attribute='Cleartext-Password' THEN rc.value END) AS password,
MAX(CASE WHEN rc.attribute='Expiration' THEN rc.value END) AS expiration,
MAX(rug.groupname) AS profile

FROM
(
SELECT DISTINCT username FROM radcheck
UNION
SELECT DISTINCT username FROM radusergroup
) u

LEFT JOIN radcheck rc ON u.username = rc.username
LEFT JOIN radusergroup rug ON u.username = rug.username

WHERE LOWER(u.username) LIKE LOWER(?)
  AND u.username NOT LIKE '5K%'

GROUP BY u.username
ORDER BY u.username
");
$searchLike = '%' . $search . '%';
$stmtUsers->bind_param("s", $searchLike);
$stmtUsers->execute();
$q = $stmtUsers->get_result();

$pageTitle = 'Pelanggan';
$navTitle  = 'Pelanggan';

include __DIR__ . "/../views/users.view.php";
