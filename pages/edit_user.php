<?php
require __DIR__ . "/../core/auth.php";
require __DIR__ . "/../config/db.php";

date_default_timezone_set('Asia/Jakarta');

/* =======================
   GET USER
======================= */
$userInput = $_GET['user'] ?? ($_GET['username'] ?? '');
$userInput = trim($userInput);

$user = "";
$password = "";
$expiration = "";
$current_profile = "";

/* =======================
   FETCH USER
======================= */
if ($userInput !== "") {

    $stmt = $conn->prepare("
        SELECT
            username,
            MAX(CASE WHEN attribute='Cleartext-Password' THEN value END) as password,
            MAX(CASE WHEN attribute='Expiration' THEN value END) as expiration
        FROM radcheck
        WHERE LOWER(username)=LOWER(?)
        AND attribute IN ('Cleartext-Password','Expiration')
        GROUP BY username
        LIMIT 1
    ");

    $stmt->bind_param("s", $userInput);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$data) {
        $_SESSION['msg'] = [
            "type" => "danger",
            "text" => "User tidak ditemukan"
        ];
    } else {
        $user       = $data['username']; // pakai casing asli DB
        $password   = $data['password'] ?? "";
        $expiration = $data['expiration'] ?? "";

        /* PROFILE */
        $stmt = $conn->prepare("
            SELECT groupname 
            FROM radusergroup 
            WHERE username=? 
            ORDER BY priority ASC
            LIMIT 1
        ");
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $profile = $stmt->get_result()->fetch_assoc();
        $current_profile = $profile['groupname'] ?? 'No Profile';
        $stmt->close();
    }
}

/* =======================
   SAVE UPDATE
======================= */
if (isset($_POST['save'])) {

    $user       = trim($_POST['user'] ?? '');
    $password   = trim($_POST['password'] ?? '');
    $expiration = trim($_POST['expiration'] ?? '');

    // VALIDASI USERNAME
    if ($user === '' || !preg_match('/^[a-zA-Z0-9._-]+$/', $user)) {
        $_SESSION['msg'] = [
            "type" => "danger",
            "text" => "Username tidak valid"
        ];
        header("Location: edit_user");
        exit;
    }

    try {
        $conn->begin_transaction();

        /* LOCK USER (ANTI RACE CONDITION) */
        $stmt = $conn->prepare("
            SELECT username FROM radcheck 
            WHERE username=? 
            FOR UPDATE
        ");
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$exists) {
            throw new Exception("User tidak ditemukan saat update");
        }

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

            // VALIDASI KETAT
            $dt = DateTime::createFromFormat('d M Y H:i:s', $expiration);
            $error = DateTime::getLastErrors();

            if (!$dt || $error['warning_count'] > 0 || $error['error_count'] > 0 ||
                $dt->format('d M Y H:i:s') !== $expiration) {
                throw new Exception("Format Expiration harus: DD MMM YYYY HH:MM:SS");
            }

            $expiration = $dt->format('d M Y H:i:s');

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
        error_log($e->getMessage());

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