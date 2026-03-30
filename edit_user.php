<?php
require "auth.php";
require "config/db.php";

$user = $_GET['user'] ?? "";

/* cari user */
if (isset($_GET['find'])) {
    $user = $_GET['username'];
}

$password = "";
$expiration = "";

if ($user != "") {

    $q = $conn->query("
    SELECT
    MAX(CASE WHEN attribute='Cleartext-Password' THEN value END) as password,
    MAX(CASE WHEN attribute='Expiration' THEN value END) as expiration
    FROM radcheck
    WHERE BINARY username='$user'
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

    // =========================
    // PASSWORD
    // =========================
    if ($password != "") {

        $cekPass = $conn->query("
        SELECT * FROM radcheck 
        WHERE BINARY username='$user' 
        AND attribute='Cleartext-Password'
        ");

        if ($cekPass->num_rows > 0) {

            $conn->query("
            UPDATE radcheck 
            SET value='$password' 
            WHERE BINARY username='$user' 
            AND attribute='Cleartext-Password'
            ");
        } else {

            $conn->query("
            INSERT INTO radcheck (username,attribute,op,value)
            VALUES ('$user','Cleartext-Password',':=','$password')
            ");
        }
    }

    // =========================
    // EXPIRATION
    // =========================
    if ($expiration != "") {

        $cekExp = $conn->query("
        SELECT * FROM radcheck 
        WHERE BINARY username='$user' 
        AND attribute='Expiration'
        ");

        if ($cekExp->num_rows > 0) {

            $conn->query("
            UPDATE radcheck 
            SET value='$expiration' 
            WHERE BINARY username='$user' 
            AND attribute='Expiration'
            ");
        } else {

            $conn->query("
            INSERT INTO radcheck (username,attribute,op,value)
            VALUES ('$user','Expiration',':=','$expiration')
            ");
        }
    }

    // 🔥 NOTIF + STAY DI HALAMAN
    $_SESSION['msg'] = "User berhasil di update";
    header("Location: edit_user.php?user=$user");
    exit;
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

        <!-- 🔥 NOTIF -->
        <?php if (isset($_SESSION['msg'])) { ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['msg']; ?>
            </div>
        <?php unset($_SESSION['msg']);
        } ?>

        <h3>Cari User</h3>

        <form method="GET" class="mb-4">
            <input type="text" name="username" class="form-control mb-2" placeholder="Masukkan username">
            <button name="find" class="btn btn-primary">Cari</button>
        </form>

        <?php if ($user != "") { ?>

            <h3>Edit User: <?php echo $user ?></h3>

            <form method="POST">

                <input type="hidden" name="user" value="<?php echo $user ?>">

                <div class="mb-3">
                    <label>Password</label>
                    <input name="password" value="<?php echo $password ?>" class="form-control">
                </div>

                <div class="mb-3">
                    <label>Expiration</label>
                    <input name="expiration" value="<?php echo $expiration ?>" class="form-control">
                </div>

                <button name="save" class="btn btn-success">Simpan</button>

            </form>

        <?php } ?>

    </div>

</body>

</html>