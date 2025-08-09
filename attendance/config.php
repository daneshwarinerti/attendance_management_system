<?php
$host = "sql210.infinityfree.com"; // MySQL Hostname from InfinityFree
$user = "if0_39663811"; // MySQL Username from InfinityFree
$password = "Daneshwari2004"; // Same password you use to log in to InfinityFree Control Panel
$dbname = "if0_39663811_attendance"; // MySQL Database Name from InfinityFree

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
