<?php
session_start();
session_destroy();
header("Location: /zeronet/login");
exit;