<?php
require __DIR__ . "/../core/auth.php";
require __DIR__ . "/../config/db.php";

$msg = "";

/* NOTIF UPDATE */
if (isset($_GET['updated']) && $_GET['updated'] == '1') {
    $msg = "Profile berhasil diupdate";
}

/* TAMBAH PROFIL + ATRIBUT */
if (isset($_POST['add'])) {

    $profile = isset($_POST['profile']) ? mysqli_real_escape_string($conn, trim($_POST['profile'])) : "";
    $simu    = isset($_POST['simu']) ? mysqli_real_escape_string($conn, $_POST['simu']) : "";
    $mtk     = isset($_POST['mtk']) ? mysqli_real_escape_string($conn, $_POST['mtk']) : "";

    if ($profile != "") {

        // validasi angka
        if ($simu != "" && !is_numeric($simu)) {
            $msg = "Simultaneous harus angka";
        } else {

            // cek profil sudah ada (gabung 2 tabel)
            $cek = $conn->query("SELECT groupname FROM (
                SELECT groupname FROM radgroupcheck
                UNION
                SELECT groupname FROM radgroupreply
            ) AS allgroups WHERE groupname='$profile'");

            if ($cek->num_rows == 0) {

                // wajib
                $conn->query("
                INSERT INTO radgroupcheck (groupname,attribute,op,value)
                VALUES ('$profile','Auth-Type',':=','Accept')
                ");

                // simultaneous
                if ($simu != "") {
                    $conn->query("
                    INSERT INTO radgroupcheck (groupname,attribute,op,value)
                    VALUES ('$profile','Simultaneous-Use',':=','$simu')
                    ");
                }

                // mikrotik group
                if ($mtk != "") {
                    $conn->query("
                    INSERT INTO radgroupreply (groupname,attribute,op,value)
                    VALUES ('$profile','Mikrotik-Group',':=','$mtk')
                    ");
                }

                $msg = "Profil berhasil dibuat";

            } else {
                $msg = "Profil sudah ada";
            }
        }

    } else {
        $msg = "Nama profil kosong";
    }
}

/* HAPUS PROFIL */
if (isset($_GET['hapus']) && $_GET['hapus'] != "") {

    $p = mysqli_real_escape_string($conn, $_GET['hapus']);

    $conn->query("DELETE FROM radgroupcheck WHERE groupname='$p'");
    $conn->query("DELETE FROM radgroupreply WHERE groupname='$p'");
    $conn->query("DELETE FROM radusergroup WHERE groupname='$p'");

    echo "<script>alert('Profil dihapus'); location.href='profile.php';</script>";
}

/* AMBIL SEMUA PROFIL */
$q = $conn->query("
SELECT DISTINCT groupname FROM (
    SELECT groupname FROM radgroupcheck
    UNION
    SELECT groupname FROM radgroupreply
) AS allgroups
ORDER BY groupname
");

$pageTitle = 'Manajemen Profil';
$navTitle  = 'Manajemen Profil';

include __DIR__ . "/../views/profile.view.php";
