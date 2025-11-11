<?php 

/**
 * ExchangeBridge - Admin Panel Exchange Rates
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

// Get all exchange rates with currency names
$db = Database::getInstance();

// Check if new columns exist
$hasNewColumns = false;
try {
    $checkColumns = $db->query("SHOW COLUMNS FROM exchange_rates LIKE 'display_on_homepage'");
    if ($checkColumns && $checkColumns->rowCount() > 0) {
        $hasNewColumns = true;
    }
} catch (Exception $e) {
    // Ignore if columns don't exist yet
}

// Build query based on available columns
if ($hasNewColumns) {
    $rates = $db->getRows(
        "SELECT er.*, 
         fc.name as from_currency_name, fc.display_name as from_display_name,
         tc.name as to_currency_name, tc.display_name as to_display_name
         FROM exchange_rates er
         JOIN currencies fc ON er.from_currency = fc.code
         JOIN currencies tc ON er.to_currency = tc.code
         ORDER BY er.display_on_homepage DESC, er.from_currency, er.to_currency"
    );
} else {
    $rates = $db->getRows(
        "SELECT er.*, 
         fc.name as from_currency_name, fc.display_name as from_display_name,
         tc.name as to_currency_name, tc.display_name as to_display_name
         FROM exchange_rates er
         JOIN currencies fc ON er.from_currency = fc.code
         JOIN currencies tc ON er.to_currency = tc.code
         ORDER BY er.from_currency, er.to_currency"
    );
}

// Get all active currencies for the add form
$currencies = getAllCurrencies(true);

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
    <li class="breadcrumb-item active">Exchange Rates</li>
</ol>

<!-- Page Content -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-chart-line mr-1"></i> Exchange Rates Management
        <a href="add.php" class="btn btn-primary btn-sm float-right">
            <i class="fas fa-plus"></i> Add New Rate
        </a>
    </div>
    <div class="card-body">
        <?php if (!$hasNewColumns): ?>
        <div class="alert alert-warning" role="alert">
            <i class="fas fa-info-circle"></i> <strong>Notice:</strong> To use the new homepage display features, please run this SQL command:
            <br><br>
            <code>
                ALTER TABLE exchange_rates ADD COLUMN display_on_homepage TINYINT(1) DEFAULT 0;<br>
                ALTER TABLE exchange_rates ADD COLUMN we_buy DECIMAL(15,8) DEFAULT 0;<br>
                ALTER TABLE exchange_rates ADD COLUMN we_sell DECIMAL(15,8) DEFAULT 0;
            </code>
        </div>
        <?php endif; ?>
        
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
        
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>From Currency</th>
                        <th>To Currency</th>
                        <th>Rate</th>
                        <?php if ($hasNewColumns): ?>
                        <th>Homepage Display</th>
                        <th>We Buy</th>
                        <th>We Sell</th>
                        <?php endif; ?>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rates) > 0): ?>
                    <?php foreach ($rates as $rate): ?>
                    <tr <?php echo ($hasNewColumns && isset($rate['display_on_homepage']) && $rate['display_on_homepage']) ? 'class="table-info"' : ''; ?>>
                        <td>
                            <?php echo htmlspecialchars($rate['from_currency_name']); ?>
                            (<?php echo htmlspecialchars($rate['from_display_name'] ?: $rate['from_currency']); ?>)
                        </td>
                        <td>
                            <?php echo htmlspecialchars($rate['to_currency_name']); ?>
                            (<?php echo htmlspecialchars($rate['to_display_name'] ?: $rate['to_currency']); ?>)
                        </td>
                        <td>
                            <?php echo number_format($rate['rate'], 8); ?>
                        </td>
                        <?php if ($hasNewColumns): ?>
                        <td>
                            <?php if (isset($rate['display_on_homepage']) && $rate['display_on_homepage']): ?>
                                <span class="badge badge-success">
                                    <i class="fas fa-eye"></i> Visible
                                </span>
                            <?php else: ?>
                                <span class="badge badge-secondary">
                                    <i class="fas fa-eye-slash"></i> Hidden
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo (isset($rate['we_buy']) && $rate['we_buy'] > 0) ? number_format($rate['we_buy'], 2) : '-'; ?>
                        </td>
                        <td>
                            <?php echo (isset($rate['we_sell']) && $rate['we_sell'] > 0) ? number_format($rate['we_sell'], 2) : '-'; ?>
                        </td>
                        <?php endif; ?>
                        <td>
                            <span class="badge badge-<?php echo $rate['status'] === 'active' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($rate['status']); ?>
                            </span>
                        </td>
                        <td>
                            <button type="button" class="btn btn-primary btn-sm edit-rate" 
                                data-id="<?php echo $rate['id']; ?>"
                                data-from="<?php echo $rate['from_currency']; ?>"
                                data-to="<?php echo $rate['to_currency']; ?>"
                                data-rate="<?php echo $rate['rate']; ?>"
                                data-status="<?php echo $rate['status']; ?>"
                                <?php if ($hasNewColumns): ?>
                                data-homepage="<?php echo isset($rate['display_on_homepage']) ? $rate['display_on_homepage'] : 0; ?>"
                                data-buy="<?php echo isset($rate['we_buy']) ? $rate['we_buy'] : 0; ?>"
                                data-sell="<?php echo isset($rate['we_sell']) ? $rate['we_sell'] : 0; ?>"
                                <?php endif; ?>>
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="delete.php?id=<?php echo $rate['id']; ?>" class="btn btn-danger btn-sm delete-confirm">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="<?php echo $hasNewColumns ? '8' : '5'; ?>" class="text-center">No exchange rates found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Edit Rate Modal -->
<div class="modal fade" id="editRateModal" tabindex="-1" role="dialog" aria-labelledby="editRateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRateModalLabel">Edit Exchange Rate</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="update.php" method="post">
                <input type="hidden" id="edit_id" name="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_from_currency" class="required">From Currency</label>
                                <select class="form-control" id="edit_from_currency" name="from_currency" required disabled>
                                    <?php foreach ($currencies as $currency): ?>
                                    <option value="<?php echo $currency['code']; ?>">
                                        <?php echo htmlspecialchars($currency['name']); ?> (<?php echo htmlspecialchars($currency['display_name'] ?: $currency['code']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">Currency pairs cannot be changed.</small>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_to_currency" class="required">To Currency</label>
                                <select class="form-control" id="edit_to_currency" name="to_currency" required disabled>
                                    <?php foreach ($currencies as $currency): ?>
                                    <option value="<?php echo $currency['code']; ?>">
                                        <?php echo htmlspecialchars($currency['name']); ?> (<?php echo htmlspecialchars($currency['display_name'] ?: $currency['code']); ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_rate" class="required">Exchange Rate</label>
                                <input type="number" class="form-control" id="edit_rate" name="rate" step="0.00000001" min="0" required>
                                <small class="form-text text-muted">Example: 1 USD = 100 BDT would be entered as 100</small>
                            </div>
                        </div>
                        
                        <?php if ($hasNewColumns): ?>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_we_buy">We Buy</label>
                                <input type="number" class="form-control" id="edit_we_buy" name="we_buy" step="0.01" min="0">
                                <small class="form-text text-muted">Rate at which we buy</small>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="edit_we_sell">We Sell</label>
                                <input type="number" class="form-control" id="edit_we_sell" name="we_sell" step="0.01" min="0">
                                <small class="form-text text-muted">Rate at which we sell</small>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_status">Status</label>
                                <select class="form-control" id="edit_status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>
                        
                        <?php if ($hasNewColumns): ?>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="edit_display_on_homepage" name="display_on_homepage">
                                    <label class="custom-control-label" for="edit_display_on_homepage">
                                        <strong>Display this rate on homepage</strong>
                                    </label>
                                </div>
                                <small class="form-text text-muted">You want to display this rate on the homepage</small>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Rate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Initialize edit modal
    $('.edit-rate').click(function() {
        const id = $(this).data('id');
        const fromCurrency = $(this).data('from');
        const toCurrency = $(this).data('to');
        const rate = $(this).data('rate');
        const status = $(this).data('status');
        
        $('#edit_id').val(id);
        $('#edit_from_currency').val(fromCurrency);
        $('#edit_to_currency').val(toCurrency);
        $('#edit_rate').val(rate);
        $('#edit_status').val(status);
        
        <?php if ($hasNewColumns): ?>
        const homepage = $(this).data('homepage');
        const buy = $(this).data('buy');
        const sell = $(this).data('sell');
        
        $('#edit_display_on_homepage').prop('checked', homepage == 1);
        $('#edit_we_buy').val(buy);
        $('#edit_we_sell').val(sell);
        <?php endif; ?>
        
        $('#editRateModal').modal('show');
    });
    
    // Confirm delete
    $('.delete-confirm').click(function(e) {
        if (!confirm('Are you sure you want to delete this exchange rate?')) {
            e.preventDefault();
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>