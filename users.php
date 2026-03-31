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
    <b>Pelanggan</b>
    </nav>

    <div class="content p-4">

        <h3 class="mb-4">Daftar Pelanggan dan Status</h3>

        <div class="d-flex justify-content-between mb-3">

            <ul class="nav nav-tabs">

                <li class="nav-item">
                    <a class="nav-link <?php if ($filter == "") echo "active"; ?>"
                        href="users.php?search=<?php echo $search; ?>">
                        Semua
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php if ($filter == "online") echo "active"; ?>"
                        href="users.php?filter=online&search=<?php echo $search; ?>">
                        Online
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php if ($filter == "expired") echo "active"; ?>"
                        href="users.php?filter=expired&search=<?php echo $search; ?>">
                        Kadaluarsa
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php if ($filter == "disabled") echo "active"; ?>"
                        href="users.php?filter=disabled&search=<?php echo $search; ?>">
                        Dinonaktifkan
                    </a>
                </li>

            </ul>

            <form method="GET" class="d-flex">

                <input type="hidden" name="filter" value="<?php echo $filter; ?>">

                <input
                    type="text"
                    name="search"
                    class="form-control me-2"
                    placeholder="Cari user..."
                    value="<?php echo $search; ?>">

                <button class="btn btn-primary">
                    Cari
                </button>

            </form>

        </div>

        <div class="table-scroll">

            <table class="table table-striped table-hover text-center">

                <thead class="table-dark">

                    <tr>
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

                            <td><?php echo $r['username']; ?></td>

                            <td><?php echo $r['password']; ?></td>

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

                            <td colspan="6">

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