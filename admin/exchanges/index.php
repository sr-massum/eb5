<?php 

/**
 * ExchangeBridge - Admin Panel Exchange
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

// Set timezone to match frontend
date_default_timezone_set('Asia/Dhaka'); // Change this to your local timezone

// Check if user is logged in, if not redirect to login page
if (!Auth::isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Get filter parameters
$status = isset($_GET['status']) ? sanitizeInput($_GET['status']) : '';
$fromCurrency = isset($_GET['from_currency']) ? sanitizeInput($_GET['from_currency']) : '';
$toCurrency = isset($_GET['to_currency']) ? sanitizeInput($_GET['to_currency']) : '';
$searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$perPage = 20;

// Build query based on filters
$db = Database::getInstance();
$params = [];
$whereConditions = [];

if (!empty($status)) {
    $whereConditions[] = "e.status = ?";
    $params[] = $status;
}

if (!empty($fromCurrency)) {
    $whereConditions[] = "e.from_currency = ?";
    $params[] = $fromCurrency;
}

if (!empty($toCurrency)) {
    $whereConditions[] = "e.to_currency = ?";
    $params[] = $toCurrency;
}

if (!empty($searchTerm)) {
    $whereConditions[] = "(e.reference_id LIKE ? OR e.customer_name LIKE ? OR e.customer_email LIKE ? OR e.customer_phone LIKE ?)";
    $searchPattern = '%' . $searchTerm . '%';
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $params[] = $searchPattern;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get total count for pagination (filtered)
$countQuery = "SELECT COUNT(*) FROM exchanges e $whereClause";
$totalExchanges = $db->getValue($countQuery, $params);
$totalPages = ceil($totalExchanges / $perPage);

// Get total count of ALL exchanges (for serial number calculation)
$totalAllExchanges = $db->getValue("SELECT COUNT(*) FROM exchanges");

// Adjust current page if out of bounds
if ($page < 1) $page = 1;
if ($page > $totalPages && $totalPages > 0) $page = $totalPages;

// Calculate offset for pagination
$offset = ($page - 1) * $perPage;

// Add pagination parameters to query
$params[] = $perPage;
$params[] = $offset;

// Get exchanges with pagination
$query = "SELECT e.*, 
           fc.name as from_currency_name, fc.display_name as from_display_name, fc.logo as from_logo, fc.background_class as from_bg_class, fc.icon_class as from_icon_class,
           tc.name as to_currency_name, tc.display_name as to_display_name, tc.logo as to_logo, tc.background_class as to_bg_class, tc.icon_class as to_icon_class
          FROM exchanges e
          JOIN currencies fc ON e.from_currency = fc.code
          JOIN currencies tc ON e.to_currency = tc.code
          $whereClause
          ORDER BY e.created_at DESC
          LIMIT ? OFFSET ?";

$exchanges = $db->getRows($query, $params);

// Calculate proper serial numbers for each exchange
foreach ($exchanges as &$exchange) {
    // Count how many exchanges were created before or at the same time as this exchange
    $serialNumber = $db->getValue(
        "SELECT COUNT(*) FROM exchanges WHERE created_at <= ?", 
        [$exchange['created_at']]
    );
    $exchange['serial_number'] = $serialNumber;
}

// Get all currencies for filters
$currencies = getAllCurrencies();

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
    <li class="breadcrumb-item active">Exchanges</li>
</ol>

<!-- Page Content -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-exchange-alt mr-1"></i> Exchanges
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
        
        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-filter mr-1"></i> Filters
            </div>
            <div class="card-body">
                <form method="get" action="index.php" class="row">
                    <div class="col-md-3 mb-3">
                        <label for="status">Status</label>
                        <select class="form-control" id="status" name="status">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="refunded" <?php echo $status === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="from_currency">From Currency</label>
                        <select class="form-control" id="from_currency" name="from_currency">
                            <option value="">All Currencies</option>
                            <?php foreach ($currencies as $currency): ?>
                            <option value="<?php echo $currency['code']; ?>" <?php echo $fromCurrency === $currency['code'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($currency['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="to_currency">To Currency</label>
                        <select class="form-control" id="to_currency" name="to_currency">
                            <option value="">All Currencies</option>
                            <?php foreach ($currencies as $currency): ?>
                            <option value="<?php echo $currency['code']; ?>" <?php echo $toCurrency === $currency['code'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($currency['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <label for="search">Search</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="search" name="search" placeholder="ID, Name, Email..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                            <div class="input-group-append">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-12 text-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter mr-1"></i> Apply Filters
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times mr-1"></i> Clear Filters
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Exchanges List -->
        <div class="table-responsive">
            <table class="table table-bordered" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>SL</th>
                        <th>Ref ID</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>From</th>
                        <th>To</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($exchanges) > 0): ?>
                    <?php foreach ($exchanges as $exchange): ?>
                    <tr>
                        <td><?php echo $exchange['serial_number']; ?></td>
                        <td><?php echo htmlspecialchars($exchange['reference_id']); ?></td>
                        <td>
                            <?php 
                            // Convert UTC database time to local timezone
                            $date = new DateTime($exchange['created_at'], new DateTimeZone('UTC'));
                            $date->setTimezone(new DateTimeZone('Asia/Dhaka')); // Change this to your local timezone
                            echo $date->format('d-m-Y H:i:s'); 
                            ?>
                        </td>
                        <td>
                            <div><?php echo htmlspecialchars($exchange['customer_name']); ?></div>
                            <small class="text-muted"><?php echo htmlspecialchars($exchange['customer_email']); ?></small>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="payment-icon <?php echo $exchange['from_bg_class'] ?: 'bg-primary text-white'; ?> mr-2" style="width: 24px; height: 24px;">
                                    <?php if ($exchange['from_logo']): ?>
                                        <img src="<?php echo ASSETS_URL; ?>/uploads/currencies/<?php echo $exchange['from_logo']; ?>" alt="<?php echo $exchange['from_currency_name']; ?>" class="img-fluid">
                                    <?php else: ?>
                                        <i class="<?php echo $exchange['from_icon_class'] ?: 'fas fa-money-bill-wave'; ?> fa-sm"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php echo htmlspecialchars($exchange['from_currency_name']); ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="payment-icon <?php echo $exchange['to_bg_class'] ?: 'bg-primary text-white'; ?> mr-2" style="width: 24px; height: 24px;">
                                    <?php if ($exchange['to_logo']): ?>
                                        <img src="<?php echo ASSETS_URL; ?>/uploads/currencies/<?php echo $exchange['to_logo']; ?>" alt="<?php echo $exchange['to_currency_name']; ?>" class="img-fluid">
                                    <?php else: ?>
                                        <i class="<?php echo $exchange['to_icon_class'] ?: 'fas fa-money-bill-wave'; ?> fa-sm"></i>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <?php echo htmlspecialchars($exchange['to_currency_name']); ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div><?php echo number_format($exchange['send_amount'], 2); ?> <?php echo $exchange['from_currency']; ?></div>
                            <div><?php echo number_format($exchange['receive_amount'], 2); ?> <?php echo $exchange['to_currency']; ?></div>
                        </td>
                        <td>
                            <span class="badge badge-<?php 
                                echo $exchange['status'] === 'confirmed' ? 'success' : 
                                    ($exchange['status'] === 'pending' ? 'warning' : 
                                        ($exchange['status'] === 'cancelled' ? 'danger' : 'secondary')); 
                            ?>">
                                <?php echo ucfirst($exchange['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <a href="view.php?id=<?php echo $exchange['id']; ?>" class="btn btn-primary btn-sm" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="update_status.php?id=<?php echo $exchange['id']; ?>&status=confirmed" class="btn btn-success btn-sm <?php echo $exchange['status'] === 'confirmed' ? 'disabled' : ''; ?>" title="Confirm">
                                    <i class="fas fa-check"></i>
                                </a>
                                <a href="update_status.php?id=<?php echo $exchange['id']; ?>&status=cancelled" class="btn btn-warning btn-sm <?php echo $exchange['status'] === 'cancelled' ? 'disabled' : ''; ?>" title="Cancel">
                                    <i class="fas fa-times"></i>
                                </a>
                                <a href="delete.php?id=<?php echo $exchange['id']; ?>" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure you want to delete this exchange? This action cannot be undone.')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="9" class="text-center">No exchanges found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <nav>
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=1<?php echo !empty($status) ? '&status=' . $status : ''; ?><?php echo !empty($fromCurrency) ? '&from_currency=' . $fromCurrency : ''; ?><?php echo !empty($toCurrency) ? '&to_currency=' . $toCurrency : ''; ?><?php echo !empty($searchTerm) ? '&search=' . $searchTerm : ''; ?>" aria-label="First">
                        <span aria-hidden="true">&laquo;&laquo;</span>
                    </a>
                </li>
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($status) ? '&status=' . $status : ''; ?><?php echo !empty($fromCurrency) ? '&from_currency=' . $fromCurrency : ''; ?><?php echo !empty($toCurrency) ? '&to_currency=' . $toCurrency : ''; ?><?php echo !empty($searchTerm) ? '&search=' . $searchTerm : ''; ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                if ($startPage > 1) {
                    echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                }
                
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($status) ? '&status=' . $status : ''; ?><?php echo !empty($fromCurrency) ? '&from_currency=' . $fromCurrency : ''; ?><?php echo !empty($toCurrency) ? '&to_currency=' . $toCurrency : ''; ?><?php echo !empty($searchTerm) ? '&search=' . $searchTerm : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <?php if ($endPage < $totalPages): ?>
                <li class="page-item disabled"><a class="page-link" href="#">...</a></li>
                <?php endif; ?>
                
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($status) ? '&status=' . $status : ''; ?><?php echo !empty($fromCurrency) ? '&from_currency=' . $fromCurrency : ''; ?><?php echo !empty($toCurrency) ? '&to_currency=' . $toCurrency : ''; ?><?php echo !empty($searchTerm) ? '&search=' . $searchTerm : ''; ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo !empty($status) ? '&status=' . $status : ''; ?><?php echo !empty($fromCurrency) ? '&from_currency=' . $fromCurrency : ''; ?><?php echo !empty($toCurrency) ? '&to_currency=' . $toCurrency : ''; ?><?php echo !empty($searchTerm) ? '&search=' . $searchTerm : ''; ?>" aria-label="Last">
                        <span aria-hidden="true">&raquo;&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>