<?php
require "db.php";

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$hari     = $_POST['hari'] ?? 30;
$profile  = $_POST['profile'] ?? 'Radius-Member';

if(empty($username) || empty($password)){

?>

<h2>Tambah User Baru</h2>

<form method="POST">

Username<br> <input type="text" name="username"><br><br>

Password<br> <input type="text" name="password"><br><br>

Profil<br> <select name="profile">

<option value="Radius-Member">Radius-Member</option>
<option value="TEST">TEST</option>
</select>

<br><br>

Masa Aktif (hari)<br> <input type="number" name="hari" value="30">

<br><br>

<button type="submit">Simpan</button>

</form>

<?php
exit;
}

/* hitung expiration */
$expiration = date("d M Y 23:59", strtotime("+$hari days"));

/* password */
$conn->query("
INSERT INTO radcheck (username,attribute,op,value)
VALUES ('$username','Cleartext-Password',':=','$password')
");

/* expiration */
$conn->query("
INSERT INTO radcheck (username,attribute,op,value)
VALUES ('$username','Expiration',':=','$expiration')
");

/* profile */
$conn->query("
INSERT INTO radusergroup (username,groupname,priority)
VALUES ('$username','$profile',0)
");

header("Location: users.php");
exit;

?>
