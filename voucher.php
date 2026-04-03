<?php
require "auth.php";
require "config/db.php";

/* =======================
   HELPERS
======================= */
function normalizeProfileName(string $name): string
{
    $n = strtolower(trim($name));
    $n = str_replace([' ', '-', '_'], '', $n);

    if ($n === '5jam') {
        return '5jam';
    }

    if ($n === '4jam') {
        return '4jam';
    }

    if ($n === '7hari' || $n === 'mingguan') {
        return 'mingguan';
    }

    return $n;
}

function sessionTimeoutByPaket(string $paket): int
{
    $n = normalizeProfileName($paket);

    if ($n === 'mingguan' || strpos($n, '7hari') !== false) {
        return 7 * 24 * 3600;
    }

    if (strpos($n, '5jam') !== false || preg_match('/(^|[^0-9])5([^0-9]|$)/', $paket)) {
        return 5 * 3600;
    }

    return 4 * 3600;
}

function hargaByPaket(mysqli $conn, string $paket): ?int
{
    $n = normalizeProfileName($paket);

    $stmt = $conn->prepare("SELECT harga FROM paket WHERE LOWER(REPLACE(REPLACE(REPLACE(durasi,' ',''),'-',''),'_','')) = ? LIMIT 1");
    if (!$stmt) {
        throw new RuntimeException("Prepare failed (hargaByPaket): " . $conn->error);
    }

    $stmt->bind_param("s", $n);
    $stmt->execute();
    $res = $stmt->get_result();
    $harga = null;

    if ($res && ($row = $res->fetch_assoc())) {
        $harga = (int)$row['harga'];
    }

    $stmt->close();
    return $harga;
}

/* =======================
   AMBIL SEMUA PROFIL
======================= */
$profiles = [];
$resProfiles = $conn->query("
    SELECT DISTINCT groupname
    FROM (
        SELECT groupname FROM radgroupcheck
        UNION
        SELECT groupname FROM radgroupreply
    ) g
    WHERE groupname IS NOT NULL AND groupname <> ''
    ORDER BY groupname
");
if ($resProfiles) {
    while ($row = $resProfiles->fetch_assoc()) {
        $profiles[] = $row['groupname'];
    }
}

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

        $groupname = null;
        $normalizedInput = normalizeProfileName($paket);

        foreach ($profiles as $p) {
            if (normalizeProfileName($p) === $normalizedInput) {
                $groupname = $p;
                break;
            }
        }

        if (empty($groupname) && in_array($paket, $profiles, true)) {
            $groupname = $paket;
        }

        if (empty($groupname)) {
            throw new RuntimeException("Paket tidak valid / tidak terdaftar pada profile.");
        }

        $paket = $groupname;

        $hargaVoucher = hargaByPaket($conn, $paket);
        if ($hargaVoucher === null) {
            throw new RuntimeException("Harga paket tidak ditemukan di tabel paket.");
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

            $stmtAssign->bind_param("ss", $user, $groupname);
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

        $msg = "Voucher berhasil dibuat dan tersinkron ke profile '$groupname'";
    } catch (Throwable $e) {
        if ($conn->errno === 0) {
            // no-op for connection-level check
        }
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

        $stmtCheckPaket = $conn->prepare("SELECT harga FROM paket WHERE LOWER(REPLACE(REPLACE(REPLACE(durasi,' ',''),'-',''),'_','')) = ? LIMIT 1");
        if (!$stmtCheckPaket) throw new RuntimeException("Prepare failed (check paket): " . $conn->error);

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

            $stmtDupUnion->bind_param("ss", $username, $username);
            $stmtDupUnion->execute();
            $resDup = $stmtDupUnion->get_result();
            if ($resDup && $resDup->fetch_assoc()) {
                $skipped++;
                continue;
            }

            $paketNormalized = normalizeProfileName($paketCsvRaw);
            $stmtCheckPaket->bind_param("s", $paketNormalized);
            $stmtCheckPaket->execute();
            $resPaketCsv = $stmtCheckPaket->get_result();
            $paketRow = $resPaketCsv ? $resPaketCsv->fetch_assoc() : null;

            if (!$paketRow) {
                $skipped++;
                continue;
            }

            $hargaCsv = (int)$paketRow['harga'];
            $stmtInsertVoucher->bind_param("sssis", $username, $password, $paketCsvRaw, $hargaCsv, $statusBaru);
            if (!$stmtInsertVoucher->execute()) {
                throw new RuntimeException("Execute failed (insert voucher import): " . $stmtInsertVoucher->error);
            }
            $inserted++;
        }

        fclose($handle);
        $stmtDupUnion->close();
        $stmtCheckPaket->close();
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

        $stmtDelVoucher = $conn->prepare("DELETE FROM voucher WHERE username = ?");
        $stmtDelRadcheck = $conn->prepare("DELETE FROM radcheck WHERE username = ?");
        $stmtDelRadusergroup = $conn->prepare("DELETE FROM radusergroup WHERE username = ?");
        $stmtDelRadreply = $conn->prepare("DELETE FROM radreply WHERE username = ?");
        $stmtDelRadpostauth = $conn->prepare("DELETE FROM radpostauth WHERE username = ?");
        $stmtDelUserbillinfo = $conn->prepare("DELETE FROM userbillinfo WHERE username = ?");
        $stmtDelUserinfo = $conn->prepare("DELETE FROM userinfo WHERE username = ?");
        $stmtDelRadacct = $conn->prepare("DELETE FROM radacct WHERE username = ?");

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

        if ($stmtDelVoucher) $stmtDelVoucher->close();
        if ($stmtDelRadcheck) $stmtDelRadcheck->close();
        if ($stmtDelRadusergroup) $stmtDelRadusergroup->close();
        if ($stmtDelRadreply) $stmtDelRadreply->close();
        if ($stmtDelRadpostauth) $stmtDelRadpostauth->close();
        if ($stmtDelUserbillinfo) $stmtDelUserbillinfo->close();
        if ($stmtDelUserinfo) $stmtDelUserinfo->close();
        if ($stmtDelRadacct) $stmtDelRadacct->close();

        $msg = $deletedCount . " voucher berhasil dihapus (beserta data terkait selain log)";
    }
}

