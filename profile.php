<?php
require "auth.php";
require "config/db.php";

$msg = "";

/* TAMBAH PROFIL + ATRIBUT */
if (isset($_POST['add'])) {

    $profile = trim($_POST['profile']);
    $simu    = $_POST['simu'];
    $mtk     = trim($_POST['mtk']);

    if ($profile != "") {

        $cek = $conn->query("SELECT * FROM radgroupcheck WHERE groupname='$profile'");

        if ($cek->num_rows == 0) {

            // WAJIB (IDENTITAS)
            $conn->query("
            INSERT INTO radgroupcheck (groupname,attribute,op,value)
            VALUES ('$profile','Auth-Type',':=','Accept')
            ");

            // SIMULTANEOUS
            if ($simu != "") {
                $conn->query("
                INSERT INTO radgroupcheck (groupname,attribute,op,value)
                VALUES ('$profile','Simultaneous-Use',':=','$simu')
                ");
            }

            // MIKROTIK GROUP
            if ($mtk != "") {
                $conn->query("
                INSERT INTO radgroupreply (groupname,attribute,op,value)
                VALUES ('$profile','Mikrotik-Group',':=','$mtk')
                ");
            }

            $msg = "Profil + atribut berhasil dibuat";

        } else {
            $msg = "Profil sudah ada";
        }
    }
}

/* HAPUS PROFIL */
if (isset($_GET['hapus'])) {

    $p = $_GET['hapus'];

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
?>

<!DOCTYPE html>
<html>

<head>
    <title>Manajemen Profil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>

<body>

<?php include "sidebar.php"; ?>

<nav class="navbar navbar-light bg-white shadow-sm px-4">
    <b>Manajemen Profil</b>
</nav>

<div class="content p-4">

    <?php if ($msg) { ?>
        <div class="alert alert-info"><?php echo $msg ?></div>
    <?php } ?>

    <div class="row">

        <!-- TAMBAH PROFIL -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">

                    <h5>Tambah Profil</h5>

                    <form method="POST">

                        <input type="text" name="profile" class="form-control mb-2" placeholder="Nama Profil" required>

                        <input type="number" name="simu" class="form-control mb-2" placeholder="Simultaneous (1)">

                        <input type="text" name="mtk" class="form-control mb-2" placeholder="Mikrotik Group (paket-5M)">

                        <button class="btn btn-primary w-100" name="add">
                            Simpan
                        </button>

                    </form>

                </div>
            </div>
        </div>

        <!-- LIST -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">

                    <h5>Daftar Profil</h5>

                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>Nama Profil</th>
                                <th width="120">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php while($row = $q->fetch_assoc()) { ?>

                            <tr>
                                <td><?php echo $row['groupname']; ?></td>
                                <td>
                                    <a href="?hapus=<?php echo $row['groupname']; ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Yakin hapus?')">
                                       Hapus
                                    </a>
                                </td>
                            </tr>

                            <?php } ?>

                        </tbody>

                    </table>

                </div>
            </div>
        </div>

    </div>

</div>

</body>
</html>
