<?php 

/**
 * ExchangeBridge - Admin Panel Receipt View
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

// Check if user is logged in
if (!Auth::isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Get exchange ID
$exchangeId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($exchangeId <= 0) {
    $_SESSION['error_message'] = 'Invalid exchange ID';
    header("Location: index.php");
    exit;
}

// Get exchange details
$db = Database::getInstance();
$exchange = $db->getRow("
    SELECT e.*, 
           fc.name as from_currency_name, fc.code as from_currency_code, fc.display_name as from_display_name,
           tc.name as to_currency_name, tc.code as to_currency_code, tc.display_name as to_display_name
    FROM exchanges e
    LEFT JOIN currencies fc ON e.from_currency_id = fc.id
    LEFT JOIN currencies tc ON e.to_currency_id = tc.id
    WHERE e.id = ?
", [$exchangeId]);

if (!$exchange) {
    $_SESSION['error_message'] = 'Exchange not found';
    header("Location: index.php");
    exit;
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = sanitizeInput($_POST['status']);
    $adminNotes = sanitizeInput($_POST['admin_notes'] ?? '');
    
    $updateData = [
        'status' => $newStatus,
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    // Add admin notes if provided
    if (!empty($adminNotes)) {
        $updateData['admin_notes'] = $adminNotes;
    }
    
    $success = $db->update('exchanges', $updateData, 'id = ?', [$exchangeId]);
    
    if ($success) {
        $_SESSION['success_message'] = 'Exchange status updated successfully!';
        header("Location: view.php?id={$exchangeId}");
        exit;
    } else {
        $_SESSION['error_message'] = 'Failed to update exchange status.';
    }
}

// Check for messages
$successMessage = $_SESSION['success_message'] ?? '';
$errorMessage = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Include header
include '../includes/header.php';
?>

<!-- Breadcrumbs-->
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="../index.php">Dashboard</a>
    </li>
    <li class="breadcrumb-item">
        <a href="index.php">Receipts</a>
    </li>
    <li class="breadcrumb-item active">Exchange #<?php echo htmlspecialchars($exchange['reference_id']); ?></li>
</ol>

<!-- Page Content -->
<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-receipt mr-1"></i> Exchange Details
                <span class="badge badge-<?php 
                    switch($exchange['status']) {
                        case 'pending': echo 'warning'; break;
                        case 'confirmed': echo 'success'; break;
                        case 'cancelled': echo 'danger'; break;
                        case 'refunded': echo 'info'; break;
                        default: echo 'secondary';
                    }
                ?> ml-2"><?php echo ucfirst($exchange['status']); ?></span>
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
                
                <!-- Exchange Information -->
                <div class="row">
                    <div class="col-md-6">
                        <h5>Customer Information</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Name:</strong></td>
                                <td><?php echo htmlspecialchars($exchange['customer_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td>
                                    <a href="mailto:<?php echo htmlspecialchars($exchange['customer_email']); ?>">
                                        <?php echo htmlspecialchars($exchange['customer_email']); ?>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Phone:</strong></td>
                                <td>
                                    <a href="tel:<?php echo htmlspecialchars($exchange['customer_phone']); ?>">
                                        <?php echo htmlspecialchars($exchange['customer_phone']); ?>
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Payment Address:</strong></td>
                                <td><code><?php echo htmlspecialchars($exchange['payment_address']); ?></code></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h5>Transaction Information</h5>
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Reference ID:</strong></td>
                                <td><code><?php echo htmlspecialchars($exchange['reference_id']); ?></code></td>
                            </tr>
                            <tr>
                                <td><strong>Created:</strong></td>
                                <td><?php echo date('M d, Y g:i A', strtotime($exchange['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Updated:</strong></td>
                                <td><?php echo date('M d, Y g:i A', strtotime($exchange['updated_at'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge badge-<?php 
                                        switch($exchange['status']) {
                                            case 'pending': echo 'warning'; break;
                                            case 'confirmed': echo 'success'; break;
                                            case 'cancelled': echo 'danger'; break;
                                            case 'refunded': echo 'info'; break;
                                            default: echo 'secondary';
                                        }
                                    ?>"><?php echo ucfirst($exchange['status']); ?></span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Exchange Details -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h5>Exchange Details</h5>
                        <div class="row">
                            <div class="col-md-5">
                                <div class="card bg-danger text-white">
                                    <div class="card-body">
                                        <h6><i class="fas fa-arrow-up mr-2"></i>Customer Sends</h6>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="h4 mb-0"><?php echo number_format($exchange['send_amount'], 2); ?></div>
                                                <small><?php echo htmlspecialchars($exchange['from_currency_name'] ?? $exchange['from_currency']); ?></small>
                                            </div>
                                            <div class="text-right">
                                                <div class="badge badge-light text-dark">
                                                    <?php echo htmlspecialchars($exchange['from_display_name'] ?? $exchange['from_currency']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-2 text-center">
                                <div class="mt-4">
                                    <i class="fas fa-exchange-alt fa-2x text-muted"></i>
                                    <div class="small text-muted mt-2">
                                        Rate: <?php echo number_format($exchange['exchange_rate'], 4); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-5">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <h6><i class="fas fa-arrow-down mr-2"></i>Customer Receives</h6>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="h4 mb-0"><?php echo number_format($exchange['receive_amount'], 2); ?></div>
                                                <small><?php echo htmlspecialchars($exchange['to_currency_name'] ?? $exchange['to_currency']); ?></small>
                                            </div>
                                            <div class="text-right">
                                                <div class="badge badge-light text-dark">
                                                    <?php echo htmlspecialchars($exchange['to_display_name'] ?? $exchange['to_currency']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Admin Notes -->
                <?php if (!empty($exchange['admin_notes'])): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <h5>Admin Notes</h5>
                        <div class="alert alert-info">
                            <?php echo nl2br(htmlspecialchars($exchange['admin_notes'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Status Update -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-edit mr-1"></i> Update Status
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="pending" <?php echo $exchange['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $exchange['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo $exchange['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="refunded" <?php echo $exchange['status'] === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_notes">Admin Notes</label>
                        <textarea class="form-control" id="admin_notes" name="admin_notes" rows="3" 
                                  placeholder="Add notes about this status change..."><?php echo htmlspecialchars($exchange['admin_notes'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" name="update_status" class="btn btn-primary btn-block">
                        <i class="fas fa-save mr-1"></i> Update Status
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-bolt mr-1"></i> Quick Actions
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="../../receipt.php?ref=<?php echo urlencode($exchange['reference_id']); ?>" 
                       target="_blank" class="btn btn-info btn-block">
                        <i class="fas fa-eye mr-1"></i> View Receipt
                    </a>
                    <button type="button" class="btn btn-success btn-block" onclick="sendReceiptEmail()">
                        <i class="fas fa-paper-plane mr-1"></i> Send Receipt Email
                    </button>
                    <button type="button" class="btn btn-warning btn-block" onclick="printReceipt()">
                        <i class="fas fa-print mr-1"></i> Print Receipt
                    </button>
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $exchange['customer_phone']); ?>?text=Hello%20regarding%20your%20exchange%20<?php echo urlencode($exchange['reference_id']); ?>" 
                       target="_blank" class="btn btn-success btn-block">
                        <i class="fab fa-whatsapp mr-1"></i> Contact Customer
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Exchange History -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-history mr-1"></i> Status History
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-marker bg-primary"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-header">Exchange Created</h6>
                            <p class="timeline-text"><?php echo date('M d, Y g:i A', strtotime($exchange['created_at'])); ?></p>
                        </div>
                    </div>
                    
                    <?php if ($exchange['updated_at'] != $exchange['created_at']): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker bg-warning"></div>
                        <div class="timeline-content">
                            <h6 class="timeline-header">Status Updated</h6>
                            <p class="timeline-text"><?php echo date('M d, Y g:i A', strtotime($exchange['updated_at'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 1.5rem;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 8px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 1rem;
}

.timeline-marker {
    position: absolute;
    left: -8px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid #fff;
}

.timeline-content {
    margin-left: 1rem;
}

.timeline-header {
    margin-bottom: 0.25rem;
    font-size: 0.875rem;
}

.timeline-text {
    font-size: 0.75rem;
    color: #6c757d;
    margin: 0;
}
</style>

<script>
function sendReceiptEmail() {
    if (confirm('Send receipt email to customer?')) {
        $.post('../receipts/send_receipt.php', {
            exchange_id: <?php echo $exchangeId; ?>
        }, function(response) {
            if (response.success) {
                alert('Receipt email sent successfully!');
            } else {
                alert('Failed to send receipt email: ' + response.message);
            }
        }, 'json').fail(function() {
            alert('Error sending receipt email.');
        });
    }
}

function printReceipt() {
    window.open('../../receipt.php?ref=<?php echo urlencode($exchange['reference_id']); ?>&print=1', '_blank');
}
</script>

<?php include '../includes/footer.php'; ?>