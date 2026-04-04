<?php include __DIR__ . "/layout/header.php"; ?>

<!-- SIDEBAR -->
<?php include __DIR__ . "/layout/sidebar.php"; ?>

<!-- NAVBAR -->
<?php include __DIR__ . "/layout/navbar.php"; ?>

<!-- CONTENT -->

<div class="content">

    <div class="row g-4 text-center">

        <a href="users" class="col-md-3 text-decoration-none">

            <div class="card dashboard-card">

                <div class="card-body">

                    <h3 class="text-primary"><?php echo $total ?></h3>

                    Total User

                </div>

            </div>

        </a>

        <a href="users?filter=online" class="col-md-3 text-decoration-none">

            <div class="card dashboard-card">

                <div class="card-body">

                    <h3 class="text-success"><?php echo $online ?></h3>

                    User Online

                </div>

            </div>

        </a>

        <a href="users?filter=expired" class="col-md-3 text-decoration-none">

            <div class="card dashboard-card">

                <div class="card-body">

                    <h3 class="text-warning"><?php echo $expired ?></h3>

                    Kadaluarsa

                </div>

            </div>

        </a>

        <a href="users?filter=disabled" class="col-md-3 text-decoration-none">

            <div class="card dashboard-card">

                <div class="card-body">

                    <h3 class="text-danger"><?php echo $disabled ?></h3>

                    Dinonaktifkan

                </div>

            </div>

        </a>

    </div>

    <div class="row mt-5">

        <div class="col-md-6">

            <h5>User Online Sekarang</h5>

            <div class="table-scroll">

                <table class="table table-striped">

                    <thead class="table-dark">

                        <tr>

                            <th>No</th>
                            <th>User</th>
                            <th>IP</th>
                            <th>Login Time</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php $noOnline = 1; ?>
                        <?php while ($o = $online_list->fetch_assoc()) { ?>

                            <tr>

                                <td><?php echo $noOnline++; ?></td>
                                <td><?php echo $o['username'] ?></td>
                                <td><?php echo $o['framedipaddress'] ?></td>
                                <td><?php echo $o['acctstarttime'] ?></td>

                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>

        <div class="col-md-6">

            <h5>Login Terakhir</h5>

            <div class="table-scroll">

                <table class="table table-striped">

                    <thead class="table-dark">

                        <tr>

                            <th>No</th>
                            <th>User</th>
                            <th>NAS</th>
                            <th>Login Time</th>

                        </tr>

                    </thead>

                    <tbody>

                        <?php $noLog = 1; ?>
                        <?php while ($r = $log->fetch_assoc()) { ?>

                            <tr>

                                <td><?php echo $noLog++; ?></td>
                                <td><?php echo $r['username'] ?></td>
                                <td><?php echo $r['nasipaddress'] ?></td>
                                <td><?php echo $r['acctstarttime'] ?></td>

                            </tr>

                        <?php } ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>

<?php include __DIR__ . "/layout/footer.php"; ?>
