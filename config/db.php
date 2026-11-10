<?php
// config/db.php

$host = '156.38.230.252';
$username = 'belliunasd_bc';
$password = 'YAEX4rP3n83gzm98nda8';
$database = 'belliunasd_bc';

$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>