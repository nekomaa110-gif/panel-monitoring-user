<?php include __DIR__ . "/layout/header.php"; ?>

<?php include __DIR__ . "/layout/sidebar.php"; ?>

<?php include __DIR__ . "/layout/navbar.php"; ?>

<div class="content p-4">

    <?php if ($msg) { ?>
        <div class="alert alert-info"><?php echo $msg ?></div>
    <?php } ?>

    <div class="row">

        <!-- TAMBAH PROFIL -->
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">

                    <h5>Tambah Profil</h5>

                    <form method="POST">

                        <input type="text" name="profile" class="form-control mb-2" placeholder="Nama Profil" required>

                        <input type="number" name="simu" class="form-control mb-2" placeholder="Simultaneous (1)">

                        <input type="text" name="mtk" class="form-control mb-2" placeholder="Mikrotik Group (paket-5M)">

                        <button class="btn btn-primary w-100" name="add">
                            Simpan
                        </button>

                    </form>

                </div>
            </div>
        </div>

        <!-- LIST -->
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">

                    <h5>Daftar Profil</h5>

                    <table class="table table-bordered">
                        <thead class="table-dark">
                            <tr>
                                <th>No</th>
                                <th>Nama Profil</th>
                                <th width="120">Aksi</th>
                            </tr>
                        </thead>

                        <tbody>

                        <?php $no = 1; ?>
                        <?php while ($row = $q->fetch_assoc()) { ?>

                            <tr>
                                <td><?php echo $no++; ?></td>
                                <td><?php echo htmlspecialchars($row['groupname']); ?></td>
                                <td>
                                    <a href="edit_profile?name=<?php echo urlencode($row['groupname']); ?>"
                                       class="btn btn-warning btn-sm mb-1">
                                        Edit
                                    </a>
                                    <a href="?hapus=<?php echo urlencode($row['groupname']); ?>"
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Yakin hapus?')">
                                        Hapus
                                    </a>
                                </td>
                            </tr>

                        <?php } ?>

                        </tbody>

                    </table>

                </div>
            </div>
        </div>

    </div>

</div>

<?php include __DIR__ . "/layout/footer.php"; ?>
