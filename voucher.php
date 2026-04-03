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

function hargaByPaket(mysqli $conn, string $paket): int
{
    $n = normalizeProfileName($paket);

    if ($n === 'mingguan' || strpos($n, '7hari') !== false) {
        return 55000;
    }

    if ($n === '5jam') {
        return 5000;
    }

    if ($n === '4jam') {
        return 5000;
    }

    $harga = 5000;
    $stmt = $conn->prepare("SELECT harga FROM paket WHERE LOWER(REPLACE(REPLACE(REPLACE(durasi,' ',''),'-',''),'_','')) = ? LIMIT 1");
    if ($stmt) {
        $key = $n;
        $stmt->bind_param("s", $key);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && ($row = $res->fetch_assoc())) {
            $harga = (int)$row['harga'];
        }
        $stmt->close();
    }
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
    $paket = trim($_POST['paket']);
    $jumlah = (int)$_POST['jumlah'];

    $groupname = null;
    $stmtGroup = $conn->prepare("
        SELECT groupname
        FROM (
            SELECT groupname FROM radgroupcheck
            UNION
            SELECT groupname FROM radgroupreply
        ) g
        WHERE REPLACE(REPLACE(LOWER(groupname), ' ', ''), '-', '') = REPLACE(REPLACE(LOWER(?), ' ', ''), '-', '')
        LIMIT 1
    ");
    $stmtGroup->bind_param("s", $paket);
    $stmtGroup->execute();
    $resGroup = $stmtGroup->get_result();
    if ($resGroup && $resGroup->num_rows > 0) {
        $groupname = $resGroup->fetch_assoc()['groupname'];
    }

    for ($i = 0; $i < $jumlah; $i++) {
        $user = "5K" . rand(1, 9) . chr(rand(65, 90)) . chr(rand(97, 122));
        $pass = (string) rand(1000, 9999);

        $stmt = $conn->prepare("INSERT INTO voucher (username,password,paket,harga) VALUES (?,?,?,?)");
        $harga = 5000;
        $stmt->bind_param("sssi", $user, $pass, $paket, $harga);
        $stmt->execute();

        // auto assign voucher user ke group radius jika profile ditemukan
        if (!empty($groupname)) {
            $stmtAssign = $conn->prepare("
                INSERT INTO radusergroup (username,groupname,priority)
                VALUES (?,?,0)
                ON DUPLICATE KEY UPDATE groupname=VALUES(groupname), priority=VALUES(priority)
            ");
            $stmtAssign->bind_param("ss", $user, $groupname);
            $stmtAssign->execute();
        }
    }

    $msg = !empty($groupname)
        ? "Voucher berhasil dibuat dan tersinkron ke profile '$groupname'"
        : "Voucher berhasil dibuat (profil RADIUS belum ditemukan untuk paket '$paket')";
}

/* =======================
   IMPORT CSV
======================= */
if (isset($_POST['import'])) {
    if ($_FILES['csv']['error'] == 0) {
        $file = $_FILES['csv']['tmp_name'];
        $handle = fopen($file, "r");

        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $username = $data[0] ?? '';
            $password = $data[1] ?? '';
            $paketCsv = $data[2] ?? 'unknown';

            if ($username && $password) {
                $stmt = $conn->prepare("INSERT INTO voucher (username,password,paket,harga) VALUES (?,?,?,?)");
                $harga = 0;
                $stmt->bind_param("sssi", $username, $password, $paketCsv, $harga);
                $stmt->execute();
            }
        }

        fclose($handle);
        $msg = "Import CSV selesai";
    } else {
        $msg = "Upload gagal";
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
$q = $conn->query("SELECT * FROM paket WHERE durasi='$paket'");
$data = $q ? $q->fetch_assoc() : null;
$harga = $data['harga'] ?? 5000;
$vouchers = $conn->query("SELECT * FROM voucher ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
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
                    <div></div>
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
                            <?php $no = count($vouchers); ?>
                            <?php foreach (array_reverse($vouchers) as $v): ?>
                                <tr>
                                    <td>
                                        <input
                                            type="checkbox"
                                            class="voucher-checkbox"
                                            name="selected_vouchers[]"
                                            value="<?= htmlspecialchars($v['username']) ?>">
                                    </td>
                                    <td><?= $no-- ?></td>
                                    <td><?= htmlspecialchars($v['username']) ?></td>
                                    <td><code><?= htmlspecialchars($v['password']) ?></code></td>
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
    </script>
</body>

</html>
