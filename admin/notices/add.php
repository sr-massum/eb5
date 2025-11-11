<?php 

/**
 * ExchangeBridge - Admin Panel Notices
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

// Check if user is logged in, if not redirect to login page
if (!Auth::isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $type = isset($_POST['type']) ? sanitizeInput($_POST['type']) : '';
    $title = isset($_POST['title']) ? sanitizeInput($_POST['title']) : '';
    $content = isset($_POST['content']) ? $_POST['content'] : ''; // Allow HTML in content
    $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'active';
    $position = isset($_POST['position']) ? (int)$_POST['position'] : 1;
    $selectedMediaPath = isset($_POST['selected_media_path']) ? $_POST['selected_media_path'] : '';
    
    // Handle media selection or upload
    $mediaPath = '';
    
    // Check if media was selected from library
    if (!empty($selectedMediaPath)) {
        // Extract filename from the media path
        $mediaPath = basename($selectedMediaPath);
        
        // Copy the file to notices directory if it's not already there
        $sourcePath = '../../' . $selectedMediaPath;
        $targetDir = '../../assets/uploads/notices/';
        
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        // Generate new filename to avoid conflicts
        $extension = pathinfo($mediaPath, PATHINFO_EXTENSION);
        $newFileName = 'notice_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
        $targetPath = $targetDir . $newFileName;
        
        if (file_exists($sourcePath) && copy($sourcePath, $targetPath)) {
            $mediaPath = $newFileName;
        } else {
            $mediaPath = ''; // Reset if copy failed
        }
    }
    
    // Handle direct file upload
    if (empty($mediaPath) && isset($_FILES['notice_image']) && $_FILES['notice_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../assets/uploads/notices/';
        
        // Create directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $fileName = $_FILES['notice_image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'ogg'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            // Generate unique filename
            $newFileName = 'notice_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
            $uploadPath = $uploadDir . $newFileName;
            
            // Check file size (max 10MB for videos, 5MB for images)
            $maxSize = in_array($fileExtension, ['mp4', 'webm', 'ogg']) ? 10 * 1024 * 1024 : 5 * 1024 * 1024;
            
            if ($_FILES['notice_image']['size'] <= $maxSize) {
                if (move_uploaded_file($_FILES['notice_image']['tmp_name'], $uploadPath)) {
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
    if (empty($type) || !in_array($type, ['scrolling', 'popup'])) {
        $_SESSION['error_message'] = 'Invalid notice type';
    } elseif (empty($content)) {
        $_SESSION['error_message'] = 'Content is required';
    } else {
        // Insert notice
        $db = Database::getInstance();
        $noticeData = [
            'type' => $type,
            'title' => $title,
            'content' => $content,
            'status' => $status,
            'position' => $position
        ];
        
        // Add media path if available
        if (!empty($mediaPath)) {
            $noticeData['image_path'] = $mediaPath;
        }
        
        $noticeId = $db->insert('notices', $noticeData);
        
        if ($noticeId) {
            $_SESSION['success_message'] = 'Notice added successfully';
        } else {
            $_SESSION['error_message'] = 'Failed to add notice';
            
            // Delete uploaded media if database insert failed
            if (!empty($mediaPath) && file_exists($uploadDir . $mediaPath)) {
                unlink($uploadDir . $mediaPath);
            }
        }
    }
}

// Redirect back to notices list
header("Location: index.php");
exit;
?>