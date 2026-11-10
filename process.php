<?php
session_start();
require_once "config/db.php"; // mysqli connection

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit();
}

$email    = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($email) || empty($password)) {
    $_SESSION['error'] = "Email and password are required";
    header("Location: index.php");
    exit();
}

// Get user by email
$stmt = $conn->prepare("
    SELECT id, user_name,email, passwords, status, user_id 
    FROM User 
    WHERE email = ? 
    LIMIT 1
");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    $_SESSION['error'] = "Invalid login details user";
    header("Location: index.php");
    exit();
}

$user = $result->fetch_assoc();

// Check password
if ($password !== $user['passwords']) {
    $_SESSION['error'] = "Invalid login details pass";
    header("Location: index.php");
    exit();
}


// Check account status
if ($user['status'] !== 'ACTIVE') {
    $_SESSION['error'] = "Your account is not active";
    header("Location: index.php");
    exit();
}

// Ã¢ÂÂ Login success
$_SESSION['user_id']  = $user['id'];
$_SESSION['users']  = $user['user_id'];
$_SESSION['email']    = $user['email'];
$_SESSION['names']     = $user['user_name'];
$_SESSION['status']   = $user['status'];

header("Location: info_card.php");
exit();
