<?php 

/**
 * ExchangeBridge - Blog Upload Handler
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

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_FILES['file'])) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];

// Check for upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Upload error: ' . $file['error']]);
    exit;
}

// Define upload directory - Use relative path from current location
$uploadDir = '../../assets/uploads/blog/';

// Create directory if it doesn't exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Get file extension
$fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// Validate file type
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'ogg'];
if (!in_array($fileExtension, $allowedExtensions)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowedExtensions)]);
    exit;
}

// Set size limits
$maxSize = in_array($fileExtension, ['mp4', 'webm', 'ogg']) ? 10 * 1024 * 1024 : 5 * 1024 * 1024;

if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File too large. Max: ' . round($maxSize / 1024 / 1024, 1) . 'MB']);
    exit;
}

// Generate unique filename
$newFileName = 'blog_tinymce_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
$uploadPath = $uploadDir . $newFileName;

// Move uploaded file
if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
    chmod($uploadPath, 0644);
    
    // Log to media table
    try {
        $db = Database::getInstance();
        $user = Auth::getUser();
        
        $mediaData = [
            'filename' => $newFileName,
            'original_name' => $file['name'],
            'file_path' => 'assets/uploads/blog/' . $newFileName,
            'file_size' => $file['size'],
            'mime_type' => $file['type'],
            'file_type' => in_array($fileExtension, ['mp4', 'webm', 'ogg']) ? 'video' : 'image',
            'uploaded_by' => $user['id'],
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $db->insert('media', $mediaData);
    } catch (Exception $e) {
        // Continue even if media logging fails
        error_log('Media logging failed: ' . $e->getMessage());
    }
    
    // Create multiple URL formats for compatibility
    $relativePath = '../../assets/uploads/blog/' . $newFileName;
    $absolutePath = SITE_URL . '/assets/uploads/blog/' . $newFileName;
    
    // Verify file exists and is accessible
    if (file_exists($uploadPath)) {
        // Return the URL that works best for TinyMCE
        echo json_encode([
            'success' => true, 
            'location' => $absolutePath,
            'file' => $newFileName,
            'debug' => [
                'relative' => $relativePath,
                'absolute' => $absolutePath,
                'site_url' => SITE_URL,
                'file_exists' => true,
                'file_size' => filesize($uploadPath)
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'File uploaded but not accessible']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
}
?>