<?php
session_start();
require_once "config/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Invalid request method";
    exit;
}

// Check if card_id is provided
if (!isset($_POST['card_id']) || !is_numeric($_POST['card_id'])) {
    echo "Invalid card ID";
    exit;
}

$card_id = intval($_POST['card_id']);
$user_id = $_SESSION['user_id'];

try {
    // Start transaction
    $conn->begin_transaction();
    
    // First, get the photo filenames to delete from server
    $sql_select = "SELECT front_photo, back_photo FROM business_cards WHERE id = ? AND users = ?";
    $stmt_select = $conn->prepare($sql_select);
    $stmt_select->bind_param("ii", $card_id, $user_id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    
    if ($result->num_rows === 0) {
        echo "Card not found or access denied";
        $stmt_select->close();
        exit;
    }
    
    $card = $result->fetch_assoc();
    $stmt_select->close();
    
    // Delete the card from database
    $sql_delete = "DELETE FROM business_cards WHERE id = ? AND users = ?";
    $stmt_delete = $conn->prepare($sql_delete);
    $stmt_delete->bind_param("ii", $card_id, $user_id);
    
    if (!$stmt_delete->execute()) {
        throw new Exception("Database error: " . $stmt_delete->error);
    }
    
    // Check if any row was deleted
    if ($stmt_delete->affected_rows === 0) {
        throw new Exception("No card was deleted. Please check your permissions.");
    }
    
    $stmt_delete->close();
    
    // Delete associated photos from server
    $upload_dir = "uploads/";
    
    // Delete front photo if exists
    if (!empty($card['front_photo']) && file_exists($upload_dir . $card['front_photo'])) {
        unlink($upload_dir . $card['front_photo']);
    }
    
    // Delete back photo if exists
    if (!empty($card['back_photo']) && file_exists($upload_dir . $card['back_photo'])) {
        unlink($upload_dir . $card['back_photo']);
    }
    
    // Commit transaction
    $conn->commit();
    
    echo "success";
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo "Error: " . $e->getMessage();
} finally {
    $conn->close();
}
?>