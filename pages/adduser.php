<?php
require __DIR__ . "/../core/auth.php";
require __DIR__ . "/../config/db.php";

$msg = "";

/* AMBIL SEMUA PROFIL (CHECK + REPLY) */
$profiles = $conn->query("
SELECT DISTINCT groupname FROM (
    SELECT groupname FROM radgroupcheck
    UNION
    SELECT groupname FROM radgroupreply
) AS allgroups
ORDER BY groupname
");

if (isset($_POST['save'])) {

    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $hari     = $_POST['hari'];
    $profile  = $_POST['profile'];

    if ($username && $password && $profile) {

        // hitung expiration
        $expiration = date("d M Y 23:59", strtotime("+$hari days"));

        // CEK USER SUDAH ADA
        $cek = $conn->query("
        SELECT * FROM radcheck WHERE username='$username'
        ");

        if ($cek->num_rows > 0) {
            $msg = "User sudah ada!";
        } else {

            // PASSWORD
            $conn->query("
            INSERT INTO radcheck (username,attribute,op,value)
            VALUES ('$username','Cleartext-Password',':=','$password')
            ");

            // EXPIRATION
            $conn->query("
            INSERT INTO radcheck (username,attribute,op,value)
            VALUES ('$username','Expiration',':=','$expiration')
            ");

            // MASUKKAN KE PROFILE (GROUP)
            $conn->query("
            INSERT INTO radusergroup (username,groupname,priority)
            VALUES ('$username','$profile',0)
            ");

            $msg = "User berhasil dibuat";
        }
    } else {
        $msg = "Isi semua field!";
    }
}

$pageTitle = 'Tambah User';
$navTitle  = 'Tambah User';

include __DIR__ . "/../views/adduser.view.php";
