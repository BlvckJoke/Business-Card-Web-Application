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
$user_id = $_SESSION['user_6u6id'];

// Fetch card details
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

// Close statement
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Card Details - <?php echo htmlspecialchars($card['company']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm mb-6">
        <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <i class="fas fa-user-circle text-blue-500 text-2xl"></i>
                <span class="text-gray-800 font-semibold text-lg">
                    Welcome, <?php echo htmlspecialchars($_SESSION['names']); ?>
                </span>
            </div>
            
            <div class="flex items-center space-x-4">
                <button onclick="openLogoutModal()"
                    class="flex items-center space-x-2 text-red-600 hover:text-red-700 font-medium">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </div>
        </div>
    </header>

    <div class="max-w-4xl mx-auto p-4">
        <!-- Card -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Header with company name -->
            <div class="bg-gradient-to-r from-gray-600 to-gray-500 p-6">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-2xl font-bold text-white">
                            <?php echo htmlspecialchars($card['company']); ?>
                        </h1>
                        
                    </div>
                    <button onclick="deleteCard(<?php echo $card['id']; ?>)"
                        class="bg-red-500 hover:bg-red-600 text-white p-2 rounded-lg transition-colors"
                        title="Delete Card">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6">
                <!-- Photos Section -->
                <div class="mb-8">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-images text-red-500 mr-2"></i>
                        Card Photos
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Front Photo -->
                        <div>
                            <h3 class="font-medium text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-id-card mr-2 text-gray-500"></i>
                                Front Side
                            </h3>
                            <?php if (!empty($card['front_photo'])): ?>
                                <div class="border border-gray-200 rounded-lg overflow-hidden">
                                    <img src="uploads/<?php echo htmlspecialchars($card['front_photo']); ?>" 
                                         alt="Front of business card" 
                                         class="w-full h-auto max-h-64 object-contain cursor-pointer"
                                         onclick="viewPhoto('<?php echo htmlspecialchars($card['front_photo']); ?>', 'Front Side')">
                                    <div class="bg-gray-50 p-2 text-center">
                                        <button onclick="viewPhoto('<?php echo htmlspecialchars($card['front_photo']); ?>', 'Front Side')"
                                            class="text-blue-600 hover:text-blue-800 text-sm">
                                            <i class="fas fa-expand mr-1"></i>View Full Size
                                        </button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                                    <i class="fas fa-ban text-gray-400 text-3xl mb-2"></i>
                                    <p class="text-gray-500">No front photo available</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Back Photo -->
                        <div>
                            <h3 class="font-medium text-gray-700 mb-2 flex items-center">
                                <i class="fas fa-id-card-alt mr-2 text-gray-500"></i>
                                Back Side
                            </h3>
                            <?php if (!empty($card['back_photo'])): ?>
                                <div class="border border-gray-200 rounded-lg overflow-hidden">
                                    <img src="uploads/<?php echo htmlspecialchars($card['back_photo']); ?>" 
                                         alt="Back of business card" 
                                         class="w-full h-auto max-h-64 object-contain cursor-pointer"
                                         onclick="viewPhoto('<?php echo htmlspecialchars($card['back_photo']); ?>', 'Back Side')">
                                    <div class="bg-gray-50 p-2 text-center">
                                        <button onclick="viewPhoto('<?php echo htmlspecialchars($card['back_photo']); ?>', 'Back Side')"
                                            class="text-blue-600 hover:text-blue-800 text-sm">
                                            <i class="fas fa-expand mr-1"></i>View Full Size
                                        </button>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                                    <i class="fas fa-ban text-gray-400 text-3xl mb-2"></i>
                                    <p class="text-gray-500">No back photo available</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Details Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Company Details -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-building text-red-500 mr-2"></i>
                            Company Information
                        </h3>
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm text-gray-500">Company Name</label>
                                <p class="font-medium"><?php echo htmlspecialchars($card['company']); ?></p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500">Category</label>
                                <p class="font-medium"><?php echo htmlspecialchars($card['category']); ?></p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500">Country</label>
                                <p class="font-medium"><?php echo htmlspecialchars($card['country']); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Date Information -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                            <i class="fas fa-calendar text-red-500 mr-2"></i>
                            Date Information
                        </h3>
                        <div class="space-y-3">
                            <div>
                                <label class="text-sm text-gray-500">Date Added</label>
                                <p class="font-medium">
                                    <?php echo date('F d, Y', strtotime($card['date_added'])); ?>
                                </p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500">Last Updated</label>
                                <p class="font-medium">
                                    <?php 
                                    if (!empty($card['last_updated'])) {
                                        echo date('F d, Y', strtotime($card['last_updated']));
                                    } else {
                                        echo 'Never updated';
                                    }
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notes Section -->
                <?php if (!empty($card['note'])): ?>
                <div class="mb-6">
                    <h3 class="font-semibold text-gray-800 mb-3 flex items-center">
                        <i class="fas fa-sticky-note text-yellow-500 mr-2"></i>
                        Notes
                    </h3>
                    <div class="bg-yellow-50 border border-yellow-100 rounded-lg p-4">
                        <p class="text-gray-700 whitespace-pre-wrap"><?php echo htmlspecialchars($card['note']); ?></p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex justify-between items-center mt-6">
            <a href="info_card.php"
               class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-gray-300 transition-colors flex items-center">
                <i class="fas fa-arrow-left mr-2"></i>
                Back
            </a>
            <div class="flex space-x-3">
                <a href="edit_card.php?id=<?php echo $card['id']; ?>"
                   class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-gray-700 transition-colors flex items-center">
                    <i class="fas fa-edit mr-2"></i>
                    Edit Card
                </a>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-xl p-4 m-4 max-w-4xl max-h-[90vh]">
            <div class="flex justify-between items-center mb-4">
                <h3 id="modalTitle" class="text-lg font-semibold"></h3>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>
            <img id="modalImage" src="" alt="Preview" class="max-w-full max-h-[70vh] rounded mx-auto">
            <div class="text-center mt-4">
                <a id="downloadLink" href="" download class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 inline-flex items-center">
                    <i class="fas fa-download mr-2"></i>
                    Download Image
                </a>
            </div>
        </div>
    </div>

    <!-- Logout Modal -->
    <div id="logoutModal" class="fixed inset-0 bg-black/50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-xl shadow-lg w-full max-w-xs p-6">
            <h2 class="text-lg font-semibold text-gray-800 mb-3">
                Confirm Logout
            </h2>
            <p class="text-gray-600 mb-6">
                Are you sure you want to log out?
            </p>
            <div class="flex justify-end gap-3">
                <button onclick="closeLogoutModal()"
                    class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300">
                    Cancel
                </button>
                <button onclick="confirmLogout()"
                    class="px-4 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">
                    Logout
                </button>
            </div>
        </div>
    </div>

    <script>
        // View photo in modal
     // View photo in modal
function viewPhoto(imageSrc, title) {
    const modal = document.getElementById('imageModal');
    const modalImage = document.getElementById('modalImage');
    const modalTitle = document.getElementById('modalTitle');
    const downloadLink = document.getElementById('downloadLink');
    
    // Prepend the uploads directory to the image source
    const fullImagePath = 'uploads/' + imageSrc;
    
    modalImage.src = fullImagePath;
    modalTitle.textContent = title;
    downloadLink.href = fullImagePath;
    downloadLink.download = title.toLowerCase().replace(/\s+/g, '_') + '.jpg';
    modal.classList.remove('hidden');
}

        // Close modal
        function closeModal() {
            document.getElementById('imageModal').classList.add('hidden');
        }

        // Print function
        function printCard() {
            window.print();
        }

        // Logout functions
        function openLogoutModal() {
            document.getElementById('logoutModal').classList.remove('hidden');
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.add('hidden');
        }

        function confirmLogout() {
            window.location.href = 'logout.php';
        }

        // Delete card
        function deleteCard(cardId) {
            if (!confirm('Are you sure you want to delete this card?')) return;
            
            fetch('delete_card.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'card_id=' + cardId
            })
            .then(response => response.text())
            .then(data => {
                if (data === 'success') {
                    window.location.href = 'info_card.php';
                } else {
                    alert('Error deleting card: ' + data);
                }
            })
            .catch(error => {
                alert('Error deleting card: ' + error);
            });
        }

        // Close modal when clicking outside
        document.getElementById('imageModal')?.addEventListener('click', function(e) {
            if (e.target.id === 'imageModal') {
                closeModal();
            }
        });
    </script>
</body>
</html>