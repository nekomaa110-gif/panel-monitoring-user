<?php
require "auth.php";
require "config/db.php";

/* TOTAL USER */
$total = $conn->query("
SELECT COUNT(DISTINCT username) as t
FROM radcheck
")->fetch_assoc()['t'];

/* USER EXPIRED */
$expired = $conn->query("
SELECT COUNT(*) as t FROM radcheck
WHERE attribute='Expiration'
AND STR_TO_DATE(value,'%d %b %Y %H:%i') < NOW()
")->fetch_assoc()['t'];

/* USER DISABLED */
$disabled = $conn->query("
SELECT COUNT(*) as t FROM radusergroup
WHERE groupname='daloRADIUS-Disabled-Users'
")->fetch_assoc()['t'];

/* USER ONLINE */
$online = $conn->query("
SELECT COUNT(*) as t
FROM radacct
WHERE acctstoptime IS NULL
")->fetch_assoc()['t'];

/* USER ONLINE LIST */
$online_list = $conn->query("
SELECT username,framedipaddress,acctstarttime
FROM radacct
WHERE acctstoptime IS NULL
ORDER BY acctstarttime DESC
");

/* LOGIN TERAKHIR */
$log = $conn->query("
SELECT username,nasipaddress,acctstarttime
FROM radacct
ORDER BY acctstarttime DESC
LIMIT 20
");
?>

<!DOCTYPE html>

<html>

<head>
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">

</head>

<body>

    <!-- SIDEBAR -->
    <?php include "sidebar.php"; ?>
    <!-- NAVBAR -->
    <nav class="navbar bg-white shadow-sm">

        <div class="container-fluid">

            <b>Dashboard</b>

        </div>

    </nav>

    <!-- CONTENT -->

    <div class="content">

        <div class="row g-4 text-center">

            <a href="users.php" class="col-md-3 text-decoration-none">

                <div class="card dashboard-card">

                    <div class="card-body">

                        <h3 class="text-primary"><?php echo $total ?></h3>

                        Total User

                    </div>

                </div>

            </a>

            <a href="users.php?filter=online" class="col-md-3 text-decoration-none">

                <div class="card dashboard-card">

                    <div class="card-body">

                        <h3 class="text-success"><?php echo $online ?></h3>

                        User Online

                    </div>

                </div>

            </a>

            <a href="users.php?filter=expired" class="col-md-3 text-decoration-none">

                <div class="card dashboard-card">

                    <div class="card-body">

                        <h3 class="text-warning"><?php echo $expired ?></h3>

                        Expired

                    </div>

                </div>

            </a>

            <a href="users.php?filter=disabled" class="col-md-3 text-decoration-none">

                <div class="card dashboard-card">

                    <div class="card-body">

                        <h3 class="text-danger"><?php echo $disabled ?></h3>

                        Disabled

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

                                <th>User</th>
                                <th>IP</th>
                                <th>Login Time</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php while ($o = $online_list->fetch_assoc()) { ?>

                                <tr>

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

                                <th>User</th>
                                <th>NAS</th>
                                <th>Login Time</th>

                            </tr>

                        </thead>

                        <tbody>

                            <?php while ($r = $log->fetch_assoc()) { ?>

                                <tr>

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

   

</body>

</html>