<?php include __DIR__ . "/layout/header.php"; ?>
<?php include __DIR__ . "/layout/sidebar.php"; ?>
<?php include __DIR__ . "/layout/navbar.php"; ?>

<div class="content p-4">

    <h3 class="mb-4">Admin Activity Log</h3>

    <div class="card shadow-sm">
        <div class="card-body p-0">

            <table class="table table-hover table-striped mb-0 align-middle text-center">
                <thead class="table-dark">
                    <tr>
                        <th style="width:60px;">No</th>
                        <th>Waktu</th>
                        <th>Admin</th>
                        <th>Tindakan</th>
                        <th>User</th>
                        <th>Status</th>
                        <th>IP</th>
                    </tr>
                </thead>

                <tbody>

                <?php 
                $no = 1;

                if (!empty($logs) && is_array($logs)) {

                    foreach ($logs as $l) {

                        // VALIDASI DASAR
                        if (!is_string($l) || trim($l) === '') continue;

                        // PARSE AMAN
                        $parts = array_map('trim', explode('|', $l));

                        $time   = $parts[0] ?? '-';
                        $admin  = $parts[1] ?? '-';
                        $action = $parts[2] ?? '-';
                        $user   = $parts[3] ?? '-';
                        $status = $parts[4] ?? '-';
                        $ip     = $parts[5] ?? '-';

                        // NORMALISASI STATUS
                        $statusLower = strtolower($status);

                        if ($statusLower === 'success') {
                            $badge = 'success';
                        } elseif ($statusLower === 'gagal') {
                            $badge = 'danger';
                        } else {
                            $badge = 'secondary';
                        }
                ?>

                    <tr>
                        <td><?= $no++ ?></td>

                        <td>
                            <small><?= htmlspecialchars($time) ?></small>
                        </td>

                        <td>
                            <strong><?= htmlspecialchars($admin) ?></strong>
                        </td>

                        <td>
                            <span class="badge bg-dark">
                                <?= htmlspecialchars($action) ?>
                            </span>
                        </td>

                        <td>
                            <?= htmlspecialchars($user) ?>
                        </td>

                        <td>
                            <span class="badge bg-<?= $badge ?>">
                                <?= htmlspecialchars($status) ?>
                            </span>
                        </td>

                        <td>
                            <small><?= htmlspecialchars($ip) ?></small>
                        </td>
                    </tr>

                <?php 
                    } 
                } else { 
                ?>

                    <tr>
                        <td colspan="7" class="text-muted py-4">
                            Belum ada log admin
                        </td>
                    </tr>

                <?php } ?>

                </tbody>
            </table>

        </div>
    </div>

</div>

<?php include __DIR__ . "/layout/footer.php"; ?>