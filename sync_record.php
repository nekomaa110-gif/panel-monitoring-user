<?php
require __DIR__ . "/config/db.php";
require __DIR__ . "/core/auth.php";

$stmt = $conn->prepare("
INSERT IGNORE INTO user_record (username, first_login, ip_address)
VALUES (?, ?, ?)
");

$q = $conn->query("
SELECT username, acctstarttime, framedipaddress
FROM radacct
ORDER BY acctstarttime ASC
");

while ($r = $q->fetch_assoc()) {

    $username = $r['username'];
    $time     = $r['acctstarttime'];
    $ip       = $r['framedipaddress'];

    if (!$username || !$time) continue;

    $stmt->bind_param("sss", $username, $time, $ip);
    $stmt->execute();
}

echo "Sync selesai";