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


// Set UTF-8 encoding headers
header('Content-Type: text/html; charset=UTF-8');

// Check if user is logged in
if (!Auth::isLoggedIn()) {
    header("Location: ../../login.php");
    exit;
}

// Function to extract first image from HTML content
function getFirstImageFromContent($content) {
    if (empty($content)) return null;
    
    // Use DOMDocument to parse HTML and find first image
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $content);
    $images = $dom->getElementsByTagName('img');
    
    if ($images->length > 0) {
        $firstImage = $images->item(0);
        return $firstImage->getAttribute('src');
    }
    
    return null;
}

// Get all popup notices
$db = Database::getInstance();
$popupNotices = $db->getRows("SELECT * FROM notices WHERE type = 'popup' ORDER BY created_at DESC");

// Check for success message
$successMessage = '';
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Check for error message
$errorMessage = '';
if (isset($_SESSION['error_message'])) {
    $errorMessage = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
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

.notice-content {
    font-family: 'Poppins', Arial, sans-serif;
    line-height: 1.6;
}

.media-preview {
    max-width: 60px;
    max-height: 60px;
    object-fit: cover;
    border-radius: 4px;
}

.popup-preview-card {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    background-color: #f8f9fa;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
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
    <li class="breadcrumb-item active">Popup Notices</li>
</ol>

<!-- Page Content -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-window-restore mr-1"></i> Popup Notices Management
        <a href="add.php" class="btn btn-primary btn-sm float-right">
            <i class="fas fa-plus"></i> Add New Popup Notice
        </a>
    </div>
    <div class="card-body">
        <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $successMessage; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $errorMessage; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th>Title</th>
                        <th>Content Preview</th>
                        <th width="80">Media</th>
                        <th width="100">Status</th>
                        <th width="120">Created</th>
                        <th width="180">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($popupNotices) > 0): ?>
                    <?php foreach ($popupNotices as $notice): ?>
                    <tr>
                        <td class="notice-content">
                            <strong><?php echo $notice['title'] ? htmlspecialchars($notice['title']) : '(No title)'; ?></strong>
                        </td>
                        <td class="notice-content">
                            <?php 
                            $content = strip_tags($notice['content']);
                            echo mb_substr($content, 0, 80, 'UTF-8') . (mb_strlen($content, 'UTF-8') > 80 ? '...' : ''); 
                            ?>
                        </td>
                        <td class="text-center">
                            <?php 
                            // Check for image_path first, then check content for images
                            $imageUrl = null;
                            $isVideo = false;
                            
                            if (!empty($notice['image_path'])) {
                                // Traditional upload via form
                                $imageUrl = SITE_URL . '/assets/uploads/notices/' . $notice['image_path'];
                                $extension = strtolower(pathinfo($notice['image_path'], PATHINFO_EXTENSION));
                                $isVideo = in_array($extension, ['mp4', 'webm', 'ogg']);
                            } else {
                                // Check for images in TinyMCE content
                                $imageUrl = getFirstImageFromContent($notice['content']);
                                if ($imageUrl) {
                                    // Check if it's a video by URL or extension
                                    $extension = strtolower(pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION));
                                    $isVideo = in_array($extension, ['mp4', 'webm', 'ogg']);
                                }
                            }
                            ?>
                            
                            <?php if ($imageUrl): ?>
                                <?php if ($isVideo): ?>
                                    <video class="media-preview" muted>
                                        <source src="<?php echo $imageUrl; ?>" type="video/<?php echo $extension; ?>">
                                    </video>
                                <?php else: ?>
                                    <img src="<?php echo $imageUrl; ?>" alt="Notice Media" class="media-preview" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                                    <span class="text-muted" style="display: none;"><i class="fas fa-image"></i></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted"><i class="fas fa-image"></i></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $notice['status'] === 'active' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($notice['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($notice['created_at'])); ?></td>
                        <td>
                            <a href="view.php?id=<?php echo $notice['id']; ?>" class="btn btn-info btn-sm" title="Preview">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="edit.php?id=<?php echo $notice['id']; ?>" class="btn btn-primary btn-sm" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="delete.php?id=<?php echo $notice['id']; ?>" class="btn btn-danger btn-sm delete-confirm" title="Delete">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">
                            <div class="text-muted">
                                <i class="fas fa-window-restore fa-3x mb-3"></i>
                                <p class="mb-0">No popup notices found</p>
                                <a href="add.php" class="btn btn-primary btn-sm mt-2">Create your first popup notice</a>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
$(document).ready(function() {
    // Delete confirmation
    $('.delete-confirm').click(function(e) {
        if (!confirm('Are you sure you want to delete this popup notice? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>