<?php
require "auth.php";
require "config/db.php";

$msg = "";

if (isset($_POST['save'])) {

    $username = $_POST['username'];
    $password = $_POST['password'];
    $hari = $_POST['hari'];
    $profile = $_POST['profile'];

    if ($username && $password) {

        $expiration = date("d M Y 23:59", strtotime("+$hari days"));

        $conn->query("
INSERT INTO radcheck (username,attribute,op,value)
VALUES ('$username','Cleartext-Password',':=','$password')
");

        $conn->query("
INSERT INTO radcheck (username,attribute,op,value)
VALUES ('$username','Expiration',':=','$expiration')
");

        $conn->query("
INSERT INTO radusergroup (username,groupname,priority)
VALUES ('$username','$profile',0)
");

        $msg = "User berhasil dibuat";
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

    <div class="sidebar">

        <h4>ZERO NET</h4>

        <a href="index.php">Dashboard</a> <a href="users.php">Pelanggan</a> <a href="adduser.php">Tambah User</a> <a href="logout.php">Logout</a>

    </div>

    <nav class="navbar navbar-light bg-white shadow-sm px-4">
        <b>Tambah User</b>
    </nav>

    <div class="content">

        <h3 class="mb-4">Tambah User Baru</h3>

        <?php if ($msg) { ?>

            <div class="alert alert-success">
                <?php echo $msg ?>
            </div>

        <?php } ?>

        <form method="POST">

            <div class="row mb-3">

                <div class="col-md-2">
                    <label class="form-label">Username</label>
                </div>

                <div class="col-md-4">
                    <input name="username" class="form-control">
                </div>

            </div>

            <div class="row mb-3">

                <div class="col-md-2">
                    <label class="form-label">Password</label>
                </div>

                <div class="col-md-4">
                    <input name="password" class="form-control">
                </div>

            </div>

            <div class="row mb-3">

                <div class="col-md-2">
                    <label class="form-label">Profil</label>
                </div>

                <div class="col-md-4">

                    <select name="profile" class="form-control">

                        <option value="Radius-Member">Radius-Member</option>
                        <option value="TEST">TEST</option>

                    </select>

                </div>

            </div>

            <div class="row mb-4">

                <div class="col-md-2">
                    <label class="form-label">Masa Aktif</label>
                </div>

                <div class="col-md-2">
                    <input type="number" name="hari" value="30" class="form-control">
                </div>

            </div>

            <div class="row">

                <div class="col-md-2"></div>

                <div class="col-md-2">

                    <button name="save" class="btn btn-primary w-100">
                        Simpan
                    </button>

                </div>

            </div>

        </form>

    </div>

</body>

</html>