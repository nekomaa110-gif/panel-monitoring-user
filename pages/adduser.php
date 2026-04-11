<?php
require __DIR__ . "/../core/auth.php";
require __DIR__ . "/../config/db.php";

$msg = "";

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

    if ($username === '' || $password === '' || $profile === '' || $hari <= 0) {
        $msg = "Isi semua field dengan benar!";
    } else {

        try {
            $conn->begin_transaction();

            /* ===== CEK DUPLIKAT ===== */
            $stmt = $conn->prepare("
                SELECT 1 FROM radcheck WHERE username=? LIMIT 1
            ");
            $stmt->bind_param("s", $username);
            $stmt->execute();

            if ($stmt->get_result()->fetch_assoc()) {
                throw new Exception("User sudah ada!");
            }
            $stmt->close();

            /* ===== HITUNG EXPIRATION (PASTI 23:59:59) ===== */
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

            /* ===== ASSIGN PROFILE ===== */
            $stmt = $conn->prepare("
                INSERT INTO radusergroup (username,groupname,priority)
                VALUES (?, ?, 0)
            ");
            $stmt->bind_param("ss", $username, $profile);
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            $msg = "User berhasil dibuat";

        } catch (Throwable $e) {

            $conn->rollback();
            $msg = "Error: " . $e->getMessage();
        }
    }
}

/* =======================
   VIEW
======================= */
$pageTitle = 'Tambah User';
$navTitle  = 'Tambah User';

include __DIR__ . "/../views/adduser.view.php";