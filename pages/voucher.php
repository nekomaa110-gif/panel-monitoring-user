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
            throw new RuntimeException("Jumlah generate harus antara 1 sampai 500.");
        }

        if (!in_array($paket, $allowedPaket, true)) {
            throw new RuntimeException("Paket tidak valid.");
        }

        $hargaVoucher = getHarga($paket);
        if ($hargaVoucher === null) {
            throw new RuntimeException("Harga paket tidak ditemukan.");
        }

        $stmtDupUnion = $conn->prepare("
            SELECT username FROM voucher WHERE username=?
            UNION
            SELECT username FROM radcheck WHERE username=?
            LIMIT 1
        ");
        if (!$stmtDupUnion) throw new RuntimeException("Prepare failed (dup union): " . $conn->error);

        $stmtVoucher = $conn->prepare("INSERT INTO voucher (username,password,paket,harga,status) VALUES (?,?,?,?,?)");
        if (!$stmtVoucher) throw new RuntimeException("Prepare failed (voucher): " . $conn->error);

        $stmtDelPwd = $conn->prepare("DELETE FROM radcheck WHERE username=? AND attribute='Cleartext-Password'");
        if (!$stmtDelPwd) throw new RuntimeException("Prepare failed (del pwd): " . $conn->error);

        $stmtInsPwd = $conn->prepare("
            INSERT INTO radcheck (username,attribute,op,value)
            VALUES (?, 'Cleartext-Password', ':=', ?)
        ");
        if (!$stmtInsPwd) throw new RuntimeException("Prepare failed (ins pwd): " . $conn->error);

        $stmtDelTimeout = $conn->prepare("DELETE FROM radcheck WHERE username=? AND attribute='Session-Timeout'");
        if (!$stmtDelTimeout) throw new RuntimeException("Prepare failed (del timeout): " . $conn->error);

        $stmtInsTimeout = $conn->prepare("
            INSERT INTO radcheck (username,attribute,op,value)
            VALUES (?, 'Session-Timeout', ':=', ?)
        ");
        if (!$stmtInsTimeout) throw new RuntimeException("Prepare failed (ins timeout): " . $conn->error);

        $stmtDelExp = $conn->prepare("DELETE FROM radcheck WHERE username=? AND attribute='Expiration'");
        if (!$stmtDelExp) throw new RuntimeException("Prepare failed (del expiration): " . $conn->error);

        $stmtInsExp = $conn->prepare("
            INSERT INTO radcheck (username,attribute,op,value)
            VALUES (?, 'Expiration', ':=', ?)
        ");
        if (!$stmtInsExp) throw new RuntimeException("Prepare failed (ins expiration): " . $conn->error);

        $stmtDelGroup = $conn->prepare("DELETE FROM radusergroup WHERE username=?");
        if (!$stmtDelGroup) throw new RuntimeException("Prepare failed (del group): " . $conn->error);

        $stmtAssign = $conn->prepare("
            INSERT INTO radusergroup (username,groupname,priority)
            VALUES (?,?,0)
        ");
        if (!$stmtAssign) throw new RuntimeException("Prepare failed (assign group): " . $conn->error);

        $statusBaru = 'baru';
        $conn->begin_transaction();

        for ($i = 0; $i < $jumlah; $i++) {
            $user = "5K" . rand(1, 9) . chr(rand(65, 90)) . chr(rand(97, 122));
            $pass = (string) rand(1000, 9999);

            $stmtDupUnion->bind_param("ss", $user, $user);
            $stmtDupUnion->execute();
            $resDup = $stmtDupUnion->get_result();
            if ($resDup && $resDup->fetch_assoc()) {
                throw new RuntimeException("Generate gagal: username duplikat terdeteksi ($user).");
            }

            $stmtVoucher->bind_param("sssis", $user, $pass, $paket, $hargaVoucher, $statusBaru);
            if (!$stmtVoucher->execute()) {
                throw new RuntimeException("Execute failed (voucher): " . $stmtVoucher->error);
            }

            $stmtDelPwd->bind_param("s", $user);
            if (!$stmtDelPwd->execute()) {
                throw new RuntimeException("Execute failed (del pwd): " . $stmtDelPwd->error);
            }

            $stmtInsPwd->bind_param("ss", $user, $pass);
            if (!$stmtInsPwd->execute()) {
                throw new RuntimeException("Execute failed (ins pwd): " . $stmtInsPwd->error);
            }

            $sessionTimeout = (string) sessionTimeoutByPaket($paket);

            $stmtDelTimeout->bind_param("s", $user);
            if (!$stmtDelTimeout->execute()) {
                throw new RuntimeException("Execute failed (del timeout): " . $stmtDelTimeout->error);
            }

            $stmtInsTimeout->bind_param("ss", $user, $sessionTimeout);
            if (!$stmtInsTimeout->execute()) {
                throw new RuntimeException("Execute failed (ins timeout): " . $stmtInsTimeout->error);
            }

            $expiration = date("d M Y 23:59", strtotime("+1 day"));

            $stmtDelExp->bind_param("s", $user);
            if (!$stmtDelExp->execute()) {
                throw new RuntimeException("Execute failed (del expiration): " . $stmtDelExp->error);
            }

            $stmtInsExp->bind_param("ss", $user, $expiration);
            if (!$stmtInsExp->execute()) {
                throw new RuntimeException("Execute failed (ins expiration): " . $stmtInsExp->error);
            }

            $stmtDelGroup->bind_param("s", $user);
            if (!$stmtDelGroup->execute()) {
                throw new RuntimeException("Execute failed (del group): " . $stmtDelGroup->error);
            }

            $stmtAssign->bind_param("ss", $user, $paket);
            if (!$stmtAssign->execute()) {
                throw new RuntimeException("Execute failed (assign group): " . $stmtAssign->error);
            }
        }

        $conn->commit();

        $stmtDupUnion->close();
        $stmtVoucher->close();
        $stmtDelPwd->close();
        $stmtInsPwd->close();
        $stmtDelTimeout->close();
        $stmtInsTimeout->close();
        $stmtDelExp->close();
        $stmtInsExp->close();
        $stmtDelGroup->close();
        $stmtAssign->close();

        $msg = "Voucher berhasil dibuat untuk paket '$paket'";
    } catch (Throwable $e) {
        if ($conn->ping()) {
            @$conn->rollback();
        }
        $msg = "Generate gagal (rollback): " . $e->getMessage();
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
        $statusBaru = 'baru';

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

            $stmtInsertVoucher->bind_param("sssis", $username, $password, $paketCsvRaw, $hargaCsv, $statusBaru);
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
        $stmtDelUserbillinfo = $conn->prepare("DELETE FROM userbillinfo WHERE username = ?");
        $stmtDelUserinfo     = $conn->prepare("DELETE FROM userinfo WHERE username = ?");
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

            if ($stmtDelUserbillinfo) {
                $stmtDelUserbillinfo->bind_param("s", $username);
                $stmtDelUserbillinfo->execute();
            }

            if ($stmtDelUserinfo) {
                $stmtDelUserinfo->bind_param("s", $username);
                $stmtDelUserinfo->execute();
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
        if ($stmtDelUserbillinfo) $stmtDelUserbillinfo->close();
        if ($stmtDelUserinfo)     $stmtDelUserinfo->close();
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

$extraJs = <<<'JS'
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const selectAll = document.getElementById('select-all');
    const checkboxes = document.querySelectorAll('.voucher-checkbox');

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                cb.checked = selectAll.checked;
            });
        });
    }

    checkboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const total = checkboxes.length;
            const checked = document.querySelectorAll('.voucher-checkbox:checked').length;
            if (selectAll) {
                selectAll.checked = total > 0 && checked === total;
            }
        });
    });

    const togglePassword = document.getElementById('toggle-password');
    const passwordCells = document.querySelectorAll('.password-cell');

    if (togglePassword) {
        togglePassword.addEventListener('change', function() {
            const showPlain = togglePassword.checked;
            passwordCells.forEach(el => {
                el.textContent = showPlain ? el.dataset.plain : el.dataset.masked;
            });
        });
    }
</script>
JS;

include __DIR__ . "/../views/voucher.view.php";
