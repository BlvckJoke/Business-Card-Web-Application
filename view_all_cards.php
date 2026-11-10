<?php
session_start();
require_once "config/db.php";

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['names'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['users'];
$upload_dir = 'uploads/'; // Define upload directory

// Initialize variables
$search_query = '';
$category_filter = '';
$country_filter = '';
$sort_by = 'date_added';
$sort_order = 'DESC';

// Handle search and filters
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
    $category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';
    $country_filter = isset($_GET['country']) ? trim($_GET['country']) : '';
    $sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'date_added';
    $sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
    
    // Validate sort fields
    $allowed_sort_fields = ['date_added', 'company', 'category', 'country'];
    if (!in_array($sort_by, $allowed_sort_fields)) {
        $sort_by = 'date_added';
    }
    
    $sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';
}

// Build the base query
$query = "SELECT * FROM business_cards WHERE users = ?";
$params = [$user_id];
$param_types = "i";

// Add search conditions
if (!empty($search_query)) {
    $query .= " AND (company LIKE ? OR note LIKE ?)";
    $search_param = "%{$search_query}%";
    $params[] = $search_param;
    $params[] = $search_param;
    $param_types .= "ss";
}

// Add category filter
if (!empty($category_filter)) {
    $query .= " AND category = ?";
    $params[] = $category_filter;
    $param_types .= "s";
}

// Add country filter
if (!empty($country_filter)) {
    $query .= " AND country = ?";
    $params[] = $country_filter;
    $param_types .= "s";
}

// Add sorting
$query .= " ORDER BY {$sort_by} {$sort_order}";

// Prepare and execute
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $cards = $result->fetch_all(MYSQLI_ASSOC);
    $total_cards = $result->num_rows;
} else {
    $cards = [];
    $total_cards = 0;
}

// Get unique categories and countries
$categories = [];
$countries = [];

$cat_query = "SELECT DISTINCT category FROM business_cards WHERE users = ? ORDER BY category";
if ($cat_stmt = $conn->prepare($cat_query)) {
    $cat_stmt->bind_param("i", $user_id);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

$country_query = "SELECT DISTINCT country FROM business_cards WHERE users = ? ORDER BY country";
if ($country_stmt = $conn->prepare($country_query)) {
    $country_stmt->bind_param("i", $user_id);
    $country_stmt->execute();
    $country_result = $country_stmt->get_result();
    while ($row = $country_result->fetch_assoc()) {
        $countries[] = $row['country'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>All Cards</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Mobile-optimized styles */
        * {
            -webkit-tap-highlight-color: transparent;
        }
        
        body {
            touch-action: manipulation;
            -webkit-font-smoothing: antialiased;
        }
        
        .safe-area-top {
            padding-top: env(safe-area-inset-top);
        }
        
        .safe-area-bottom {
            padding-bottom: env(safe-area-inset-bottom);
        }
        
        .mobile-tap {
            min-height: 44px;
            min-width: 44px;
        }
        
        .card-shadow {
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .mobile-card {
            border-radius: 12px;
            transition: all 0.2s ease;
        }
        
        .mobile-card:active {
            transform: scale(0.99);
            background-color: #f8fafc;
        }
        
        .photo-thumb {
            height: 70px;
            border-radius: 6px;
            object-fit: cover;
        }
        
        .text-ellipsis {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .line-clamp-4 {
  display: -webkit-box;
  -webkit-line-clamp: 4;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
        
        /* Custom scrollbar for mobile */
        ::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }
        
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 2px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 2px;
        }
        
        /* Better touch feedback */
        .touch-feedback:active {
            opacity: 0.7;
        }
        
        /* Mobile menu animations */
        .slide-up {
            animation: slideUp 0.3s ease-out;
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Loading skeleton */
        .skeleton {
            background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
            background-size: 200% 100%;
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Bottom sheet modal */
        .bottom-sheet {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-radius: 20px 20px 0 0;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
            z-index: 1000;
            transform: translateY(100%);
            transition: transform 0.3s ease;
        }
        
        .bottom-sheet.active {
            transform: translateY(0);
        }
        
        .bottom-sheet-handle {
            width: 40px;
            height: 4px;
            background: #ddd;
            border-radius: 2px;
            margin: 10px auto;
        }
        
        /* Card Action Menu */
      /* Fix for card menu z-index */
/* Card Menu styling */
.card-menu {
    position: absolute;
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    z-index: 1001;
    overflow: hidden;
    min-width: 180px;
    animation: menuFadeIn 0.2s ease;
}

@keyframes menuFadeIn {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card-menu-item {
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    cursor: pointer;
    transition: background-color 0.2s;
    border-bottom: 1px solid #f0f0f0;
    font-size: 15px;
}

.card-menu-item:last-child {
    border-bottom: none;
}

.card-menu-item:hover {
    background-color: #f8f8f8;
}

.card-menu-item:active {
    background-color: #f0f0f0;
}

/* Fix for delete modal z-index */
#deleteModal {
    z-index: 1100; /* Higher than backdrop (1000) and card menu (1001) */
}

#deleteModal .bg-white {
    position: relative;
    z-index: 1101;
}

/* Ensure backdrop doesn't block modal interactions */
#backdrop {
    z-index: 1000;
    pointer-events: auto;
}

#deleteModal {
    pointer-events: auto;
}

#deleteModal * {
    pointer-events: auto;
}

#cardMenu {
    pointer-events: auto;
}

/* Ensure menu items are clickable */
.card-menu-item {
    pointer-events: auto;
    position: relative;
}
        /* View Button */
        .view-btn {
            background: linear-gradient(135deg, #3B82F6, #1D4ED8);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 500;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            box-shadow: 0 2px 5px rgba(59, 130, 246, 0.3);
        }
        
        .view-btn:active {
            transform: scale(0.95);
        }
        
        /* Quick Actions */
        .quick-action-btn {
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        
        .quick-action-btn:active {
            opacity: 0.8;
        }
        
        /* FAB for adding new cards */
        .fab {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
            z-index: 100;
        }
        
        .fab:active {
            transform: scale(0.95);
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.4);
        }
        
        /* Photo preview overlay */
        .photo-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.2s;
            border-radius: 6px;
        }
        
        .photo-container:hover .photo-overlay {
            opacity: 1;
        }
        
        .photo-overlay-btn {
            background: rgba(255,255,255,0.9);
            color: #333;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Mobile Header -->
    <header class="bg-white shadow-sm safe-area-top sticky top-0 z-10">
        <div class="px-4 py-3">
            <div class="flex items-center justify-between">
                <!-- Back Button -->
                <a href="info_card.php" class="mobile-tap p-2 -ml-2">
                    <i class="fas fa-arrow-left text-xl text-gray-700"></i>
                </a>
                
                <!-- Title -->
                <div class="flex-1 text-center">
                    <h1 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-id-card text-blue-500 mr-2"></i>
                        All Cards
                    </h1>
                    <?php if ($total_cards > 0): ?>
                    <p class="text-xs text-gray-500 mt-1"><?php echo $total_cards; ?> card<?php echo $total_cards != 1 ? 's' : ''; ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Filter Toggle -->
                <button onclick="toggleFilterSheet()" class="mobile-tap p-2 -mr-2">
                    <i class="fas fa-sliders-h text-xl text-gray-700"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="min-h-screen pb-24 safe-area-bottom">
        <!-- Quick Search Bar -->
        <div class="sticky top-14 z-5 bg-white px-4 py-3 border-b">
            <form method="GET" action="" class="relative">
                <div class="relative">
                    <input type="text" 
                           name="search" 
                           value="<?php echo htmlspecialchars($search_query); ?>"
                           placeholder="Search cards..."
                           class="w-full pl-12 pr-10 py-3 bg-gray-100 border-0 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 focus:bg-white"
                           autocomplete="off">
                    <i class="fas fa-search absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    <?php if (!empty($search_query)): ?>
                    <button type="button" onclick="clearSearch()" 
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 mobile-tap">
                        <i class="fas fa-times"></i>
                    </button>
                    <?php endif; ?>
                </div>
                <!-- Quick Filters (Pills) -->
                <?php if (!empty($search_query) || !empty($category_filter) || !empty($country_filter)): ?>
                <div class="flex flex-wrap gap-2 mt-3">
                    <?php if (!empty($search_query)): ?>
                    <span class="inline-flex items-center bg-blue-100 text-blue-800 text-xs px-3 py-1 rounded-full">
                        Search: <?php echo htmlspecialchars($search_query); ?>
                        <button type="button" onclick="removeFilter('search')" class="ml-2 mobile-tap">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($category_filter)): ?>
                    <span class="inline-flex items-center bg-green-100 text-green-800 text-xs px-3 py-1 rounded-full">
                        <?php echo htmlspecialchars($category_filter); ?>
                        <button type="button" onclick="removeFilter('category')" class="ml-2 mobile-tap">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($country_filter)): ?>
                    <span class="inline-flex items-center bg-purple-100 text-purple-800 text-xs px-3 py-1 rounded-full">
                        <?php echo htmlspecialchars($country_filter); ?>
                        <button type="button" onclick="removeFilter('country')" class="ml-2 mobile-tap">
                            <i class="fas fa-times text-xs"></i>
                        </button>
                    </span>
                    <?php endif; ?>
                    
                    <a href="view_all_cards.php" 
                       class="inline-flex items-center text-gray-600 text-xs px-3 py-1 mobile-tap">
                        Clear all
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Cards List -->
        <div class="px-4 py-3">
            <?php if ($total_cards > 0): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 slide-up">
                    <?php foreach ($cards as $card): 
                        // Updated image paths with upload directory
                        $frontPhoto = !empty($card['front_photo']) ? $upload_dir . htmlspecialchars($card['front_photo']) : '';
                        $backPhoto  = !empty($card['back_photo']) ? $upload_dir . htmlspecialchars($card['back_photo']) : '';
                        $company    = htmlspecialchars($card['company']);
                        $category   = htmlspecialchars($card['category']);
                        $country    = htmlspecialchars($card['country']);
                        $note       = htmlspecialchars($card['note']);
                        $date       = date('M d, Y', strtotime($card['date_added']));
                        $cardId     = $card['id'];
                        
                        // Mobile truncation
                        $companyDisplay = strlen($company) > 25 ? substr($company, 0, 25) . '...' : $company;
                        $noteDisplay = $note ? (strlen($note) > 60 ? substr($note, 0, 60) . '...' : $note) : '';
                    ?>
                    
                    <div class="mobile-card bg-white card-shadow p-4 relative" data-card-id="<?php echo $cardId; ?>">
                        <!-- Card Menu Button (3 dots) -->
                        <button onclick="event.stopPropagation(); toggleCardMenu(<?php echo $cardId; ?>, event)" 
                                class="absolute top-3 right-3 mobile-tap p-2 text-gray-400">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                        
                        <!-- Company & Date -->
                        <div class="pr-8">
                            <h3 class="font-semibold text-gray-800 text-base mb-1">
                                <?php echo $companyDisplay; ?>
                            </h3>
                            <div class="flex items-center text-xs text-gray-500 mb-3">
                                <i class="far fa-calendar mr-1"></i>
                                <span><?php echo $date; ?></span>
                            </div>
                        </div>
                        
                        <!-- Category & Country Badges -->
                        <div class="flex flex-wrap gap-2 mb-3">
                            <span class="inline-flex items-center bg-blue-50 text-blue-700 text-xs px-2 py-1 rounded-full">
                                <i class="fas fa-tag mr-1 text-xs"></i>
                                <span class="max-w-[80px] truncate"><?php echo htmlspecialchars($category); ?></span>
                            </span>
                            <span class="inline-flex items-center bg-green-50 text-green-700 text-xs px-2 py-1 rounded-full">
                                <i class="fas fa-globe mr-1 text-xs"></i>
                                <span class="max-w-[70px] truncate"><?php echo htmlspecialchars($country); ?></span>
                            </span>
                        </div>
                        
                        <!-- Note Preview -->
                        <?php if (!empty($noteDisplay)): ?>
                        <div class="mb-3">
                            <p class="text-gray-600 text-xs line-clamp-4">
                                <?php echo nl2br(htmlspecialchars($noteDisplay)); ?>
                            </p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="flex gap-2 mb-4">
    <?php if ($frontPhoto): ?>
    <div class="flex-1 photo-container relative">
        <img src="<?php echo $frontPhoto; ?>" 
             alt="Front"
             class="photo-thumb w-full h-auto max-h-[500px] object-contain"
             onclick="event.stopPropagation(); viewImage('<?php echo $frontPhoto; ?>', 'Front Side')">
        <div class="photo-overlay">
            <button onclick="event.stopPropagation(); viewImage('<?php echo $frontPhoto; ?>', 'Front Side')" 
                    class="photo-overlay-btn mobile-tap">
                <i class="fas fa-expand mr-1"></i> View
            </button>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!$frontPhoto && !$backPhoto): ?>
    <div class="flex-1 text-center py-8 bg-gray-50 rounded-lg">
        <i class="fas fa-camera text-gray-300 text-2xl"></i>
        <p class="text-sm text-gray-400 mt-2">No photos</p>
    </div>
    <?php endif; ?>
