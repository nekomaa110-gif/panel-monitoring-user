<?php
$extraCss = '
<style>
    .table-scroll {
        max-height: 550px;
        overflow-y: auto;
    }

    .table td {
        vertical-align: middle;
    }

    .badge {
        font-size: 14px;
        padding: 6px 10px;
    }

    .btn-action-uniform {
        width: 96px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 0;
        line-height: 1;
    }

    .status-icon-uniform {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
        line-height: 1;
        font-size: 16px;
    }
</style>
';
?>

<?php include __DIR__ . "/layout/header.php"; ?>

<?php include __DIR__ . "/layout/sidebar.php"; ?>

<?php include __DIR__ . "/layout/navbar.php"; ?>

<div class="content p-4">

    <div class="customers-header mb-4">
        <h3 class="mb-1">Daftar Pelanggan dan Status</h3>
    </div>

    <div class="customers-toolbar mb-3">
        <div class="customers-toolbar-inner">
            <ul class="nav customer-tabs customers-submenu">
                <li class="nav-item">
                    <a class="nav-link <?php if ($filter == "") echo "active"; ?>"
                    href="users?search=<?php echo urlencode($search); ?>">
                        Semua
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php if ($filter == "online") echo "active"; ?>"
                    href="users?filter=online&search=<?php echo urlencode($search); ?>">
                        Online
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php if ($filter == "expired") echo "active"; ?>"
                    href="users?filter=expired&search=<?php echo urlencode($search); ?>">
                        Kadaluarsa
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php if ($filter == "disabled") echo "active"; ?>"
                    href="users?filter=disabled&search=<?php echo urlencode($search); ?>">
                        Dinonaktifkan
                    </a>
                </li>
            </ul>

            <form method="GET" class="customer-search-form">
                <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">

                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Cari user..."
                    value="<?php echo htmlspecialchars($search); ?>">

                <button class="btn btn-primary" type="submit">
                    Cari
                </button>
            </form>
        </div>
    </div>

    <div class="d-flex justify-content-start align-items-center mb-2">
        <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="toggle-password">
            <label class="form-check-label" for="toggle-password">Tampilkan Password</label>
        </div>
    </div>

    <div class="table-scroll customers-table-wrap">

        <table class="table table-striped text-center">

            <thead class="table-dark">

                <tr>
                    <th>No</th>
                    <th>Username</th>
                    <th>Password</th>
                    <th>Profile</th>
                    <th>Masa Aktif</th>
                    <th>Status</th>
                    <th>Tindakan</th>
                </tr>

            </thead>

            <tbody>

                <?php

                $found = false;
                $no = 1;

                while ($r = $q->fetch_assoc()) {

                    $exp_string = $r['expiration'];
                    $exp = strtotime($exp_string);
                    $now = time();

                    $status = "AKTIF";

                    if ($r['profile'] == "daloRADIUS-Disabled-Users") {
                        $status = "NONAKTIF";
                    } elseif (!empty($exp_string) && $exp < $now) {
                        $status = "EXPIRED";
                    }

                    $isOnline = isset($onlineUsers[$r['username']]);

                    /* FILTER TAB */
                    if ($filter == "online" && !$isOnline) continue;
                    if ($filter == "expired" && $status != "EXPIRED") continue;
                    if ($filter == "disabled" && $status != "NONAKTIF") continue;

                    $found = true;
                ?>

                    <tr>

                        <td><?php echo $no++; ?></td>
                        <td><?php echo $r['username']; ?></td>

                        <td>
                            <?php
                            $plainPassword = (string)($r['password'] ?? '');
                            if ($plainPassword === '') {
                                echo '-';
                            } else {
                                $maskedPassword = str_repeat('*', max(6, strlen($plainPassword)));
                            ?>
                                <code
                                    class="password-cell"
                                    data-masked="<?php echo htmlspecialchars($maskedPassword); ?>"
                                    data-plain="<?php echo htmlspecialchars($plainPassword); ?>">
                                    <?php echo htmlspecialchars($maskedPassword); ?>
                                </code>
                            <?php } ?>
                        </td>

                        <td><?php echo $r['profile']; ?></td>

                        <td><?php echo !empty($r['expiration']) && strtotime($r['expiration']) !== false ? date('d M Y H:i', strtotime($r['expiration'])) : '-'; ?></td>

                        <td>

                            <?php

                            if ($filter == "online" && $search == "") {
                                echo '<span class="badge bg-primary">ONLINE</span>';
                            } else {

                                if ($status == "NONAKTIF") {
                                    echo '<span class="badge bg-danger status-icon-uniform">⦸</span>';
                                } elseif ($status == "EXPIRED") {
                                    echo '<span class="badge bg-warning text-dark status-icon-uniform">✖</span>';
                                } else {
                                    echo '<span class="badge bg-success status-icon-uniform">✔</span>';
                                }
                            }

                            ?>

                        </td>

                        <td>

                            <?php if ($status == "NONAKTIF") { ?>

                                <a
                                    href="actions/enable?user=<?php echo $r['username']; ?>&search=<?php echo $search; ?>&filter=<?php echo $filter; ?>"
                                    class="btn btn-sm btn-success btn-action-uniform">
                                    Aktifkan </a>

                            <?php } else { ?>

                                <a
                                    href="actions/disable?user=<?php echo $r['username']; ?>&search=<?php echo $search; ?>&filter=<?php echo $filter; ?>"
                                    class="btn btn-sm btn-danger btn-action-uniform">
                                    Nonaktifkan </a>

                            <?php } ?>

                            <a
                                href="actions/delete?user=<?php echo $r['username']; ?>&search=<?php echo $search; ?>&filter=<?php echo $filter; ?>"
                                onclick="return confirm('Yakin hapus user ini?')"
                                class="btn btn-sm btn-dark">
                                Hapus </a>

                        </td>

                    </tr>

                <?php } ?>

                <?php if (!$found) { ?>

                    <tr>

                        <td colspan="7">

                            <div class="alert alert-warning m-2">
                                User tidak ditemukan
                            </div>

                        </td>

                    </tr>

                <?php } ?>

            </tbody>

        </table>

    </div>

</div>

<?php
$extraJs = <<<'JS'
<script>
    const togglePassword = document.getElementById('toggle-password');
    const passwordCells = document.querySelectorAll('.password-cell');

    if (togglePassword) {
        togglePassword.addEventListener('change', function() {
            const showPlain = togglePassword.checked;
            passwordCells.forEach(el => {
                el.textContent = showPlain ? el.dataset.plain : el.dataset.masked;
            });
        });
    }
</script>
JS;
?>

<?php include __DIR__ . "/layout/footer.php"; ?>
