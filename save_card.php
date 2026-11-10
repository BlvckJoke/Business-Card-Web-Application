<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug function
function debug($data) {
    file_put_contents('debug.log', date('Y-m-d H:i:s') . ': ' . print_r($data, true) . "\n", FILE_APPEND);
}

debug("Save card script started");
debug($_POST);
debug($_FILES);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php?error=not_logged_in");
    exit();
}

$user_id = $_SESSION['users'];
debug("User ID: " . $user_id);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate required fields
    $required = ['company', 'category', 'country', 'date_added'];
    $errors = [];
    
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required";
        }
    }
    
    if (!empty($errors)) {
        // Store errors in session and redirect back
        $_SESSION['form_errors'] = $errors;
        $_SESSION['old_form_data'] = $_POST;
        header("Location: info_card.php?error=validation_failed");
        exit();
    }
    
    require_once "config/db.php";
    
    // Check database connection
    if ($conn->connect_error) {
        $_SESSION['error'] = "Database connection failed: " . $conn->connect_error;
        header("Location: info_card.php?error=db_connection");
        exit();
    }
    
    // Get form data
    $company  = trim($_POST['company']);
    $category = trim($_POST['category']);
    $country  = trim($_POST['country']);
    $note     = trim($_POST['note'] ?? '');
    $date     = trim($_POST['date_added']);
    $user_id  = $_SESSION['users'];
    
    // Handle photo uploads (base64 data)
    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            $_SESSION['error'] = "Failed to create uploads directory";
            header("Location: info_card.php?error=directory_creation");
            exit();
        }
    }
    
    $frontPhotoName = null;
    $backPhotoName = null;
    
    // Function to save base64 image with better error handling
    function saveBase64Image($base64Data, $filenamePrefix, $uploadDir) {
        if (empty($base64Data)) {
            return '';
        }
        
        // Check if it's a base64 string
        if (strpos($base64Data, 'data:image') === false) {
            // It might already be a filename
            return $base64Data;
        }
        
        try {
            // Extract the base64 data
            list($type, $data) = array_pad(explode(';', $base64Data, 2), 2, null);
            list(, $data) = array_pad(explode(',', $data, 2), 2, null);
            
            if (!$data) {
                return '';
            }
            
            // Decode base64
            $imageData = base64_decode($data, true);
            
            if ($imageData === false) {
                return '';
            }
            
            // Validate it's actually an image
            if (!@imagecreatefromstring($imageData)) {
                return '';
            }
            
            // Get file extension from mime type
            $mime = str_replace('data:', '', $type);
            $extension = '';
            
            if (strpos($mime, 'jpeg') !== false || strpos($mime, 'jpg') !== false) {
                $extension = 'jpg';
            } elseif (strpos($mime, 'png') !== false) {
                $extension = 'png';
            } elseif (strpos($mime, 'gif') !== false) {
                $extension = 'gif';
            } elseif (strpos($mime, 'webp') !== false) {
                $extension = 'webp';
            } else {
                $extension = 'jpg';
            }
            
            // Generate unique filename
            $uniqueFilename = $filenamePrefix . '_' . uniqid() . '_' . time() . '.' . $extension;
            $filePath = $uploadDir . $uniqueFilename;
            
            // Check if directory is writable
            if (!is_writable($uploadDir)) {
                return '';
            }
            
            // Save file
            $bytesWritten = file_put_contents($filePath, $imageData);
            
            if ($bytesWritten === false) {
                return '';
            }
            
            // Verify file was created
            if (!file_exists($filePath) || filesize($filePath) === 0) {
                return '';
            }
            
            return $uniqueFilename;
            
        } catch (Exception $e) {
            return '';
        }
    }
    
    // Save front photo if exists
    if (!empty($_POST['front_photo_data'])) {
        $frontPhotoName = saveBase64Image($_POST['front_photo_data'], 'front', $uploadDir);
    }
    
    // Save back photo if exists
    if (!empty($_POST['back_photo_data'])) {
        $backPhotoName = saveBase64Image($_POST['back_photo_data'], 'back', $uploadDir);
    }
    
    // Insert into database
    $stmt = $conn->prepare("INSERT INTO business_cards 
        (company, category, country, front_photo, back_photo, note, date_added, users) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    if ($stmt) {
        $stmt->bind_param("sssssssi", $company, $category, $country, $frontPhotoName, $backPhotoName, $note, $date, $user_id);
        
        if ($stmt->execute()) {
            $new_card_id = $stmt->insert_id;
            $stmt->close();
            $conn->close();
            
            // Log to file for debugging
            $log = "Form submission at " . date('Y-m-d H:i:s') . "\n";
            $log .= "User ID: " . $user_id . "\n";
            $log .= "Company: " . $company . "\n";
            $log .= "Category: " . $category . "\n";
            $log .= "Country: " . $country . "\n";
            $log .= "Front Photo: " . $frontPhotoName . "\n";
            $log .= "Back Photo: " . $backPhotoName . "\n";
            $log .= "Note: " . $note . "\n";
            $log .= "Date: " . $date . "\n\n";
            file_put_contents('form_submission_log.txt', $log, FILE_APPEND);
            
            // Success - redirect with success message
            $_SESSION['success_message'] = "Business card saved successfully!";
            $_SESSION['new_card_id'] = $new_card_id;
            header("Location: info_card.php?success=card_saved");
            exit();
        } else {
            $error_msg = "Database Error: " . $stmt->error;
            $stmt->close();
            $conn->close();
            
            $_SESSION['error'] = $error_msg;
            $_SESSION['old_form_data'] = $_POST;
            header("Location: info_card.php?error=db_insert");
            exit();
        }
    } else {
        $error_msg = "Database Prepare Error: " . $conn->error;
        $conn->close();
        
        $_SESSION['error'] = $error_msg;
        $_SESSION['old_form_data'] = $_POST;
        header("Location: info_card.php?error=db_prepare");
        exit();
    }
    
} else {
    // Not a POST request
    header("Location: info_card.php?error=invalid_method");
    exit();
}
?>