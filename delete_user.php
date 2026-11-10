<?php
session_start();
require_once "config/db.php";

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['names'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if user is admin
$isAdmin = false;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("SELECT role FROM User WHERE id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $isAdmin = ($row['role'] === 'ADMIN');
    }
    $stmt->close();
}

if (!$isAdmin) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get the user ID from POST data
$user_id = $_POST['user_id'] ?? null;

if (!$user_id) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'User ID is required']);
    exit;
}

// Prevent user from deleting themselves
if ($user_id == $_SESSION['user_id']) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT user_name, role FROM User WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->rollback();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'User not found']);
        exit;
    }
    
    $user = $result->fetch_assoc();
    $stmt->close();
    
    // Check if trying to delete the last admin
    if ($user['role'] === 'ADMIN') {
        $stmt = $conn->prepare("SELECT COUNT(*) as admin_count FROM User WHERE role = 'ADMIN' AND status = 'ACTIVE'");
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if ($row['admin_count'] <= 1) {
            $conn->rollback();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Cannot delete the last administrator']);
            exit;
        }
    }
    
    // Optional: Check if user has any related data in other tables
    // You can add checks here based on your database structure
    
    // Delete the user
    $stmt = $conn->prepare("DELETE FROM User WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $stmt->close();
        $conn->commit();
        
        // Log the deletion (optional)
        // You could add an audit log here
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'User deleted successfully',
            'user_name' => $user['user_name']
        ]);
    } else {
        $stmt->close();
        $conn->rollback();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to delete user: ' . $conn->error]);
    }
    
} catch (Exception $e) {
    if (isset($conn)) {
        $conn->rollback();
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}