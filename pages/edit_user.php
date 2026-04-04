<?php
require __DIR__ . "/../core/auth.php";
require __DIR__ . "/../config/db.php";

$user = $_GET['user'] ?? "";

/* cari user */
if (isset($_GET['find'])) {
    $user = $_GET['username'];
}

$password = "";
$expiration = "";

/* AMBIL DATA USER */
if ($user != "") {

    $stmt = $conn->prepare("
    SELECT
    username,
    MAX(CASE WHEN attribute='Cleartext-Password' THEN value END) as password,
    MAX(CASE WHEN attribute='Expiration' THEN value END) as expiration
    FROM radcheck
    WHERE LOWER(username)=LOWER(?)
    AND attribute IN ('Cleartext-Password','Expiration')
    LIMIT 1
    ");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    if (!$data || ($data['password'] === null && $data['expiration'] === null)) {

        $user = ""; // biar form ga muncul
        $_SESSION['msg'] = [
            "type" => "danger",
            "text" => "User tidak ditemukan"
        ];
    } else {

        $user = $data['username'] ?? $user; // tetap pakai casing original dari DB
        $password = $data['password'] ?? "";
        $expiration = $data['expiration'] ?? "";
    }

    $stmt->close();
}

/* SIMPAN PERUBAHAN */
if (isset($_POST['save'])) {

    $user = $_POST['user'];
    $password = $_POST['password'];
    $expiration = $_POST['expiration'];

    /* PASSWORD */
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

    /* EXPIRATION */
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

    $_SESSION['msg'] = [
        "type" => "success",
        "text" => "User berhasil di update"
    ];

    header("Location: edit_user?user=$user");
    exit;
}

$pageTitle = 'Edit User';
$navTitle  = 'Edit User';

include __DIR__ . "/../views/edit_user.view.php";
