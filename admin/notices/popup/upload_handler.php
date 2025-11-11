<?php 

/**
 * ExchangeBridge - Admin Panel Popup Notice Upload Handler
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


// Set JSON header immediately
header('Content-Type: application/json');

// Buffer output to catch any warnings
ob_start();

try {

// Include configuration files
require_once '../../../config/config.php';
require_once '../../../config/verification.php';
require_once '../../../config/license.php';
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/app.php';
require_once '../../../includes/auth.php';
require_once '../../../includes/security.php';




} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Configuration error: ' . $e->getMessage()]);
    exit;
}

// Check if user is logged in
if (!Auth::isLoggedIn()) {
    ob_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $errorMessages = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
        UPLOAD_ERR_PARTIAL => 'File partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary directory',
        UPLOAD_ERR_CANT_WRITE => 'Cannot write to disk',
        UPLOAD_ERR_EXTENSION => 'Upload stopped by extension'
    ];
    
    $errorCode = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
    $errorMsg = $errorMessages[$errorCode] ?? 'Unknown upload error';
    
    ob_clean();
    echo json_encode(['success' => false, 'message' => $errorMsg]);
    exit;
}

// Get upload directory path - use relative path consistently
$uploadDir = '../../../assets/uploads/notices/';

// Create directory if it doesn't exist
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory']);
        exit;
    }
}

// Check if directory is writable
if (!is_writable($uploadDir)) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Upload directory not writable']);
    exit;
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
    ob_clean();
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowedExtensions)
    ]);
    exit;
}

// Validate file size
if ($fileSize > $maxFileSize) {
    ob_clean();
    echo json_encode([
        'success' => false, 
        'message' => 'File too large (' . round($fileSize / 1024 / 1024, 2) . 'MB). Max size: 5MB'
    ]);
    exit;
}

// Validate actual file type (not just extension)
if (function_exists('finfo_open')) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $fileTmpName);
    finfo_close($finfo);

    $allowedMimeTypes = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 
        'image/webp', 'image/bmp', 'image/x-ms-bmp'
    ];

    if (!in_array($mimeType, $allowedMimeTypes)) {
        ob_clean();
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid file format detected'
        ]);
        exit;
    }
} else {
    // Fallback if finfo not available
    $mimeType = 'image/' . ($fileExtension === 'jpg' ? 'jpeg' : $fileExtension);
}

// Generate unique filename with consistent prefix
$newFileName = 'popup_tinymce_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
$uploadPath = $uploadDir . $newFileName;

// Move uploaded file
if (!move_uploaded_file($fileTmpName, $uploadPath)) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
    exit;
}

// Set proper file permissions
if (function_exists('chmod')) {
    chmod($uploadPath, 0644);
}

// Build the URL for TinyMCE - use absolute URL
$fileUrl = SITE_URL . '/assets/uploads/notices/' . $newFileName;

// Verify the file is accessible
$testPath = $uploadDir . $newFileName;
if (!file_exists($testPath)) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'File uploaded but not found']);
    exit;
}

// Save to database
try {
    $db = Database::getInstance();
    
    // Get current user ID
    $userId = $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? 1;
    
    $mediaData = [
        'filename' => $newFileName,
        'original_name' => $fileName,
        'file_path' => 'assets/uploads/notices/' . $newFileName, // Store relative path
        'file_type' => 'image',
        'mime_type' => $mimeType,
        'file_size' => $fileSize,
        'uploaded_by' => $userId,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $db->insert('media', $mediaData);
} catch (Exception $e) {
    // Continue even if database insert fails
    error_log('Media database insert failed: ' . $e->getMessage());
}

// Clean any output buffer before sending JSON
ob_clean();

// Return success response with debug information
echo json_encode([
    'success' => true, 
    'location' => $fileUrl,
    'message' => 'File uploaded successfully',
    'filename' => $newFileName,
    'debug' => [
        'site_url' => SITE_URL,
        'file_path' => $uploadPath,
        'file_exists' => file_exists($testPath),
        'file_size' => filesize($testPath),
        'mime_type' => $mimeType
    ]
]);

exit;
?>