<?php
require __DIR__ . "/../core/auth.php";
require __DIR__ . "/../config/db.php";

/* =======================
   HELPERS
======================= */
function allowedPaketList(): array
{
    return ['4 jam', '5 jam', 'Mingguan'];
}

function getHarga(string $paket): ?int
{
    return match ($paket) {
        '4 jam' => 5000,
        '5 jam' => 5000,
        'Mingguan' => 55000,
        default => null
    };
}

function getPrefix(string $paket): string
{
    return match ($paket) {
        '4 jam','5 jam' => '5K',
        'Mingguan' => '7D',
        default => 'X'
    };
}

function genereteUsername(string $paket): string
{
    $prefix = getPrefix($paket);
    return $prefix . strtolower(bin2hex(random_bytes(2)));
}

function sessionTimeoutByPaket(string $paket): int
{
    return match ($paket) {
        'Mingguan' => 7 * 24 * 3600,
        '5 jam' => 5 * 3600,
        default => 4 * 3600
    };
}

$allowedPaket = allowedPaketList();
$profiles = $allowedPaket;

$msg = "";

/* =======================
   GENERATE VOUCHER
======================= */
if (isset($_POST['generate'])) {
    try {
        $paket = trim((string)($_POST['paket'] ?? ''));
        $jumlah = (int)($_POST['jumlah'] ?? 0);

        if ($jumlah <= 0 || $jumlah > 500) {
            throw new RuntimeException("Jumlah generate harus 1 - 500");
        }

        if (!in_array($paket, $allowedPaket, true)) {
            throw new RuntimeException("Paket tidak valid");
        }

        $hargaVoucher = getHarga($paket);
        if ($hargaVoucher === null) {
            throw new RuntimeException("Harga paket tidak ditemukan");
        }

        // ================= PREPARE =================
        $stmtDup = $conn->prepare("
            SELECT username FROM voucher WHERE username=?
            UNION
            SELECT username FROM radcheck WHERE username=?
            LIMIT 1
        ");

        $stmtVoucher = $conn->prepare("
            INSERT INTO voucher (username,password,paket,harga,status)
            VALUES (?,?,?,?,?)
        ");

        $stmtDelPwd = $conn->prepare("
            DELETE FROM radcheck 
            WHERE username=? AND attribute='Cleartext-Password'
        ");

        $stmtInsPwd = $conn->prepare("
            INSERT INTO radcheck (username,attribute,op,value)
            VALUES (?, 'Cleartext-Password', ':=', ?)
        ");

        $stmtDelTimeout = $conn->prepare("
            DELETE FROM radcheck 
            WHERE username=? AND attribute='Session-Timeout'
        ");

        $stmtInsTimeout = $conn->prepare("
            INSERT INTO radcheck (username,attribute,op,value)
            VALUES (?, 'Session-Timeout', ':=', ?)
        ");

        $stmtDelGroup = $conn->prepare("
            DELETE FROM radusergroup WHERE username=?
        ");

        $stmtAssign = $conn->prepare("
            INSERT INTO radusergroup (username,groupname,priority)
            VALUES (?, ?, 0)
        ");

        if (
            !$stmtDup || !$stmtVoucher || !$stmtDelPwd || !$stmtInsPwd ||
            !$stmtDelTimeout || !$stmtInsTimeout || !$stmtDelGroup || !$stmtAssign
        ) {
            throw new RuntimeException("Prepare gagal: " . $conn->error);
        }

        $conn->begin_transaction();

        for ($i = 0; $i < $jumlah; $i++) {

            // generate user random
            $prefix = getPrefix($paket);
            $user = genereteUsername($paket);
            $pass = (string) rand(1000, 9999);

            // cek duplikat
            $stmtDup->bind_param("ss", $user, $user);
            $stmtDup->execute();
            if ($stmtDup->get_result()->fetch_assoc()) {
                throw new RuntimeException("Username duplicate: $user");
            }

            // insert voucher
            $status = 'active';
            $stmtVoucher->bind_param("sssis", $user, $pass, $paket, $hargaVoucher, $status);
            $stmtVoucher->execute();

            // password
            $stmtDelPwd->bind_param("s", $user);
            $stmtDelPwd->execute();

            $stmtInsPwd->bind_param("ss", $user, $pass);
            $stmtInsPwd->execute();

            // session timeout (INI YANG PENTING)
            $timeout = (string) sessionTimeoutByPaket($paket);

            $stmtDelTimeout->bind_param("s", $user);
            $stmtDelTimeout->execute();

            $stmtInsTimeout->bind_param("ss", $user, $timeout);
            $stmtInsTimeout->execute();

            // group profile
            $stmtDelGroup->bind_param("s", $user);
            $stmtDelGroup->execute();

            $stmtAssign->bind_param("ss", $user, $paket);
            $stmtAssign->execute();
        }

        $conn->commit();

        $msg = "Voucher berhasil dibuat ($paket)";
        
    } catch (Throwable $e) {
        $conn->rollback();
        $msg = "Gagal generate: " . $e->getMessage();
    }
}

/* =======================
   IMPORT CSV
======================= */
if (isset($_POST['import'])) {
    try {
        if (($_FILES['csv']['error'] ?? UPLOAD_ERR_NO_FILE) !== 0) {
            throw new RuntimeException("Upload gagal");
        }

        $file = $_FILES['csv']['tmp_name'];
        $lineCount = 0;
        $countHandle = fopen($file, "r");
        if (!$countHandle) {
            throw new RuntimeException("File CSV tidak dapat dibuka");
        }
        while (fgetcsv($countHandle, 1000, ",") !== false) {
            $lineCount++;
        }
        fclose($countHandle);

        if ($lineCount > 500) {
            throw new RuntimeException("Import gagal: jumlah baris CSV melebihi batas 500.");
        }

        $handle = fopen($file, "r");
        if (!$handle) {
            throw new RuntimeException("File CSV tidak dapat dibuka");
        }

        $stmtDupUnion = $conn->prepare("
            SELECT username FROM voucher WHERE username=?
            UNION
            SELECT username FROM radcheck WHERE username=?
            LIMIT 1
        ");
        if (!$stmtDupUnion) throw new RuntimeException("Prepare failed (dup union import): " . $conn->error);

        $stmtInsertVoucher = $conn->prepare("INSERT INTO voucher (username,password,paket,harga,status) VALUES (?,?,?,?,?)");
        if (!$stmtInsertVoucher) throw new RuntimeException("Prepare failed (insert voucher): " . $conn->error);

        $inserted = 0;
        $skipped = 0;
        $statusactive = 'active';

        while (($data = fgetcsv($handle, 1000, ",")) !== false) {
            $username = trim((string)($data[0] ?? ''));
            $password = trim((string)($data[1] ?? ''));
            $paketCsvRaw = trim((string)($data[2] ?? ''));

            if ($username === '' || $password === '' || $paketCsvRaw === '') {
                $skipped++;
                continue;
            }

            if (!in_array($paketCsvRaw, $allowedPaket, true)) {
                $skipped++;
                continue;
            }

            $hargaCsv = getHarga($paketCsvRaw);
            if ($hargaCsv === null) {
                $skipped++;
                continue;
            }

            $stmtDupUnion->bind_param("ss", $username, $username);
            $stmtDupUnion->execute();
            $resDup = $stmtDupUnion->get_result();
            if ($resDup && $resDup->fetch_assoc()) {
                $skipped++;
                continue;
            }

            $stmtInsertVoucher->bind_param("sssis", $username, $password, $paketCsvRaw, $hargaCsv, $statusactive);
            if (!$stmtInsertVoucher->execute()) {
                throw new RuntimeException("Execute failed (insert voucher import): " . $stmtInsertVoucher->error);
            }
            $inserted++;
        }

        fclose($handle);
        $stmtDupUnion->close();
        $stmtInsertVoucher->close();

        $msg = "Import CSV selesai. Inserted: {$inserted}, Skipped: {$skipped}";
    } catch (Throwable $e) {
        $msg = "Import gagal: " . $e->getMessage();
    }
}

/* =======================
   HAPUS MASSAL VOUCHER
======================= */
if (isset($_POST['delete_selected'])) {
    $selectedUsernames = $_POST['selected_vouchers'] ?? [];

    if (!is_array($selectedUsernames) || count($selectedUsernames) === 0) {
        $msg = "Tidak ada voucher yang dipilih untuk dihapus";
    } else {
        $deletedCount = 0;

        $stmtDelVoucher      = $conn->prepare("DELETE FROM voucher WHERE username = ?");
        $stmtDelRadcheck     = $conn->prepare("DELETE FROM radcheck WHERE username = ?");
        $stmtDelRadusergroup = $conn->prepare("DELETE FROM radusergroup WHERE username = ?");
        $stmtDelRadreply     = $conn->prepare("DELETE FROM radreply WHERE username = ?");
        $stmtDelRadpostauth  = $conn->prepare("DELETE FROM radpostauth WHERE username = ?");
        $stmtDelRadacct      = $conn->prepare("DELETE FROM radacct WHERE username = ?");

        foreach ($selectedUsernames as $usernameRaw) {
            $username = trim((string)$usernameRaw);
            if ($username === '') {
                continue;
            }

            if ($stmtDelVoucher) {
                $stmtDelVoucher->bind_param("s", $username);
                $stmtDelVoucher->execute();
                $deletedCount += $stmtDelVoucher->affected_rows > 0 ? 1 : 0;
            }

            if ($stmtDelRadcheck) {
                $stmtDelRadcheck->bind_param("s", $username);
                $stmtDelRadcheck->execute();
            }

            if ($stmtDelRadusergroup) {
                $stmtDelRadusergroup->bind_param("s", $username);
                $stmtDelRadusergroup->execute();
            }

            if ($stmtDelRadreply) {
                $stmtDelRadreply->bind_param("s", $username);
                $stmtDelRadreply->execute();
            }

            if ($stmtDelRadpostauth) {
                $stmtDelRadpostauth->bind_param("s", $username);
                $stmtDelRadpostauth->execute();
            }

            if ($stmtDelRadacct) {
                $stmtDelRadacct->bind_param("s", $username);
                $stmtDelRadacct->execute();
            }
        }

        if ($stmtDelVoucher)      $stmtDelVoucher->close();
        if ($stmtDelRadcheck)     $stmtDelRadcheck->close();
        if ($stmtDelRadusergroup) $stmtDelRadusergroup->close();
        if ($stmtDelRadreply)     $stmtDelRadreply->close();
        if ($stmtDelRadpostauth)  $stmtDelRadpostauth->close();
        if ($stmtDelRadacct)      $stmtDelRadacct->close();

        $msg = $deletedCount . " voucher berhasil dihapus (beserta data terkait selain log)";
    }
}

/* =======================
   AMBIL DATA
======================= */
$defaultPaket = $profiles[0] ?? '4 jam';
$paket = (string)($_POST['paket'] ?? $defaultPaket);
if (!in_array($paket, $allowedPaket, true)) {
    $paket = $defaultPaket;
}

$limit = 100;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$offset = ($page - 1) * $limit;

$stmtVouchers = $conn->prepare("SELECT * FROM voucher ORDER BY id DESC LIMIT ? OFFSET ?");
if (!$stmtVouchers) {
    throw new RuntimeException("Prepare failed (list voucher): " . $conn->error);
}
$stmtVouchers->bind_param("ii", $limit, $offset);
$stmtVouchers->execute();
$resVouchers = $stmtVouchers->get_result();
$vouchers = $resVouchers ? $resVouchers->fetch_all(MYSQLI_ASSOC) : [];
$stmtVouchers->close();

$pageTitle = 'Voucher';
$navTitle  = 'Voucher';

require __DIR__ . '/../views/voucher.view.php';
