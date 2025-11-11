<?php 

/**
 * ExchangeBridge - Admin Panel Exchange View
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

// Set timezone to match frontend (same as index.php)
date_default_timezone_set('Asia/Dhaka');

// Check if user is logged in, if not redirect to login page
if (!Auth::isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Get exchange ID from URL
$exchangeId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get exchange details
$db = Database::getInstance();
$exchange = $db->getRow(
    "SELECT e.*, 
     fc.name as from_currency_name, fc.display_name as from_display_name, fc.logo as from_logo, fc.background_class as from_bg_class, fc.icon_class as from_icon_class,
     tc.name as to_currency_name, tc.display_name as to_display_name, tc.logo as to_logo, tc.background_class as to_bg_class, tc.icon_class as to_icon_class
     FROM exchanges e
     JOIN currencies fc ON e.from_currency = fc.code
     JOIN currencies tc ON e.to_currency = tc.code
     WHERE e.id = ?",
    [$exchangeId]
);

if (!$exchange) {
    $_SESSION['error_message'] = 'Exchange not found';
    header("Location: index.php");
    exit;
}

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

// Function to format date with proper timezone (same as index.php)
function formatDateTime($datetime) {
    $date = new DateTime($datetime, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone('Asia/Dhaka'));
    return $date->format('d-m-Y H:i:s');
}

// Include header
include '../includes/header.php';
?>

<!-- Breadcrumbs-->
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="../index.php">Dashboard</a>
    </li>
    <li class="breadcrumb-item">
        <a href="index.php">Exchanges</a>
    </li>
    <li class="breadcrumb-item active">View Exchange</li>
</ol>

<!-- Page Content -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <i class="fas fa-exchange-alt mr-1"></i> Exchange Details
            <span class="ml-2 badge badge-<?php 
                echo $exchange['status'] === 'confirmed' ? 'success' : 
                    ($exchange['status'] === 'pending' ? 'warning' : 
                        ($exchange['status'] === 'cancelled' ? 'danger' : 'secondary')); 
            ?>">
                <?php echo ucfirst($exchange['status']); ?>
            </span>
        </div>
        <div>
            <a href="index.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> Back to List
            </a>
        </div>
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
        
        <div class="row">
            <!-- Exchange Information -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-info-circle mr-1"></i> Exchange Information
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="150">Reference ID:</th>
                                <td><strong><?php echo htmlspecialchars($exchange['reference_id']); ?></strong></td>
                            </tr>
                            <tr>
                                <th>Created Date:</th>
                                <td><?php echo formatDateTime($exchange['created_at']); ?></td>
                            </tr>
                            <tr>
                                <th>Last Updated:</th>
                                <td><?php echo formatDateTime($exchange['updated_at']); ?></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <span class="badge badge-<?php 
                                        echo $exchange['status'] === 'confirmed' ? 'success' : 
                                            ($exchange['status'] === 'pending' ? 'warning' : 
                                                ($exchange['status'] === 'cancelled' ? 'danger' : 'secondary')); 
                                    ?>">
                                        <?php echo ucfirst($exchange['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Customer Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-user mr-1"></i> Customer Information
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <th width="150">Name:</th>
                                <td><?php echo htmlspecialchars($exchange['customer_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?php echo htmlspecialchars($exchange['customer_email']); ?></td>
                            </tr>
                            <tr>
                                <th>Phone:</th>
                                <td><?php echo htmlspecialchars($exchange['customer_phone']); ?></td>
                            </tr>
                            <tr>
                                <th>Payment Address:</th>
                                <td><?php echo htmlspecialchars($exchange['payment_address']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Exchange Details -->
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-money-bill-wave mr-1"></i> Exchange Details
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-5 text-center">
                                <div class="payment-icon <?php echo $exchange['from_bg_class'] ?: 'bg-primary text-white'; ?> mx-auto mb-2" style="width: 48px; height: 48px;">
                                    <?php if ($exchange['from_logo']): ?>
                                        <img src="<?php echo ASSETS_URL; ?>/uploads/currencies/<?php echo $exchange['from_logo']; ?>" alt="<?php echo $exchange['from_currency_name']; ?>" class="img-fluid">
                                    <?php else: ?>
                                        <i class="<?php echo $exchange['from_icon_class'] ?: 'fas fa-money-bill-wave'; ?>"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="font-weight-bold"><?php echo htmlspecialchars($exchange['from_currency_name']); ?></div>
                                <div class="h4 mt-2"><?php echo number_format($exchange['send_amount'], 2); ?> <?php echo $exchange['from_display_name'] ?: $exchange['from_currency']; ?></div>
                            </div>
                            
                            <div class="col-md-2 text-center d-flex align-items-center justify-content-center">
                                <i class="fas fa-arrow-right fa-2x text-muted"></i>
                            </div>
                            
                            <div class="col-md-5 text-center">
                                <div class="payment-icon <?php echo $exchange['to_bg_class'] ?: 'bg-primary text-white'; ?> mx-auto mb-2" style="width: 48px; height: 48px;">
                                    <?php if ($exchange['to_logo']): ?>
                                        <img src="<?php echo ASSETS_URL; ?>/uploads/currencies/<?php echo $exchange['to_logo']; ?>" alt="<?php echo $exchange['to_currency_name']; ?>" class="img-fluid">
                                    <?php else: ?>
                                        <i class="<?php echo $exchange['to_icon_class'] ?: 'fas fa-money-bill-wave'; ?>"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="font-weight-bold"><?php echo htmlspecialchars($exchange['to_currency_name']); ?></div>
                                <div class="h4 mt-2"><?php echo number_format($exchange['receive_amount'], 2); ?> <?php echo $exchange['to_display_name'] ?: $exchange['to_currency']; ?></div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <div class="row">
                                <div class="col-md-4 text-right">Exchange Rate:</div>
                                <div class="col-md-8 font-weight-bold">
                                    1 <?php echo $exchange['from_display_name'] ?: $exchange['from_currency']; ?> = 
                                    <?php echo number_format($exchange['exchange_rate'], 4); ?> 
                                    <?php echo $exchange['to_display_name'] ?: $exchange['to_currency']; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="card mb-4">
                    <div class="card-header">
                        <i class="fas fa-cogs mr-1"></i> Actions
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="btn-group d-flex mb-3">
                                    <a href="update_status.php?id=<?php echo $exchange['id']; ?>&status=confirmed" class="btn btn-success <?php echo $exchange['status'] === 'confirmed' ? 'disabled' : ''; ?>">
                                        <i class="fas fa-check mr-1"></i> Confirm
                                    </a>
                                    <a href="update_status.php?id=<?php echo $exchange['id']; ?>&status=cancelled" class="btn btn-danger <?php echo $exchange['status'] === 'cancelled' ? 'disabled' : ''; ?>">
                                        <i class="fas fa-times mr-1"></i> Cancel
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="btn-group d-flex mb-3">
                                    <a href="update_status.php?id=<?php echo $exchange['id']; ?>&status=pending" class="btn btn-warning <?php echo $exchange['status'] === 'pending' ? 'disabled' : ''; ?>">
                                        <i class="fas fa-spinner mr-1"></i> Pending
                                    </a>
                                    <a href="update_status.php?id=<?php echo $exchange['id']; ?>&status=refunded" class="btn btn-secondary <?php echo $exchange['status'] === 'refunded' ? 'disabled' : ''; ?>">
                                        <i class="fas fa-undo mr-1"></i> Refund
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <a href="javascript:void(0)" class="btn btn-primary btn-block" onclick="window.open('../../receipt.php?ref=<?php echo urlencode($exchange['reference_id']); ?>', '_blank')">
                                    <i class="fas fa-receipt mr-1"></i> View Receipt
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <?php 
                                // Use customer's phone number instead of admin number
                                $customerPhone = preg_replace('/[^0-9]/', '', $exchange['customer_phone']);
                                // Remove leading zeros and ensure proper format
                                if (substr($customerPhone, 0, 2) === '88') {
                                    $customerPhone = $customerPhone; // Already has country code
                                } elseif (substr($customerPhone, 0, 1) === '0') {
                                    $customerPhone = '88' . $customerPhone; // Add BD country code
                                } else {
                                    $customerPhone = '88' . $customerPhone; // Add BD country code
                                }
                                
                                $whatsappMessage = "Hello " . $exchange['customer_name'] . "! 👋\n\n";
                                $whatsappMessage .= "We're contacting you regarding your exchange transaction:\n";
                                $whatsappMessage .= "📋 Reference ID: " . $exchange['reference_id'] . "\n";
                                $whatsappMessage .= "💱 Exchange: " . number_format($exchange['send_amount'], 2) . " " . $exchange['from_currency'] . " → " . number_format($exchange['receive_amount'], 2) . " " . $exchange['to_currency'] . "\n";
                                $whatsappMessage .= "📊 Status: " . ucfirst($exchange['status']) . "\n\n";
                                $whatsappMessage .= "How can we help you today?";
                                ?>
                                <a href="https://wa.me/<?php echo $customerPhone; ?>?text=<?php echo urlencode($whatsappMessage); ?>" class="btn btn-success btn-block" target="_blank">
                                    <i class="fab fa-whatsapp mr-1"></i> Contact Customer
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="delete.php?id=<?php echo $exchange['id']; ?>" class="btn btn-danger btn-block" onclick="return confirm('Are you sure you want to delete this exchange? This action cannot be undone.')">
                                    <i class="fas fa-trash mr-1"></i> Delete Exchange
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>