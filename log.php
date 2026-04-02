<?php
require "auth.php";
require "config/db.php";

$logfile = "/var/log/freeradius/radius.log";

$search = $_GET['search'] ?? "";
$lines = [];

if (file_exists($logfile)) {
    $lines = array_reverse(file($logfile));
}

function indoDate($date)
{
    $bulan = [
        "Jan" => "Januari",
        "Feb" => "Februari",
        "Mar" => "Maret",
        "Apr" => "April",
        "May" => "Mei",
        "Jun" => "Juni",
        "Jul" => "Juli",
        "Aug" => "Agustus",
        "Sep" => "September",
        "Oct" => "Oktober",
        "Nov" => "November",
        "Dec" => "Desember"
    ];

    $hari = [
        "Sun" => "Minggu",
        "Mon" => "Senin",
        "Tue" => "Selasa",
        "Wed" => "Rabu",
        "Thu" => "Kamis",
        "Fri" => "Jumat",
        "Sat" => "Sabtu"
    ];

    $pecah = explode(" ", $date);

    // contoh: Wed Mar 30 01:40:41 2026
    $h = $hari[$pecah[0]] ?? $pecah[0];
    $b = $bulan[$pecah[1]] ?? $pecah[1];
    $tgl = $pecah[2];
    $jam = substr($pecah[3], 0, 5);
    $thn = $pecah[4];

    return "$h, $tgl $b $thn $jam";
}
?>

<!DOCTYPE html>

<html>

<head>
    <title>Log</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">

</head>

<body>

    <?php include "sidebar.php"; ?>

    <nav class="navbar navbar-light bg-white shadow-sm px-4 d-flex justify-content-between">

        <b>Log</b>

        <a href="logout.php" class="btn btn-danger d-flex align-items-center gap-2">
            Logout
        </a>

    </nav>
    <b>Log</b>
    </nav>

    <div class="content">

        <div class="d-flex justify-content-between align-items-center mb-4">

            <div>
                <h3 class="mb-1">Riwayat Login User RADIUS</h3>
                <small class="text-muted">
                    Menampilkan log autentikasi user internet (RADIUS), bukan login admin panel.
                </small>
            </div>

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
                        $user = "-";
                        $ket = "";
                        $time = ["", "-"];

                        /* AMBIL WAKTU & USER */
                        preg_match('/^(.+?) :/', $line, $time);
                        preg_match('/\[(.*?)\//', $line, $u);
                        $time_val = $time[1] ?? "-";
                        $user = $u[1] ?? "-";

                        /* LOGIN SUKSES */
                        if (strpos($line, "Login OK") !== false) {

                            $status = "Login Sukses";
                            $ket = "Autentikasi berhasil";
                        }

                        /* LOGIN GAGAL */ elseif (strpos($line, "Login incorrect") !== false) {

                            $status = "Login Gagal";
                            $ket = "Password salah";
                        } elseif (strpos($line, "Invalid user") !== false || strpos($line, "No such user") !== false) {

                            $status = "Login Gagal";
                            $ket = "User tidak ditemukan";
                        } elseif (strpos($line, "user locked") !== false) {

                            $status = "Login Gagal";
                            $ket = "User dinonaktifkan";
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
                        echo "<td>" . indoDate($time_val) . "</td>";
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