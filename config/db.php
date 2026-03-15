<?php

$host="localhost";
$user="radius";
$pass="radius123";
$db="radius";

$conn = new mysqli($host,$user,$pass,$db);

if ($conn->connect_error) {
    die("Database gagal konek");
}

?>
