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

// Get all active currencies for the form
$currencies = getAllCurrencies(true);

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $fromCurrency = isset($_POST['from_currency']) ? sanitizeInput($_POST['from_currency']) : '';
    $toCurrency = isset($_POST['to_currency']) ? sanitizeInput($_POST['to_currency']) : '';
    $rate = isset($_POST['rate']) ? floatval($_POST['rate']) : 0;
    $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'active';
    $displayOnHomepage = isset($_POST['display_on_homepage']) ? 1 : 0;
    $weBuy = isset($_POST['we_buy']) ? floatval($_POST['we_buy']) : 0;
    $weSell = isset($_POST['we_sell']) ? floatval($_POST['we_sell']) : 0;
    
    // Validate form data
    if (empty($fromCurrency)) {
        $_SESSION['error_message'] = 'From currency is required';
    } elseif (empty($toCurrency)) {
        $_SESSION['error_message'] = 'To currency is required';
    } elseif ($fromCurrency === $toCurrency) {
        $_SESSION['error_message'] = 'From and To currencies cannot be the same';
    } elseif ($rate <= 0) {
        $_SESSION['error_message'] = 'Rate must be greater than zero';
    } else {
        // Check if the exchange rate already exists
        $db = Database::getInstance();
        $existingRate = $db->getRow(
            "SELECT * FROM exchange_rates WHERE from_currency = ? AND to_currency = ?", 
            [$fromCurrency, $toCurrency]
        );
        
        if ($existingRate) {
            $_SESSION['error_message'] = 'Exchange rate already exists for this currency pair';
        } else {
            // Prepare data for insertion - only include new fields if they exist in database
            $insertData = [
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'rate' => $rate,
                'status' => $status
            ];
            
            // Check if new columns exist and add them
            try {
                $checkColumns = $db->query("SHOW COLUMNS FROM exchange_rates LIKE 'display_on_homepage'");
                if ($checkColumns && $checkColumns->rowCount() > 0) {
                    $insertData['display_on_homepage'] = $displayOnHomepage;
                    $insertData['we_buy'] = $weBuy;
                    $insertData['we_sell'] = $weSell;
                }
            } catch (Exception $e) {
                // Ignore if columns don't exist yet
            }
            
            // Insert exchange rate
            $rateId = $db->insert('exchange_rates', $insertData);
            
            if ($rateId) {
                $_SESSION['success_message'] = 'Exchange rate added successfully';
                header("Location: index.php");
                exit;
            } else {
                $_SESSION['error_message'] = 'Failed to add exchange rate';
            }
        }
    }
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
    <li class="breadcrumb-item">
        <a href="index.php">Exchange Rates</a>
    </li>
    <li class="breadcrumb-item active">Add New Rate</li>
</ol>

<!-- Page Content -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-plus mr-1"></i> Add New Exchange Rate
        <a href="index.php" class="btn btn-secondary btn-sm float-right">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
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
        
        <form action="" method="post">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="from_currency" class="required">From Currency</label>
                        <select class="form-control" id="from_currency" name="from_currency" required>
                            <option value="">Select Currency</option>
                            <?php foreach ($currencies as $currency): ?>
                            <option value="<?php echo $currency['code']; ?>" <?php echo (isset($_POST['from_currency']) && $_POST['from_currency'] === $currency['code']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($currency['name']); ?> (<?php echo htmlspecialchars($currency['display_name'] ?: $currency['code']); ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="to_currency" class="required">To Currency</label>
                        <select class="form-control" id="to_currency" name="to_currency" required>
                            <option value="">Select Currency</option>
                            <?php foreach ($currencies as $currency): ?>
                            <option value="<?php echo $currency['code']; ?>" <?php echo (isset($_POST['to_currency']) && $_POST['to_currency'] === $currency['code']) ? 'selected' : ''; ?>>
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
                        <label for="rate" class="required">Exchange Rate</label>
                        <input type="number" class="form-control" id="rate" name="rate" step="0.00000001" min="0" 
                               value="<?php echo isset($_POST['rate']) ? htmlspecialchars($_POST['rate']) : ''; ?>" required>
                        <small class="form-text text-muted">Example: 1 USD = 100 BDT would be entered as 100</small>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="we_buy">We Buy</label>
                        <input type="number" class="form-control" id="we_buy" name="we_buy" step="0.01" min="0" 
                               value="<?php echo isset($_POST['we_buy']) ? htmlspecialchars($_POST['we_buy']) : ''; ?>">
                        <small class="form-text text-muted">Rate at which we buy this currency</small>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="we_sell">We Sell</label>
                        <input type="number" class="form-control" id="we_sell" name="we_sell" step="0.01" min="0" 
                               value="<?php echo isset($_POST['we_sell']) ? htmlspecialchars($_POST['we_sell']) : ''; ?>">
                        <small class="form-text text-muted">Rate at which we sell this currency</small>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="active" <?php echo (!isset($_POST['status']) || $_POST['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="display_on_homepage" name="display_on_homepage" 
                                   <?php echo (isset($_POST['display_on_homepage']) && $_POST['display_on_homepage']) ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="display_on_homepage">
                                <strong>Display this rate on homepage</strong>
                            </label>
                        </div>
                        <small class="form-text text-muted">You want to display this rate on the homepage</small>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Add Exchange Rate
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>