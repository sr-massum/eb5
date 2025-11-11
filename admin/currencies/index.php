<?php 

/**
 * ExchangeBridge - Admin Panel Currency
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

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['currency_id'])) {
        $currencyId = intval($_POST['currency_id']);
        $action = $_POST['action'];
        
        $db = Database::getInstance();
        
        try {
            if ($action === 'activate') {
                $result = $db->update('currencies', ['status' => 'active'], 'id = ?', [$currencyId]);
                if ($result) {
                    $_SESSION['success_message'] = 'Currency activated successfully';
                } else {
                    $_SESSION['error_message'] = 'Failed to activate currency';
                }
            } elseif ($action === 'deactivate') {
                $result = $db->update('currencies', ['status' => 'inactive'], 'id = ?', [$currencyId]);
                if ($result) {
                    $_SESSION['success_message'] = 'Currency deactivated successfully';
                } else {
                    $_SESSION['error_message'] = 'Failed to deactivate currency';
                }
            }
        } catch (Exception $e) {
            error_log("Error updating currency status: " . $e->getMessage());
            $_SESSION['error_message'] = 'An error occurred while updating currency status';
        }
    }
}

// Get all currencies with enhanced error handling
try {
    $db = Database::getInstance();
    $currencies = $db->getRows("SELECT * FROM currencies ORDER BY name ASC");
    
    if (!is_array($currencies)) {
        $currencies = [];
        error_log("Failed to fetch currencies - invalid result type");
    }
} catch (Exception $e) {
    error_log("Error fetching currencies: " . $e->getMessage());
    $currencies = [];
    $_SESSION['error_message'] = 'Error loading currencies. Please try again.';
}

// Calculate statistics
$totalCurrencies = count($currencies);
$activeCurrencies = 0;
$inactiveCurrencies = 0;

foreach ($currencies as $currency) {
    if (isset($currency['status']) && $currency['status'] === 'active') {
        $activeCurrencies++;
    } else {
        $inactiveCurrencies++;
    }
}

// Get counts for different statuses
try {
    $db = Database::getInstance();
    $pendingCount = $db->getValue("SELECT COUNT(*) FROM currencies WHERE status = ?", ['pending']) ?: 0;
    $activeCount = $db->getValue("SELECT COUNT(*) FROM currencies WHERE status = ?", ['active']) ?: 0;
    $inactiveCount = $db->getValue("SELECT COUNT(*) FROM currencies WHERE status = ?", ['inactive']) ?: 0;
} catch (Exception $e) {
    error_log("Error getting currency counts: " . $e->getMessage());
    $pendingCount = 0;
    $activeCount = $activeCurrencies;
    $inactiveCount = $inactiveCurrencies;
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

// Include header
include '../includes/header.php';
?>

<style>
/* Statistics Card Styles */
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
.border-left-danger {
    border-left: 0.25rem solid #e74a3b !important;
}
.currency-logo {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 50%;
}
.currency-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
}
</style>

<!-- Breadcrumbs-->
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="../index.php">Dashboard</a>
    </li>
    <li class="breadcrumb-item active">Currencies</li>
</ol>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Currencies</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalCurrencies; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Currencies</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $activeCount; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Inactive Currencies</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $inactiveCount; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Success Rate</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?php 
                            echo $totalCurrencies > 0 ? number_format(($activeCount / $totalCurrencies) * 100, 1) . '%' : '0%'; 
                            ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-percentage fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Page Content -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-money-bill-wave mr-1"></i> Currencies Management
        <div class="float-right">
            <?php if ($pendingCount > 0): ?>
            <span class="badge badge-warning mr-2">
                <i class="fas fa-clock"></i> <?php echo $pendingCount; ?> Pending
            </span>
            <?php endif; ?>
            <span class="badge badge-success mr-2">
                <i class="fas fa-check"></i> <?php echo $activeCount; ?> Active
            </span>
            <a href="add.php" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Add New Currency
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($successMessage); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($errorMessage); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Logo</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Display Name</th>
                        <th>Payment Address</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($currencies) > 0): ?>
                    <?php foreach ($currencies as $currency): ?>
                    <tr class="<?php echo (isset($currency['status']) && $currency['status'] === 'pending') ? 'table-warning' : ''; ?>">
                        <td>
                            <?php if (!empty($currency['logo'])): ?>
                                <?php 
                                $logoPath = ASSETS_URL . '/uploads/currencies/' . htmlspecialchars($currency['logo']);
                                ?>
                                <img src="<?php echo $logoPath; ?>" 
                                     alt="<?php echo htmlspecialchars($currency['name'] ?? ''); ?>" 
                                     class="currency-logo"
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="currency-icon" style="display: none;">
                                    <i class="<?php echo htmlspecialchars($currency['icon_class'] ?? 'fas fa-money-bill-wave'); ?>"></i>
                                </div>
                            <?php else: ?>
                                <div class="currency-icon">
                                    <i class="<?php echo htmlspecialchars($currency['icon_class'] ?? 'fas fa-money-bill-wave'); ?>"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($currency['code'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($currency['name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($currency['display_name'] ?? ($currency['code'] ?? '')); ?></td>
                        <td>
                            <?php if (!empty($currency['payment_address'])): ?>
                                <span class="text-truncate d-inline-block" style="max-width: 150px;" title="<?php echo htmlspecialchars($currency['payment_address']); ?>">
                                    <?php echo htmlspecialchars($currency['payment_address']); ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">Not set</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($currency['status']) && $currency['status'] === 'pending'): ?>
                            <span class="badge badge-warning">
                                <i class="fas fa-clock"></i> Pending
                            </span>
                            <?php elseif (isset($currency['status']) && $currency['status'] === 'active'): ?>
                            <span class="badge badge-success">
                                <i class="fas fa-check"></i> Active
                            </span>
                            <?php else: ?>
                            <span class="badge badge-danger">
                                <i class="fas fa-times"></i> Inactive
                            </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (isset($currency['status']) && $currency['status'] === 'inactive'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="currency_id" value="<?php echo $currency['id']; ?>">
                                <input type="hidden" name="action" value="activate">
                                <button type="submit" class="btn btn-success btn-sm" title="Activate">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            <?php elseif (isset($currency['status']) && $currency['status'] === 'active'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="currency_id" value="<?php echo $currency['id']; ?>">
                                <input type="hidden" name="action" value="deactivate">
                                <button type="submit" class="btn btn-warning btn-sm" title="Deactivate">
                                    <i class="fas fa-pause"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <a href="edit.php?id=<?php echo $currency['id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="delete.php?id=<?php echo $currency['id']; ?>" class="btn btn-danger btn-sm delete-confirm">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No currencies found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Custom modal for delete confirmation
    $('.delete-confirm').click(function(e) {
        e.preventDefault();
        
        const url = $(this).attr('href');
        const currencyName = $(this).closest('tr').find('td:nth-child(3)').text().trim();
        
        // Create custom confirmation modal
        const confirmModal = `
            <div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Delete</h5>
                            <button type="button" class="close" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete the currency <strong>${currencyName}</strong>?</p>
                            <p class="text-danger"><small>This action cannot be undone and may affect related exchange rates.</small></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                            <a href="${url}" class="btn btn-danger">Delete</a>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Remove existing modal if any
        $('#deleteConfirmModal').remove();
        
        // Add modal to body and show
        $('body').append(confirmModal);
        $('#deleteConfirmModal').modal('show');
    });
});
</script>

<?php include '../includes/footer.php'; ?>