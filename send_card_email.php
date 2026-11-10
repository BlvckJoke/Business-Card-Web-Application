<?php
// send_card_email.php
require_once "config/db.php";
session_start();

if (!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

$card_id = $_POST['card_id'] ?? 0;
$email = $_POST['email'] ?? '';
$include_images = $_POST['include_images'] ?? false;

// Fetch card details
$query = "SELECT * FROM business_cards WHERE id = ? AND users = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $card_id, $_SESSION['user_id']);
$stmt->execute();
$card = $stmt->get_result()->fetch_assoc();

if (!$card) {
    die('Card not found');
}

// Prepare email
$to = $email;
$subject = "Business Card: " . $card['company'];
$message = "Company: " . $card['company'] . "\n";
$message .= "Category: " . $card['category'] . "\n";
$message .= "Country: " . $card['country'] . "\n";
$message .= "Note: " . $card['note'] . "\n";

$headers = "From: your-email@example.com\r\n";

// If including images as attachments
if ($include_images) {
    $boundary = md5(time());
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"" . $boundary . "\"\r\n";
    
    $message = "--" . $boundary . "\r\n";
    $message .= "Content-Type: text/plain; charset=\"UTF-8\"\r\n";
    $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $message .= $card_content . "\r\n\r\n";
    
    // Add front photo
    if (!empty($card['front_photo'])) {
        $file = 'uploads/' . $card['front_photo'];
        if (file_exists($file)) {
            $file_content = file_get_contents($file);
            $file_content = chunk_split(base64_encode($file_content));
            
            $message .= "--" . $boundary . "\r\n";
            $message .= "Content-Type: image/jpeg; name=\"" . basename($file) . "\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n";
            $message .= "Content-Disposition: attachment\r\n\r\n";
            $message .= $file_content . "\r\n";
        }
    }
    
    $message .= "--" . $boundary . "--";
}

// Send email
if (mail($to, $subject, $message, $headers)) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to send email']);
}
?>