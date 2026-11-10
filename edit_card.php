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
    header("Location: view_all_cards.php");
    exit;
}

$card_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Check if user is admin
$isAdmin = false;
$stmt = $conn->prepare("SELECT role FROM User WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $isAdmin = ($row['role'] === 'ADMIN');
}
$stmt->close();

// Fetch card details
$sql = "SELECT * FROM business_cards WHERE id = ? AND users = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $card_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: view_all_cards.php");
    exit;
}

$card = $result->fetch_assoc();
$stmt->close();

// Load countries and categories (same as before)
$countries = [];
$countriesJsonFilePath = __DIR__ . '/json/countries.json';
if (file_exists($countriesJsonFilePath)) {
    $countriesJson = file_get_contents($countriesJsonFilePath);
    $countries = json_decode($countriesJson, true);
    sort($countries);
} else {
    $countries = ["United States", "United Kingdom", "Canada", "Australia", "Germany"];
    sort($countries);
}

$categories = [];
$categoriesJsonFilePath = __DIR__ . '/json/category.json';
if (file_exists($categoriesJsonFilePath)) {
    $categoriesJson = file_get_contents($categoriesJsonFilePath);
    $categories = json_decode($categoriesJson, true);
    sort($categories);
} else {
    $categories = ["Bakery & Pastry Shop", "Restaurant", "Café & Coffee Shop", "Fast Food"];
    sort($categories);
}

