<?php 

/**
 * ExchangeBridge - Admin Panel Scrolling Notice
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

// Get all scrolling notices with ordering
$db = Database::getInstance();
$scrollingNotices = $db->getRows("SELECT * FROM notices WHERE type = 'scrolling' ORDER BY position ASC, created_at DESC");

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
.scrolling-notice {
    font-family: 'Poppins', Arial, sans-serif;
    font-weight: 500;
    font-size: 14px;
    line-height: 1.6;
}

.scrolling-preview {
    background: linear-gradient(135deg, #fff3cd 0%, #fde68a 100%);
    border: 2px solid #f59e0b;
    border-radius: 0.5rem;
    padding: 1rem;
    overflow: hidden;
    white-space: nowrap;
    position: relative;
    min-height: 60px;
    display: flex;
    align-items: center;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.scrolling-preview .scrolling-text {
    display: inline-block;
    animation: scroll-left 30s linear infinite;
    font-family: 'Poppins', Arial, sans-serif;
    font-weight: 600;
    font-size: 16px;
    color: #92400e;
    padding-left: 100%;
}

@keyframes scroll-left {
    0% { transform: translateX(0); }
    100% { transform: translateX(-100%); }
}

.position-badge {
    background-color: #6c757d;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.notice-content {
    font-family: 'Poppins', Arial, sans-serif;
    line-height: 1.6;
}

.preview-info {
    background-color: #e3f2fd;
    border-left: 4px solid #2196f3;
    padding: 0.75rem;
    margin-top: 0.5rem;
    border-radius: 0 0.25rem 0.25rem 0;
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
    <li class="breadcrumb-item active">Scrolling Notices</li>
</ol>

<!-- Page Content -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-scroll mr-1"></i> Scrolling Notices Management
        <a href="add.php" class="btn btn-primary btn-sm float-right">
            <i class="fas fa-plus"></i> Add New Scrolling Notice
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

        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-eye mr-2"></i>Live Preview - Combined Scrolling</h6>
                    </div>
                    <div class="card-body">
                        <div id="scrolling-preview-container">
                            <?php 
                            // Get all active scrolling notices ordered by position
                            $activeNotices = [];
                            foreach ($scrollingNotices as $notice) {
                                if ($notice['status'] === 'active') {
                                    $activeNotices[] = $notice;
                                }
                            }
                            
                            if (count($activeNotices) > 0): 
                                // Sort by position
                                usort($activeNotices, function($a, $b) {
                                    return $a['position'] - $b['position'];
                                });
                                
                                // Combine all notices into one scrolling text
                                $combinedText = [];
                                foreach ($activeNotices as $notice) {
                                    $combinedText[] = strip_tags($notice['content']);
                                }
                                $scrollingContent = implode('     |     ', $combinedText);
                            ?>
                            <div class="scrolling-preview">
                                <div class="scrolling-text">
                                    <?php echo $scrollingContent; ?>
                                </div>
                            </div>
                            <div class="preview-info">
                                <small>
                                    <strong><i class="fas fa-info-circle mr-1"></i>Active Notices (<?php echo count($activeNotices); ?>):</strong>
                                    <?php foreach ($activeNotices as $index => $notice): ?>
                                        <span class="badge badge-secondary ml-1">Pos.<?php echo $notice['position']; ?></span>
                                    <?php endforeach; ?>
                                    <br><strong>Scrolling Pattern:</strong> All active notices scroll together in position order, separated by " | "
                                </small>
                            </div>
                            <?php else: ?>
                                <div class="text-muted text-center py-4">
                                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                                    <p class="mb-0">No active scrolling notices to preview</p>
                                    <small>Add some active notices to see the combined scrolling effect</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                <thead class="thead-light">
                    <tr>
                        <th width="80">Position</th>
                        <th>Content</th>
                        <th width="100">Status</th>
                        <th width="120">Created</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($scrollingNotices) > 0): ?>
                    <?php foreach ($scrollingNotices as $notice): ?>
                    <tr>
                        <td class="text-center">
                            <span class="position-badge"><?php echo $notice['position']; ?></span>
                        </td>
                        <td class="notice-content">
                            <?php 
                            $content = strip_tags($notice['content']);
                            echo mb_substr($content, 0, 100, 'UTF-8') . (mb_strlen($content, 'UTF-8') > 100 ? '...' : ''); 
                            ?>
                        </td>
                        <td>
                            <span class="badge badge-<?php echo $notice['status'] === 'active' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($notice['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($notice['created_at'])); ?></td>
                        <td>
                            <a href="view.php?id=<?php echo $notice['id']; ?>" class="btn btn-info btn-sm" title="View">
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
                        <td colspan="5" class="text-center py-4">
                            <div class="text-muted">
                                <i class="fas fa-scroll fa-3x mb-3"></i>
                                <p class="mb-0">No scrolling notices found</p>
                                <a href="add.php" class="btn btn-primary btn-sm mt-2">Create your first scrolling notice</a>
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
        if (!confirm('Are you sure you want to delete this scrolling notice? This action cannot be undone.')) {
            e.preventDefault();
        }
    });
    
    // Auto refresh scrolling animation every 30 seconds
    setInterval(function() {
        $('#scrolling-preview-container .scrolling-text').each(function() {
            $(this).css('animation', 'none').offset().height; // trigger reflow
            $(this).css('animation', 'scroll-left 30s linear infinite');
        });
    }, 30000);
});
</script>

<?php include '../../includes/footer.php'; ?>