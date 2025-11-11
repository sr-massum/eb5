<?php 

/**
 * ExchangeBridge - Admin Panel Reserve Money 
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
    if (isset($_POST['action']) && isset($_POST['reserve_id'])) {
        $reserveId = intval($_POST['reserve_id']);
        $action = $_POST['action'];
        
        $db = Database::getInstance();
        
        try {
            if ($action === 'toggle_auto_update') {
                // Toggle auto update
                $currentReserve = $db->getRow('SELECT auto_update FROM reserves WHERE id = ?', [$reserveId]);
                if ($currentReserve) {
                    $newValue = $currentReserve['auto_update'] ? 0 : 1;
                    $result = $db->update('reserves', ['auto_update' => $newValue], 'id = ?', [$reserveId]);
                    if ($result) {
                        $_SESSION['success_message'] = 'Auto-update setting changed successfully';
                    } else {
                        $_SESSION['error_message'] = 'Failed to change auto-update setting';
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Error updating reserve: " . $e->getMessage());
            $_SESSION['error_message'] = 'An error occurred while updating reserve';
        }
    }
}

// Get all reserves with currency info with enhanced error handling
try {
    $db = Database::getInstance();
    $reserves = $db->getRows("
        SELECT r.*, c.name, c.display_name, c.logo, c.background_class, c.icon_class 
        FROM reserves r 
        LEFT JOIN currencies c ON r.currency_code = c.code 
        WHERE c.status = ?
        ORDER BY 
        CASE 
            WHEN r.amount <= r.min_amount THEN 1 
            WHEN r.amount >= r.max_amount * 0.8 THEN 2 
            ELSE 3 
        END, c.name ASC
    ", ['active']);
    
    if (!is_array($reserves)) {
        $reserves = [];
        error_log("Failed to fetch reserves - invalid result type");
    }
} catch (Exception $e) {
    error_log("Error fetching reserves: " . $e->getMessage());
    $reserves = [];
    $_SESSION['error_message'] = 'Error loading reserves. Please try again.';
}

// Get counts for different statuses
try {
    $totalReserves = count($reserves);
    $highReserves = 0;
    $lowReserves = 0;
    $autoUpdateEnabled = 0;
    
    foreach ($reserves as $reserve) {
        if (isset($reserve['min_amount']) && $reserve['min_amount'] > 0 && 
            isset($reserve['amount']) && $reserve['amount'] <= $reserve['min_amount']) {
            $lowReserves++;
        }
        
        if (isset($reserve['max_amount']) && $reserve['max_amount'] > 0 && 
            isset($reserve['amount']) && $reserve['amount'] >= $reserve['max_amount'] * 0.8) {
            $highReserves++;
        }
        
        if (isset($reserve['auto_update']) && $reserve['auto_update']) {
            $autoUpdateEnabled++;
        }
    }
} catch (Exception $e) {
    error_log("Error calculating reserve stats: " . $e->getMessage());
    $totalReserves = count($reserves);
    $highReserves = 0;
    $lowReserves = 0;
    $autoUpdateEnabled = 0;
}

// Get all currencies for dropdown (only those not already in reserves) with enhanced error handling
try {
    $existingCurrencies = array_column($reserves, 'currency_code');
    $allCurrencies = getAllCurrencies(true);
    
    if (!is_array($allCurrencies)) {
        $allCurrencies = [];
    }
    
    $availableCurrencies = array_filter($allCurrencies, function($currency) use ($existingCurrencies) {
        return !in_array($currency['code'], $existingCurrencies);
    });
} catch (Exception $e) {
    error_log("Error fetching available currencies: " . $e->getMessage());
    $availableCurrencies = [];
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

<!-- Breadcrumbs-->
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="../index.php">Dashboard</a>
    </li>
    <li class="breadcrumb-item active">Currency Reserves</li>
</ol>

<!-- Page Content -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-wallet mr-1"></i> Currency Reserves Management
        <div class="float-right">
            <?php if ($lowReserves > 0): ?>
            <span class="badge badge-danger mr-2">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $lowReserves; ?> Low
            </span>
            <?php endif; ?>
            <span class="badge badge-success mr-2">
                <i class="fas fa-arrow-up"></i> <?php echo $highReserves; ?> High
            </span>
            <span class="badge badge-info mr-2">
                <i class="fas fa-sync-alt"></i> <?php echo $autoUpdateEnabled; ?> Auto-Update
            </span>
            <?php if (!empty($availableCurrencies)): ?>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#addReserveModal">
                <i class="fas fa-plus"></i> Add Reserve
            </button>
            <?php endif; ?>
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

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Currencies</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $totalReserves; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-coins fa-2x text-gray-300"></i>
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
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">High Reserves</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $highReserves; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-arrow-up fa-2x text-gray-300"></i>
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
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Low Reserves</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $lowReserves; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Auto Updates</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $autoUpdateEnabled; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-sync-alt fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Currency</th>
                        <th>Current Amount</th>
                        <th>Min Amount</th>
                        <th>Max Amount</th>
                        <th>Status</th>
                        <th>Auto Update</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($reserves) > 0): ?>
                    <?php foreach ($reserves as $reserve): ?>
                    <?php
                    $amount = $reserve['amount'] ?? 0;
                    $minAmount = $reserve['min_amount'] ?? 0;
                    $maxAmount = $reserve['max_amount'] ?? 0;
                    $percentage = $maxAmount > 0 ? ($amount / $maxAmount) * 100 : 0;
                    
                    $rowClass = '';
                    if ($minAmount > 0 && $amount <= $minAmount) {
                        $rowClass = 'table-danger';
                        $progressClass = 'bg-danger';
                        $statusBadge = '<span class="badge badge-danger">Low</span>';
                    } elseif ($percentage >= 80) {
                        $rowClass = 'table-success';
                        $progressClass = 'bg-success';
                        $statusBadge = '<span class="badge badge-success">High</span>';
                    } else {
                        $progressClass = 'bg-warning';
                        $statusBadge = '<span class="badge badge-warning">Medium</span>';
                    }
                    ?>
                    <tr class="<?php echo $rowClass; ?>">
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="mr-3">
                                    <?php if (!empty($reserve['logo'])): ?>
                                        <?php 
                                        $logoPath = ASSETS_URL . '/uploads/currencies/' . htmlspecialchars($reserve['logo']);
                                        ?>
                                        <img src="<?php echo $logoPath; ?>" 
                                             alt="<?php echo htmlspecialchars($reserve['name'] ?? ''); ?>" 
                                             class="rounded-circle" width="40" height="40"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center <?php echo htmlspecialchars($reserve['background_class'] ?? 'bg-primary'); ?>" 
                                             style="width: 40px; height: 40px; display: none;">
                                            <i class="<?php echo htmlspecialchars($reserve['icon_class'] ?? 'fas fa-money-bill-wave'); ?> text-white"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="rounded-circle d-flex align-items-center justify-content-center <?php echo htmlspecialchars($reserve['background_class'] ?? 'bg-primary'); ?>" 
                                             style="width: 40px; height: 40px;">
                                            <i class="<?php echo htmlspecialchars($reserve['icon_class'] ?? 'fas fa-money-bill-wave'); ?> text-white"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="font-weight-bold"><?php echo htmlspecialchars($reserve['name'] ?? ''); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($reserve['currency_code'] ?? ''); ?></small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <span class="editable-amount font-weight-bold" 
                                      data-id="<?php echo $reserve['id']; ?>" 
                                      data-amount="<?php echo $amount; ?>">
                                    <?php echo number_format($amount, 2); ?>
                                </span>
                                <button class="btn btn-sm btn-outline-primary ml-2 edit-amount-btn" 
                                        data-id="<?php echo $reserve['id']; ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                            </div>
                        </td>
                        <td><?php echo number_format($minAmount, 2); ?></td>
                        <td><?php echo number_format($maxAmount, 2); ?></td>
                        <td>
                            <?php echo $statusBadge; ?>
                            <div class="progress mt-1" style="height: 5px;">
                                <div class="progress-bar <?php echo $progressClass; ?>" 
                                     style="width: <?php echo min(100, max(0, $percentage)); ?>%"></div>
                            </div>
                        </td>
                        <td>
                            <?php if (isset($reserve['auto_update']) && $reserve['auto_update']): ?>
                                <span class="badge badge-success"><i class="fas fa-check"></i> Enabled</span>
                            <?php else: ?>
                                <span class="badge badge-secondary"><i class="fas fa-times"></i> Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php 
                            if (isset($reserve['updated_at'])) {
                                echo date('M j, Y H:i', strtotime($reserve['updated_at']));
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="reserve_id" value="<?php echo $reserve['id']; ?>">
                                <input type="hidden" name="action" value="toggle_auto_update">
                                <button type="submit" class="btn btn-info btn-sm" title="Toggle Auto-Update">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </form>
                            
                            <button class="btn btn-sm btn-outline-primary edit-reserve-btn" 
                                    data-id="<?php echo $reserve['id']; ?>"
                                    data-currency="<?php echo htmlspecialchars($reserve['currency_code']); ?>"
                                    data-amount="<?php echo $amount; ?>"
                                    data-min="<?php echo $minAmount; ?>"
                                    data-max="<?php echo $maxAmount; ?>"
                                    data-auto="<?php echo $reserve['auto_update'] ?? 0; ?>">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="delete.php?id=<?php echo $reserve['id']; ?>" class="btn btn-sm btn-outline-danger delete-confirm">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">No reserves found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Reserve Modal -->
<?php if (!empty($availableCurrencies)): ?>
<div class="modal fade" id="addReserveModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Reserve</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="add.php" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="currency_code">Currency</label>
                        <select name="currency_code" id="currency_code" class="form-control" required>
                            <option value="">Select Currency</option>
                            <?php foreach ($availableCurrencies as $currency): ?>
                            <option value="<?php echo htmlspecialchars($currency['code']); ?>">
                                <?php echo htmlspecialchars($currency['name']) . ' (' . htmlspecialchars($currency['code']) . ')'; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="amount">Current Amount</label>
                        <input type="number" name="amount" id="amount" class="form-control" 
                               step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="min_amount">Minimum Amount</label>
                        <input type="number" name="min_amount" id="min_amount" class="form-control" 
                               step="0.01" min="0" required>
                        <small class="form-text text-muted">Alert will be shown when reserve goes below this amount</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="max_amount">Maximum Amount</label>
                        <input type="number" name="max_amount" id="max_amount" class="form-control" 
                               step="0.01" min="0" required>
                        <small class="form-text text-muted">Maximum reserve capacity</small>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="auto_update" id="auto_update" class="custom-control-input" checked>
                            <label class="custom-control-label" for="auto_update">
                                Enable auto-update from exchange transactions
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Reserve</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Edit Reserve Modal -->
<div class="modal fade" id="editReserveModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Reserve</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="update.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="reserve_id" id="edit_reserve_id">
                    
                    <div class="form-group">
                        <label for="edit_currency_code">Currency</label>
                        <select name="currency_code" id="edit_currency_code" class="form-control" required disabled>
                            <option value="">Select Currency</option>
                            <?php foreach ($allCurrencies as $currency): ?>
                            <option value="<?php echo htmlspecialchars($currency['code']); ?>">
                                <?php echo htmlspecialchars($currency['name']) . ' (' . htmlspecialchars($currency['code']) . ')'; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="currency_code" id="edit_currency_code_hidden">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_amount">Current Amount</label>
                        <input type="number" name="amount" id="edit_amount" class="form-control" 
                               step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_min_amount">Minimum Amount</label>
                        <input type="number" name="min_amount" id="edit_min_amount" class="form-control" 
                               step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_max_amount">Maximum Amount</label>
                        <input type="number" name="max_amount" id="edit_max_amount" class="form-control" 
                               step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="auto_update" id="edit_auto_update" class="custom-control-input">
                            <label class="custom-control-label" for="edit_auto_update">
                                Enable auto-update from exchange transactions
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Reserve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Quick Amount Update Modal -->
<div class="modal fade" id="quickUpdateModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Amount</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form action="update_amount.php" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" id="quick_update_id">
                    
                    <div class="form-group">
                        <label for="quick_amount">New Amount</label>
                        <input type="number" name="amount" id="quick_amount" class="form-control" 
                               step="0.01" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Edit reserve button
    $('.edit-reserve-btn').click(function() {
        const data = $(this).data();
        $('#edit_reserve_id').val(data.id);
        $('#edit_currency_code').val(data.currency);
        $('#edit_currency_code_hidden').val(data.currency);
        $('#edit_amount').val(data.amount);
        $('#edit_min_amount').val(data.min);
        $('#edit_max_amount').val(data.max);
        $('#edit_auto_update').prop('checked', data.auto == 1);
        $('#editReserveModal').modal('show');
    });
    
    // Quick amount edit
    $('.edit-amount-btn').click(function() {
        const id = $(this).data('id');
        const currentAmount = $(this).closest('td').find('.editable-amount').data('amount');
        $('#quick_update_id').val(id);
        $('#quick_amount').val(currentAmount);
        $('#quickUpdateModal').modal('show');
    });
    
    // Custom delete confirmation
    $('.delete-confirm').click(function(e) {
        e.preventDefault();
        
        const url = $(this).attr('href');
        const currencyName = $(this).closest('tr').find('.font-weight-bold').text().trim();
        
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
                            <p>Are you sure you want to delete the reserve for <strong>${currencyName}</strong>?</p>
                            <p class="text-danger"><small>This action cannot be undone.</small></p>
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

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}
.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}
.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}
.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}
.editable-amount {
    cursor: pointer;
}
.progress {
    background-color: #f8f9fc;
}
</style>

<?php include '../includes/footer.php'; ?>