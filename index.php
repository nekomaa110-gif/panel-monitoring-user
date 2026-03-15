<?php
error_reporting(E_ALL);
ini_set('display_errors',1);

$host="localhost";
$user="radius";
$pass="radius123";
$db="radius";

$conn=new mysqli($host,$user,$pass,$db);
if($conn->connect_error){die("DB error");}

function addUser($conn,$u,$p,$days){
$exp=date("d M Y 23:59",strtotime("+$days days"));

$conn->query("INSERT INTO radcheck(username,attribute,op,value)
VALUES('$u','Cleartext-Password',':=','$p')");

$conn->query("INSERT INTO radcheck(username,attribute,op,value)
VALUES('$u','Expiration',':=','$exp')");

$conn->query("INSERT INTO radusergroup(username,groupname,priority)
VALUES('$u','Radius-Member',0)");
}

function disableUser($conn,$u){
$conn->query("UPDATE radusergroup
SET groupname='daloRADIUS-Disabled-Users'
WHERE username='$u'");
}

function extendDays($conn,$u,$days){
$q=$conn->query("SELECT value FROM radcheck
WHERE username='$u' AND attribute='Expiration'");
$r=$q->fetch_assoc();

$old=strtotime($r['value']);
$new=date("d M Y 23:59",$old + ($days*86400));

$conn->query("UPDATE radcheck
SET value='$new'
WHERE username='$u' AND attribute='Expiration'");
}

function extendManual($conn,$u,$date){
$conn->query("UPDATE radcheck
SET value='$date'
WHERE username='$u' AND attribute='Expiration'");
}

if(isset($_POST['add'])){
addUser($conn,$_POST['user'],$_POST['pass'],$_POST['days']);
}

if(isset($_POST['disable'])){
disableUser($conn,$_POST['user']);
}

if(isset($_POST['extend'])){
extendDays($conn,$_POST['user'],$_POST['days']);
}

if(isset($_POST['manual'])){
extendManual($conn,$_POST['user'],$_POST['date']);
}
?>

<html>
<head>
<title>ZERO NET PANEL</title>

<style>

body{
font-family:Arial;
background:#f4f6f9;
margin:40px;
}

.panel{
background:white;
padding:20px;
border-radius:8px;
margin-bottom:20px;
box-shadow:0 0 8px rgba(0,0,0,0.1);
}

input{
padding:6px;
margin:5px 0;
width:200px;
}

button{
padding:8px 14px;
background:#3498db;
color:white;
border:none;
border-radius:4px;
cursor:pointer;
}

button:hover{
background:#2980b9;
}

h1{
margin-bottom:30px;
}

</style>

</head>

<body>

<h1>ZERO NET MINI PANEL</h1>

<div class="panel">
<h3>Tambah User</h3>

<form method="post">

username<br> <input name="user"><br>

password<br> <input name="pass"><br>

masa aktif (hari)<br> <input name="days" value="30"><br><br>

<button name="add">buat user</button>

</form>
</div>

<div class="panel">
<h3>Disable User</h3>

<form method="post">

username<br> <input name="user"><br><br>

<button name="disable">disable</button>

</form>
</div>

<div class="panel">
<h3>Perpanjang Masa Aktif</h3>

<form method="post">

username<br> <input name="user"><br>

tambah hari<br> <input name="days" value="30"><br><br>

<button name="extend">tambah hari</button>

</form>

<hr>

<form method="post">

username<br> <input name="user"><br>

tanggal manual<br> <input name="date" placeholder="20 Apr 2026 23:59"><br><br>

<button name="manual">set manual</button>

</form>

</div>

</body>
</html>
