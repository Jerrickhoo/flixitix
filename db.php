<?php
$host = "localhost";
$user = "root";
$pass = ""; // Default XAMPP password is empty
$dbname = "flixtixfinal";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
