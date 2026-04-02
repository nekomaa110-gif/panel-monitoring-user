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

    if (strpos($n, '5jam') !== false || preg_match('/(^|[^0-9])5([^0-9]|$)/', $paket)) {
        return 5 * 3600;
    }

    return 4 * 3600;
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

    if (!empty($groupname)) {
        $paket = $groupname;
    }

    $hargaVoucher = 5000;
    $stmtHarga = $conn->prepare("SELECT harga FROM paket WHERE durasi=? LIMIT 1");
    if ($stmtHarga) {
        $stmtHarga->bind_param("s", $paket);
        $stmtHarga->execute();
        $resHarga = $stmtHarga->get_result();
        if ($resHarga && ($rowHarga = $resHarga->fetch_assoc())) {
            $hargaVoucher = (int)$rowHarga['harga'];
        }
        $stmtHarga->close();
    }

    for ($i = 0; $i < $jumlah; $i++) {
        $user = "5K" . rand(1, 9) . chr(rand(65, 90)) . chr(rand(97, 122));
        $pass = (string) rand(1000, 9999);

        $stmtVoucher = $conn->prepare("INSERT INTO voucher (username,password,paket,harga,status) VALUES (?,?,?,?,?)");
        $statusBaru = 'baru';
        $stmtVoucher->bind_param("sssis", $user, $pass, $paket, $hargaVoucher, $statusBaru);
        $stmtVoucher->execute();

        // Cleartext-Password
        $stmtDelPwd = $conn->prepare("DELETE FROM radcheck WHERE username=? AND attribute='Cleartext-Password'");
        $stmtDelPwd->bind_param("s", $user);
        $stmtDelPwd->execute();

        $stmtInsPwd = $conn->prepare("
            INSERT INTO radcheck (username,attribute,op,value)
            VALUES (?, 'Cleartext-Password', ':=', ?)
        ");
        $stmtInsPwd->bind_param("ss", $user, $pass);
        $stmtInsPwd->execute();

        // Session-Timeout 4/5 jam total pemakaian
        $sessionTimeout = (string) sessionTimeoutByPaket($paket);

        $stmtDelTimeout = $conn->prepare("DELETE FROM radcheck WHERE username=? AND attribute='Session-Timeout'");
        $stmtDelTimeout->bind_param("s", $user);
        $stmtDelTimeout->execute();

        $stmtInsTimeout = $conn->prepare("
            INSERT INTO radcheck (username,attribute,op,value)
            VALUES (?, 'Session-Timeout', ':=', ?)
        ");
        $stmtInsTimeout->bind_param("ss", $user, $sessionTimeout);
        $stmtInsTimeout->execute();

        // Expiration hangus 1 hari (23:59)
        $expiration = date("d M Y 23:59", strtotime("+1 day"));

        $stmtDelExp = $conn->prepare("DELETE FROM radcheck WHERE username=? AND attribute='Expiration'");
        $stmtDelExp->bind_param("s", $user);
        $stmtDelExp->execute();

        $stmtInsExp = $conn->prepare("
            INSERT INTO radcheck (username,attribute,op,value)
            VALUES (?, 'Expiration', ':=', ?)
        ");
        $stmtInsExp->bind_param("ss", $user, $expiration);
        $stmtInsExp->execute();

        // Group assignment
        if (!empty($groupname)) {
            $stmtDelGroup = $conn->prepare("DELETE FROM radusergroup WHERE username=?");
            $stmtDelGroup->bind_param("s", $user);
            $stmtDelGroup->execute();

            $stmtAssign = $conn->prepare("
                INSERT INTO radusergroup (username,groupname,priority)
                VALUES (?,?,0)
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
   AMBIL DATA
======================= */
$defaultPaket = $profiles[0] ?? '4 jam';
$paket = $_POST['paket'] ?? $defaultPaket;
$q = $conn->query("SELECT * FROM paket WHERE durasi='$paket'");
$data = $q ? $q->fetch_assoc() : null;
$harga = $data['harga'] ?? 5000;
$vouchers = $conn->query("SELECT * FROM voucher ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
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

            <div class="table-scroll">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
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
                        <?php $no = 1; ?>
                        <?php foreach ($vouchers as $v): ?>
                            <tr>
                                <td><?= $no++ ?></td>
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
