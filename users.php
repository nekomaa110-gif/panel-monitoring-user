<?php
require "db.php";

$search = $_GET['search'] ?? "";
$page   = $_GET['page'] ?? 1;

$limit = 15;
$start = ($page - 1) * $limit;

$total_q = $conn->query("
SELECT COUNT(*) as total FROM
(
SELECT username FROM radcheck
UNION
SELECT username FROM radusergroup
) u
");

$total_row = $total_q->fetch_assoc();
$total_user = $total_row['total'];
$total_page = ceil($total_user / $limit);

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
LIMIT $start,$limit
");
?>

<!DOCTYPE html>

<html>
<head>

<title>Panel Radius</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>

<body class="bg-light">

<div class="container mt-5">

<h3 class="text-center mb-4">Panel Pelanggan Hotspot</h3>

<div class="d-flex justify-content-between mb-3">

<a href="adduser.php" class="btn btn-success">+ Tambah User</a>

<form method="GET" class="d-flex">

<input type="text" name="search" class="form-control me-2" placeholder="Cari user..." value="<?php echo $search; ?>">

<button class="btn btn-primary">Cari</button>

</form>

</div>

<table class="table table-striped table-hover">

<thead class="table-dark text-center">

<tr>
<th>Username</th>
<th>Password</th>
<th>Profile</th>
<th>Expiration</th>
<th>Status</th>
<th>Tindakan</th>
</tr>

</thead>

<tbody>

<?php while($r=$q->fetch_assoc()){

$exp_string = $r['expiration'];
$exp = strtotime($exp_string);
$now = time();

$status="AKTIF";
$badge="success";

if($r['profile']=="daloRADIUS-Disabled-Users"){
$status="NONAKTIF";
$badge="danger";
}
elseif(!empty($exp_string)){
if($exp!==false && $exp<$now){
$status="EXPIRED";
$badge="warning";
}
}

?>

<tr class="text-center">

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

<a href="extend.php?user=<?php echo $r['username']; ?>" class="btn btn-sm btn-primary">
Perpanjang
</a>

<?php if($r['profile']=="daloRADIUS-Disabled-Users"){ ?>

<a href="enable.php?user=<?php echo $r['username']; ?>" class="btn btn-sm btn-success">
Aktifkan
</a>

<?php } else { ?>

<a href="disable.php?user=<?php echo $r['username']; ?>" class="btn btn-sm btn-danger">
Nonaktifkan
</a>

<?php } ?>

</td>

</tr>

<?php } ?>

</tbody>

</table>

<div class="text-center mt-4">

<?php
for($i=1;$i<=$total_page;$i++){

echo "<a class='btn btn-sm btn-secondary me-1' href='?page=$i&search=$search'>$i</a>";

}
?>

</div>

</div>

</body>
</html>
