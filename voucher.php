<?php
require "auth.php";
require "config/db.php";

/* =======================
   GENERATE VOUCHER
======================= */
if (isset($_POST['generate'])) {
    $paket = $_POST['paket'];
    $jumlah = (int)$_POST['jumlah'];

    for ($i = 0; $i < $jumlah; $i++) {
        $user = "5K" . rand(1, 9) . chr(rand(65, 90)) . chr(rand(97, 122));
        $pass = rand(1000, 9999);

        $stmt = $conn->prepare("INSERT INTO voucher (username,password,paket,harga) VALUES (?,?,?,?)");
        $harga = 5000;
        $stmt->bind_param("sssi", $user, $pass, $paket, $harga);
        $stmt->execute();
    }

    $msg = "Voucher berhasil dibuat";
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

$paket = $_POST['paket'] ?? '4jam';
$q = $conn->query("SELECT * FROM paket WHERE durasi='$paket'");
$data = $q->fetch_assoc();
$harga = $data['harga'];
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
                            <option value="4jam">4 jam</option>
                            <option value="5jam">5 jam</option>
                            <option value="7hari">7 hari</option>
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