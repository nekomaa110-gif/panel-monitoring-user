<?php
require "auth.php";
require "config/db.php";

$search = $_GET['search'] ?? "";
$filter = $_GET['filter'] ?? "";

/* USER ONLINE */

$onlineUsers = [];

$online_q = $conn->query("
SELECT DISTINCT username
FROM radacct
WHERE acctstoptime IS NULL
");

while ($o = $online_q->fetch_assoc()) {
    $onlineUsers[] = $o['username'];
}

/* AMBIL DATA USER */

$q = $conn->query("
SELECT
u.username,
MAX(CASE WHEN rc.attribute='Cleartext-Password' THEN rc.value END) AS password,
MAX(CASE WHEN rc.attribute='Expiration' THEN rc.value END) AS expiration,
MAX(rug.groupname) AS profile

FROM
(
SELECT DISTINCT username FROM radcheck
UNION
SELECT DISTINCT username FROM radusergroup
) u

LEFT JOIN radcheck rc ON u.username = rc.username
LEFT JOIN radusergroup rug ON u.username = rug.username

WHERE LOWER(u.username) LIKE LOWER('%$search%')
  AND u.username NOT LIKE '5K%'

GROUP BY u.username
ORDER BY u.username
");
?>

<!DOCTYPE html>

<html>

<head>

    <title>Pelanggan</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">

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
    </style>

</head>

<body>

    <?php include "sidebar.php"; ?>

    <nav class="navbar navbar-light bg-white shadow-sm px-4 d-flex justify-content-between">

        <b>Pelanggan</b>

        <a href="logout.php" class="btn btn-danger d-flex align-items-center gap-2">
            Logout
        </a>

    </nav>
    <div class="content p-4">

        <div class="customers-header mb-4">
            <h3 class="mb-1">Daftar Pelanggan dan Status</h3>
        </div>

        <div class="customers-toolbar mb-3">
            <div class="customers-toolbar-inner">
                <ul class="nav customer-tabs customers-submenu">
                    <li class="nav-item">
                        <a class="nav-link <?php if ($filter == "") echo "active"; ?>"
                            href="users.php?search=<?php echo urlencode($search); ?>">
                            Semua
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php if ($filter == "online") echo "active"; ?>"
                            href="users.php?filter=online&search=<?php echo urlencode($search); ?>">
                            Online
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php if ($filter == "expired") echo "active"; ?>"
                            href="users.php?filter=expired&search=<?php echo urlencode($search); ?>">
                            Kadaluarsa
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php if ($filter == "disabled") echo "active"; ?>"
                            href="users.php?filter=disabled&search=<?php echo urlencode($search); ?>">
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

                        $isOnline = in_array($r['username'], $onlineUsers);

                        /* FILTER TAB */

                        if ($search == "") {

                            if ($filter == "online" && !$isOnline) continue;
                            if ($filter == "expired" && $status != "EXPIRED") continue;
                            if ($filter == "disabled" && $status != "NONAKTIF") continue;
                        }

                        $found = true;
                    ?>

                        <tr>

                            <td><?php echo $no++; ?></td>
                            <td><?php echo $r['username']; ?></td>

                            <td><?php echo $r['password'] !== null && $r['password'] !== '' ? $r['password'] : '-'; ?></td>

                            <td><?php echo $r['profile']; ?></td>

                            <td><?php echo $r['expiration']; ?></td>

                            <td>

                                <?php

                                if ($filter == "online" && $search == "") {
                                    echo '<span class="badge bg-primary">ONLINE</span>';
                                } else {

                                    if ($status == "NONAKTIF") {
                                        echo '<span class="badge bg-danger">⦸</span>';
                                    } elseif ($status == "EXPIRED") {
                                        echo '<span class="badge bg-warning text-dark">✖</span>';
                                    } else {
                                        echo '<span class="badge bg-success">✔</span>';
                                    }
                                }

                                ?>

                            </td>

                            <td>

                                <?php if ($status == "NONAKTIF") { ?>

                                    <a
                                        href="actions/enable.php?user=<?php echo $r['username']; ?>&search=<?php echo $search; ?>&filter=<?php echo $filter; ?>"
                                        class="btn btn-sm btn-success">
                                        Aktifkan </a>

                                <?php } else { ?>

                                    <a
                                        href="actions/disable.php?user=<?php echo $r['username']; ?>&search=<?php echo $search; ?>&filter=<?php echo $filter; ?>"
                                        class="btn btn-sm btn-danger">
                                        Nonaktifkan </a>

                                <?php } ?>

                                <a
                                    href="actions/delete.php?user=<?php echo $r['username']; ?>&search=<?php echo $search; ?>&filter=<?php echo $filter; ?>"
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

</body>

</html>