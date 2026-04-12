<?php
require __DIR__ . "/../core/auth.php";
require __DIR__ . "/../config/db.php";
require __DIR__ . "/../core/admin_log.php";

date_default_timezone_set('Asia/Jakarta');

$msg = [];

/* =======================
   AMBIL SEMUA PROFIL
======================= */
$profiles = $conn->query("
SELECT DISTINCT groupname FROM (
    SELECT groupname FROM radgroupcheck
    UNION
    SELECT groupname FROM radgroupreply
) AS allgroups
ORDER BY groupname
");

/* =======================
   SAVE USER
======================= */
if (isset($_POST['save'])) {

    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $hari     = (int)($_POST['hari'] ?? 0);
    $profile  = trim($_POST['profile'] ?? '');

    /* ===== VALIDASI ===== */
    if ($username === '' || $password === '' || $profile === '' || $hari <= 0) {
        $msg = [
            "type" => "danger",
            "text" => "Isi semua field dengan benar!"
        ];
    }

    elseif (!preg_match('/^[a-zA-Z0-9._-]{3,32}$/', $username)) {
        $msg = [
            "type" => "danger",
            "text" => "Username tidak valid!"
        ];
    }

    elseif ($hari > 3650) {
        $msg = [
            "type" => "warning",
            "text" => "Durasi terlalu panjang!"
        ];
    }

    else {

        try {
            $conn->begin_transaction();

            /* ===== CEK USER ===== */
            $stmt = $conn->prepare("
                SELECT username FROM radcheck WHERE username=?
                UNION
                SELECT username FROM radusergroup WHERE username=?
                LIMIT 1
            ");
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();

            if ($stmt->get_result()->fetch_assoc()) {
                throw new Exception("User sudah ada!");
            }
            $stmt->close();

            /* ===== CEK PROFILE ===== */
            $stmt = $conn->prepare("
                SELECT 1 FROM (
                    SELECT groupname FROM radgroupcheck
                    UNION
                    SELECT groupname FROM radgroupreply
                ) g WHERE groupname=? LIMIT 1
            ");
            $stmt->bind_param("s", $profile);
            $stmt->execute();

            if (!$stmt->get_result()->fetch_assoc()) {
                throw new Exception("Profile tidak valid!");
            }
            $stmt->close();

            /* ===== EXPIRATION ===== */
            $dt = new DateTime();
            $dt->modify("+$hari days");
            $dt->setTime(23, 59, 59);
            $expiration = $dt->format('d M Y H:i:s');

            /* ===== INSERT PASSWORD ===== */
            $stmt = $conn->prepare("
                INSERT INTO radcheck (username,attribute,op,value)
                VALUES (?, 'Cleartext-Password', ':=', ?)
            ");
            $stmt->bind_param("ss", $username, $password);
            $stmt->execute();
            $stmt->close();

            /* ===== INSERT EXPIRATION ===== */
            $stmt = $conn->prepare("
                INSERT INTO radcheck (username,attribute,op,value)
                VALUES (?, 'Expiration', ':=', ?)
            ");
            $stmt->bind_param("ss", $username, $expiration);
            $stmt->execute();
            $stmt->close();

            /* ===== INSERT PROFILE ===== */
            $stmt = $conn->prepare("
                INSERT INTO radusergroup (username,groupname,priority)
                VALUES (?, ?, 0)
            ");
            $stmt->bind_param("ss", $username, $profile);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            /* ===== LOG SUCCESS ===== */
            adminLogFile(
                "ADD_USER_SUCCESS",
                $username . " | profile=" . $profile . " | days=" . $hari
            );

            $msg = [
                "type" => "success",
                "text" => "User berhasil dibuat"
            ];

        } catch (Throwable $e) {

            $conn->rollback();

            /* ===== LOG FAILED ===== */
            adminLogFile(
                "ADD_USER_FAILED",
                ($username ?: 'unknown') . " | error=" . $e->getMessage()
            );

            $msg = [
                "type" => "danger",
                "text" => $e->getMessage() === "User sudah ada!"
                    ? "User sudah ada!"
                    : "Gagal membuat user!"
            ];
        }
    }
}

/* =======================
   VIEW
======================= */
$pageTitle = 'Tambah User';
$navTitle  = 'Tambah User';

include __DIR__ . "/../views/adduser.view.php";