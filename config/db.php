<?php
$servername = "127.0.0.1:3307";
$username = "root";
$password = "";
$database = "pawthway_db";

$conn = mysqli_connect($servername, $username, $password, $database);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
