<?php
require "auth.php";

$logfile = "/var/log/freeradius/radius.log";

$search = $_GET['search'] ?? "";
$lines = [];

if (file_exists($logfile)) {
    $lines = array_reverse(file($logfile));
}
?>

<!DOCTYPE html>

<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Log</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">

</head>

<body>

    <?php include "sidebar.php"; ?>

    <nav class="navbar navbar-light bg-white shadow-sm px-4">
        <b>Log</b>
    </nav>

    <div class="content">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <h3 class="mb-0">Riwayat Login User</h3>

            <form method="GET" class="d-flex">

                <input
                    type="text"
                    name="search"
                    class="form-control me-2"
                    placeholder="Cari user..."
                    value="<?php echo htmlspecialchars($search); ?>">

                <button class="btn btn-primary">Cari</button>

            </form>

        </div>

        <div class="table-scroll">

            <table class="table table-striped text-center">

                <thead class="table-dark">
                    <tr>
                        <th>Waktu</th>
                        <th>User</th>
                        <th>Status</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>

                <tbody>

                    <?php

                    $count = 0;

                    foreach ($lines as $line) {

                        if ($count > 300) break;

                        $status = "";
                        $user = "";
                        $ket = "";

                        /* LOGIN SUKSES */

                        if (strpos($line, "Login OK") !== false) {

                            preg_match('/^(.+?) :/', $line, $time);
                            preg_match('/\[(.*?)\//', $line, $u);

                            $status = "Login Sukses";
                            $user = $u[1] ?? "-";
                            $ket = "Autentikasi berhasil";
                        }

                        /* LOGIN GAGAL */ elseif (strpos($line, "Login incorrect") !== false) {

                            preg_match('/^(.+?) :/', $line, $time);
                            preg_match('/\[(.*?)\//', $line, $u);

                            $status = "Login Gagal";
                            $user = $u[1] ?? "-";
                            $ket = "Password salah atau user tidak ditemukan";
                        } else {
                            continue;
                        }

                        /* FILTER SEARCH */

                        $fulltext = strtolower($user . " " . $status . " " . $ket);

                        if ($search && strpos($fulltext, strtolower($search)) === false) {
                            continue;
                        }

                        $badge = $status == "Login Sukses" ? "success" : "danger";

                        echo "<tr>";
                        echo "<td>" . $time[1] . "</td>";
                        echo "<td>" . $user . "</td>";
                        echo "<td><span class='badge bg-$badge'>$status</span></td>";
                        echo "<td>" . $ket . "</td>";
                        echo "</tr>";

                        $count++;
                    }

                    ?>

                </tbody>

            </table>

        </div>

    </div>


</body>

</html>