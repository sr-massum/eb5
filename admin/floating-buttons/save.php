<?php 

/**
 * ExchangeBridge - Admin Panel Floating Button Save
 *
 * package     ExchangeBridge
 * author      Saieed Rahman
 * copyright   SidMan Solution 2025
 * version     1.0.0
 */


// Start session
session_start();

// Define access constant
define('ALLOW_ACCESS', true);

// Include configuration files
require_once '../../config/config.php';
require_once '../../config/verification.php';
require_once '../../config/license.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/app.php';
require_once '../../includes/auth.php';
require_once '../../includes/security.php';


// Check if user is logged in
if (!Auth::isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = 'Invalid request method.';
    header("Location: index.php");
    exit;
}

try {
    $db = Database::getInstance();
    
    // Get form data and sanitize
    $buttonId = !empty($_POST['button_id']) ? (int)$_POST['button_id'] : 0;
    $title = trim($_POST['title'] ?? '');
    $icon = trim($_POST['icon'] ?? 'fas fa-life-ring');
    $customIcon = trim($_POST['custom_icon'] ?? '');
    $color = trim($_POST['color'] ?? '#25D366');
    $url = trim($_POST['url'] ?? '');
    $target = trim($_POST['target'] ?? '_blank');
    $position = trim($_POST['position'] ?? 'bottom-right');
    $orderIndex = (int)($_POST['order_index'] ?? 0);
    $status = trim($_POST['status'] ?? 'active');
    $showOnMobile = isset($_POST['show_on_mobile']) ? 1 : 0;
    $showOnDesktop = isset($_POST['show_on_desktop']) ? 1 : 0;
    
    // Validate required fields
    if (empty($title)) {
        throw new Exception('Button title is required.');
    }
    
    if (empty($url)) {
        throw new Exception('Button URL is required.');
    }
    
    // Validate color format
    if (!preg_match('/^#[a-fA-F0-9]{6}$/', $color)) {
        throw new Exception('Invalid color format.');
    }
    
    // Validate position
    $validPositions = ['left', 'right', 'bottom-left', 'bottom-right'];
    if (!in_array($position, $validPositions)) {
        $position = 'bottom-right';
    }
    
    // Validate status
    $validStatuses = ['active', 'inactive'];
    if (!in_array($status, $validStatuses)) {
        $status = 'active';
    }
    
    // Validate target
    $validTargets = ['_blank', '_self'];
    if (!in_array($target, $validTargets)) {
        $target = '_blank';
    }
    
    // Handle file upload for custom icon
    $uploadedIconPath = '';
    if (isset($_FILES['icon_upload']) && $_FILES['icon_upload']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../uploads/icons/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                throw new Exception('Failed to create upload directory.');
            }
        }
        
        $uploadedFile = $_FILES['icon_upload'];
        $fileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
        
        // Validate file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
        if (!in_array($fileExtension, $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPG, PNG, GIF, SVG, and WebP files are allowed.');
        }
        
        // Validate file size (max 2MB)
        if ($uploadedFile['size'] > 2 * 1024 * 1024) {
            throw new Exception('File size too large. Maximum 2MB allowed.');
        }
        
        // Generate unique filename
        $fileName = 'icon_' . time() . '_' . uniqid() . '.' . $fileExtension;
        $targetPath = $uploadDir . $fileName;
        
        if (move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
            $uploadedIconPath = 'uploads/icons/' . $fileName;
            $customIcon = $uploadedIconPath; // Use uploaded file
        } else {
            throw new Exception('Failed to upload icon file.');
        }
    }
    
    // Use uploaded icon if available, otherwise use the selected custom icon
    $finalCustomIcon = !empty($uploadedIconPath) ? $uploadedIconPath : $customIcon;
    
    // Add button_type field for new structure compatibility
    $buttonType = 'custom';
    
    if ($buttonId > 0) {
        // Update existing button
        // First check if button exists
        $existingButton = $db->getRow("SELECT * FROM floating_buttons WHERE id = ?", [$buttonId]);
        if (!$existingButton) {
            throw new Exception('Button not found.');
        }
        
        // If we have a new uploaded icon, delete the old custom icon file
        if (!empty($uploadedIconPath) && !empty($existingButton['custom_icon']) && 
            file_exists('../../' . $existingButton['custom_icon'])) {
            @unlink('../../' . $existingButton['custom_icon']);
        }
        
        // Check if button_type column exists for update
        $checkColumn = $db->getRows("SHOW COLUMNS FROM floating_buttons LIKE 'button_type'");
        
        if (!empty($checkColumn)) {
            // New structure with button_type column
            $sql = "UPDATE floating_buttons SET 
                    title = ?, icon = ?, custom_icon = ?, color = ?, url = ?, target = ?, 
                    position = ?, order_index = ?, status = ?, show_on_mobile = ?, 
                    show_on_desktop = ?, button_type = ?, updated_at = NOW() 
                    WHERE id = ?";
            $params = [$title, $icon, $finalCustomIcon, $color, $url, $target, 
                      $position, $orderIndex, $status, $showOnMobile, $showOnDesktop, 
                      $buttonType, $buttonId];
        } else {
            // Old structure without button_type column
            $sql = "UPDATE floating_buttons SET 
                    title = ?, icon = ?, custom_icon = ?, color = ?, url = ?, target = ?, 
                    position = ?, order_index = ?, status = ?, show_on_mobile = ?, 
                    show_on_desktop = ?, updated_at = NOW() 
                    WHERE id = ?";
            $params = [$title, $icon, $finalCustomIcon, $color, $url, $target, 
                      $position, $orderIndex, $status, $showOnMobile, $showOnDesktop, $buttonId];
        }
        
        $result = $db->query($sql, $params);
        
        if ($result) {
            $_SESSION['success_message'] = 'Floating button "' . htmlspecialchars($title) . '" has been updated successfully.';
        } else {
            throw new Exception('Failed to update button in database.');
        }
        
    } else {
        // Create new button
        // Check if button_type column exists for insert
        $checkColumn = $db->getRows("SHOW COLUMNS FROM floating_buttons LIKE 'button_type'");
        
        if (!empty($checkColumn)) {
            // New structure with button_type column
            $sql = "INSERT INTO floating_buttons 
                    (title, icon, custom_icon, color, url, target, position, order_index, 
                     status, show_on_mobile, show_on_desktop, button_type, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $params = [$title, $icon, $finalCustomIcon, $color, $url, $target, 
                      $position, $orderIndex, $status, $showOnMobile, $showOnDesktop, $buttonType];
        } else {
            // Old structure without button_type column
            $sql = "INSERT INTO floating_buttons 
                    (title, icon, custom_icon, color, url, target, position, order_index, 
                     status, show_on_mobile, show_on_desktop, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $params = [$title, $icon, $finalCustomIcon, $color, $url, $target, 
                      $position, $orderIndex, $status, $showOnMobile, $showOnDesktop];
        }
        
        $result = $db->query($sql, $params);
        
        if ($result) {
            $_SESSION['success_message'] = 'Floating button "' . htmlspecialchars($title) . '" has been created successfully.';
        } else {
            throw new Exception('Failed to insert button into database.');
        }
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Error saving floating button: " . $e->getMessage());
    
    // Set error message
    $_SESSION['error_message'] = $e->getMessage();
    
    // If there was an uploaded file and we failed, clean it up
    if (!empty($uploadedIconPath) && file_exists('../../' . $uploadedIconPath)) {
        @unlink('../../' . $uploadedIconPath);
    }
}

// Redirect back to the main page
header("Location: index.php");
exit;
?>