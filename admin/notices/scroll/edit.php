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

// Get notice ID
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['error_message'] = 'Invalid notice ID';
    header("Location: index.php");
    exit;
}

$db = Database::getInstance();

// Get notice data and verify it's a scrolling notice
$notice = $db->getRow("SELECT * FROM notices WHERE id = ? AND type = 'scrolling'", [$id]);

if (!$notice) {
    $_SESSION['error_message'] = 'Scrolling notice not found';
    header("Location: index.php");
    exit;
}

// Get existing positions to help with ordering
$existingPositions = $db->getRows("SELECT position, content FROM notices WHERE type = 'scrolling' AND id != ? ORDER BY position ASC", [$id]);

// Include header
include '../../includes/header.php';
?>

<style>
.scrolling-notice {
    font-family: 'Poppins', Arial, sans-serif;
    font-weight: 500;
    font-size: 16px;
    line-height: 1.6;
}

.preview-container {
    background-color: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 0.375rem;
    padding: 1rem;
    overflow: hidden;
    white-space: nowrap;
    position: relative;
    min-height: 60px;
    display: flex;
    align-items: center;
}

.scrolling-text {
    display: inline-block;
    animation: scroll-left 15s linear infinite;
    font-family: 'Poppins', Arial, sans-serif;
    font-weight: 500;
    font-size: 14px;
}

@keyframes scroll-left {
    0% { transform: translateX(100%); }
    100% { transform: translateX(-100%); }
}

.position-indicator {
    background-color: #e9ecef;
    border: 1px solid #dee2e6;
    border-radius: 0.25rem;
    padding: 0.5rem;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
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
        <a href="index.php">Scrolling Notices</a>
    </li>
    <li class="breadcrumb-item active">Edit Notice</li>
</ol>

<!-- Page Content -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-edit mr-1"></i> Edit Scrolling Notice
            </div>
            <div class="card-body">
                <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>

                <form method="post" action="update.php" id="scrollingNoticeForm">
                    <input type="hidden" name="id" value="<?php echo $notice['id']; ?>">
                    
                    <div class="form-group">
                        <label for="content" class="font-weight-bold">Notice Content <span class="text-danger">*</span></label>
                        <textarea class="form-control scrolling-notice" id="content" name="content" rows="4" 
                                placeholder="Enter your scrolling notice message here..." required><?php echo htmlspecialchars($notice['content']); ?></textarea>
                        <small class="form-text text-muted">
                            Keep the message concise for better readability in scrolling format. Bold text and emojis are supported.
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="position" class="font-weight-bold">Display Position</label>
                        <select class="form-control" id="position" name="position">
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $notice['position'] == $i ? 'selected' : ''; ?>>
                                Position <?php echo $i; ?> <?php echo $i == 1 ? '(First to scroll)' : ''; ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                        <small class="form-text text-muted">
                            Lower positions scroll first. Multiple notices will scroll one after another based on position.
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="status" class="font-weight-bold">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="active" <?php echo $notice['status'] === 'active' ? 'selected' : ''; ?>>Active (Visible on website)</option>
                            <option value="inactive" <?php echo $notice['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive (Hidden from website)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Update Scrolling Notice
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                        <a href="view.php?id=<?php echo $notice['id']; ?>" class="btn btn-info">
                            <i class="fas fa-eye mr-2"></i>Preview
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Live Preview -->
        <div class="card mb-4">
            <div class="card-header bg-info text-white">
                <i class="fas fa-eye mr-2"></i>Live Preview
            </div>
            <div class="card-body">
                <div class="preview-container" id="preview-container">
                    <div class="scrolling-text"><?php echo strip_tags($notice['content']); ?></div>
                </div>
            </div>
        </div>
        
        <!-- Other Positions -->
        <?php if (count($existingPositions) > 0): ?>
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list mr-2"></i>Other Positions
            </div>
            <div class="card-body">
                <?php foreach ($existingPositions as $existing): ?>
                <div class="position-indicator">
                    <strong>Position <?php echo $existing['position']; ?>:</strong><br>
                    <small><?php echo mb_substr(strip_tags($existing['content']), 0, 50, 'UTF-8') . '...'; ?></small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
$(document).ready(function() {
    // Live preview functionality
    $('#content').on('input', function() {
        const content = $(this).val().trim();
        const previewContainer = $('#preview-container');
        
        if (content) {
            previewContainer.html('<div class="scrolling-text">' + content + '</div>');
        } else {
            previewContainer.html('<div class="text-muted">Type content to see preview...</div>');
        }
    });
    
    // Form validation
    $('#scrollingNoticeForm').on('submit', function(e) {
        const content = $('#content').val().trim();
        
        if (!content) {
            e.preventDefault();
            alert('Content is required');
            $('#content').focus();
            return false;
        }
        
        if (content.length < 10) {
            e.preventDefault();
            alert('Content should be at least 10 characters long');
            $('#content').focus();
            return false;
        }
    });
});
</script>

<?php include '../../includes/footer.php'; ?>