// Handle form submission (same as before)
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $company = trim($_POST['company'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $date_added = trim($_POST['date_added'] ?? '');
    $note = trim($_POST['note'] ?? '');
    
    if (empty($company)) $errors[] = "Company name is required.";
    if (empty($category)) $errors[] = "Category is required.";
    if (empty($country)) $errors[] = "Country is required.";
    if (empty($date_added)) $errors[] = "Date is required.";
    
    $front_photo = $card['front_photo'];
    $back_photo = $card['back_photo'];
    
    $remove_front = isset($_POST['remove_front']) && $_POST['remove_front'] === '1';
    $remove_back = isset($_POST['remove_back']) && $_POST['remove_back'] === '1';
    
    $upload_dir = "uploads/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    // Process front photo
    if (isset($_POST['front_photo_data']) && !empty($_POST['front_photo_data'])) {
        $front_photo_data = $_POST['front_photo_data'];
        if (strpos($front_photo_data, 'data:image') === 0) {
            list($type, $data) = explode(';', $front_photo_data);
            list(, $data) = explode(',', $data);
            $data = base64_decode($data);
            $filename = 'front_' . uniqid() . '_' . time() . '.jpg';
            $target_path = $upload_dir . $filename;
            
            if (file_put_contents($target_path, $data)) {
                if (!empty($card['front_photo']) && file_exists($upload_dir . $card['front_photo'])) {
                    unlink($upload_dir . $card['front_photo']);
                }
                $front_photo = $filename;
            } else {
                $errors[] = "Failed to save front photo.";
            }
        }
    } elseif ($remove_front) {
        if (!empty($card['front_photo']) && file_exists($upload_dir . $card['front_photo'])) {
            unlink($upload_dir . $card['front_photo']);
        }
        $front_photo = '';
    }
    
    // Process back photo
    if (isset($_POST['back_photo_data']) && !empty($_POST['back_photo_data'])) {
        $back_photo_data = $_POST['back_photo_data'];
        if (strpos($back_photo_data, 'data:image') === 0) {
            list($type, $data) = explode(';', $back_photo_data);
            list(, $data) = explode(',', $data);
            $data = base64_decode($data);
            $filename = 'back_' . uniqid() . '_' . time() . '.jpg';
            $target_path = $upload_dir . $filename;
            
            if (file_put_contents($target_path, $data)) {
                if (!empty($card['back_photo']) && file_exists($upload_dir . $card['back_photo'])) {
                    unlink($upload_dir . $card['back_photo']);
                }
                $back_photo = $filename;
            } else {
                $errors[] = "Failed to save back photo.";
            }
        }
    } elseif ($remove_back) {
        if (!empty($card['back_photo']) && file_exists($upload_dir . $card['back_photo'])) {
            unlink($upload_dir . $card['back_photo']);
        }
        $back_photo = '';
    }
    
    if (empty($errors)) {
        $update_sql = "UPDATE business_cards SET 
                      company = ?, 
                      category = ?, 
                      country = ?, 
                      front_photo = ?, 
                      back_photo = ?, 
                      note = ?, 
                      date_added = ?,
                      last_updated = NOW() 
                      WHERE id = ? AND users = ?";
        
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param(
            "sssssssii", 
            $company, 
            $category, 
            $country, 
            $front_photo, 
            $back_photo, 
            $note, 
            $date_added,
            $card_id, 
            $user_id
        );
        
        if ($update_stmt->execute()) {
            $success = true;
            $card['company'] = $company;
            $card['category'] = $category;
            $card['country'] = $country;
            $card['front_photo'] = $front_photo;
            $card['back_photo'] = $back_photo;
            $card['note'] = $note;
            $card['date_added'] = $date_added;
        } else {
            $errors[] = "Database error: " . $update_stmt->error;
        }
        $update_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Edit Card - <?php echo htmlspecialchars($card['company']); ?></title>
    
    <!-- Optimized for mobile -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* Mobile-First Design */
        * {
            -webkit-tap-highlight-color: transparent;
            -webkit-touch-callout: none;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f3f4f6;
            padding: 0;
            margin: 0;
            overscroll-behavior: none;
        }
        
        /* Better touch targets */
        button, 
        .clickable,
        select,
        input[type="file"],
        [onclick] {
            touch-action: manipulation;
            min-height: 48px;
        }
        
        /* Camera Modal - Mobile Optimized */
        .camera-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #000000;
            z-index: 99999;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 0;
        }
        
        .camera-container {
            width: 100%;
            height: 100%;
            background: #000000;
            display: flex;
            flex-direction: column;
        }
        
        .camera-header {
            padding: 16px 16px 12px 16px;
            background: #000000;
            color: white;
            border-bottom: 1px solid #333333;
        }
        
        .camera-preview {
            flex: 1;
            position: relative;
            background: #000000;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .camera-video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .camera-frame {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 320px;
            aspect-ratio: 1.6 / 1;
            border: 2px solid rgba(16, 185, 129, 0.8);
            border-radius: 12px;
            box-shadow: 0 0 0 1000px rgba(0,0,0,0.7);
            pointer-events: none;
        }
        
        .frame-corner {
            position: absolute;
            width: 20px;
            height: 20px;
            border: 2px solid #10b981;
        }
        
        .corner-tl { top: -2px; left: -2px; border-right: none; border-bottom: none; border-radius: 8px 0 0 0; }
        .corner-tr { top: -2px; right: -2px; border-left: none; border-bottom: none; border-radius: 0 8px 0 0; }
        .corner-bl { bottom: -2px; left: -2px; border-right: none; border-top: none; border-radius: 0 0 0 8px; }
        .corner-br { bottom: -2px; right: -2px; border-left: none; border-top: none; border-radius: 0 0 8px 0; }
        
        .camera-controls {
            padding: 20px 16px 30px 16px;
            background: #000000;
            border-top: 1px solid #333333;
        }
        
        .capture-btn {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            border: 4px solid rgba(255,255,255,0.9);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.5);
            transition: transform 0.1s;
            margin: 0 auto;
        }
        
        .capture-btn:active {
            transform: scale(0.95);
        }
        
        .control-btn {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background: rgba(255,255,255,0.15);
            color: white;
            border: 2px solid rgba(255,255,255,0.3);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            backdrop-filter: blur(10px);
        }
        
        .control-btn:active {
            background: rgba(255,255,255,0.25);
        }
        
        /* Mobile Notifications */
        .mobile-notification {
            position: fixed;
            top: 20px;
            left: 20px;
            right: 20px;
            padding: 16px 20px;
            border-radius: 12px;
            color: white;
            z-index: 100000;
            animation: slideDown 0.3s ease-out;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            font-size: 16px;
            font-weight: 500;
        }
        
        @keyframes slideDown {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        /* Photo preview containers */
        .photo-preview-container {
            aspect-ratio: 1.6 / 1;
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
        }
        
        .photo-preview {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background-size: cover;
            background-position: center;
        }
        
        /* Better touch feedback */
        .touch-feedback {
            transition: background-color 0.1s;
        }
        
        .touch-feedback:active {
            background-color: rgba(0,0,0,0.05);
        }
        
        /* iOS specific */
        @supports (-webkit-touch-callout: none) {
            .camera-preview {
                height: -webkit-fill-available;
            }
            
            .min-h-screen {
                min-height: -webkit-fill-available;
            }
        }
        
        /* Hide select2 on mobile - use native selects */
        @media (max-width: 640px) {
            .select2-container {
                display: none !important;
            }
            
            select {
                display: block !important;
                appearance: none;
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3E%3Cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3E%3C/svg%3E");
                background-position: right 12px center;
                background-repeat: no-repeat;
                background-size: 20px;
                padding-right: 40px !important;
            }
        }
        
        /* Better form inputs for mobile */
        input, select, textarea {
            font-size: 16px !important; /* Prevents zoom on iOS */
        }
        
        /* Photo action buttons */
        .photo-action-btn {
            padding: 12px;
            font-size: 15px;
            border-radius: 10px;
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Simple Header for Mobile -->
    <div class="bg-white shadow-sm sticky top-0 z-40">
        <div class="px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="view_all_cards.php" class="text-gray-600 p-2 -ml-2">
                    <i class="fas fa-arrow-left text-xl"></i>
                </a>
                <div>
                    <h1 class="font-semibold text-gray-900 text-lg">Edit Card</h1>
                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($card['company']); ?></p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <?php if ($isAdmin): ?>
                    <a href="create_users.php" class="bg-purple-100 p-2 rounded-lg">
                        <i class="fas fa-user-plus text-purple-600 text-lg"></i>
                    </a>
                <?php endif; ?>
                <button onclick="openLogoutModal()" class="p-2">
                    <i class="fas fa-sign-out-alt text-red-500 text-lg"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="px-4 py-4 max-w-lg mx-auto">
        
        <!-- Success/Error Messages -->
        <?php if (!empty($errors)): ?>
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-xl">
                <div class="flex items-start gap-2">
                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5"></i>
                    <div>
                        <p class="font-semibold text-red-800 text-sm mb-1">Please fix:</p>
                        <ul class="list-disc list-inside text-red-600 text-xs">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-xl">
                <div class="flex items-start gap-2">
                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                    <div class="flex-1">
                        <p class="font-semibold text-green-800">Card updated successfully!</p>
                        <div class="flex gap-4 mt-2">
                            <a href="view_all_cards.php" class="text-green-700 text-sm underline">Back to Cards</a>
                            <a href="card_details.php?id=<?php echo $card_id; ?>" class="text-green-700 text-sm underline">View Card</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Edit Form -->
        <form id="editCardForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $card_id; ?>" enctype="multipart/form-data" class="space-y-5">
            
            <!-- Basic Information Card -->
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="text-md font-semibold text-gray-800 mb-4 flex items-center gap-2 border-b pb-3">
                    <i class="fas fa-info-circle text-blue-500"></i>
                    Basic Information
                </h2>
                
                <div class="space-y-4">
                    <!-- Company -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Company Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="company" 
                               value="<?php echo htmlspecialchars($card['company']); ?>"
                               class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base"
                               placeholder="e.g. Starbucks Coffee"
                               required>
                    </div>
                    
                    <!-- Category -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Category <span class="text-red-500">*</span>
                        </label>
                        <select name="category" 
                                class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base"
                                required>
                            <option value="">Select category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo htmlspecialchars($category); ?>" 
                                    <?php echo ($card['category'] == $category) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Country -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Country <span class="text-red-500">*</span>
                        </label>
                        <select name="country" 
                                class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base"
                                required>
                            <option value="">Select country</option>
                            <?php foreach ($countries as $country): ?>
                                <option value="<?php echo htmlspecialchars($country); ?>" 
                                    <?php echo ($card['country'] == $country) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($country); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Date -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="date_added" 
                               value="<?php echo htmlspecialchars($card['date_added']); ?>"
                               class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base"
                               required>
                    </div>
                </div>
            </div>

            <!-- Photos Section -->
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="text-md font-semibold text-gray-800 mb-3 flex items-center gap-2">
                    <i class="fas fa-camera text-green-500"></i>
                    Card Photos
                </h2>
                <p class="text-xs text-gray-500 mb-4 flex items-center gap-1">
                    <i class="fas fa-info-circle"></i>
                    Tap photo to retake with back camera
                </p>
                
                <div class="space-y-6">
                    <!-- Front Photo -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium text-gray-700 flex items-center gap-1">
                                <i class="fas fa-id-card text-blue-500"></i>
                                Front Side
                            </label>
                            <?php if (!empty($card['front_photo'])): ?>
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">
                                    <i class="fas fa-check-circle mr-1"></i>Has photo
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Photo Preview -->
                        <div id="frontPreviewContainer" 
                             onclick="startBackCamera('front')"
                             class="photo-preview-container border-2 <?php echo !empty($card['front_photo']) ? 'border-green-500' : 'border-dashed border-gray-300 bg-gray-50'; ?> rounded-xl mb-3 overflow-hidden cursor-pointer">
                            
                            <div id="frontPreview" class="photo-preview">
                                <?php if (!empty($card['front_photo'])): ?>
                                    <div class="relative w-full h-full group">
                                        <img src="uploads/<?php echo htmlspecialchars($card['front_photo']); ?>" 
                                             class="w-full h-full object-cover" 
                                             alt="Front photo">
                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-active:opacity-100 transition-opacity flex items-center justify-center">
                                            <span class="bg-white/90 text-gray-900 px-4 py-2 rounded-full text-sm font-medium shadow-lg">
                                                <i class="fas fa-camera mr-2"></i>Tap to retake
                                            </span>
                                        </div>
                                        <button type="button" 
                                                onclick="removeExistingPhoto('front', event)" 
                                                class="absolute top-2 right-2 bg-red-500 text-white w-10 h-10 rounded-full flex items-center justify-center shadow-lg active:bg-red-600">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center p-6">
                                        <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <i class="fas fa-camera text-gray-500 text-2xl"></i>
                                        </div>
                                        <span class="text-sm font-medium text-gray-700 block">Tap to take photo</span>
                                        <span class="text-xs text-gray-500 mt-1 block">or choose from gallery</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Hidden inputs -->
                        <input type="file" id="frontPhoto" accept="image/*" capture="environment" class="hidden">
                        <input type="hidden" id="frontPhotoData" name="front_photo_data">
                        <input type="hidden" id="remove_front" name="remove_front" value="0">
                        
                        <!-- Action Buttons - Mobile optimized -->
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" 
                                    onclick="startBackCamera('front')" 
                                    class="photo-action-btn bg-green-600 text-white rounded-xl flex items-center justify-center gap-2 active:bg-green-700 font-medium shadow-sm">
                                <i class="fas fa-camera"></i>
                                <span>Retake</span>
                            </button>
                            <button type="button" 
                                    onclick="document.getElementById('frontPhoto').click()"
                                    class="photo-action-btn bg-gray-100 text-gray-700 rounded-xl flex items-center justify-center gap-2 active:bg-gray-200 font-medium">
                                <i class="fas fa-image"></i>
                                <span>Gallery</span>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Back Photo -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-sm font-medium text-gray-700 flex items-center gap-1">
                                <i class="fas fa-id-card-alt text-purple-500"></i>
                                Back Side
                            </label>
                            <?php if (!empty($card['back_photo'])): ?>
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">
                                    <i class="fas fa-check-circle mr-1"></i>Has photo
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Photo Preview -->
                        <div id="backPreviewContainer" 
                             onclick="startBackCamera('back')"
                             class="photo-preview-container border-2 <?php echo !empty($card['back_photo']) ? 'border-green-500' : 'border-dashed border-gray-300 bg-gray-50'; ?> rounded-xl mb-3 overflow-hidden cursor-pointer">
                            
                            <div id="backPreview" class="photo-preview">
                                <?php if (!empty($card['back_photo'])): ?>
                                    <div class="relative w-full h-full group">
                                        <img src="uploads/<?php echo htmlspecialchars($card['back_photo']); ?>" 
                                             class="w-full h-full object-cover" 
                                             alt="Back photo">
                                        <div class="absolute inset-0 bg-black/40 opacity-0 group-active:opacity-100 transition-opacity flex items-center justify-center">
                                            <span class="bg-white/90 text-gray-900 px-4 py-2 rounded-full text-sm font-medium shadow-lg">
                                                <i class="fas fa-camera mr-2"></i>Tap to retake
                                            </span>
                                        </div>
                                        <button type="button" 
                                                onclick="removeExistingPhoto('back', event)" 
                                                class="absolute top-2 right-2 bg-red-500 text-white w-10 h-10 rounded-full flex items-center justify-center shadow-lg active:bg-red-600">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="text-center p-6">
                                        <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-3">
                                            <i class="fas fa-camera text-gray-500 text-2xl"></i>
                                        </div>
                                        <span class="text-sm font-medium text-gray-700 block">Tap to take photo</span>
                                        <span class="text-xs text-gray-500 mt-1 block">or choose from gallery</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Hidden inputs -->
                        <input type="file" id="backPhoto" accept="image/*" capture="environment" class="hidden">
                        <input type="hidden" id="backPhotoData" name="back_photo_data">
                        <input type="hidden" id="remove_back" name="remove_back" value="0">
                        
                        <!-- Action Buttons -->
                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" 
                                    onclick="startBackCamera('back')" 
                                    class="photo-action-btn bg-green-600 text-white rounded-xl flex items-center justify-center gap-2 active:bg-green-700 font-medium shadow-sm">
                                <i class="fas fa-camera"></i>
                                <span>Retake</span>
                            </button>
                            <button type="button" 
                                    onclick="document.getElementById('backPhoto').click()"
                                    class="photo-action-btn bg-gray-100 text-gray-700 rounded-xl flex items-center justify-center gap-2 active:bg-gray-200 font-medium">
                                <i class="fas fa-image"></i>
                                <span>Gallery</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes Section -->
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="text-md font-semibold text-gray-800 mb-3 flex items-center gap-2">
                    <i class="fas fa-sticky-note text-yellow-500"></i>
                    Notes
                </h2>
                
                <textarea name="note" rows="4"
                          class="w-full px-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-base"
                          placeholder="Add any notes about this business card..."><?php echo htmlspecialchars($card['note'] ?? ''); ?></textarea>
            </div>

            <!-- Submit Button - Fixed at bottom on mobile -->
            <div class="sticky bottom-4 z-30">
                <div class="flex gap-3">
                    <a href="view_all_cards.php" 
                       class="flex-1 px-5 py-4 bg-gray-200 text-gray-700 rounded-xl text-center font-medium active:bg-gray-300">
                        Cancel
                    </a>
                    <button type="submit" id="submitBtn"
                            class="flex-2 px-5 py-4 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl font-medium shadow-lg active:from-blue-600 active:to-blue-700 flex items-center justify-center gap-2">
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Image Preview Modal (Full screen on mobile) -->
    <div id="imageModal" class="fixed inset-0 bg-black z-50 hidden flex-col">
        <div class="relative flex-1 flex items-center justify-center bg-black">
            <button onclick="closeModal()" class="absolute top-4 right-4 text-white z-10 p-3">
                <i class="fas fa-times-circle text-3xl"></i>
            </button>
            <img id="modalImage" src="" alt="Preview" class="max-w-full max-h-full object-contain">
        </div>
        <div id="modalCaption" class="bg-black text-white p-4 text-center border-t border-gray-800">
            Loading...
        </div>
    </div>

    <!-- Logout Modal - Mobile optimized -->
    <div id="logoutModal" class="fixed inset-0 bg-black/70 flex items-center justify-center hidden z-[100001] p-5">
        <div class="bg-white rounded-2xl w-full max-w-sm p-6">
            <div class="text-center mb-4">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i class="fas fa-sign-out-alt text-red-500 text-2xl"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-900 mb-2">Confirm Logout</h2>
                <p class="text-gray-600">Are you sure you want to log out?</p>
            </div>
            <div class="flex gap-3">
                <button onclick="closeLogoutModal()" 
                        class="flex-1 py-3.5 bg-gray-100 text-gray-700 rounded-xl font-medium active:bg-gray-200">
                    Cancel
                </button>
                <button onclick="confirmLogout()" 
                        class="flex-1 py-3.5 bg-red-500 text-white rounded-xl font-medium active:bg-red-600">
                    Logout
                </button>
            </div>
        </div>
    </div>

    <script>
        // ==================== MOBILE-OPTIMIZED CAMERA FUNCTIONS ====================
        let cameraStream = null;
        let isCameraActive = false;
        let currentCameraSide = null;
        
        const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) || 
                     (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
        
        // Start back camera - Mobile optimized
        async function startBackCamera(side) {
            console.log('Starting camera for:', side);
            currentCameraSide = side;
            
            try {
                showNotification('📷 Opening camera...', 'info');
                stopCamera();
                
                // Try to get back camera with mobile-optimized constraints
                const constraints = {
                    video: {
                        facingMode: { exact: "environment" },
                        width: { ideal: 1280 },
                        height: { ideal: 720 },
                        aspectRatio: 1.6
                    },
                    audio: false
                };
                
                const stream = await navigator.mediaDevices.getUserMedia(constraints);
                createCameraModal(side, stream);
                
            } catch (error) {
                console.error('Camera error:', error);
                
                // Try fallback without exact facingMode
                try {
                    const fallbackConstraints = {
                        video: {
                            facingMode: "environment",
                            width: { ideal: 1280 },
                            height: { ideal: 720 }
                        },
                        audio: false
                    };
                    const stream = await navigator.mediaDevices.getUserMedia(fallbackConstraints);
                    createCameraModal(side, stream);
                } catch (fallbackError) {
                    handleMobileCameraError(error);
                }
            }
        }
        
        // Create mobile-optimized camera modal
        function createCameraModal(side, stream) {
            // Remove existing modal
            const existingModal = document.getElementById('cameraModal');
            if (existingModal) existingModal.remove();
            
            // Full-screen camera modal for mobile
            const modalHTML = `
            <div id="cameraModal" class="camera-modal-overlay">
                <div class="camera-container">
                    <!-- Header -->
                    <div class="camera-header flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-bold flex items-center">
                                <i class="fas fa-camera mr-2"></i>
                                ${side === 'front' ? 'Front' : 'Back'} Card
                            </h3>
                            <p class="text-xs text-gray-400 mt-1">Align card in frame</p>
                        </div>
                        <button id="closeCameraBtn" class="p-2">
                            <i class="fas fa-times text-2xl"></i>
                        </button>
                    </div>
                    
                    <!-- Camera Preview -->
                    <div class="camera-preview">
                        <video id="cameraVideo" autoplay playsinline class="camera-video"></video>
                        <div class="camera-frame">
                            <div class="frame-corner corner-tl"></div>
                            <div class="frame-corner corner-tr"></div>
                            <div class="frame-corner corner-bl"></div>
                            <div class="frame-corner corner-br"></div>
                        </div>
                        <div id="cameraInfo" class="absolute bottom-4 left-4 bg-black/70 text-white px-3 py-1.5 rounded-full text-xs">
                            Back Camera
                        </div>
                    </div>
                    
                    <!-- Controls -->
                    <div class="camera-controls">
                        <!-- Auto-crop toggle -->
                        <div class="flex justify-center items-center mb-5">
                            <label class="flex items-center gap-3 text-white text-sm">
                                <span class="text-gray-300">Auto-crop</span>
                                <div class="relative">
                                    <input type="checkbox" id="autoCropToggle" class="sr-only" checked>
                                    <div class="w-12 h-6 bg-gray-600 rounded-full peer-checked:bg-green-600 transition-colors"></div>
                                    <div class="absolute left-1 top-1 w-4 h-4 bg-white rounded-full transition-transform peer-checked:translate-x-6"></div>
                                </div>
                            </label>
                        </div>
                        
                        <!-- Action buttons -->
                        <div class="flex justify-center items-center gap-8">
                            <button id="toggleFlashBtn" class="control-btn">
                                <i class="fas fa-bolt"></i>
                            </button>
                            <button id="captureBtn" class="capture-btn">
                                <i class="fas fa-camera"></i>
                            </button>
                            <button id="cancelBtn" class="control-btn">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>`;
            
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            // Initialize camera
            const video = document.getElementById('cameraVideo');
            video.srcObject = stream;
            cameraStream = stream;
            isCameraActive = true;
            
            // Setup event listeners
            document.getElementById('closeCameraBtn').onclick = closeCamera;
            document.getElementById('cancelBtn').onclick = closeCamera;
            document.getElementById('captureBtn').onclick = () => capturePhoto(video, side);
            
            // Flash toggle
            let flashOn = false;
            document.getElementById('toggleFlashBtn').onclick = async function() {
                const track = stream.getVideoTracks()[0];
                if (track && track.getCapabilities && track.getCapabilities().torch) {
                    flashOn = !flashOn;
                    await track.applyConstraints({ advanced: [{ torch: flashOn }] });
                    this.innerHTML = flashOn ? '<i class="fas fa-bolt text-yellow-400"></i>' : '<i class="fas fa-bolt"></i>';
                } else {
                    showNotification('Flash not available', 'info');
                }
            };
            
            // Close on background tap
            document.getElementById('cameraModal').onclick = function(e) {
                if (e.target === this) closeCamera();
            };
        }
        
        // Close camera
        function closeCamera() {
            if (cameraStream) {
                cameraStream.getTracks().forEach(track => track.stop());
                cameraStream = null;
            }
            isCameraActive = false;
            const modal = document.getElementById('cameraModal');
            if (modal) modal.remove();
        }
        
        // Capture photo with auto-crop
        async function capturePhoto(video, side) {
            if (!video || video.readyState !== 4) {
                showNotification('Camera not ready', 'error');
                return;
            }
            
            const captureBtn = document.getElementById('captureBtn');
            captureBtn.innerHTML = '<i class="fas fa-spinner fa-pulse"></i>';
            captureBtn.style.pointerEvents = 'none';
            
            try {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                
                // Auto-crop
                const autoCropToggle = document.getElementById('autoCropToggle');
                const shouldAutoCrop = autoCropToggle ? autoCropToggle.checked : true;
                
                let imageData;
                
                if (shouldAutoCrop) {
                    const cropWidth = canvas.width * 0.7;
                    const cropHeight = cropWidth / 1.6;
                    const cropX = (canvas.width - cropWidth) / 2;
                    const cropY = (canvas.height - cropHeight) / 2;
                    
                    const croppedCanvas = document.createElement('canvas');
                    croppedCanvas.width = cropWidth;
                    croppedCanvas.height = cropHeight;
                    croppedCanvas.getContext('2d').drawImage(
                        canvas,
                        cropX, cropY, cropWidth, cropHeight,
                        0, 0, cropWidth, cropHeight
                    );
                    
                    imageData = croppedCanvas.toDataURL('image/jpeg', 0.85);
                } else {
                    imageData = canvas.toDataURL('image/jpeg', 0.9);
                }
                
                // Compress for mobile
                const compressedData = await compressImage(imageData);
                
                // Update preview
                updatePhotoPreview(side, compressedData, shouldAutoCrop);
                
                // Store data
                document.getElementById(side + 'PhotoData').value = compressedData;
                document.getElementById('remove_' + side).value = '0';
                
                showNotification('✅ Photo captured!', 'success');
                
                // Close camera
                closeCamera();
                
            } catch (error) {
                console.error('Capture error:', error);
                showNotification('Failed to capture photo', 'error');
            } finally {
                captureBtn.innerHTML = '<i class="fas fa-camera"></i>';
                captureBtn.style.pointerEvents = 'auto';
            }
        }
        
        // Compress image
        function compressImage(imageData, maxWidth = 1024, quality = 0.8) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    let width = img.width;
                    let height = img.height;
                    
                    if (width > maxWidth) {
                        height = (maxWidth / width) * height;
                        width = maxWidth;
                    }
                    
                    canvas.width = width;
                    canvas.height = height;
                    
                    canvas.getContext('2d').drawImage(img, 0, 0, width, height);
                    resolve(canvas.toDataURL('image/jpeg', quality));
                };
                img.onerror = reject;
                img.src = imageData;
            });
        }
        
        // Update photo preview
        function updatePhotoPreview(side, imageData, isCropped) {
            const previewDiv = document.getElementById(side + 'Preview');
            const container = document.getElementById(side + 'PreviewContainer');
            
            previewDiv.innerHTML = `
                <div class="relative w-full h-full">
                    <img src="${imageData}" class="w-full h-full object-cover" alt="${side} photo">
                    <div class="absolute inset-0 bg-black/40 opacity-0 active:opacity-100 transition-opacity flex items-center justify-center">
                        <span class="bg-white/90 text-gray-900 px-4 py-2 rounded-full text-sm font-medium shadow-lg">
                            <i class="fas fa-camera mr-2"></i>Tap to retake
                        </span>
                    </div>
                    <button type="button" 
                            onclick="removeExistingPhoto('${side}', event)" 
                            class="absolute top-2 right-2 bg-red-500 text-white w-10 h-10 rounded-full flex items-center justify-center shadow-lg active:bg-red-600">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                    ${isCropped ? '<div class="absolute top-2 left-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full">Auto-cropped</div>' : ''}
                </div>
            `;
            
            container.classList.remove('border-dashed', 'border-gray-300', 'bg-gray-50');
            container.classList.add('border-2', 'border-green-500');
        }
        
        // Remove existing photo
        function removeExistingPhoto(side, event) {
            event.stopPropagation();
            event.preventDefault();
            
            if (confirm(`Remove ${side} photo?`)) {
                const previewDiv = document.getElementById(side + 'Preview');
                const container = document.getElementById(side + 'PreviewContainer');
                
                previewDiv.innerHTML = `
                    <div class="text-center p-6">
                        <div class="w-16 h-16 bg-gray-200 rounded-full flex items-center justify-center mx-auto mb-3">
                            <i class="fas fa-camera text-gray-500 text-2xl"></i>
                        </div>
                        <span class="text-sm font-medium text-gray-700 block">Tap to take photo</span>
                        <span class="text-xs text-gray-500 mt-1 block">or choose from gallery</span>
                    </div>
                `;
                
                container.classList.remove('border-2', 'border-green-500');
                container.classList.add('border-dashed', 'border-gray-300', 'bg-gray-50');
                
                document.getElementById(side + 'PhotoData').value = '';
                document.getElementById('remove_' + side).value = '1';
                
                showNotification(`${side} photo removed`, 'info');
            }
        }
        
        // Handle file upload
        document.getElementById('frontPhoto').addEventListener('change', function(e) {
            handleFileUpload(this, 'front');
        });
        
        document.getElementById('backPhoto').addEventListener('change', function(e) {
            handleFileUpload(this, 'back');
        });
        
        function handleFileUpload(input, side) {
            if (input.files && input.files[0]) {
                const file = input.files[0];
                
                if (file.size > 10 * 1024 * 1024) {
                    showNotification('File too large (max 10MB)', 'error');
                    input.value = '';
                    return;
                }
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    compressImage(e.target.result).then(compressed => {
                        updatePhotoPreview(side, compressed, false);
                        document.getElementById(side + 'PhotoData').value = compressed;
                        document.getElementById('remove_' + side).value = '0';
                        showNotification('✅ Photo uploaded!', 'success');
                    });
                };
                reader.readAsDataURL(file);
            }
        }
        
        // Mobile camera error handling
        function handleMobileCameraError(error) {
            let message = 'Could not access camera.';
            
            if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
                message = 'Camera permission denied. Please allow camera access.';
            } else if (error.name === 'NotFoundError') {
                message = 'No back camera found on this device.';
            }
            
            showNotification(message, 'error');
            
            // Fallback to file upload
            setTimeout(() => {
                if (currentCameraSide) {
                    document.getElementById(currentCameraSide + 'Photo').click();
                }
            }, 500);
        }
        
        // Mobile notification
        function showNotification(message, type = 'success') {
            const notif = document.createElement('div');
            notif.className = `mobile-notification ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 'bg-blue-500'
            }`;
            notif.innerHTML = message;
            
            document.body.appendChild(notif);
            setTimeout(() => notif.remove(), 2500);
        }
        
        // Modal functions
        function closeModal() {
            document.getElementById('imageModal').classList.add('hidden');
        }
        
        function openLogoutModal() {
            document.getElementById('logoutModal').classList.remove('hidden');
        }
        
        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.add('hidden');
        }
        
        function confirmLogout() {
            window.location.href = 'logout.php';
        }
        
        // Form validation
        function validateForm() {
            const company = document.getElementById('company').value.trim();
            const category = document.querySelector('select[name="category"]').value;
            const country = document.querySelector('select[name="country"]').value;
            const date = document.querySelector('input[name="date_added"]').value;
            
            if (!company || !category || !country || !date) {
                alert('Please fill in all required fields.');
                return false;
            }
            return true;
        }
        
        // Form submit
        document.getElementById('editCardForm').addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                return;
            }
            
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-pulse mr-2"></i>Saving...';
            btn.disabled = true;
        });
        
        // Cleanup on page leave
        window.addEventListener('beforeunload', closeCamera);
        
        // Set default date if empty
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.querySelector('input[name="date_added"]');
            if (!dateInput.value) {
                const today = new Date().toISOString().split('T')[0];
                dateInput.value = today;
            }
        });
    </script>
</body>
</html>