<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "pawthway_db";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    // Log error instead of echoing to avoid breaking sessions/redirects
    error_log("Database connection failed: " . mysqli_connect_error());
    die("Database connection failed.");
}

// No closing PHP tag on purpose — prevents accidental whitespace output.
