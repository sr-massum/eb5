<?php 

/**
 * ExchangeBridge - Admin Panel Video Upload
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
require_once '../../../config/config.php';
require_once '../../../config/verification.php';
require_once '../../../config/license.php';
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/app.php';
require_once '../../../includes/auth.php';
require_once '../../../includes/security.php';

// Set UTF-8 encoding headers
header('Content-Type: text/html; charset=UTF-8');
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// Check if user is logged in
if (!Auth::isLoggedIn()) {
    header("Location: ../../login.php");
    exit;
}

// Create upload directory if it doesn't exist
$uploadDir = '../../../assets/uploads/media/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $priority = intval($_POST['priority']);
    $displayOnHomepage = isset($_POST['display_on_homepage']) ? 1 : 0;
    $type = $_POST['type']; // video_upload or image_upload
    
    $errors = [];
    
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (!isset($_FILES['media_file']) || $_FILES['media_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Please select a valid file";
    }
    
    if (empty($errors)) {
        $file = $_FILES['media_file'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Validate file type
        if ($type === 'video_upload') {
            $allowedTypes = ['mp4', 'webm', 'ogg', 'avi', 'mov'];
            if (!in_array($fileExtension, $allowedTypes)) {
                $errors[] = "Invalid video format. Allowed: " . implode(', ', $allowedTypes);
            }
        } else {
            $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($fileExtension, $allowedTypes)) {
                $errors[] = "Invalid image format. Allowed: " . implode(', ', $allowedTypes);
            }
        }
        
        // Check file size (50MB limit for videos, 5MB for images)
        $maxSize = ($type === 'video_upload') ? 50 * 1024 * 1024 : 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            $maxSizeMB = $maxSize / (1024 * 1024);
            $errors[] = "File size must be less than {$maxSizeMB}MB";
        }
        
        if (empty($errors)) {
            // Generate unique filename
            $fileName = uniqid() . '_' . time() . '.' . $fileExtension;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Handle thumbnail upload if provided
                $thumbnailName = null;
                if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === UPLOAD_ERR_OK) {
                    $thumbnail = $_FILES['thumbnail'];
                    $thumbExtension = strtolower(pathinfo($thumbnail['name'], PATHINFO_EXTENSION));
                    $allowedThumbTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array($thumbExtension, $allowedThumbTypes) && $thumbnail['size'] <= 2 * 1024 * 1024) {
                        $thumbnailName = 'thumb_' . uniqid() . '_' . time() . '.' . $thumbExtension;
                        $thumbnailPath = $uploadDir . $thumbnailName;
                        move_uploaded_file($thumbnail['tmp_name'], $thumbnailPath);
                    }
                }
                
                // Save to database
                $db = Database::getInstance();
                $result = $db->insert('tutorials_ads', [
                    'title' => $title,
                    'description' => $description,
                    'type' => $type,
                    'file_path' => $fileName,
                    'thumbnail' => $thumbnailName,
                    'priority' => $priority,
                    'display_on_homepage' => $displayOnHomepage,
                    'created_by' => Auth::getUser()['id']
                ]);
                
                if ($result) {
                    $_SESSION['success_message'] = ucfirst(str_replace('_', ' ', $type)) . " uploaded successfully!";
                    header("Location: ../index.php");
                    exit;
                } else {
                    $errors[] = "Failed to save to database";
                    // Delete uploaded file
                    unlink($filePath);
                    if ($thumbnailName) unlink($uploadDir . $thumbnailName);
                }
            } else {
                $errors[] = "Failed to upload file";
            }
        }
    }
}

// Include header
include '../../includes/header.php';
?>

<style>
.upload-guidelines {
    background-color: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 1rem;
}

.file-info {
    font-size: 0.875rem;
    color: #6c757d;
}
</style>

<!-- Breadcrumbs-->
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="../../index.php">Dashboard</a>
    </li>
    <li class="breadcrumb-item">
        <a href="../index.php">Tutorials</a>
    </li>
    <li class="breadcrumb-item active">Upload Video/Image</li>
</ol>

<!-- Page Content -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-upload mr-1"></i> Upload Media File
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="type">Media Type</label>
                        <select name="type" id="type" class="form-control" onchange="updateFileInput()">
                            <option value="video_upload">Video Upload</option>
                            <option value="image_upload">Image Upload</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" name="title" id="title" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="3"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="media_file" id="file-label">Video File *</label>
                        <input type="file" name="media_file" id="media_file" class="form-control-file" 
                               accept="video/*" required>
                        <small class="form-text text-muted file-info" id="file-help">
                            Maximum file size: 50MB. Supported formats: MP4, WebM, OGG, AVI, MOV
                        </small>
                    </div>
                    
                    <div class="form-group" id="thumbnail-group">
                        <label for="thumbnail">Thumbnail (Optional)</label>
                        <input type="file" name="thumbnail" id="thumbnail" class="form-control-file" 
                               accept="image/*">
                        <small class="form-text text-muted file-info">
                            Upload a custom thumbnail image. Maximum size: 2MB
                        </small>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label for="priority">Priority</label>
                            <input type="number" name="priority" id="priority" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['priority'] ?? '0'); ?>" min="0">
                            <small class="form-text text-muted file-info">Lower numbers appear first</small>
                        </div>
                        <div class="form-group col-md-6">
                            <div class="form-check mt-4">
                                <input type="checkbox" name="display_on_homepage" id="display_on_homepage" 
                                       class="form-check-input" <?php echo isset($_POST['display_on_homepage']) ? 'checked' : 'checked'; ?>>
                                <label class="form-check-label" for="display_on_homepage">
                                    Display on Homepage
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-upload mr-1"></i> Upload
                    </button>
                    <a href="../index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Tutorials
                    </a>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h6 class="card-title mb-0">Upload Guidelines</h6>
            </div>
            <div class="card-body">
                <h6>Video Guidelines:</h6>
                <ul class="small">
                    <li>Maximum file size: 50MB</li>
                    <li>Supported formats: MP4, WebM, OGG, AVI, MOV</li>
                    <li>Recommended resolution: 1280x720 or higher</li>
                    <li>Optimal duration: 30 seconds to 5 minutes</li>
                </ul>
                
                <h6 class="mt-3">Image Guidelines:</h6>
                <ul class="small">
                    <li>Maximum file size: 5MB</li>
                    <li>Supported formats: JPG, PNG, GIF, WebP</li>
                    <li>Recommended resolution: 1200x630</li>
                    <li>Use high-quality images for best results</li>
                </ul>
                
                <h6 class="mt-3">Best Practices:</h6>
                <ul class="small">
                    <li>Use descriptive titles</li>
                    <li>Add relevant descriptions</li>
                    <li>Set appropriate priority for ordering</li>
                    <li>Upload custom thumbnails for videos</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function updateFileInput() {
    const type = document.getElementById('type').value;
    const fileInput = document.getElementById('media_file');
    const fileLabel = document.getElementById('file-label');
    const fileHelp = document.getElementById('file-help');
    const thumbnailGroup = document.getElementById('thumbnail-group');
    
    if (type === 'video_upload') {
        fileLabel.textContent = 'Video File *';
        fileInput.accept = 'video/*';
        fileHelp.textContent = 'Maximum file size: 50MB. Supported formats: MP4, WebM, OGG, AVI, MOV';
        thumbnailGroup.style.display = 'block';
    } else {
        fileLabel.textContent = 'Image File *';
        fileInput.accept = 'image/*';
        fileHelp.textContent = 'Maximum file size: 5MB. Supported formats: JPG, PNG, GIF, WebP';
        thumbnailGroup.style.display = 'none';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    updateFileInput();
});
</script>

<?php include '../../includes/footer.php'; ?>
