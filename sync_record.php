<?php
require __DIR__ . "/config/db.php";
require __DIR__ . "/config/db.php";

/*
========================================
 SYNC FIRST LOGIN USER
========================================*/


try {

    // Pastikan timezone konsisten
    date_default_timezone_set('Asia/Jakarta');

    // QUERY UTAMA
    $sql = "
    INSERT INTO user_record (username, first_login, ip_address)
    SELECT 
        ra.username,
        MIN(ra.acctstarttime) AS first_login,

        -- ambil IP pertama berdasarkan login paling awal
        SUBSTRING_INDEX(
            GROUP_CONCAT(ra.framedipaddress ORDER BY ra.acctstarttime ASC),
            ',', 
            1
        ) AS ip_address

    FROM radacct ra

    WHERE ra.username IS NOT NULL
      AND ra.username <> ''

    GROUP BY ra.username

    ON DUPLICATE KEY UPDATE 
        first_login = LEAST(user_record.first_login, VALUES(first_login))
    ";

    if (!$conn->query($sql)) {
        throw new Exception("Query gagal: " . $conn->error);
    }

    echo "[" . date('Y-m-d H:i:s') . "] Sync record selesai\n";

} catch (Throwable $e) {

    error_log("SYNC RECORD ERROR: " . $e->getMessage());

    echo "[" . date('Y-m-d H:i:s') . "] ERROR\n";
}