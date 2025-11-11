<?php 

/**
 * ExchangeBridge - Admin Panel Dashboard
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
require_once '../config/config.php';
require_once '../config/verification.php';
require_once '../config/license.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/app.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

// Check if user is logged in, if not redirect to login page
if (!Auth::isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Get user info
$user = Auth::getUser();

// Get dashboard statistics
$db = Database::getInstance();
$totalExchanges = $db->getValue("SELECT COUNT(*) FROM exchanges");
$pendingExchanges = $db->getValue("SELECT COUNT(*) FROM exchanges WHERE status = 'pending'");
$confirmedExchanges = $db->getValue("SELECT COUNT(*) FROM exchanges WHERE status = 'confirmed'");
$cancelledExchanges = $db->getValue("SELECT COUNT(*) FROM exchanges WHERE status = 'cancelled'");

// Get total amount exchanged
$totalSent = $db->getValue("SELECT SUM(send_amount) FROM exchanges WHERE status = 'confirmed'") ?: 0;
$totalReceived = $db->getValue("SELECT SUM(receive_amount) FROM exchanges WHERE status = 'confirmed'") ?: 0;

// Get recent exchanges
$recentExchanges = getRecentExchanges(5);

// Get currency reserves
$reserves = getCurrencyReserves();

// Include header
include 'includes/header.php';
?>

<!-- Main Content -->
<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Breadcrumbs-->
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="index.php">Dashboard</a>
            </li>
            <li class="breadcrumb-item active">Overview</li>
        </ol>
        
        <!-- Icon Cards-->
        <div class="row">
            <div class="col-xl-3 col-sm-6 mb-3">
                <div class="card text-white bg-primary o-hidden h-100">
                    <div class="card-body">
                        <div class="card-body-icon">
                            <i class="fas fa-fw fa-exchange-alt"></i>
                        </div>
                        <div class="mr-5"><?php echo $totalExchanges; ?> Total Exchanges</div>
                    </div>
                    <a class="card-footer text-white clearfix small z-1" href="exchanges/">
                        <span class="float-left">View Details</span>
                        <span class="float-right">
                            <i class="fas fa-angle-right"></i>
                        </span>
                    </a>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 mb-3">
                <div class="card text-white bg-warning o-hidden h-100">
                    <div class="card-body">
                        <div class="card-body-icon">
                            <i class="fas fa-fw fa-spinner"></i>
                        </div>
                        <div class="mr-5"><?php echo $pendingExchanges; ?> Pending Exchanges</div>
                    </div>
                    <a class="card-footer text-white clearfix small z-1" href="exchanges/index.php?status=pending">
                        <span class="float-left">View Details</span>
                        <span class="float-right">
                            <i class="fas fa-angle-right"></i>
                        </span>
                    </a>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 mb-3">
                <div class="card text-white bg-success o-hidden h-100">
                    <div class="card-body">
                        <div class="card-body-icon">
                            <i class="fas fa-fw fa-check-circle"></i>
                        </div>
                        <div class="mr-5"><?php echo $confirmedExchanges; ?> Confirmed Exchanges</div>
                    </div>
                    <a class="card-footer text-white clearfix small z-1" href="exchanges/index.php?status=confirmed">
                        <span class="float-left">View Details</span>
                        <span class="float-right">
                            <i class="fas fa-angle-right"></i>
                        </span>
                    </a>
                </div>
            </div>
            <div class="col-xl-3 col-sm-6 mb-3">
                <div class="card text-white bg-danger o-hidden h-100">
                    <div class="card-body">
                        <div class="card-body-icon">
                            <i class="fas fa-fw fa-times-circle"></i>
                        </div>
                        <div class="mr-5"><?php echo $cancelledExchanges; ?> Cancelled Exchanges</div>
                    </div>
                    <a class="card-footer text-white clearfix small z-1" href="exchanges/index.php?status=cancelled">
                        <span class="float-left">View Details</span>
                        <span class="float-right">
                            <i class="fas fa-angle-right"></i>
                        </span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Recent Exchanges and Currency Reserves -->
        <div class="row">
            <!-- Recent Exchanges -->
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="fas fa-history"></i> Recent Exchanges
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>Ref ID</th>
                                        <th>Time</th>
                                        <th>Exchange</th>
                                        <th>Customer</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentExchanges as $exchange): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($exchange['reference_id']); ?></td>
                                        <td><?php echo timeAgo($exchange['created_at']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($exchange['from_currency_name']); ?> → 
                                            <?php echo htmlspecialchars($exchange['to_currency_name']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($exchange['customer_name']); ?></td>
                                        <td>
                                            <span class="badge <?php 
                                                echo $exchange['status'] === 'confirmed' ? 'badge-success' : 
                                                    ($exchange['status'] === 'pending' ? 'badge-warning' : 
                                                        ($exchange['status'] === 'cancelled' ? 'badge-danger' : 'badge-secondary')); 
                                            ?>">
                                                <?php echo ucfirst($exchange['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="exchanges/view.php?id=<?php echo $exchange['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    
                                    <?php if (empty($recentExchanges)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No recent exchanges found</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer small text-muted">
                        <a href="exchanges/" class="btn btn-primary btn-sm">
                            <i class="fas fa-list"></i> View All Exchanges
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Currency Reserves -->
            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="fas fa-wallet"></i> Currency Reserves
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php foreach ($reserves as $reserve): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="d-flex align-items-center">
                                        <div class="reserve-icon mr-2">
                                            <?php if ($reserve['logo']): ?>
                                                <img src="<?php echo ASSETS_URL; ?>/uploads/currencies/<?php echo htmlspecialchars($reserve['logo']); ?>" alt="<?php echo htmlspecialchars($reserve['name']); ?>" class="img-fluid" width="24">
                                            <?php else: ?>
                                                <i class="<?php echo $reserve['icon_class'] ?: 'fas fa-money-bill-wave'; ?>"></i>
                                            <?php endif; ?>
                                        </div>
                                        <span><?php echo htmlspecialchars($reserve['name']); ?></span>
                                    </div>
                                </div>
                                <span class="badge badge-primary badge-pill">
                                    <?php echo number_format($reserve['amount'], 2); ?> 
                                    <?php echo htmlspecialchars($reserve['display_name'] ?: $reserve['currency_code']); ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($reserves)): ?>
                            <div class="list-group-item text-center">
                                No reserves found
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer small text-muted">
                        <a href="reserves/" class="btn btn-primary btn-sm">
                            <i class="fas fa-wallet"></i> Manage Reserves
                        </a>
                    </div>
                </div>
                
                <!-- System Status -->
                <div class="card mb-3">
                    <div class="card-header">
                        <i class="fas fa-server"></i> System Status
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Operator Status</span>
                                <span class="badge badge-<?php echo getSetting('operator_status', 'online') === 'online' ? 'success' : 'warning'; ?> badge-pill">
                                    <?php echo ucfirst(getSetting('operator_status', 'online')); ?>
                                </span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Working Hours</span>
                                <span class="badge badge-info badge-pill">
                                    <?php echo getSetting('working_hours', '9 am-11.50pm +6'); ?>
                                </span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Notification Sound</span>
                                <span class="badge badge-<?php echo getSetting('enable_notification_sound', 'yes') === 'yes' ? 'success' : 'danger'; ?> badge-pill">
                                    <?php echo getSetting('enable_notification_sound', 'yes') === 'yes' ? 'Enabled' : 'Disabled'; ?>
                                </span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <span>Popup Notice</span>
                                <span class="badge badge-<?php echo getSetting('enable_popup_notice', 'yes') === 'yes' ? 'success' : 'danger'; ?> badge-pill">
                                    <?php echo getSetting('enable_popup_notice', 'yes') === 'yes' ? 'Enabled' : 'Disabled'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer small text-muted">
                        <a href="settings/" class="btn btn-primary btn-sm">
                            <i class="fas fa-cog"></i> Manage Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>