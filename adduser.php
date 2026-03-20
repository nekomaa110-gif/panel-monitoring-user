<?php
require "auth.php";
require "config/db.php";

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
?>

<!DOCTYPE html>
<html>

<head>
    <title>Tambah User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>

<body>

<?php include "sidebar.php"; ?>

<nav class="navbar navbar-light bg-white shadow-sm px-4">
    <b>Tambah User</b>
</nav>

<div class="content p-4">

    <h3 class="mb-4">Tambah User Baru</h3>

    <?php if ($msg) { ?>
        <div class="alert alert-info"><?php echo $msg ?></div>
    <?php } ?>

    <form method="POST">

        <!-- USERNAME -->
        <div class="mb-3">
            <label>Username</label>
            <input name="username" class="form-control" required>
        </div>

        <!-- PASSWORD -->
        <div class="mb-3">
            <label>Password</label>
            <input name="password" class="form-control" required>
        </div>

        <!-- PROFIL -->
        <div class="mb-3">
            <label>Profil</label>

            <select name="profile" class="form-control" required>

                <?php while($p = $profiles->fetch_assoc()) { ?>
                    <option value="<?php echo $p['groupname']; ?>">
                        <?php echo $p['groupname']; ?>
                    </option>
                <?php } ?>

            </select>

        </div>

        <!-- MASA AKTIF -->
        <div class="mb-3">
            <label>Masa Aktif (Hari)</label>
            <input type="number" name="hari" value="30" class="form-control">
        </div>

        <button name="save" class="btn btn-primary w-100">
            Simpan
        </button>

    </form>

</div>

</body>
</html>
