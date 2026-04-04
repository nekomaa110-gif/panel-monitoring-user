<?php
session_start();
require __DIR__ . "/../config/db.php";

$error = $_SESSION['login_error'] ?? '';
unset($_SESSION['login_error']);

if (isset($_POST['login'])) {

    $user = $_POST['user'] ?? '';
    $pass = $_POST['pass'] ?? '';

    // validasi basic
    if (empty($user) || empty($pass)) {
        $_SESSION['login_error'] = "Username atau Password kosong";
        header("Location: login.php");
        exit;
    }

    // prepared statement (anti SQL injection)
    $stmt = $conn->prepare("SELECT * FROM admin WHERE username=? LIMIT 1");

    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $user);
    $stmt->execute();

    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if ($data && password_verify($pass, $data['password'])) {

        // WAJIB: regenerate session setelah login sukses
        session_regenerate_id(true);

        $_SESSION['login'] = true;
        $_SESSION['username'] = $user;

        header("Location: index.php");
        exit;

    } else {

        $_SESSION['login_error'] = "Username atau Password salah";
        header("Location: login.php");
        exit;
    }
}

$pageTitle = 'Login ZERO.Net PANEL';

$extraCss = <<<'CSS'
<meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        0%   { transform: translateX(0) }
        25%  { transform: translateX(-6px) }
        50%  { transform: translateX(6px) }
        75%  { transform: translateX(-6px) }
        100% { transform: translateX(0) }
    }
</style>
CSS;

$extraJs = <<<'JS'
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
JS;

include __DIR__ . "/../views/login.view.php";
