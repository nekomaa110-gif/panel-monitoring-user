<?php
require __DIR__ . "/../core/auth.php";
require __DIR__ . "/../config/db.php";

/* =======================
   GET USER (FIX COMPAT)
======================= */
$user = $_GET['user'] ?? ($_GET['username'] ?? '');
$user = trim($user);

$password = "";
$expiration = "";
$current_profile = "";

/* =======================
   FETCH USER
======================= */
if ($user !== "") {

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
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$data) {
        $_SESSION['msg'] = [
            "type" => "danger",
            "text" => "User tidak ditemukan"
        ];
        $user = "";
    } else {
        $user       = $data['username']; // pakai casing asli
        $password   = $data['password'] ?? "";
        $expiration = $data['expiration'] ?? "";
    }

    /* PROFILE */
    $stmt = $conn->prepare("
        SELECT groupname 
        FROM radusergroup 
        WHERE LOWER(username)=LOWER(?) 
        LIMIT 1
    ");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $current_profile = $profile['groupname'] ?? 'No Profile';
    $stmt->close();
}

/* =======================
   SAVE UPDATE
======================= */
if (isset($_POST['save'])) {

    $user       = trim($_POST['user'] ?? '');
    $password   = trim($_POST['password'] ?? '');
    $expiration = trim($_POST['expiration'] ?? '');

    if ($user === '') {
        $_SESSION['msg'] = [
            "type" => "danger",
            "text" => "User kosong"
        ];
        header("Location: edit_user");
        exit;
    }

    try {
        $conn->begin_transaction();

        /* ===== PASSWORD ===== */
        if ($password !== "") {

            $stmt = $conn->prepare("
                DELETE FROM radcheck 
                WHERE username=? 
                AND attribute='Cleartext-Password'
            ");
            $stmt->bind_param("s", $user);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("
                INSERT INTO radcheck (username,attribute,op,value)
                VALUES (?, 'Cleartext-Password', ':=', ?)
            ");
            $stmt->bind_param("ss", $user, $password);
            $stmt->execute();
            $stmt->close();
        }

        /* ===== EXPIRATION ===== */
        if ($expiration !== "") {

            $stmt = $conn->prepare("
                DELETE FROM radcheck 
                WHERE username=? 
                AND attribute='Expiration'
            ");
            $stmt->bind_param("s", $user);
            $stmt->execute();
            $stmt->close();

            $stmt = $conn->prepare("
                INSERT INTO radcheck (username,attribute,op,value)
                VALUES (?, 'Expiration', ':=', ?)
            ");
            $stmt->bind_param("ss", $user, $expiration);
            $stmt->execute();
            $stmt->close();
        }

        $conn->commit();

        $_SESSION['msg'] = [
            "type" => "success",
            "text" => "User berhasil di update"
        ];

    } catch (Throwable $e) {

        $conn->rollback();

        $_SESSION['msg'] = [
            "type" => "danger",
            "text" => "Error: " . $e->getMessage()
        ];
    }

    header("Location: edit_user?user=" . urlencode($user));
    exit;
}

/* =======================
   VIEW
======================= */
$pageTitle = 'Edit User';
$navTitle  = 'Edit User';

include __DIR__ . "/../views/edit_user.view.php";