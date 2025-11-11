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
require_once '../../config/config.php';
require_once '../../config/verification.php';
require_once '../../config/license.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../../includes/app.php';
require_once '../../includes/auth.php';
require_once '../../includes/security.php';


// Set UTF-8 encoding headers
header('Content-Type: text/html; charset=UTF-8');

// Check if user is logged in
if (!Auth::isLoggedIn()) {
    header("Location: ../../login.php");
    exit;
}

// Get notice ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['error_message'] = 'Invalid notice ID';
    header("Location: index.php");
    exit;
}

$db = Database::getInstance();

// Get notice data and verify it's a popup notice
$notice = $db->getRow("SELECT * FROM notices WHERE id = ? AND type = 'popup'", [$id]);

if (!$notice) {
    $_SESSION['error_message'] = 'Popup notice not found';
    header("Location: index.php");
    exit;
}

// Include header
include '../../includes/header.php';
?>

<style>
.popup-notice {
    font-family: 'Poppins', Arial, sans-serif;
    font-size: 14px;
    line-height: 1.6;
}

.notice-image, .notice-video {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 10px 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.popup-preview-card {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 1.5rem;
    background-color: #f8f9fa;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    max-height: 500px;
    overflow-y: auto;
}

.notice-details {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    padding: 1rem;
}

.media-preview {
    max-width: 200px;
    height: auto;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>

<!-- Breadcrumbs-->
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="../../index.php">Dashboard</a>
    </li>
    <li class="breadcrumb-item">
        <a href="../index.php">Notices</a>
    </li>
    <li class="breadcrumb-item">
        <a href="index.php">Popup Notices</a>
    </li>
    <li class="breadcrumb-item active">View Notice</li>
</ol>

<!-- Page Content -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-eye mr-1"></i> Popup Notice Preview
                <div class="float-right">
                    <a href="edit.php?id=<?php echo $notice['id']; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="index.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h5 class="text-primary mb-3">Live Preview:</h5>
                    <div class="popup-preview-card">
                        <?php if (!empty($notice['title'])): ?>
                        <h4 class="popup-notice mb-3"><?php echo htmlspecialchars($notice['title']); ?></h4>
                        <?php endif; ?>
                        
                        <?php if (!empty($notice['image_path'])): ?>
                            <?php 
                            $imagePath = SITE_URL . '/assets/uploads/notices/' . $notice['image_path'];
                            $extension = strtolower(pathinfo($notice['image_path'], PATHINFO_EXTENSION));
                            ?>
                            <?php if (in_array($extension, ['mp4', 'webm', 'ogg'])): ?>
                                <video class="notice-video" controls>
                                    <source src="<?php echo $imagePath; ?>" type="video/<?php echo $extension; ?>">
                                    Your browser does not support the video tag.
                                </video>
                            <?php else: ?>
                                <img src="<?php echo $imagePath; ?>" class="notice-image" alt="Notice Media">
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <div class="popup-notice">
                            <?php echo $notice['content']; ?>
                        </div>
                    </div>
                    <small class="text-muted mt-2 d-block">
                        This is how the notice will appear as a popup on your website
                    </small>
                </div>
                
                <div class="notice-details">
                    <h6 class="text-primary mb-3">Notice Details:</h6>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Title:</strong> <?php echo $notice['title'] ? htmlspecialchars($notice['title']) : '(No title)'; ?></p>
                            <p><strong>Status:</strong> 
                                <span class="badge badge-<?php echo $notice['status'] === 'active' ? 'success' : 'danger'; ?>">
                                    <?php echo ucfirst($notice['status']); ?>
                                </span>
                            </p>
                            <p><strong>Created:</strong> <?php echo date('Y-m-d H:i:s', strtotime($notice['created_at'])); ?></p>
                            <?php if ($notice['updated_at']): ?>
                            <p><strong>Updated:</strong> <?php echo date('Y-m-d H:i:s', strtotime($notice['updated_at'])); ?></p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-6">
                            <?php if (!empty($notice['image_path'])): ?>
                            <p><strong>Media File:</strong></p>
                            <?php 
                            $imagePath = SITE_URL . '/assets/uploads/notices/' . $notice['image_path'];
                            $extension = strtolower(pathinfo($notice['image_path'], PATHINFO_EXTENSION));
                            ?>
                            <?php if (in_array($extension, ['mp4', 'webm', 'ogg'])): ?>
                                <video class="media-preview" muted>
                                    <source src="<?php echo $imagePath; ?>" type="video/<?php echo $extension; ?>">
                                </video>
                            <?php else: ?>
                                <img src="<?php echo $imagePath; ?>" class="media-preview" alt="Media Preview">
                            <?php endif; ?>
                            <br><small class="text-muted"><?php echo $notice['image_path']; ?></small>
                            <?php else: ?>
                            <p><strong>Media:</strong> No media attached</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mt-3">
                        <p><strong>Raw Content:</strong></p>
                        <div class="bg-white p-3 border rounded" style="max-height: 200px; overflow-y: auto;">
                            <code><?php echo htmlspecialchars($notice['content']); ?></code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <i class="fas fa-info-circle mr-2"></i>Information
            </div>
            <div class="card-body">
                <h6>Popup Notice Features:</h6>
                <ul class="small">
                    <li>Rich text formatting with HTML support</li>
                    <li>Image and video media support</li>
                    <li>Responsive design for all devices</li>
                    <li>Professional popup display</li>
                </ul>
                
                <hr>
                
                <h6>Quick Actions:</h6>
                <div class="d-grid gap-2">
                    <a href="edit.php?id=<?php echo $notice['id']; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit mr-1"></i> Edit Notice
                    </a>
                    <a href="delete.php?id=<?php echo $notice['id']; ?>" class="btn btn-danger btn-sm" 
                       onclick="return confirm('Are you sure you want to delete this popup notice and its media?')">
                        <i class="fas fa-trash mr-1"></i> Delete Notice
                    </a>
                </div>
                
                <hr>
                
                <h6>Media Information:</h6>
                <?php if (!empty($notice['image_path'])): ?>
                    <?php
                    $mediaPath = '../../../assets/uploads/notices/' . $notice['image_path'];
                    if (file_exists($mediaPath)) {
                        $fileSize = filesize($mediaPath);
                        $fileSizeFormatted = $fileSize > 1024 * 1024 ? 
                            round($fileSize / (1024 * 1024), 2) . ' MB' : 
                            round($fileSize / 1024, 2) . ' KB';
                    }
                    ?>
                    <p class="small">
                        <strong>File:</strong> <?php echo $notice['image_path']; ?><br>
                        <strong>Size:</strong> <?php echo isset($fileSizeFormatted) ? $fileSizeFormatted : 'Unknown'; ?><br>
                        <strong>Type:</strong> <?php echo strtoupper($extension); ?>
                    </p>
                <?php else: ?>
                    <p class="small text-muted">No media attached to this notice.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<?php include '../../includes/footer.php'; ?>