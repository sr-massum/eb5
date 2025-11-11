<?php 

/**
 * ExchangeBridge - Admin Panel Exchange Delete
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

// Check if user is logged in, if not redirect to login page
if (!Auth::isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Get exchange ID from URL
$exchangeId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Check if exchange exists
$db = Database::getInstance();
$exchange = $db->getRow("SELECT * FROM exchanges WHERE id = ?", [$exchangeId]);

if (!$exchange) {
    $_SESSION['error_message'] = 'Exchange not found';
    header("Location: index.php");
    exit;
}

// Handle POST request for actual deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    // Delete the exchange
    $result = $db->delete('exchanges', 'id = ?', [$exchangeId]);
    
    if ($result) {
        $_SESSION['success_message'] = 'Exchange deleted successfully';
        header("Location: index.php");
    } else {
        $_SESSION['error_message'] = 'Failed to delete exchange';
        header("Location: view.php?id=$exchangeId");
    }
    exit;
}

// If GET request, show confirmation page
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
    <li class="breadcrumb-item active">Delete Exchange</li>
</ol>

<!-- Page Content -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-exclamation-triangle mr-1"></i> Delete Exchange Confirmation
    </div>
    <div class="card-body">
        <div class="alert alert-danger">
            <h5><i class="fas fa-exclamation-triangle mr-2"></i>Warning!</h5>
            <p class="mb-0">You are about to permanently delete this exchange transaction. This action cannot be undone.</p>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Exchange Details</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="150">Reference ID:</th>
                                <td><strong><?php echo htmlspecialchars($exchange['reference_id']); ?></strong></td>
                            </tr>
                            <tr>
                                <th>Customer Name:</th>
                                <td><?php echo htmlspecialchars($exchange['customer_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Customer Email:</th>
                                <td><?php echo htmlspecialchars($exchange['customer_email']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th width="150">Created Date:</th>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($exchange['created_at'])); ?></td>
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
                            <tr>
                                <th>Amount:</th>
                                <td>
                                    <?php echo number_format($exchange['send_amount'], 2); ?> <?php echo $exchange['from_currency']; ?> 
                                    → <?php echo number_format($exchange['receive_amount'], 2); ?> <?php echo $exchange['to_currency']; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-4">
            <div class="col-md-6">
                <form method="POST" action="delete.php?id=<?php echo $exchangeId; ?>">
                    <input type="hidden" name="confirm_delete" value="1">
                    <button type="submit" class="btn btn-danger btn-lg btn-block">
                        <i class="fas fa-trash mr-2"></i> Yes, Delete Exchange
                    </button>
                </form>
            </div>
            <div class="col-md-6">
                <a href="view.php?id=<?php echo $exchangeId; ?>" class="btn btn-secondary btn-lg btn-block">
                    <i class="fas fa-times mr-2"></i> Cancel
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>