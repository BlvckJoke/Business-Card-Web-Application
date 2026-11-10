<?php
session_start();

// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log everything
file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Save script started\n", FILE_APPEND);
file_put_contents('debug.log', "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);
file_put_contents('debug.log', "FILES data: " . print_r($_FILES, true) . "\n", FILE_APPEND);

// Test user session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // For testing
}

// Simple database connection
$conn = new mysqli('192.160.0.4', 'admin', 'n1tT4aGm!', 'Business_card');

if ($conn->connect_error) {
    file_put_contents('debug.log', "DB Connection failed: " . $conn->connect_error . "\n", FILE_APPEND);
    die("Connection failed: " . $conn->connect_error);
}

// Get POST data
$company = isset($_POST['company']) ? $_POST['company'] : '';
$category = isset($_POST['category']) ? $_POST['category'] : '';
$country = isset($_POST['country']) ? $_POST['country'] : '';
$date_added = isset($_POST['date_added']) ? $_POST['date_added'] : date('Y-m-d');
$note = isset($_POST['note']) ? $_POST['note'] : '';

// Log what we received
file_put_contents('debug.log', "Form data received:\n", FILE_APPEND);
file_put_contents('debug.log', "Company: $company\n", FILE_APPEND);
file_put_contents('debug.log', "Category: $category\n", FILE_APPEND);
file_put_contents('debug.log', "Country: $country\n", FILE_APPEND);
file_put_contents('debug.log', "Date: $date_added\n", FILE_APPEND);

// Handle photo data (simplified - just save filenames for now)
$front_photo = '';
$back_photo = '';

// Check if we have base64 photo data
if (!empty($_POST['front_photo_data']) && strpos($_POST['front_photo_data'], 'data:image') === 0) {
    $front_photo = 'front_' . time() . '.jpg';
    file_put_contents('debug.log', "Front photo data received (length: " . strlen($_POST['front_photo_data']) . ")\n", FILE_APPEND);
}

if (!empty($_POST['back_photo_data']) && strpos($_POST['back_photo_data'], 'data:image') === 0) {
    $back_photo = 'back_' . time() . '.jpg';
    file_put_contents('debug.log', "Back photo data received (length: " . strlen($_POST['back_photo_data']) . ")\n", FILE_APPEND);
}

// Prepare SQL
$sql = "INSERT INTO business_cards (users, company, category, country, front_photo, back_photo, date_added, note) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

file_put_contents('debug.log', "SQL: $sql\n", FILE_APPEND);

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("isssssss", $_SESSION['user_id'], $company, $category, $country, $front_photo, $back_photo, $date_added, $note);
    
    if ($stmt->execute()) {
        file_put_contents('debug.log', "SUCCESS: Card saved with ID: " . $stmt->insert_id . "\n", FILE_APPEND);
        $stmt->close();
        $conn->close();
        header("Location: business-card.php?success=1");
        exit;
    } else {
        $error = "Execute failed: " . $stmt->error;
        file_put_contents('debug.log', "ERROR: $error\n", FILE_APPEND);
        $stmt->close();
    }
} else {
    $error = "Prepare failed: " . $conn->error;
    file_put_contents('debug.log', "ERROR: $error\n", FILE_APPEND);
}

$conn->close();

// If we get here, there was an error
file_put_contents('debug.log', "FAILED: Redirecting with error\n", FILE_APPEND);
header("Location: business-card.php?error=1");
exit;
?>