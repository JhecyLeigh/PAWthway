<?php
// pages/clinic_logout.php
session_start();
$_SESSION = [];
session_destroy();
header('Location: /pawthway/pages/login.php');
exit;
