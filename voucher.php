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

    // sinkronisasi alias lama -> nama profil
    if ($n === '5jam') {
        return '5jam';
    }

    if ($n === '7hari' || $n === 'mingguan') {
        return 'mingguan';
    }

    return $n;
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

    // cari groupname yang paling cocok untuk paket/profil terpilih
    $groupname = null;
    $normalizedInput = normalizeProfileName($paket);

    foreach ($profiles as $p) {
        if (normalizeProfileName($p) === $normalizedInput) {
            $groupname = $p;
            break;
        }
    }

    // fallback: jika user pilih langsung nama group
    if (empty($groupname) && in_array($paket, $profiles, true)) {
        $groupname = $paket;
    }

    // gunakan nama profil DB sebagai paket jika ketemu, agar sinkron
    if (!empty($groupname)) {
        $paket = $groupname;
    }

    for ($i = 0; $i < $jumlah; $i++) {
        $user = "5K" . rand(1, 9) . chr(rand(65, 90)) . chr(rand(97, 122));
        $pass = rand(1000, 9999);

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
            $paket   = $data[2] ?? 'unknown';

            if ($username && $password) {
                $stmt = $conn->prepare("INSERT INTO voucher (username,password,paket,harga) VALUES (?,?,?,?)");
                $harga = 0;
                $stmt->bind_param("sssi", $username, $password, $paket, $harga);
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

            <!-- Generate Form -->
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
                                <option value="5 jam" <?= $paket === '5 jam' ? 'selected' : '' ?>>5 jam</option>
                                <option value="mingguan" <?= $paket === 'mingguan' ? 'selected' : '' ?>>mingguan</option>
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


            <!-- Import CSV -->
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
            <!-- Data Voucher -->
            <div class="content-header bg-white shadow-sm p-4 mb-4 rounded">
                <h5>Data Voucher</h5>
            </div>

            <div class="table-scroll">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Password</th>
                            <th>Paket</th>
                            <th>Harga</th>
                            <th>Status</th>
                            <th>Dibuat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vouchers as $v): ?>
                            <tr>
                                <td><?= $v['id'] ?></td>
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