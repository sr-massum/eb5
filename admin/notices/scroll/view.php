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

// Get notice data and verify it's a scrolling notice
$notice = $db->getRow("SELECT * FROM notices WHERE id = ? AND type = 'scrolling'", [$id]);

if (!$notice) {
    $_SESSION['error_message'] = 'Scrolling notice not found';
    header("Location: index.php");
    exit;
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

.notice-details {
    background-color: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 0.375rem;
    padding: 1rem;
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
    <li class="breadcrumb-item active">View Notice</li>
</ol>

<!-- Page Content -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="fas fa-eye mr-1"></i> Scrolling Notice Preview
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
                    <div class="scrolling-preview">
                        <div class="scrolling-text">
                            <strong>Position <?php echo $notice['position']; ?>:</strong> 
                            <?php echo strip_tags($notice['content']); ?>
                        </div>
                    </div>
                    <small class="text-muted mt-2 d-block">
                        This is how the notice will appear on your website
                    </small>
                </div>
                
                <div class="notice-details">
                    <h6 class="text-primary mb-3">Notice Details:</h6>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Content:</strong></p>
                            <div class="scrolling-notice bg-white p-3 border rounded">
                                <?php echo nl2br(htmlspecialchars($notice['content'])); ?>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <p><strong>Position:</strong> <?php echo $notice['position']; ?></p>
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
                <h6>Scrolling Notice Guidelines:</h6>
                <ul class="small">
                    <li>Position determines scroll order (lower numbers scroll first)</li>
                    <li>Keep messages concise for better readability</li>
                    <li>Use bold text or emojis for emphasis</li>
                    <li>Multiple active notices will scroll sequentially</li>
                </ul>
                
                <hr>
                
                <h6>Quick Actions:</h6>
                <div class="d-grid gap-2">
                    <a href="edit.php?id=<?php echo $notice['id']; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-edit mr-1"></i> Edit Notice
                    </a>
                    <a href="delete.php?id=<?php echo $notice['id']; ?>" class="btn btn-danger btn-sm" 
                       onclick="return confirm('Are you sure you want to delete this scrolling notice?')">
                        <i class="fas fa-trash mr-1"></i> Delete Notice
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<?php include '../../includes/footer.php'; ?>