</div>
                        
                        <!-- Action Buttons -->
                        <div class="flex justify-between items-center pt-3 border-t border-gray-100">
                            <!-- View Details Button -->
                            <a href="card_details_v.php?id=<?php echo $cardId; ?>" 
                               class="view-btn mobile-tap">
                                <i class="fas fa-eye"></i>
                                View Details
                            </a>
                            
                            <!-- Quick Actions -->
                            <div class="flex gap-2">
                                
                                <!-- Share Button (Optional) -->
                                <button onclick="shareCard(<?php echo $cardId; ?>)" 
                                        class="quick-action-btn bg-blue-50 text-blue-700 mobile-tap">
                                    <i class="fas fa-share"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Load More Indicator -->
                <?php if ($total_cards > 20): ?>
                <div class="text-center py-6">
                    <button onclick="loadMore()" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-full text-sm mobile-tap">
                        <i class="fas fa-chevron-down mr-2"></i>
                        Load More Cards
                    </button>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <!-- Empty State -->
                <div class="text-center py-12 px-4">
                    <div class="mx-auto w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-inbox text-gray-300 text-2xl"></i>
                    </div>
                    
                    <h3 class="text-lg font-semibold text-gray-700 mb-2">
                        <?php if (!empty($search_query) || !empty($category_filter) || !empty($country_filter)): ?>
                            No cards found
                        <?php else: ?>
                            No cards yet
                        <?php endif; ?>
                    </h3>
                    
                    <p class="text-gray-500 text-sm mb-6">
                        <?php if (!empty($search_query) || !empty($category_filter) || !empty($country_filter)): ?>
                            Try changing your search or filters
                        <?php else: ?>
                            Add your first business card
                        <?php endif; ?>
                    </p>
                    
                    <div class="flex gap-3 justify-center">
                        <?php if (!empty($search_query) || !empty($category_filter) || !empty($country_filter)): ?>
                        <a href="view_all_cards.php" 
                           class="px-4 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium mobile-tap">
                            Clear All
                        </a>
                        <?php endif; ?>
                        <a href="info_card.php" 
                           class="px-4 py-2.5 bg-blue-600 text-white rounded-lg text-sm font-medium mobile-tap flex items-center gap-2">
                            <i class="fas fa-plus"></i>
                            Add Card
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </main>


    <!-- Card Menu (Hidden by default) -->
    <div id="cardMenu" class="card-menu hidden" onclick="event.stopPropagation()">
        <div class="card-menu-item text-blue-600" id="menuView">
            <i class="fas fa-eye"></i>
            <span>View Details</span>
        </div>
        <div class="card-menu-item text-green-600" id="menuEdit">
            <i class="fas fa-edit"></i>
            <span>Edit Card</span>
        </div>
        <div class="card-menu-item text-red-600" id="menuDelete">
            <i class="fas fa-trash"></i>
            <span>Delete Card</span>
        </div>
    </div>

    <!-- Bottom Sheet - Filters -->
