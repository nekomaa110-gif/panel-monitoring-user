<?php
require __DIR__ . "/../core/auth.php";
require __DIR__ . "/../config/db.php";

/* =========================
   TOTAL USER (UNION BIAR VALID)
========================= */
$total = $conn->query("
SELECT COUNT(DISTINCT username) as t FROM (
    SELECT username FROM radcheck
    UNION
    SELECT username FROM radusergroup
) u
")->fetch_assoc()['t'];

/* =========================
   USER EXPIRED
========================= */
$expired = $conn->query("
SELECT COUNT(DISTINCT username) as t
FROM radcheck
WHERE attribute='Expiration'
AND STR_TO_DATE(value,'%d %b %Y %H:%i:%s') <= NOW()
")->fetch_assoc()['t'];

/* =========================
   USER DISABLED
========================= */
$disabled = $conn->query("
SELECT COUNT(DISTINCT username) as t
FROM radusergroup
WHERE groupname='nonaktif'
")->fetch_assoc()['t'];

/* =========================
   USER ONLINE (SAMA PERSIS DENGAN users.php)
========================= */
$online = $conn->query("
SELECT COUNT(DISTINCT ra.username) as t
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
")->fetch_assoc()['t'];

/* =========================
   USER ONLINE LIST (VALID)
========================= */
$online_list = $conn->query("
SELECT ra.username, ra.framedipaddress, ra.acctstarttime
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

ORDER BY ra.acctstarttime DESC
");

/* =========================
   LOGIN TERAKHIR
========================= */
$log = $conn->query("
SELECT username,nasipaddress,acctstarttime
FROM radacct
ORDER BY acctstarttime DESC
LIMIT 20
");

$pageTitle = 'Dashboard';
$navTitle  = 'Dashboard';

include __DIR__ . "/../views/index.view.php";