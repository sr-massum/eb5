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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'active';
    $position = isset($_POST['position']) ? (int)$_POST['position'] : 1;
    
    // Validate form data
    if (empty($content)) {
        $_SESSION['error_message'] = 'Content is required';
    } else {
        // Insert scrolling notice
        $db = Database::getInstance();
        $noticeData = [
            'type' => 'scrolling',
            'title' => '', // Scrolling notices don't need titles
            'content' => $content,
            'status' => $status,
            'position' => $position,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $noticeId = $db->insert('notices', $noticeData);
        
        if ($noticeId) {
            $_SESSION['success_message'] = 'Scrolling notice added successfully';
            header("Location: index.php");
            exit;
        } else {
            $_SESSION['error_message'] = 'Failed to add scrolling notice';
        }
    }
}

// Get existing positions to help with ordering
$db = Database::getInstance();
$existingPositions = $db->getRows("SELECT position, content FROM notices WHERE type = 'scrolling' ORDER BY position ASC");

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
    <li class="breadcrumb-item active">Add New</li>
</ol>

<!-- Page Content -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-plus mr-1"></i> Add New Scrolling Notice
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

                <form method="post" id="scrollingNoticeForm">
                    <div class="form-group">
                        <label for="content" class="font-weight-bold">Notice Content <span class="text-danger">*</span></label>
                        <textarea class="form-control scrolling-notice" id="content" name="content" rows="4" 
                                placeholder="Enter your scrolling notice message here..." required></textarea>
                        <small class="form-text text-muted">
                            Keep the message concise for better readability in scrolling format. Bold text and emojis are supported.
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="position" class="font-weight-bold">Display Position</label>
                        <select class="form-control" id="position" name="position">
                            <option value="1">Position 1 (First to scroll)</option>
                            <option value="2">Position 2</option>
                            <option value="3">Position 3</option>
                            <option value="4">Position 4</option>
                            <option value="5">Position 5</option>
                            <option value="6">Position 6</option>
                            <option value="7">Position 7</option>
                            <option value="8">Position 8</option>
                            <option value="9">Position 9</option>
                            <option value="10">Position 10</option>
                        </select>
                        <small class="form-text text-muted">
                            Lower positions scroll first. Multiple notices will scroll one after another based on position.
                        </small>
                    </div>
                    
                    <div class="form-group">
                        <label for="status" class="font-weight-bold">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="active">Active (Visible on website)</option>
                            <option value="inactive">Inactive (Hidden from website)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Add Scrolling Notice
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times mr-2"></i>Cancel
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
                    <div class="text-muted">Type content to see preview...</div>
                </div>
            </div>
        </div>
        
        <!-- Current Positions -->
        <?php if (count($existingPositions) > 0): ?>
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list mr-2"></i>Existing Positions
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