<!-- Bottom Sheet - Filters (Updated) -->
<div id="filterSheet" class="bottom-sheet" style="z-index: 1001;">
    <div class="bottom-sheet-handle"></div>
    <div class="px-6 pb-8">
        <h2 class="text-lg font-semibold mb-4">Filter & Sort</h2>
        
        <form method="GET" action="" id="filterForm">
            <!-- Category Filter -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-tag mr-2"></i>Category
                </label>
                <div class="flex flex-wrap gap-2">
                    <button type="button" 
                            onclick="selectFilter('category', '')"
                            class="px-3 py-1.5 rounded-full text-sm border <?php echo empty($category_filter) ? 'bg-blue-100 border-blue-300 text-blue-700' : 'bg-gray-100 border-gray-300 text-gray-700'; ?> mobile-tap">
                        All
                    </button>
                    <?php foreach ($categories as $cat): ?>
                    <?php
                    $isActive = $category_filter === $cat;
                    ?>
                    <button type="button" 
                            onclick="selectFilter('category', '<?php echo htmlspecialchars($cat); ?>')"
                            class="px-3 py-1.5 rounded-full text-sm border <?php echo $isActive ? 'bg-blue-100 border-blue-300 text-blue-700' : 'bg-gray-100 border-gray-300 text-gray-700'; ?> mobile-tap">
                        <?php echo htmlspecialchars($cat); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="category" id="categoryInput" value="<?php echo htmlspecialchars($category_filter); ?>">
            </div>

            <!-- Country Filter -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-globe mr-2"></i>Country
                </label>
                <div class="flex flex-wrap gap-2">
                    <button type="button" 
                            onclick="selectFilter('country', '')"
                            class="px-3 py-1.5 rounded-full text-sm border <?php echo empty($country_filter) ? 'bg-blue-100 border-blue-300 text-blue-700' : 'bg-gray-100 border-gray-300 text-gray-700'; ?> mobile-tap">
                        All
                    </button>
                    <?php foreach ($countries as $cntry): ?>
                    <?php
                    $isActive = $country_filter === $cntry;
                    ?>
                    <button type="button" 
                            onclick="selectFilter('country', '<?php echo htmlspecialchars($cntry); ?>')"
                            class="px-3 py-1.5 rounded-full text-sm border <?php echo $isActive ? 'bg-blue-100 border-blue-300 text-blue-700' : 'bg-gray-100 border-gray-300 text-gray-700'; ?> mobile-tap">
                        <?php echo htmlspecialchars($cntry); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="country" id="countryInput" value="<?php echo htmlspecialchars($country_filter); ?>">
            </div>
            
            <!-- Sort Options -->
            <div class="mb-8">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <i class="fas fa-sort mr-2"></i>Sort By
                </label>
                <div class="space-y-2">
                    <?php 
                    $sort_options = [
                        'date_added' => ['icon' => 'calendar', 'label' => 'Date Added'],
                        'company' => ['icon' => 'building', 'label' => 'Company'],
                        'category' => ['icon' => 'tag', 'label' => 'Category'],
                        'country' => ['icon' => 'globe', 'label' => 'Country']
                    ];
                    
                    foreach ($sort_options as $key => $option): 
                    ?>
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-<?php echo $option['icon']; ?> text-gray-400 mr-3"></i>
                            <span class="text-sm"><?php echo $option['label']; ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" 
                                    onclick="selectSort('<?php echo $key; ?>', 'ASC')"
                                    class="w-8 h-8 rounded-full flex items-center justify-center <?php echo ($sort_by === $key && $sort_order === 'ASC') ? 'bg-blue-100 text-blue-600' : 'bg-gray-200 text-gray-500'; ?> mobile-tap">
                                <i class="fas fa-sort-up text-xs"></i>
                            </button>
                            <button type="button" 
                                    onclick="selectSort('<?php echo $key; ?>', 'DESC')"
                                    class="w-8 h-8 rounded-full flex items-center justify-center <?php echo ($sort_by === $key && $sort_order === 'DESC') ? 'bg-blue-100 text-blue-600' : 'bg-gray-200 text-gray-500'; ?> mobile-tap">
                                <i class="fas fa-sort-down text-xs"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <input type="hidden" name="sort" id="sortInput" value="<?php echo htmlspecialchars($sort_by); ?>">
                <input type="hidden" name="order" id="orderInput" value="<?php echo htmlspecialchars($sort_order); ?>">
            </div>
            
            <!-- Action Buttons -->
            <div class="flex gap-3">
                <button type="button" 
                        onclick="toggleFilterSheet()"
                        class="flex-1 py-3 bg-gray-100 text-gray-700 rounded-lg font-medium text-sm mobile-tap">
                    Cancel
                </button>
                <button type="submit" 
                        class="flex-1 py-3 bg-blue-600 text-white rounded-lg font-medium text-sm mobile-tap">
                    Apply Filters
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Backdrop (Updated z-index) -->
<div id="backdrop" class="fixed inset-0 bg-black/50 z-[1000] hidden" onclick="hideAllModals()"></div>

    <!-- Image View Modal -->
    <div id="imageModal" class="fixed inset-0 bg-black z-50 hidden">
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <img id="modalImage" src="" alt="" class="max-w-full max-h-full object-contain">
        </div>
        <button onclick="closeImageModal()" 
                class="absolute top-4 right-4 w-10 h-10 bg-black/50 text-white rounded-full flex items-center justify-center mobile-tap">
            <i class="fas fa-times"></i>
        </button>
        <button onclick="downloadImage()" 
                class="absolute bottom-4 right-4 w-10 h-10 bg-black/50 text-white rounded-full flex items-center justify-center mobile-tap">
            <i class="fas fa-download"></i>
        </button>
    </div>


<div id="deleteModal" class="fixed inset-0 flex items-center justify-center z-[1100] hidden" onclick="hideDeleteModal()">
 
    <div class="absolute inset-0 bg-black/50"></div>
    

    <div class="relative bg-white rounded-2xl p-6 w-11/12 max-w-sm shadow-xl" onclick="event.stopPropagation()">
        <div class="text-center mb-6">
            <div class="mx-auto w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
            </div>
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Delete Card?</h3>
            <p class="text-gray-600 text-sm">This action cannot be undone.</p>
        </div>
        <div class="flex gap-3">
            <button onclick="hideDeleteModal()" 
                    class="flex-1 py-3 bg-gray-100 text-gray-700 rounded-lg font-medium mobile-tap hover:bg-gray-200 transition-colors">
                Cancel
            </button>
            <button id="confirmDeleteBtn" 
                    class="flex-1 py-3 bg-red-600 text-white rounded-lg font-medium mobile-tap hover:bg-red-700 transition-colors">
                Delete
            </button>
        </div>
    </div>
