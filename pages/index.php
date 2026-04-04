<?php
require __DIR__ . "/../core/auth.php";
require __DIR__ . "/../config/db.php";

/* TOTAL USER */
$total = $conn->query("
SELECT COUNT(DISTINCT username) as t
FROM radcheck
")->fetch_assoc()['t'];

/* USER EXPIRED */
$expired = $conn->query("
SELECT COUNT(*) as t FROM radcheck
WHERE attribute='Expiration'
AND STR_TO_DATE(value,'%d %b %Y %H:%i') < NOW()
")->fetch_assoc()['t'];

/* USER DISABLED */
$disabled = $conn->query("
SELECT COUNT(*) as t FROM radusergroup
WHERE groupname='daloRADIUS-Disabled-Users'
")->fetch_assoc()['t'];

/* USER ONLINE */
$online = $conn->query("
SELECT COUNT(*) as t
FROM radacct
WHERE acctstoptime IS NULL
")->fetch_assoc()['t'];

/* USER ONLINE LIST */
$online_list = $conn->query("
SELECT username,framedipaddress,acctstarttime
FROM radacct
WHERE acctstoptime IS NULL
ORDER BY acctstarttime DESC
");

/* LOGIN TERAKHIR */
$log = $conn->query("
SELECT username,nasipaddress,acctstarttime
FROM radacct
ORDER BY acctstarttime DESC
LIMIT 20
");

$pageTitle = 'Dashboard';
$navTitle  = 'Dashboard';

include __DIR__ . "/../views/index.view.php";
