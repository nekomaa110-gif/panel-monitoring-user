<?php
require "auth.php";
require "config/db.php";

$user = $_GET['user'] ?? '';

/* cari user */

if (isset($_GET['find'])) {
    $user = $_GET['username'];
}

$password = "";
$expiration = "";

if ($user != "") {

    $q = $conn->query("
SELECT
MAX(CASE WHEN attribute='Cleartext-Password' THEN value END) password,
MAX(CASE WHEN attribute='Expiration' THEN value END) expiration
FROM radcheck
WHERE username='$user'
");

    $data = $q->fetch_assoc();

    $password = $data['password'] ?? "";
    $expiration = $data['expiration'] ?? "";
}

/* simpan perubahan */

if (isset($_POST['save'])) {

    $user = $_POST['user'];
    $password = $_POST['password'];
    $expiration = $_POST['expiration'];

    $conn->query("
UPDATE radcheck
SET value='$password'
WHERE username='$user'
AND attribute='Cleartext-Password'
");

    $conn->query("
UPDATE radcheck
SET value='$expiration'
WHERE username='$user'
AND attribute='Expiration'
");

    echo "<script>alert('User berhasil di update');</script>";
}
?>

<!DOCTYPE html>

<html>

<head>

    <title>Edit User</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">

</head>

<body>

    <?php include "sidebar.php"; ?>

    <nav class="navbar navbar-light bg-white shadow-sm px-4">
        <b>Edit User</b>
    </nav>

    <div class="content p-4">

        <h3>Cari User</h3>

        <form method="GET" class="mb-4">

            <input
                type="text"
                name="username"
                class="form-control mb-2"
                placeholder="Masukkan username">

            <button
                class="btn btn-primary"
                name="find">

                Cari User

            </button>

        </form>

        <?php if ($user != "") { ?>

            <h4>Edit User : <?php echo $user; ?></h4>

            <form method="POST">

                <input type="hidden" name="user" value="<?php echo $user; ?>">

                <div class="mb-3">

                    <label>Password</label>

                    <input
                        type="text"
                        name="password"
                        class="form-control"
                        value="<?php echo $password; ?>">

                </div>

                <div class="mb-3">

                    <label>Masa Aktif</label>

                    <input
                        type="text"
                        name="expiration"
                        class="form-control"
                        value="<?php echo $expiration; ?>">

                </div>

                <button class="btn btn-success" name="save">
                    Simpan
                </button>

                <a href="edit_user.php" class="btn btn-secondary">
                    Batal
                </a>

            </form>

        <?php } ?>

    </div>

</body>

</html>