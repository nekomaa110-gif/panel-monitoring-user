<?php
require __DIR__ . "/config/db.php";

$q = $conn->query("
SELECT username, value 
FROM radcheck
WHERE attribute='Expiration'
");

while ($r = $q->fetch_assoc()) {

    $exp = strtotime($r['value']);

    if ($exp < time()) {

        $user = $r['username'];

        // cek apakah sudah disabled
        $cek = $conn->query("
        SELECT * FROM radusergroup 
        WHERE BINARY username='$user' 
        AND groupname='nonaktif'
        ");

        if ($cek->num_rows == 0) {

            // update ke disabled
            $conn->query("
            UPDATE radusergroup
            SET groupname='nonaktif'
            WHERE BINARY username='$user'
            ");
        }
    }
}
