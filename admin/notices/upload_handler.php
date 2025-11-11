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

// Set JSON header
header('Content-Type: application/json');

// Check if user is logged in
if (!Auth::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$uploadDir = '../../assets/uploads/notices/';

// Create directory if it doesn't exist
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
        exit;
    }
}

$file = $_FILES['file'];
$fileName = $file['name'];
$fileSize = $file['size'];
$fileTmpName = $file['tmp_name'];
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Allowed file types
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
$maxFileSize = 5 * 1024 * 1024; // 5MB

// Validate file type
if (!in_array($fileExtension, $allowedExtensions)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowedExtensions)]);
    exit;
}

// Validate file size
if ($fileSize > $maxFileSize) {
    echo json_encode(['success' => false, 'message' => 'File too large. Max size: 5MB']);
    exit;
}

// Generate unique filename
$newFileName = 'quill_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
$uploadPath = $uploadDir . $newFileName;

// Move uploaded file
if (move_uploaded_file($fileTmpName, $uploadPath)) {
    // Return the URL for Quill
    $fileUrl = SITE_URL . '/assets/uploads/notices/' . $newFileName;
    
    // Optionally save to database
    try {
        $db = Database::getInstance();
        $mediaData = [
            'original_name' => $fileName,
            'file_name' => $newFileName,
            'file_path' => 'assets/uploads/notices/' . $newFileName,
            'file_type' => 'image',
            'mime_type' => mime_content_type($uploadPath),
            'file_size' => $fileSize,
            'uploaded_by' => Auth::getUserId(),
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $db->insert('media', $mediaData);
    } catch (Exception $e) {
        // Continue even if database insert fails
        error_log('Failed to save media to database: ' . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true, 
        'location' => $fileUrl,
        'message' => 'File uploaded successfully'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save uploaded file']);
}
?>