</div>


    <!-- FAB for adding new card -->
    <a href="info_card.php" class="fab mobile-tap">
        <i class="fas fa-plus"></i>
    </a>

    <script>
    // Mobile-specific JavaScript
    document.addEventListener('DOMContentLoaded', function() {
        // Add touch feedback to all mobile-tap elements
        document.querySelectorAll('.mobile-tap').forEach(element => {
            element.addEventListener('touchstart', function() {
                this.style.opacity = '0.7';
            });
            
            element.addEventListener('touchend', function() {
                this.style.opacity = '1';
            });
            
            element.addEventListener('touchcancel', function() {
                this.style.opacity = '1';
            });
        });
        const confirmBtn = document.getElementById('confirmDeleteBtn');
    if (confirmBtn) {
        confirmBtn.onclick = deleteCard;
    }

    document.addEventListener('click', function(event) {
        if (cardMenuVisible && !event.target.closest('#cardMenu') && !event.target.closest('[onclick*="toggleCardMenu"]')) {
            hideCardMenu();
        }
    });
        // Prevent accidental double-tap zoom
        let lastTap = 0;
        document.addEventListener('touchend', function(event) {
            const currentTime = new Date().getTime();
            const tapLength = currentTime - lastTap;
            if (tapLength < 500 && tapLength > 0) {
                event.preventDefault();
            }
            lastTap = currentTime;
        }, false);
    });
    
    // Filter Bottom Sheet
    let isFilterSheetOpen = false;
    
    function toggleFilterSheet() {
        const sheet = document.getElementById('filterSheet');
        const backdrop = document.getElementById('backdrop');
        
        if (!isFilterSheetOpen) {
            sheet.classList.add('active');
            backdrop.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        } else {
            sheet.classList.remove('active');
            backdrop.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
        
        isFilterSheetOpen = !isFilterSheetOpen;
    }
    
    // Card Menu
    let currentCardId = null;
let cardMenuVisible = false;

function hideCardMenu() {
    const menu = document.getElementById('cardMenu');
    const backdrop = document.getElementById('backdrop');
    
    if (menu) {
        menu.classList.add('hidden');
    }
    if (backdrop && !document.getElementById('filterSheet').classList.contains('active') && 
        !document.getElementById('imageModal').classList.contains('hidden') && 
        !document.getElementById('deleteModal').classList.contains('hidden')) {
        backdrop.classList.add('hidden');
    }
    
    cardMenuVisible = false;
    currentCardId = null;
    document.body.style.overflow = 'auto';
}

function toggleCardMenu(cardId, event) {
    event.preventDefault();
    event.stopPropagation();
    
    const menu = document.getElementById('cardMenu');
    const backdrop = document.getElementById('backdrop');
    
    if (cardMenuVisible && currentCardId === cardId) {
        // Hide if clicking same card
        hideCardMenu();
    } else {
        // Position menu near the button
        const button = event.target.closest('button');
        const buttonRect = button.getBoundingClientRect();
        
        // Calculate position (ensure menu stays within viewport)
        let top = buttonRect.bottom + window.scrollY + 5;
        let right = window.innerWidth - buttonRect.right;
        
        // Adjust if menu would go off screen
        const menuWidth = 180; // min-width from CSS
        if (right + menuWidth > window.innerWidth) {
            right = window.innerWidth - menuWidth - 10;
        }
        
        menu.style.top = `${top}px`;
        menu.style.right = `${right}px`;
        
        // Show menu
        menu.classList.remove('hidden');
        backdrop.classList.remove('hidden');
        cardMenuVisible = true;
        currentCardId = cardId;
        
        // Prevent body scrolling
        document.body.style.overflow = 'hidden';
        
        // Set menu actions
        document.getElementById('menuView').onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            window.location.href = 'card_details_v.php?id=' + cardId;
        };
        
        document.getElementById('menuEdit').onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            window.location.href = 'edit_card.php?id=' + cardId;
        };
        
        document.getElementById('menuDelete').onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            hideCardMenu();
            setTimeout(() => confirmDelete(cardId), 300);
        };
    }
}
function hideAllModals() {
    const backdrop = document.getElementById('backdrop');
    const filterSheet = document.getElementById('filterSheet');
    const imageModal = document.getElementById('imageModal');
    const deleteModal = document.getElementById('deleteModal');
    
    if (backdrop) backdrop.classList.add('hidden');
    if (filterSheet) filterSheet.classList.remove('active');
    if (imageModal) imageModal.classList.add('hidden');
    if (deleteModal) deleteModal.classList.add('hidden');
    
    hideCardMenu(); // Use the function instead of direct manipulation
    
    isFilterSheetOpen = false;
    cardToDelete = null;
    document.body.style.overflow = 'auto';
}
    
// Update the selectFilter function completely:
function selectFilter(type, value) {
    const input = document.getElementById(type + 'Input');
    const currentValue = input.value;
    
    // If clicking the already selected value, clear it
    const newValue = (currentValue === value) ? '' : value;
    input.value = newValue;
    
    // Get all filter buttons for this type
    const buttons = document.querySelectorAll(`[onclick*="${type}"]`);
    
    buttons.forEach(btn => {
        // Extract the value from the onclick attribute
        const onclickAttr = btn.getAttribute('onclick');
        if (!onclickAttr) return;
        
        // Extract the value parameter from selectFilter('type', 'value')
        const regex = new RegExp(`selectFilter\\('${type}',\\s*'([^']*)'\\)`);
        const match = onclickAttr.match(regex);
        const btnValue = match ? match[1] : '';
        
        // Apply appropriate styling
        if (btnValue === newValue) {
            // This is the selected button
            btn.classList.remove('bg-gray-100', 'border-gray-300', 'text-gray-700');
            btn.classList.add('bg-blue-100', 'border-blue-300', 'text-blue-700');
        } else {
            // This is not selected
            btn.classList.remove('bg-blue-100', 'border-blue-300', 'text-blue-700');
            btn.classList.add('bg-gray-100', 'border-gray-300', 'text-gray-700');
        }
    });
    
    console.log(`Filter ${type} set to: "${newValue}"`);
}

// Also update the clear search functionality to reset filters properly:
function clearSearch() {
    // Clear the search input
    const searchInput = document.querySelector('input[name="search"]');
    searchInput.value = '';
    
    // Submit the form
    document.querySelector('form').submit();
}

// Update the removeFilter function to work better:
function removeFilter(type) {
    // Create URL object
    const url = new URL(window.location.href);
    
    // Remove the specific parameter
    url.searchParams.delete(type);
    
    // If it's a filter, also update the hidden input in the filter form
    const input = document.getElementById(type + 'Input');
    if (input) {
        input.value = '';
    }
    
    // Redirect to the updated URL
    window.location.href = url.toString();
}
    
    // Sort selection
    function selectSort(field, order) {
        document.getElementById('sortInput').value = field;
        document.getElementById('orderInput').value = order;
        
        // Update all sort buttons
        document.querySelectorAll('[onclick*="selectSort"]').forEach(btn => {
            const match = btn.getAttribute('onclick').match(/selectSort\('([^']+)', '([^']+)'\)/);
            if (match) {
                const [_, btnField, btnOrder] = match;
                if (btnField === field && btnOrder === order) {
                    btn.classList.add('bg-blue-100', 'text-blue-600');
                    btn.classList.remove('bg-gray-200', 'text-gray-500');
                } else {
                    btn.classList.remove('bg-blue-100', 'text-blue-600');
                    btn.classList.add('bg-gray-200', 'text-gray-500');
                }
            }
        });
    }
    
 // Function to check if file exists
