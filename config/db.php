<?php
// config/db.php

$host = 'Localhost';
$username = 'username';
$password = 'password';
$database = 'belliunasd_bc';

$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>
