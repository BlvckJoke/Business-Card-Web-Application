<?php
session_start();
require_once "config/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['names'])) {
    header("Location: index.php");
    exit;
}

// Check if card ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: info_card.php");
    exit;
}

$card_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// First, fetch the current card to get existing photo names
$sql = "SELECT * FROM business_cards WHERE id = ? AND users = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $card_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Card not found or doesn't belong to user
    header("Location: info_card.php");
    exit;
}

$card = $result->fetch_assoc();
$stmt->close();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $company = trim($_POST['company'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $note = trim($_POST['note'] ?? '');
    
    // Validate required fields
    if (empty($company)) {
        $errors[] = "Company name is required.";
    }
    
    if (empty($category)) {
        $errors[] = "Category is required.";
    }
    
    if (empty($country)) {
        $errors[] = "Country is required.";
    }
    
    // Handle file uploads
    $front_photo = $card['front_photo']; // Keep existing if not changed
    $back_photo = $card['back_photo']; // Keep existing if not changed
    
    // Upload directory
    $upload_dir = "uploads/";
    
    // Process front photo upload
    if (isset($_FILES['front_photo']) && $_FILES['front_photo']['error'] === UPLOAD_ERR_OK) {
        $front_file = $_FILES['front_photo'];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($front_file['tmp_name']);
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Front photo must be an image (JPEG, PNG, GIF).";
        } else {
            // Validate file size (max 5MB)
            if ($front_file['size'] > 5 * 1024 * 1024) {
                $errors[] = "Front photo must be less than 5MB.";
            } else {
                // Generate unique filename
                $front_ext = pathinfo($front_file['name'], PATHINFO_EXTENSION);
                $front_filename = 'front_' . uniqid() . '_' . time() . '.' . $front_ext;
                $front_target = $upload_dir . $front_filename;
                
                // Delete old front photo if exists
                if (!empty($card['front_photo']) && file_exists($upload_dir . $card['front_photo'])) {
                    unlink($upload_dir . $card['front_photo']);
                }
                
                // Upload new file
                if (move_uploaded_file($front_file['tmp_name'], $front_target)) {
                    $front_photo = $front_filename;
                } else {
                    $errors[] = "Failed to upload front photo.";
                }
            }
        }
    } elseif (isset($_FILES['front_photo']) && $_FILES['front_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = "Error uploading front photo: " . $_FILES['front_photo']['error'];
    }
    
    // Process back photo upload
    if (isset($_FILES['back_photo']) && $_FILES['back_photo']['error'] === UPLOAD_ERR_OK) {
        $back_file = $_FILES['back_photo'];
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($back_file['tmp_name']);
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Back photo must be an image (JPEG, PNG, GIF).";
        } else {
            // Validate file size (max 5MB)
            if ($back_file['size'] > 5 * 1024 * 1024) {
                $errors[] = "Back photo must be less than 5MB.";
            } else {
                // Generate unique filename
                $back_ext = pathinfo($back_file['name'], PATHINFO_EXTENSION);
                $back_filename = 'back_' . uniqid() . '_' . time() . '.' . $back_ext;
                $back_target = $upload_dir . $back_filename;
                
                // Delete old back photo if exists
                if (!empty($card['back_photo']) && file_exists($upload_dir . $card['back_photo'])) {
                    unlink($upload_dir . $card['back_photo']);
                }
                
                // Upload new file
                if (move_uploaded_file($back_file['tmp_name'], $back_target)) {
                    $back_photo = $back_filename;
                } else {
                    $errors[] = "Failed to upload back photo.";
                }
            }
        }
    } elseif (isset($_FILES['back_photo']) && $_FILES['back_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        $errors[] = "Error uploading back photo: " . $_FILES['back_photo']['error'];
    }
    
    // If no errors, update the card
    if (empty($errors)) {
        $update_sql = "UPDATE business_cards SET 
                      company = ?, 
                      category = ?, 
                      country = ?, 
                      front_photo = ?, 
                      back_photo = ?, 
                      note = ?, 
                      last_updated = NOW() 
                      WHERE id = ? AND users = ?";
        
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param(
            "ssssssii", 
            $company, 
            $category, 
            $country, 
            $front_photo, 
            $back_photo, 
            $note, 
            $card_id, 
            $user_id
        );
        
        if ($update_stmt->execute()) {
            $success = true;
            
            // Store success message in session to display on redirect
            $_SESSION['edit_success'] = true;
            $_SESSION['edit_message'] = "Card updated successfully!";
            
            // Redirect back to edit page with success
            header("Location: edit_card.php?id=" . $card_id);
            exit;
        } else {
            $errors[] = "Database error: " . $update_stmt->error;
        }
        
        $update_stmt->close();
    }
    
    // If there are errors, store them in session and redirect back
    if (!empty($errors)) {
        $_SESSION['edit_errors'] = $errors;
        header("Location: edit_card.php?id=" . $card_id);
        exit;
    }
} else {
    // If not a POST request, redirect to edit page
    header("Location: edit_card.php?id=" . $card_id);
    exit;
}
?>