function fileExists($path) {
    return file_exists($path) && is_file($path);
}
   
    // Remove filter
    function removeFilter(type) {
        const url = new URL(window.location.href);
        url.searchParams.delete(type);
        window.location.href = url.toString();
    }
    
    // View image in modal
    let currentImageSrc = '';
    
    function viewImage(src, title) {
        currentImageSrc = src;
        document.getElementById('modalImage').src = src;
        document.getElementById('imageModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    
    function closeImageModal() {
        document.getElementById('imageModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
        currentImageSrc = '';
    }
    
    function downloadImage() {
        if (!currentImageSrc) return;
        
        const link = document.createElement('a');
        link.href = currentImageSrc;
        link.download = 'business-card-' + Date.now() + '.jpg';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showToast('Image download started', 'success');
    }
    
    let cardToDelete = null;

    function confirmDelete(cardId) {
    cardToDelete = cardId;
    const modal = document.getElementById('deleteModal');
    const backdrop = document.getElementById('backdrop');
    
    if (modal) {
        modal.classList.remove('hidden');
    }
    if (backdrop) {
        backdrop.classList.remove('hidden');
    }
    document.body.style.overflow = 'hidden';
}

function hideDeleteModal() {
    const modal = document.getElementById('deleteModal');
    const backdrop = document.getElementById('backdrop');
    
    if (modal) {
        modal.classList.add('hidden');
    }
    
    // Only hide backdrop if no other modals are open
    if (backdrop && !document.getElementById('cardMenu').classList.contains('hidden') && 
        !document.getElementById('filterSheet').classList.contains('active') && 
        !document.getElementById('imageModal').classList.contains('hidden')) {
        backdrop.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }
    
    cardToDelete = null;
}

function showToastMessage(message, type = 'info') {
    // Remove existing toast if any
    const existingToast = document.querySelector('.toast-message');
    if (existingToast) {
        existingToast.remove();
    }
    
    const toast = document.createElement('div');
    toast.className = `toast-message fixed top-20 left-1/2 transform -translate-x-1/2 px-4 py-3 rounded-lg shadow-lg z-[1100] text-white text-sm font-medium ${
        type === 'success' ? 'bg-green-500' :
        type === 'error' ? 'bg-red-500' :
        type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
    }`;
    toast.textContent = message;
    toast.style.zIndex = '1100';
    
    document.body.appendChild(toast);
    
    // Animate in
    setTimeout(() => {
        toast.style.transition = 'opacity 0.3s, transform 0.3s';
        toast.style.opacity = '1';
        toast.style.transform = 'translateX(-50%) translateY(0)';
    }, 10);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(-50%) translateY(-10px)';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 300);
    }, 3000);
}


async function deleteCard() {
    if (!cardToDelete) {
        hideDeleteModal();
        return;
    }
    
    try {
        showToastMessage('Deleting card...', 'info');
        
        const response = await fetch('delete_card.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'card_id=' + cardToDelete
        });
        
        const result = await response.text();
        
        if (result === 'success') {
            // Remove card from DOM with animation
            const card = document.querySelector(`[data-card-id="${cardToDelete}"]`);
            if (card) {
                card.style.transition = 'transform 0.3s ease, opacity 0.3s ease';
                card.style.transform = 'translateX(-100%)';
                card.style.opacity = '0';
                
                setTimeout(() => {
                    card.remove();
                    showToastMessage('Card deleted successfully', 'success');
                    
                    // Update count
                    updateCardCount();
                    
                    // Check if no cards left
                    if (document.querySelectorAll('[data-card-id]').length === 0) {
                        setTimeout(() => location.reload(), 1000);
                    }
                }, 300);
            }
            
            hideDeleteModal();
            
            // Hide backdrop if no other modals are open
            const backdrop = document.getElementById('backdrop');
            if (backdrop && !document.getElementById('cardMenu').classList.contains('hidden') && 
                !document.getElementById('filterSheet').classList.contains('active') && 
                !document.getElementById('imageModal').classList.contains('hidden')) {
                backdrop.classList.add('hidden');
            }
            
            document.body.style.overflow = 'auto';
        } else {
            throw new Error(result || 'Failed to delete card');
        }
    } catch (error) {
        showToastMessage('Error deleting card: ' + error.message, 'error');
        console.error('Delete error:', error);
        hideDeleteModal();
    }
}

// Create and download a package with images and text
async function createCardPackage(cardId, companyName, shareContent) {
    showToast('Preparing card package...', 'info');
    
    const card = document.querySelector(`[data-card-id="${cardId}"]`);
    const frontImg = card?.querySelector('.photo-container img[alt="Front"]');
    const backImg = card?.querySelector('.photo-container img[alt="Back"]');
    
    // Create a temporary link to download images individually
    if (frontImg) {
        const link = document.createElement('a');
        link.href = frontImg.src;
        link.download = `${companyName}-front.jpg`;
        link.click();
    }
    
    if (backImg) {
        const link = document.createElement('a');
        link.href = backImg.src;
        link.download = `${companyName}-back.jpg`;
        link.click();
    }
    
    // Download the text file
    downloadAsTextFile(companyName, shareContent);
    
    showToast('Card package downloaded!', 'success');
    closeShareModal();
}
// Share all card information
function shareCard(cardId) {
    // Get card element and all its data
    const card = document.querySelector(`[data-card-id="${cardId}"]`);
    
    if (!card) {
        showToast('Card not found', 'error');
        return;
    }
    
    // Extract all information from the card
    const company = card.querySelector('h3')?.textContent?.trim() || '';
    const category = card.querySelector('.bg-blue-50 .truncate')?.textContent || '';
    const country = card.querySelector('.bg-green-50 .truncate')?.textContent || '';
    const note = card.querySelector('.line-clamp-4')?.textContent?.trim() || '';
    const date = card.querySelector('.text-xs.text-gray-500 span')?.textContent || '';
    
    // Get photo URLs if available
    const frontPhoto = card.querySelector('.photo-container img[alt="Front"]')?.src || '';
    const backPhoto = card.querySelector('.photo-container img[alt="Back"]')?.src || '';
    
    // Format the sharing content
    const shareContent = formatShareContent({
        company,
        category,
        country,
        note,
        date,
        frontPhoto,
        backPhoto
    });
    
    // Show share options
    showCardShareOptions(shareContent, company, cardId);
}

// Format the card information for sharing
function formatShareContent(cardData) {
    let content = `📇 Business Card Information\n\n`;
    content += `🏢 Company: ${cardData.company}\n`;
    content += `🏷️ Category: ${cardData.category}\n`;
    content += `🌍 Country: ${cardData.country}\n`;
    
    if (cardData.note) {
        content += `\n📝 Notes:\n${cardData.note}\n`;
    }
    
    if (cardData.date) {
        content += `\n📅 Date: ${cardData.date}\n`;
    }
    
    if (cardData.frontPhoto || cardData.backPhoto) {
        content += `\n📸 Photos: ${cardData.frontPhoto ? 'Front' : ''}${cardData.frontPhoto && cardData.backPhoto ? ' & ' : ''}${cardData.backPhoto ? 'Back' : ''}`;
    }
    
    content += `\n\n---\nShared via Business Card Manager`;
    
    return content;
}

