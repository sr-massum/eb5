<?php 

/**
 * ExchangeBridge - Admin Panel Popup Notice View
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

// Get notice ID
$noticeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$noticeId) {
    $_SESSION['error_message'] = 'Invalid notice ID';
    header("Location: index.php");
    exit;
}

// Get notice data
$db = Database::getInstance();
$notice = $db->getRow("SELECT * FROM notices WHERE id = ? AND type = 'popup'", [$noticeId]);

if (!$notice) {
    $_SESSION['error_message'] = 'Notice not found';
    header("Location: index.php");
    exit;
}

// Function to extract images from HTML content
function extractImagesFromContent($content) {
    if (empty($content)) return [];
    
    $images = [];
    $dom = new DOMDocument();
    @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $content);
    $imgTags = $dom->getElementsByTagName('img');
    
    foreach ($imgTags as $img) {
        $src = $img->getAttribute('src');
        if ($src) {
            $images[] = $src;
        }
    }
    
    return $images;
}

// Get images from content
$contentImages = extractImagesFromContent($notice['content']);

// Include header
include '../../includes/header.php';
?>

<style>
.notice-preview {
    font-family: 'Poppins', Arial, sans-serif;
    line-height: 1.6;
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}

.notice-preview img, .notice-preview video {
    max-width: 100%;
    height: auto;
    border-radius: 4px;
    margin: 10px 0;
}

.media-gallery img {
    max-width: 150px;
    max-height: 150px;
    object-fit: cover;
    border-radius: 4px;
    margin: 5px;
    border: 1px solid #ddd;
}

.info-table th {
    background-color: #f8f9fa;
    font-weight: 600;
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
                <i class="fas fa-eye mr-1"></i> Notice Preview
                <div class="float-right">
                    <a href="edit.php?id=<?php echo $notice['id']; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="index.php" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="notice-preview">
                    <?php if (!empty($notice['title'])): ?>
                        <h3 class="mb-3"><?php echo htmlspecialchars($notice['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <?php endif; ?>
                    
                    <div class="notice-content">
                        <?php echo $notice['content']; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Notice Details -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle mr-1"></i> Notice Details
            </div>
            <div class="card-body">
                <table class="table table-sm info-table">
                    <tr>
                        <th width="40%">Title:</th>
                        <td><?php echo $notice['title'] ? htmlspecialchars($notice['title'], ENT_QUOTES, 'UTF-8') : '(No title)'; ?></td>
                    </tr>
                    <tr>
                        <th>Status:</th>
                        <td>
                            <span class="badge badge-<?php echo $notice['status'] === 'active' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($notice['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th>Created:</th>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($notice['created_at'])); ?></td>
                    </tr>
                    <tr>
                        <th>Updated:</th>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($notice['updated_at'])); ?></td>
                    </tr>
                    <tr>
                        <th>Media:</th>
                        <td>
                            <?php if (!empty($notice['image_path'])): ?>
                                <span class="text-success">
                                    <i class="fas fa-check-circle"></i> Form upload attached
                                </span>
                            <?php elseif (count($contentImages) > 0): ?>
                                <span class="text-info">
                                    <i class="fas fa-images"></i> <?php echo count($contentImages); ?> image(s) in content
                                </span>
                            <?php else: ?>
                                <span class="text-muted">
                                    <i class="fas fa-times-circle"></i> No media attached
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Media Gallery -->
        <?php if (!empty($notice['image_path']) || count($contentImages) > 0): ?>
        <div class="card mt-3">
            <div class="card-header">
                <i class="fas fa-images mr-1"></i> Media Gallery
            </div>
            <div class="card-body">
                <div class="media-gallery">
                    <?php if (!empty($notice['image_path'])): ?>
                        <?php 
                        $imagePath = SITE_URL . '/assets/uploads/notices/' . $notice['image_path'];
                        $extension = strtolower(pathinfo($notice['image_path'], PATHINFO_EXTENSION));
                        ?>
                        <div class="mb-2">
                            <small class="text-muted">Form Upload:</small><br>
                            <?php if (in_array($extension, ['mp4', 'webm', 'ogg'])): ?>
                                <video controls style="max-width: 200px;">
                                    <source src="<?php echo $imagePath; ?>" type="video/<?php echo $extension; ?>">
                                </video>
                            <?php else: ?>
                                <img src="<?php echo $imagePath; ?>" alt="Form Upload" onclick="window.open(this.src, '_blank')" style="cursor: pointer;">
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (count($contentImages) > 0): ?>
                        <div class="mb-2">
                            <small class="text-muted">Content Images:</small><br>
                            <?php foreach ($contentImages as $index => $imageUrl): ?>
                                <img src="<?php echo $imageUrl; ?>" 
                                     alt="Content Image <?php echo $index + 1; ?>" 
                                     onclick="window.open(this.src, '_blank')" 
                                     style="cursor: pointer;"
                                     onerror="this.style.display='none'">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<?php include '../../includes/footer.php'; ?>