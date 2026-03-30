<?php
require "auth.php";

function indoDurasi($str) {
    return preg_replace_callback('/(\d+)\s*([dhwm])/i', function($m) {
        $angka = $m[1];
        $unit  = strtolower($m[2]);
        switch ($unit) {
            case 'd': return $angka . ' hari';
            case 'h': return $angka . ' jam';
            case 'w': return $angka . ' minggu';
            case 'm': return $angka . ' menit';
            default:  return $m[0];
        }
    }, $str);
}

$data = [];

if (isset($_POST['upload'])) {
    if (!empty($_FILES['csv']['tmp_name'])) {

        $file = $_FILES['csv']['tmp_name'];
        $rows = array_map('str_getcsv', file($file));

        $header = array_map('trim', $rows[0]);
        unset($rows[0]);

        foreach ($rows as $row) {
            if (count($header) == count($row)) {
                $data[] = array_combine($header, $row);
            }
        }
    }
}

$hotspotname = "zero.net";
$price = "Rp 5.000";
$validity = "1d";
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Voucher</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/style.css" rel="stylesheet">

<style>

/* FIX PRINT */
html, body {
    width: 210mm;
}

/* GRID */
table {
    width: 100%;
    border-collapse: collapse;
}

td {
    width: 20%;
    padding: 2px;
}

/* VOUCHER BOX */
.voucher {
    border: 1px solid #000;
    height: 70px;
    font-size: 7px;
}

/* PRINT */
@page {
    size: A4;
    margin: 5mm;
}

@media print {
    .sidebar, .navbar, .no-print {
        display: none !important;
    }

   html, body {

        margin: 0;
	width: 210mm;
	height: 297mm;
	zoom: 1;
    }
}

</style>

</head>
<body>

<?php include "sidebar.php"; ?>

<nav class="navbar navbar-light bg-white shadow-sm px-4">
    <b>Voucher</b>
</nav>

<div class="content p-4">

<form method="post" enctype="multipart/form-data" class="mb-3 no-print">
    <input type="file" name="csv" required>
    <button name="upload" class="btn btn-primary btn-sm">Generate Voucher</button>
    <button type="button" onclick="window.print()" class="btn btn-secondary btn-sm">Print</button>
</form>

<?php if (empty($data)): ?>
<div class="alert alert-warning">
    Upload CSV dulu, jangan bengong 🤡
</div>
<?php endif; ?>

<table>

<?php
$num = 1;
$col = 0;

foreach ($data as $row) {

    if ($col == 0) echo "<tr>";

    $username  = $row['Username'] ?? '';
    $password  = $row['Password'] ?? '';
    $timelimit = $row['Time Limit'] ?? '';

    echo "<td>

    <table class='voucher'>
        <tr>
            <td style='border-bottom:1px solid #000; font-weight:bold; position:relative;'>
                Login http://$hotspotname
                <span style='position:absolute; right:2px; top:2px;'>[$num]</span>
            </td>
        </tr>

        <tr>
            <td>

            <table>
                <tr>
                    <td>Username</td>
                    <td>Password</td>
                </tr>

                <tr style='font-size:9px;'>
                    <td style='border:1px solid #000;'><b>$username</b></td>
                    <td style='border:1px solid #000;'><b>$password</b></td>
                </tr>

                <tr>
                    <td colspan='2'>
                        <b>".indoDurasi($timelimit)." • $price</b>
                    </td>
                </tr>

                <tr>
                    <td colspan='2'><hr></td>
                </tr>

                <tr>
                    <td colspan='2' style='font-size:6px;'>
                        Masa berlaku: ".indoDurasi($validity)."
                    </td>
                </tr>
            </table>

            </td>
        </tr>
    </table>

    </td>";

    $col++;

    if ($col == 5) {
        echo "</tr>";
        $col = 0;
    }

    $num++;
}

if ($col != 0) echo "</tr>";
?>

</table>

</div>

</body>
</html>
