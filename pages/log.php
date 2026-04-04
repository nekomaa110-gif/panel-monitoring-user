<?php
require __DIR__ . "/../core/auth.php";
require __DIR__ . "/../core/helpers.php";

$logfile = "/var/log/freeradius/radius.log";

$search = $_GET['search'] ?? "";
$lines = [];

if (file_exists($logfile)) {
    $lines = array_reverse(file($logfile));
}

$pageTitle = 'Log';
$navTitle  = 'Log';

include __DIR__ . "/../views/log.view.php";
