<?php include __DIR__ . "/layout/header.php"; ?>

<?php include __DIR__ . "/layout/sidebar.php"; ?>

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
                        <?php foreach ($profiles as $profileName): ?>
                            <option value="<?= htmlspecialchars($profileName) ?>" <?= $paket === $profileName ? 'selected' : '' ?>>
                                <?= htmlspecialchars($profileName) ?>
                            </option>
                        <?php endforeach; ?>
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

<?php include __DIR__ . "/layout/footer.php"; ?>
