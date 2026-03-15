<?php
require "auth.php";
require "config/db.php";

$search = $_GET['search'] ?? "";
$filter = $_GET['filter'] ?? "";

/* USER ONLINE */
$onlineUsers = [];
$online_q = $conn->query("
SELECT username
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
SELECT username FROM radcheck
UNION
SELECT username FROM radusergroup
) u

LEFT JOIN radcheck rc ON u.username = rc.username
LEFT JOIN radusergroup rug ON u.username = rug.username

WHERE u.username LIKE '%$search%'

GROUP BY u.username
ORDER BY u.username
");
?>

<!DOCTYPE html>

<html>

<head>

    <title>Kelola Pelanggan</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">

</head>

<body>

    <!-- SIDEBAR -->

    <div class="sidebar">

        <h4>ZERO NET</h4>

        <a href="index.php">Dashboard</a> <a href="users.php">Pelanggan</a> <a href="adduser.php">Tambah User</a> <a href="logout.php">Logout</a>

    </div>

    <!-- NAVBAR -->

    <nav class="navbar navbar-light bg-white shadow-sm">
        <b>Kelola Pelanggan</b>
    </nav>

    <!-- CONTENT -->

    <div class="content">

        <h3 class="mb-4">Daftar Pelanggan</h3>

        <div class="d-flex justify-content-between mb-3">

            <ul class="nav nav-tabs">

                <li class="nav-item">
                    <a class="nav-link <?php if ($filter == "") echo "active"; ?>" href="users.php">Semua</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php if ($filter == "online") echo "active"; ?>" href="users.php?filter=online">Online</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php if ($filter == "expired") echo "active"; ?>" href="users.php?filter=expired">Expired</a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php if ($filter == "disabled") echo "active"; ?>" href="users.php?filter=disabled">Disabled</a>
                </li>

            </ul>

            <form method="GET" class="d-flex">

                <input type="text" name="search" class="form-control me-2" placeholder="Cari user..." value="<?php echo $search; ?>">

                <button class="btn btn-primary">Cari</button>

            </form>

        </div>

        <!-- TABLE -->

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

                    <?php while ($r = $q->fetch_assoc()) {

                        $exp_string = $r['expiration'];
                        $exp = strtotime($exp_string);
                        $now = time();

                        $status = "AKTIF";
                        $badge = "success";

                        if ($r['profile'] == "daloRADIUS-Disabled-Users") {
                            $status = "NONAKTIF";
                            $badge = "danger";
                        } elseif (!empty($exp_string)) {
                            if ($exp !== false && $exp < $now) {
                                $status = "EXPIRED";
                                $badge = "warning";
                            }
                        }

                        $isOnline = in_array($r['username'], $onlineUsers);

                        /* FILTER */

                        if ($filter == "online" && (!$isOnline || $status == "NONAKTIF")) continue;
                        if ($filter == "disabled" && $status != "NONAKTIF") continue;
                        if ($filter == "expired" && $status != "EXPIRED") continue;

                    ?>

                        <tr>

                            <td><?php echo $r['username']; ?></td>
                            <td><?php echo $r['password']; ?></td>
                            <td><?php echo $r['profile']; ?></td>
                            <td><?php echo $r['expiration']; ?></td>

                            <td>
                                <span class="badge bg-<?php echo $badge; ?>">
                                    <?php echo $status; ?>
                                </span>
                            </td>

                            <td>

                                <?php if ($r['profile'] == "daloRADIUS-Disabled-Users") { ?>

                                    <a href="actions/enable.php?user=<?php echo $r['username']; ?>" class="btn btn-sm btn-success">
                                        Aktifkan
                                    </a>

                                <?php } else { ?>

                                    <a href="actions/disable.php?user=<?php echo $r['username']; ?>" class="btn btn-sm btn-danger">
                                        Nonaktifkan
                                    </a>

                                <?php } ?>

                            </td>

                        </tr>

                    <?php } ?>

                </tbody>

            </table>

        </div>

    </div>

</body>

</html>