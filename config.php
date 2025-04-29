<?php
$host = "localhost";
$user = "root";  // Default XAMPP MySQL user
$pass = "";  // Default password is empty
$dbname = "mmhr_census";  // Your database name
$port = 3308;  // Default MySQL port

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>