// Show sharing options with all card data
function showCardShareOptions(shareContent, companyName, cardId) {
    const shareModal = document.createElement('div');
    shareModal.className = 'share-modal';
    
    // Escape content properly for HTML
    const escapedContent = shareContent.replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    const escapedCompanyName = companyName.replace(/'/g, "\\'").replace(/"/g, '\\"');
    
    shareModal.innerHTML = `
        <div class="fixed inset-0 bg-black/50 z-50 flex items-end justify-center p-4">
            <div class="bg-white rounded-t-2xl p-6 w-full max-w-md">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-800">Share Card Details</h3>
                    <button class="close-share-modal p-2">
                        <i class="fas fa-times text-gray-500"></i>
                    </button>
                </div>
                
                <div class="mb-4 p-4 bg-gray-50 rounded-lg max-h-40 overflow-y-auto text-sm">
                    <pre class="whitespace-pre-wrap font-sans">${escapedContent}</pre>
                </div>
                
                <div class="space-y-3 mb-6">
                    <!-- Copy Text -->
                    <button class="copy-text-btn w-full p-4 bg-gray-50 rounded-xl flex items-center gap-4 mobile-tap">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-copy text-blue-600"></i>
                        </div>
                        <div class="text-left flex-1">
                            <div class="font-medium text-gray-800">Copy All Details</div>
                            <div class="text-xs text-gray-500">Copy text to clipboard</div>
                        </div>
                    </button>
                    
                    <!-- Share via WhatsApp -->
                    <button class="whatsapp-share-btn w-full p-4 bg-gray-50 rounded-xl flex items-center gap-4 mobile-tap">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                            <i class="fab fa-whatsapp text-green-600"></i>
                        </div>
                        <div class="text-left flex-1">
                            <div class="font-medium text-gray-800">Share on WhatsApp</div>
                            <div class="text-xs text-gray-500">Send via WhatsApp</div>
                        </div>
                    </button>
                    
                    <!-- Share via Email -->
                    <button class="email-share-btn w-full p-4 bg-gray-50 rounded-xl flex items-center gap-4 mobile-tap">
                        <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-envelope text-red-600"></i>
                        </div>
                        <div class="text-left flex-1">
                            <div class="font-medium text-gray-800">Share via Email</div>
                            <div class="text-xs text-gray-500">Send as email</div>
                        </div>
                    </button>
                    
                    <!-- Download as Text File -->
                    <button class="download-text-btn w-full p-4 bg-gray-50 rounded-xl flex items-center gap-4 mobile-tap">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-file-download text-purple-600"></i>
                        </div>
                        <div class="text-left flex-1">
                            <div class="font-medium text-gray-800">Download as Text</div>
                            <div class="text-xs text-gray-500">Save as .txt file</div>
                        </div>
                    </button>
                    
                    <!-- Share Photos -->
                    <button class="share-photos-btn w-full p-4 bg-gray-50 rounded-xl flex items-center gap-4 mobile-tap">
                        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                            <i class="fas fa-images text-yellow-600"></i>
                        </div>
                        <div class="text-left flex-1">
                            <div class="font-medium text-gray-800">Share Photos</div>
                            <div class="text-xs text-gray-500">Share card images</div>
                        </div>
                    </button>
                </div>
                
                <button class="close-share-modal w-full py-3 bg-gray-100 text-gray-700 rounded-xl font-medium mobile-tap">
                    Cancel
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(shareModal);
    document.body.style.overflow = 'hidden';
    
    // Add event listeners
    const modalContainer = shareModal.querySelector('.bg-black\\/50');
    modalContainer.addEventListener('click', closeShareModal);
    
    shareModal.querySelectorAll('.close-share-modal').forEach(btn => {
        btn.addEventListener('click', closeShareModal);
    });
    
    shareModal.querySelector('.copy-text-btn').addEventListener('click', () => {
        copyTextToClipboard(shareContent);
    });
    
    shareModal.querySelector('.whatsapp-share-btn').addEventListener('click', () => {
        shareViaWhatsApp(shareContent);
    });
    
    // In the showCardShareOptions function, find the email button section and update it:
shareModal.querySelector('.email-share-btn').addEventListener('click', () => {
    shareViaEmail(escapedCompanyName, shareContent, cardId);
});
    
    shareModal.querySelector('.download-text-btn').addEventListener('click', () => {
        downloadAsTextFile(companyName, shareContent);
    });
    
    shareModal.querySelector('.share-photos-btn').addEventListener('click', () => {
        shareCardPhotos(cardId);
    });
}

// Share via WhatsApp
function shareViaWhatsApp(text) {
    const encodedText = encodeURIComponent(text);
    window.open(`https://wa.me/?text=${encodedText}`, '_blank');
    closeShareModal();
    showToast('Opening WhatsApp...', 'info');
}

