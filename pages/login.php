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
        header("Location: login");
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

        header("Location: index");
        exit;

    } else {

        $_SESSION['login_error'] = "Username atau Password salah";
        header("Location: login");
        exit;
    }
}

$pageTitle = 'Login ZERO.Net PANEL';

require __DIR__ . '/../views/login.view.php';
