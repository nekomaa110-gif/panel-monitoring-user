<?php
session_start();
require "config/db.php";

$error = "";

if (isset($_POST['login'])) {

    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';

    $q = $conn->query("SELECT * FROM admin WHERE username='$user'");
    $data = $q->fetch_assoc();

    if ($data && password_verify($pass, $data['password'])) {
        $_SESSION['login'] = true;
        header("Location: index.php");
        exit;
    } else {
        $error = "Login salah";
    }
}
?>

<!DOCTYPE html>

<html>

<head>

    <title>Login Admin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

    <div class="container mt-5">

        <div class="row justify-content-center">

            <div class="col-md-4">

                <div class="card shadow">

                    <div class="card-body">

                        <h4 class="text-center mb-4">Login Panel</h4>

                        <?php if (!empty($error)) { ?>

                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                            </div>

                        <?php } ?>

                        <form method="POST">

                            <input class="form-control mb-3" name="user" placeholder="Username" required>

                            <input class="form-control mb-3" type="password" name="pass" placeholder="Password" required>

                            <button class="btn btn-primary w-100" name="login">
                                Login
                            </button>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>

</body>

</html>