// Share via Email
// Share via Email with images
// Share via Email optimized for Outlook
function shareViaEmail(companyName, shareContent, cardId) {
    const card = document.querySelector(`[data-card-id="${cardId}"]`);
    const frontImg = card?.querySelector('.photo-container img[alt="Front"]')?.src || '';
    const backImg = card?.querySelector('.photo-container img[alt="Back"]')?.src || '';
    
    // Parse the share content to extract information
    const lines = shareContent.split('\n');
    let company = '', category = '', country = '', date = '', notes = '';
    
    lines.forEach(line => {
        if (line.includes('Company:')) company = line.replace('🏢 Company:', '').trim();
        else if (line.includes('Category:')) category = line.replace('🏷️ Category:', '').trim();
        else if (line.includes('Country:')) country = line.replace('🌍 Country:', '').trim();
        else if (line.includes('Date:')) date = line.replace('📅 Date:', '').trim();
    });
    
    // Extract notes (if present)
    const notesMatch = shareContent.match(/Notes:([\s\S]*?)(?=\n\n|$)/);
    if (notesMatch) notes = notesMatch[1].trim();
    
    // Create Outlook-compatible HTML email content
    // Outlook uses Word HTML engine, so we need simple table-based layouts
    let htmlContent = `<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>Business Card: ${companyName}</title>
    <!--[if mso]>
    <style type="text/css">
        .outlook-table { border-collapse: collapse; table-layout: fixed; width: 100%; }
        .outlook-table td { padding: 10px; }
        .outlook-button { background-color: #2563eb; color: white; text-decoration: none; padding: 8px 16px; border-radius: 4px; display: inline-block; }
    </style>
    <![endif]-->
    <style>
        /* Styles for non-Outlook clients */
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
        .container { max-width: 600px; margin: 20px auto; background-color: #ffffff; border: 1px solid #dddddd; }
        .header { background-color: #f8f9fa; padding: 20px; text-align: left; border-bottom: 3px solid #2563eb; }
        .company-name { color: #2563eb; font-size: 24px; font-weight: bold; margin: 0 0 5px 0; }
        .section { padding: 20px; border-bottom: 1px solid #eeeeee; }
        .section-title { font-size: 18px; color: #2563eb; margin: 0 0 15px 0; font-weight: bold; }
        .info-grid { width: 100%; border-collapse: collapse; }
        .info-grid td { padding: 8px 0; border-bottom: 1px solid #f0f0f0; }
        .label { font-weight: bold; color: #666; width: 100px; }
        .value { color: #333; }
        .photo-container { margin: 15px 0; text-align: center; background-color: #fafafa; padding: 15px; border: 1px solid #eeeeee; }
        .photo { max-width: 100%; height: auto; border: 1px solid #dddddd; }
        .photo-caption { font-size: 12px; color: #666; margin-top: 5px; }
        .notes { background-color: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 15px 0; }
        .footer { padding: 15px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #eeeeee; }
        .download-links { margin: 20px 0; text-align: center; }
        .download-btn { display: inline-block; background-color: #2563eb; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; margin: 0 5px; }
        hr { border: 0; border-top: 1px solid #eeeeee; margin: 20px 0; }
        
        /* Outlook-specific table layout */
        table { border-collapse: collapse; width: 100%; }
        td { vertical-align: top; }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f5f5f5; font-family: Arial, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f5f5f5">
        <tr>
            <td align="center" style="padding: 20px;">
                <!-- Main Container -->
                <table width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff" style="border: 1px solid #dddddd; border-collapse: collapse;">
                    <!-- Header -->
                    <tr>
                        <td bgcolor="#f8f9fa" style="padding: 20px; border-bottom: 3px solid #2563eb;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td>
                                        <div style="color: #2563eb; font-size: 24px; font-weight: bold;">📇 ${companyName}</div>
                                        <div style="color: #666; font-size: 12px; margin-top: 5px;">Shared via Business Card Manager</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Card Information -->
                    <tr>
                        <td style="padding: 20px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td>
                                        <div style="font-size: 18px; color: #2563eb; margin-bottom: 15px; font-weight: bold;">📋 Card Information</div>
                                    </td>
                                </tr>
                            </table>
                            
                            <table width="100%" cellpadding="8" cellspacing="0" border="0" style="border-collapse: collapse;">
                                ${company ? `<tr><td width="100" style="font-weight: bold; color: #666;">Company:</td><td style="color: #333;">${company}</td></tr>` : ''}
                                ${category ? `<tr><td width="100" style="font-weight: bold; color: #666;">Category:</td><td style="color: #333;">${category}</td></tr>` : ''}
                                ${country ? `<tr><td width="100" style="font-weight: bold; color: #666;">Country:</td><td style="color: #333;">${country}</td></tr>` : ''}
                                ${date ? `<tr><td width="100" style="font-weight: bold; color: #666;">Date Added:</td><td style="color: #333;">${date}</td></tr>` : ''}
                            </table>
                        </td>
                    </tr>`;
    
    // Add Notes section if present
    if (notes) {
        htmlContent += `
                    <!-- Notes -->
                    <tr>
                        <td style="padding: 0 20px 20px 20px;">
                            <table width="100%" cellpadding="15" cellspacing="0" border="0" bgcolor="#fff3cd" style="border-left: 4px solid #ffc107;">
                                <tr>
                                    <td>
                                        <div style="font-weight: bold; margin-bottom: 5px;">📝 Notes</div>
                                        <div style="color: #333;">${notes.replace(/\n/g, '<br>')}</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>`;
    }
    
    // Add Photos section if available
    if (frontImg || backImg) {
        htmlContent += `
                    <!-- Photos -->
                    <tr>
                        <td style="padding: 0 20px 20px 20px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td>
                                        <div style="font-size: 18px; color: #2563eb; margin-bottom: 15px; font-weight: bold;">📸 Card Photos</div>
                                    </td>
                                </tr>
                            </table>
                            
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center">`;
        
        if (frontImg) {
            htmlContent += `
                                        <table width="250" cellpadding="0" cellspacing="0" border="0" style="margin: 0 auto 20px auto; background-color: #fafafa; border: 1px solid #eeeeee;">
                                            <tr>
                                                <td align="center" style="padding: 15px;">
                                                    <img src="${frontImg}" alt="Front of card" width="220" style="max-width: 100%; height: auto; border: 1px solid #dddddd;" border="1">
                                                    <div style="font-size: 12px; color: #666; margin-top: 10px;">Front Side</div>
                                                </td>
                                            </tr>
                                        </table>`;
        }
        
        if (backImg) {
            htmlContent += `
                                        <table width="250" cellpadding="0" cellspacing="0" border="0" style="margin: 0 auto; background-color: #fafafa; border: 1px solid #eeeeee;">
                                            <tr>
                                                <td align="center" style="padding: 15px;">
                                                    <img src="${backImg}" alt="Back of card" width="220" style="max-width: 100%; height: auto; border: 1px solid #dddddd;" border="1">
                                                    <div style="font-size: 12px; color: #666; margin-top: 10px;">Back Side</div>
                                                </td>
                                            </tr>
                                        </table>`;
        }
        
        htmlContent += `
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>`;
    }
    
    // Add download links section
    if (frontImg || backImg) {
        htmlContent += `
                    <!-- Download Links -->
                    <tr>
                        <td style="padding: 0 20px 20px 20px;">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center">
                                        <hr style="border: 0; border-top: 1px solid #eeeeee; margin: 20px 0;">
                                        <div style="font-size: 14px; color: #666; margin-bottom: 15px;">
                                            ⚠️ If images don't appear, you can download them below:
                                        </div>`;
        
        if (frontImg) {
            htmlContent += `
                                        <a href="${frontImg}" download="${companyName.replace(/[^a-z0-9]/gi, '-')}-front.jpg" style="display: inline-block; background-color: #2563eb; color: white; text-decoration: none; padding: 8px 16px; border-radius: 4px; margin: 0 5px; font-size: 12px;">📸 Download Front Image</a>`;
        }
        
        if (backImg) {
            htmlContent += `
                                        <a href="${backImg}" download="${companyName.replace(/[^a-z0-9]/gi, '-')}-back.jpg" style="display: inline-block; background-color: #2563eb; color: white; text-decoration: none; padding: 8px 16px; border-radius: 4px; margin: 0 5px; font-size: 12px;">📸 Download Back Image</a>`;
        }
        
        htmlContent += `
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>`;
    }
    
    // Footer
    htmlContent += `
                    <!-- Footer -->
                    <tr>
                        <td style="padding: 15px; text-align: center; color: #666; font-size: 12px; border-top: 1px solid #eeeeee;" bgcolor="#fafafa">
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center">
                                        <div style="margin-bottom: 5px;">This business card information was shared from Business Card Manager.</div>
                                        <div><a href="${window.location.origin}" style="color: #2563eb; text-decoration: none;">Visit our app</a> to manage more cards.</div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>`;
    
    // Create plain text version (Outlook will show this if HTML is disabled)
    const plainText = `📇 BUSINESS CARD: ${companyName}

═══════════════════════════════

📋 CARD INFORMATION:
${company ? `• Company: ${company}` : ''}
${category ? `• Category: ${category}` : ''}
${country ? `• Country: ${country}` : ''}
${date ? `• Date: ${date}` : ''}

${notes ? `📝 NOTES:
${notes}

` : ''}
${frontImg || backImg ? `📸 PHOTOS:
${frontImg ? '- Front image attached' : ''}
${backImg ? '- Back image attached' : ''}

` : ''}
═══════════════════════════════

Shared via Business Card Manager
${window.location.origin}`;
    
    // For Outlook, we'll use a data URI approach with mailto
    const subject = encodeURIComponent(`Business Card: ${companyName}`);
    
    // Create a temporary form to handle the email with HTML content
    // Some email clients support HTML in mailto, but for Outlook we'll provide both options
    
    // Option 1: Try mailto with HTML (works in some Outlook versions)
    // Option 2: Download images and provide instructions
    
    // Show user options
    const emailModal = document.createElement('div');
    emailModal.className = 'fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4';
    emailModal.innerHTML = `
        <div class="bg-white rounded-2xl p-6 w-full max-w-md" onclick="event.stopPropagation()">
            <div class="text-center mb-6">
                <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-envelope text-blue-600 text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Share via Email</h3>
                <p class="text-gray-600 text-sm">Choose how to send to Outlook</p>
            </div>
            
            <div class="space-y-3 mb-6">
                <button class="w-full p-4 bg-gray-50 rounded-xl text-left hover:bg-gray-100 transition-colors mobile-tap" onclick="window.location.href='mailto:?subject=${subject}&body=${encodeURIComponent(plainText)}'; closeEmailModal();">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-envelope text-blue-600"></i>
                        <div>
                            <div class="font-medium text-gray-800">Quick Email</div>
                            <div class="text-xs text-gray-500">Send plain text version</div>
                        </div>
                    </div>
                </button>
                
                ${frontImg || backImg ? `
                <button class="w-full p-4 bg-gray-50 rounded-xl text-left hover:bg-gray-100 transition-colors mobile-tap" onclick="prepareOutlookWithAttachments('${companyName}', '${frontImg}', '${backImg}', '${encodeURIComponent(plainText)}');">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-paperclip text-green-600"></i>
                        <div>
                            <div class="font-medium text-gray-800">Outlook with Attachments</div>
                            <div class="text-xs text-gray-500">Download images to attach manually</div>
                        </div>
                    </div>
                </button>
                ` : ''}
                
                <button class="w-full p-4 bg-gray-50 rounded-xl text-left hover:bg-gray-100 transition-colors mobile-tap" onclick="copyToClipboard('${encodeURIComponent(htmlContent)}');">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-copy text-purple-600"></i>
                        <div>
                            <div class="font-medium text-gray-800">Copy HTML</div>
                            <div class="text-xs text-gray-500">Paste into Outlook manually</div>
                        </div>
                    </div>
                </button>
            </div>
            
            <button onclick="closeEmailModal()" class="w-full py-3 bg-gray-100 text-gray-700 rounded-xl font-medium mobile-tap">
                Cancel
            </button>
        </div>
    `;
    
    document.body.appendChild(emailModal);
    document.body.style.overflow = 'hidden';
    
    // Close modal function
    window.closeEmailModal = function() {
        emailModal.remove();
        document.body.style.overflow = 'auto';
    };
    
    // Prepare for Outlook with attachments
    window.prepareOutlookWithAttachments = function(company, front, back, encodedText) {
        // Open email client
        window.location.href = `mailto:?subject=${subject}&body=${encodedText}`;
        
        // Download images for manual attachment
        setTimeout(() => {
            if (front) {
                const link = document.createElement('a');
                link.href = front;
                link.download = `${company.replace(/[^a-z0-9]/gi, '-')}-front.jpg`;
                link.click();
            }
            if (back) {
                const link = document.createElement('a');
                link.href = back;
                link.download = `${company.replace(/[^a-z0-9]/gi, '-')}-back.jpg`;
                link.click();
            }
        }, 1000);
        
        showToast('Email opened. Images downloaded for attachment.', 'info');
        closeEmailModal();
        closeShareModal();
    };
    
    // Copy HTML to clipboard
    window.copyToClipboard = function(encodedHtml) {
        const html = decodeURIComponent(encodedHtml);
        navigator.clipboard.writeText(html)
            .then(() => {
                showToast('HTML copied to clipboard! Paste into Outlook', 'success');
                closeEmailModal();
                closeShareModal();
            })
            .catch(() => {
                showToast('Failed to copy HTML', 'error');
            });
    };
    
    closeShareModal();
}

