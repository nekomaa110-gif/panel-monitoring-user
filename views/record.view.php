<?php include __DIR__ . "/layout/header.php"; ?>
<?php include __DIR__ . "/layout/sidebar.php"; ?>
<?php include __DIR__ . "/layout/navbar.php"; ?>

<div class="content p-4">

    <h3 class="mb-4">Record Login Pertama</h3>

    <!-- FILTER -->
    <form method="GET" class="row g-2 mb-4">

        <div class="col-md-4">
            <input 
                type="text" 
                name="search" 
                class="form-control"
                placeholder="Cari username..."
                value="<?= htmlspecialchars($search) ?>">
        </div>

        <div class="col-md-3">
            <select name="month" class="form-control">
                <option value="">Semua Bulan</option>
                <?php 
                $bulan = [
                    1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',
                    5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',
                    9=>'September',10=>'Oktober',11=>'November',12=>'Desember'
                ];
                foreach ($bulan as $num => $nama) { ?>
                    <option value="<?= $num ?>" <?= ($month == $num ? 'selected' : '') ?>>
                        <?= $nama ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="col-md-3">
            <select name="year" class="form-control">
                <option value="">Semua Tahun</option>
                <?php 
                $currentYear = date('Y');
                for ($y=$currentYear; $y>=2020; $y--) { ?>
                    <option value="<?= $y ?>" <?= ($year == $y ? 'selected' : '') ?>>
                        <?= $y ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="col-md-2">
            <button class="btn btn-primary w-100">Filter</button>
        </div>

    </form>

    <!-- TABLE -->
    <div class="card shadow-sm">
        <div class="card-body p-0">

            <table class="table table-hover table-striped text-center mb-0">
                <thead class="table-dark">
                    <tr>
                        <th style="width:60px;">No</th>
                        <th>Username</th>
                        <th>Login</th>
                        <th>IP Address</th>
                    </tr>
                </thead>

                <tbody>

                <?php 
                $no = 1;

                if ($q && $q->num_rows > 0):
                    while ($r = $q->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>

                            <td>
                                <strong><?= htmlspecialchars($r['username']) ?></strong>
                            </td>

                            <td>
                                <?= date('d M Y H:i:s', strtotime($r['first_login'])) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($r['ip_address'] ?? '-') ?>
                            </td>
                        </tr>
                <?php endwhile; else: ?>

                    <tr>
                        <td colspan="4">
                            <div class="text-muted py-4">
                                Tidak ada data ditemukan
                            </div>
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>

        </div>
    </div>

</div>

<?php include __DIR__ . "/layout/footer.php"; ?>