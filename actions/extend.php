<?php
require "../config/db.php";
$user = $_GET['user'] ?? '';
$hari = $_POST['hari'] ?? '';

if($user==""){
die("username kosong");
}

/* kalau form belum disubmit tampilkan form */
if(empty($hari)){
?>

<h2>Perpanjang Masa Aktif</h2>

<form method="POST">

User : <b><?php echo $user; ?></b>

<br><br>

Tambah Hari : <input type="number" name="hari" value="30">

<br><br>

<button type="submit">Simpan</button>

</form>

<?php
exit;
}

/* ambil expiration lama */
$q = $conn->query("
SELECT value
FROM radcheck
WHERE username='$user'
AND attribute='Expiration'
");

$row = $q->fetch_assoc();
$exp_lama = $row['value'] ?? '';

/* hitung expiration baru */
if(empty($exp_lama)){
$exp_baru = date("d M Y 23:59", strtotime("+$hari days"));
}else{

$exp_time = strtotime($exp_lama);
$exp_baru = date("d M Y 23:59", strtotime("+$hari days",$exp_time));

}

/* cek apakah sudah ada expiration */
$cek = $conn->query("
SELECT id
FROM radcheck
WHERE username='$user'
AND attribute='Expiration'
");

if($cek->num_rows>0){

$conn->query("
UPDATE radcheck
SET value='$exp_baru'
WHERE username='$user'
AND attribute='Expiration'
");

}else{

$conn->query("
INSERT INTO radcheck (username,attribute,op,value)
VALUES ('$user','Expiration',':=','$exp_baru')
");

}

/* kembali ke panel */
header("Location: users.php");
exit;

?>
