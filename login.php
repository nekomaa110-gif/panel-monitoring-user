<?php
session_start();
require "config/db.php";

/* ambil error dari session */
$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

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

        $_SESSION['login_error'] = "Usename atau Password Salah";

        /* redirect supaya tidak POST ulang */
        header("Location: login.php");
        exit;
    }
}
?>

<!DOCTYPE html>

<html>

<head>

    <title>Login ZERO.Net PANEL</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">

    <style>
        .login-page {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: #f4f6f9;
        }

        .login-box {
            width: 360px;
            background: white;
            padding: 35px;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .login-title {
            text-align: center;
            font-size: 22px;
            font-weight: 600;
            margin-bottom: 25px;
        }

        .password-box {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 20px;
            color: #6c757d;
            transition: transform .15s;
        }

        .password-toggle:hover {
            transform: translateY(-50%) scale(1.15);
        }

        .error-msg {
            margin-top: 15px;
            color: #dc3545;
            text-align: center;
            font-style: italic;
            font-size: 16px;
            font-weight: 400;
            animation: shake .35s;
            transition: opacity .5s;
        }

        .hide-error {
            opacity: 0;
        }

        @keyframes shake {
            0% {
                transform: translateX(0)
            }

            25% {
                transform: translateX(-6px)
            }

            50% {
                transform: translateX(6px)
            }

            75% {
                transform: translateX(-6px)
            }

            100% {
                transform: translateX(0)
            }
        }
    </style>

</head>

<body>

    <div class="login-page">

        <div class="login-box">

            <div class="login-title">
                ZERO.Net PANEL
            </div>

            <form method="POST">

                <input class="form-control mb-3"
                    name="user"
                    placeholder="Username"
                    required>

                <div class="password-box mb-3">

                    <input id="password"
                        class="form-control"
                        type="password"
                        name="pass"
                        placeholder="Password"
                        required>

                    <i id="eyeIcon"
                        class="bi bi-eye-slash password-toggle"
                        onclick="togglePass()"></i>

                </div>

                <button class="btn btn-primary w-100"
                    name="login">
                    Login </button>

            </form>

            <?php if ($error) { ?>

                <div id="errorBox" class="error-msg">
                    <?php echo $error ?>
                </div>
            <?php } ?>

        </div>

    </div>

    <script>
        function togglePass() {

            let p = document.getElementById("password");
            let eye = document.getElementById("eyeIcon");

            if (p.type === "password") {

                p.type = "text";
                eye.classList.remove("bi-eye-slash");
                eye.classList.add("bi-eye");

            } else {

                p.type = "password";
                eye.classList.remove("bi-eye");
                eye.classList.add("bi-eye-slash");

            }

        }

        let err = document.getElementById("errorBox");

        if (err) {

            setTimeout(function() {
                err.classList.add("hide-error");
            }, 1200);

        }
    </script>

</body>

</html>