/* =======================
   AMBIL DATA
======================= */
$defaultPaket = $profiles[0] ?? '4 jam';
$paket = $_POST['paket'] ?? $defaultPaket;
$data = null;
$stmtPaket = $conn->prepare("SELECT * FROM paket WHERE durasi = ? LIMIT 1");
if (!$stmtPaket) {
    throw new RuntimeException("Prepare failed (paket detail): " . $conn->error);
}
$stmtPaket->bind_param("s", $paket);
$stmtPaket->execute();
$resPaket = $stmtPaket->get_result();
$data = $resPaket ? $resPaket->fetch_assoc() : null;
$stmtPaket->close();
$harga = $data['harga'] ?? null;

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
?>

<!DOCTYPE html>
<html>

<head>
    <title>Voucher</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>

<body>
    <?php include "sidebar.php"; ?>

    <div class="content">
        <div class="container-fluid px-4">
            <?php if (!empty($msg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($msg) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="content-header bg-white shadow-sm p-4 mb-4 rounded">
                <h5 class="mb-3">Generate Voucher</h5>
                <form method="POST" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Paket</label>
                        <select name="paket" class="form-select">
                            <?php if (!empty($profiles)): ?>
                                <?php foreach ($profiles as $profileName): ?>
                                    <option value="<?= htmlspecialchars($profileName) ?>" <?= $paket === $profileName ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($profileName) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="4 jam" <?= $paket === '4 jam' ? 'selected' : '' ?>>4 jam</option>
                                <option value="5 jam" <?= $paket === '5 jam' ? 'selected' : '' ?>>5 jam</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jumlah</label>
                        <input type="number" name="jumlah" class="form-control" required min="1">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button name="generate" class="btn btn-primary">Generate</button>
                    </div>
                </form>
            </div>

            <div class="content-header bg-white shadow-sm p-4 mb-4 rounded">
                <h5 class="mb-3">Import CSV</h5>
                <div class="mb-3">
                    <small class="text-muted">Format: username,password,paket</small>
                </div>
                <form method="POST" enctype="multipart/form-data" class="row g-3">
                    <div class="col-md-8">
                        <input type="file" name="csv" class="form-control" accept=".csv" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button name="import" class="btn btn-success">Import</button>
                    </div>
                </form>
            </div>

            <div class="content-header bg-white shadow-sm p-4 mb-4 rounded">
                <h5>Data Voucher</h5>
            </div>

            <form method="POST" onsubmit="return confirm('Yakin ingin menghapus voucher yang dipilih? Data terkait juga akan dihapus (kecuali log).');">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="toggle-password">
                        <label class="form-check-label" for="toggle-password">Tampilkan Password</label>
                    </div>
                    <button type="submit" name="delete_selected" class="btn btn-danger btn-sm">
                        Hapus Terpilih
                    </button>
                </div>

                <div class="table-scroll">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 40px;">
                                    <input type="checkbox" id="select-all">
                                </th>
                                <th>No</th>
                                <th>Username</th>
                                <th>Password</th>
                                <th>Paket</th>
                                <th>Harga</th>
                                <th>Status</th>
                                <th>Dibuat</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = $offset + 1; ?>
                            <?php foreach ($vouchers as $v): ?>
                                <tr>
                                    <td>
                                        <input
                                            type="checkbox"
                                            class="voucher-checkbox"
                                            name="selected_vouchers[]"
                                            value="<?= htmlspecialchars($v['username']) ?>">
                                    </td>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($v['username']) ?></td>
                                    <td>
                                        <?php $plainPassword = (string)($v['password'] ?? ''); ?>
                                        <code
                                            class="password-cell"
                                            data-masked="<?= htmlspecialchars(str_repeat('*', max(6, strlen($plainPassword)))) ?>"
                                            data-plain="<?= htmlspecialchars($plainPassword) ?>">
                                            <?= htmlspecialchars(str_repeat('*', max(6, strlen($plainPassword)))) ?>
                                        </code>
                                    </td>
                                    <td><?= htmlspecialchars($v['paket']) ?></td>
                                    <td>Rp <?= number_format($v['harga']) ?></td>
                                    <td><?= htmlspecialchars($v['status'] ?? '-') ?></td>
                                    <td><?= date('d/m H:i', strtotime($v['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
        </div>
    </div>

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
</body>

</html>