// Update the email-share-btn click handler in showCardShareOptions function
// Find this part in your code and replace the email click handler:
/*
shareModal.querySelector('.email-share-btn').addEventListener('click', () => {
    shareViaEmail(companyName, shareContent, cardId);
});
*/
// Copy text to clipboard
function copyTextToClipboard(text) {
    navigator.clipboard.writeText(text)
        .then(() => {
            showToast('Card details copied to clipboard!', 'success');
            closeShareModal();
        })
        .catch(err => {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                showToast('Card details copied to clipboard!', 'success');
            } catch (err) {
                showToast('Failed to copy details', 'error');
            }
            document.body.removeChild(textArea);
            closeShareModal();
        });
}

// Download as text file
function downloadAsTextFile(filename, content) {
    const blob = new Blob([content], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `business-card-${filename.replace(/[^a-z0-9]/gi, '-').toLowerCase()}.txt`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
    
    showToast('File downloaded!', 'success');
    closeShareModal();
}

// Share card photos
function shareCardPhotos(cardId) {
    const card = document.querySelector(`[data-card-id="${cardId}"]`);
    const frontImg = card.querySelector('.photo-container img[alt="Front"]');
    const backImg = card.querySelector('.photo-container img[alt="Back"]');
    
    const images = [];
    if (frontImg?.src) images.push(frontImg.src);
    if (backImg?.src) images.push(backImg.src);
    
    if (images.length === 0) {
        showToast('No photos available to share', 'info');
        return;
    }
    
    // Create download links for images
    images.forEach((imgSrc, index) => {
        const link = document.createElement('a');
        link.href = imgSrc;
        link.download = `business-card-${cardId}-${index === 0 ? 'front' : 'back'}.jpg`;
        link.style.display = 'none';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });
    
    showToast(`${images.length} photo(s) downloaded`, 'success');
    closeShareModal();
}

// Close share modal
function closeShareModal() {
    const modal = document.querySelector('.share-modal');
    if (modal) {
        modal.remove();
        document.body.style.overflow = 'auto';
    }
}

// Toast notification function (if missing)
function showToast(message, type = 'info') {
    // Remove existing toast if any
    const existingToast = document.querySelector('.toast-notification');
    if (existingToast) {
        existingToast.remove();
    }
    
    const toast = document.createElement('div');
    toast.className = `toast-notification fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white font-medium transform transition-all duration-300 ${
        type === 'success' ? 'bg-green-500' :
        type === 'error' ? 'bg-red-500' :
        type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
    }`;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.remove();
            }
        }, 300);
    }, 3000);
}

// Add CSS for toast if not already in your styles
if (!document.querySelector('#toast-styles')) {
    const style = document.createElement('style');
    style.id = 'toast-styles';
    style.textContent = `
        .toast-notification {
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(style);
}
    
    // Update card count
    function updateCardCount() {
    const count = document.querySelectorAll('[data-card-id]').length;
    const countElement = document.querySelector('.text-xs.text-gray-500.mt-1');
    if (countElement) {
        countElement.textContent = count + ' card' + (count !== 1 ? 's' : '');
    }
}
    
    // Hide all modals
    function hideAllModals() {
    document.getElementById('backdrop').classList.add('hidden');
    document.getElementById('filterSheet').classList.remove('active');
    document.getElementById('imageModal').classList.add('hidden');
    document.getElementById('deleteModal').classList.add('hidden');
    hideCardMenu();
    
    isFilterSheetOpen = false;
    cardToDelete = null;
    document.body.style.overflow = 'auto';
}

    
    // Toast notification
    function showToast(message, type = 'info') {
        // Remove existing toast
        const existing = document.querySelector('.toast');
        if (existing) existing.remove();
        
        // Create toast
        const toast = document.createElement('div');
        toast.className = `toast fixed top-20 left-1/2 transform -translate-x-1/2 px-4 py-3 rounded-lg shadow-lg z-50 text-white text-sm ${
            type === 'success' ? 'bg-green-500' :
            type === 'error' ? 'bg-red-500' :
            'bg-blue-500'
        }`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        // Animate in
        setTimeout(() => {
            toast.style.transition = 'opacity 0.3s, transform 0.3s';
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(-50%) translateY(0)';
        }, 10);
        
        // Remove after 3 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(-50%) translateY(-10px)';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    // Handle back button
    window.addEventListener('popstate', function() {
        hideAllModals();
    });
    
    // Close modals on escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            hideAllModals();
        }
    });
    
    // Prevent form resubmission on refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
    </script>
</body>
</html>