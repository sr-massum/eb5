<?php 

/**
 * ExchangeBridge - Admin Panel Popup Notice
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
require_once '../../../config/config.php';
require_once '../../../config/verification.php';
require_once '../../../config/license.php';
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/app.php';
require_once '../../../includes/auth.php';
require_once '../../../includes/security.php';


// Check if user is logged in
if (!Auth::isLoggedIn()) {
    header("Location: ../../login.php");
    exit;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $title = isset($_POST['title']) ? sanitizeInput($_POST['title']) : '';
    $content = isset($_POST['content']) ? $_POST['content'] : '';
    $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'active';
    $selectedMediaPath = isset($_POST['selected_media_path']) ? $_POST['selected_media_path'] : '';
    
    if ($id <= 0) {
        $_SESSION['error_message'] = 'Invalid notice ID';
        header("Location: index.php");
        exit;
    }
    
    $db = Database::getInstance();
    
    // Get current notice data and verify it's a popup notice
    $currentNotice = $db->getRow("SELECT * FROM notices WHERE id = ? AND type = 'popup'", [$id]);
    if (!$currentNotice) {
        $_SESSION['error_message'] = 'Popup notice not found';
        header("Location: index.php");
        exit;
    }
    
    // Handle media selection or upload
    $mediaPath = $currentNotice['image_path']; // Keep current media by default
    
    // Check if new media was selected from library
    if (!empty($selectedMediaPath)) {
        // Delete old media file if exists
        if (!empty($currentNotice['image_path'])) {
            $oldMediaPath = '../../../assets/uploads/notices/' . $currentNotice['image_path'];
            if (file_exists($oldMediaPath)) {
                unlink($oldMediaPath);
            }
        }
        
        // Copy selected media to notices directory
        $sourcePath = '../../../' . $selectedMediaPath;
        $targetDir = '../../../assets/uploads/notices/';
        
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        // Generate new filename
        $extension = pathinfo($selectedMediaPath, PATHINFO_EXTENSION);
        $newFileName = 'notice_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
        $targetPath = $targetDir . $newFileName;
        
        if (file_exists($sourcePath) && copy($sourcePath, $targetPath)) {
            $mediaPath = $newFileName;
        }
    }
    
    // Handle direct file upload
    if (isset($_FILES['notice_media']) && $_FILES['notice_media']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../../assets/uploads/notices/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = $_FILES['notice_media']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'ogg'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            // Generate unique filename
            $newFileName = 'notice_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
            $uploadPath = $uploadDir . $newFileName;
            
            // Check file size (max 10MB for videos, 5MB for images)
            $maxSize = in_array($fileExtension, ['mp4', 'webm', 'ogg']) ? 10 * 1024 * 1024 : 5 * 1024 * 1024;
            
            if ($_FILES['notice_media']['size'] <= $maxSize) {
                if (move_uploaded_file($_FILES['notice_media']['tmp_name'], $uploadPath)) {
                    // Delete old media file if exists
                    if (!empty($currentNotice['image_path'])) {
                        $oldMediaPath = '../../../assets/uploads/notices/' . $currentNotice['image_path'];
                        if (file_exists($oldMediaPath)) {
                            unlink($oldMediaPath);
                        }
                    }
                    $mediaPath = $newFileName;
                } else {
                    $_SESSION['error_message'] = 'Failed to upload media file';
                    header("Location: index.php");
                    exit;
                }
            } else {
                $_SESSION['error_message'] = 'File size too large. Max: ' . ($maxSize / 1024 / 1024) . 'MB';
                header("Location: index.php");
                exit;
            }
        } else {
            $_SESSION['error_message'] = 'Invalid file format. Allowed: JPG, JPEG, PNG, GIF, WebP, MP4, WebM, OGG';
            header("Location: index.php");
            exit;
        }
    }
    
    // Validate form data
    if (empty($content)) {
        $_SESSION['error_message'] = 'Content is required';
    } else {
        // Update popup notice
        $updateData = [
            'title' => $title,
            'content' => $content,
            'status' => $status,
            'image_path' => $mediaPath,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $updated = $db->update('notices', $updateData, 'id = ?', [$id]);
        
        if ($updated) {
            $_SESSION['success_message'] = 'Popup notice updated successfully';
        } else {
            $_SESSION['error_message'] = 'Failed to update popup notice';
        }
    }
}

// Redirect back to popup notices list
header("Location: index.php");
exit;
?>