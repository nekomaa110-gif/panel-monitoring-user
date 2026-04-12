<?php include __DIR__ . "/layout/header.php"; ?>
<?php include __DIR__ . "/layout/sidebar.php"; ?>
<?php include __DIR__ . "/layout/navbar.php"; ?>

<div class="content p-4">

    <h3 class="mb-4">Admin Activity Log</h3>

    <div class="card shadow-sm">
        <div class="card-body p-0">

            <table class="table table-hover table-striped mb-0">
                <thead class="table-dark text-center">
                    <tr>
                        <th style="width:60px;">No</th>
                        <th>Activity</th>
                    </tr>
                </thead>

                <tbody>

                <?php 
                $no = 1;

                if (!empty($logs)) {
                    foreach ($logs as $l) { ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td style="font-family: monospace;">
                                <?= htmlspecialchars($l) ?>
                            </td>
                        </tr>
                <?php } } else { ?>

                    <tr>
                        <td colspan="2" class="text-center text-muted py-4">
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