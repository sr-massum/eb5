<?php 

/**
 * ExchangeBridge - Transaction Track Page
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

require_once __DIR__ . '/includes/app.php';
require_once 'config/config.php';
require_once 'config/verification.php';
require_once 'config/license.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
require_once 'includes/security.php';

// Initialize security instance
$security = Security::getInstance();

// Check if user is banned
$security->checkBanStatus();

// Initialize variables
$referenceId = '';
$exchange = null;
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ref'])) {
    // Rate limiting for tracking requests (generous limit for legitimate users)
    if (!$security->checkRateLimit('track_transaction', 30, 300)) { // 30 requests per 5 minutes
        $errors['rate_limit'] = 'Too many tracking requests. Please wait a moment before trying again.';
    } else {
        // Get and validate reference ID - allow hyphens
        $referenceId = $security->sanitizeInput($_GET['ref'], 'text'); // Changed from 'alphanum' to 'text'
        
        // Remove any characters that aren't alphanumeric or hyphen
        $referenceId = preg_replace('/[^A-Za-z0-9\-]/', '', $referenceId);
        
        // Validate reference ID format
        if (empty($referenceId)) {
            $errors['reference_id'] = 'Reference ID is required';
        } elseif (strlen($referenceId) < 6 || strlen($referenceId) > 11) { // Increased max length to accommodate hyphens
            $errors['reference_id'] = 'Invalid reference ID format';
        } elseif (!preg_match('/^[A-Za-z0-9\-]+$/', $referenceId)) { // Allow hyphens in pattern
            $errors['reference_id'] = 'Reference ID must contain only letters, numbers, and hyphens';
        } else {
            // Additional SQL injection protection
            $referenceId = $security->sanitizeForSQL($referenceId);
            
            // Log tracking attempt
            $security->logSecurityEvent('TRANSACTION_TRACK_ATTEMPT', 
                "Reference ID tracking attempt: {$referenceId}");
            
            // Get exchange data if no errors
            if (empty($errors)) {
                try {
                    $exchange = getExchangeByReferenceId($referenceId);
                    
                    // If exchange not found, log potential brute force attempt
                    if (!$exchange) {
                        $security->logSecurityEvent('TRANSACTION_TRACK_NOT_FOUND', 
                            "Invalid reference ID attempted: {$referenceId}");
                    }
                } catch (Exception $e) {
                    error_log("Error fetching exchange data: " . $e->getMessage());
                    $errors['system'] = 'Unable to process request. Please try again later.';
                }
            }
        }
    }
}

// Process AJAX requests securely
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    // Verify CSRF token for POST requests
    if (!$security->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid security token']);
        exit();
    }
    
    $action = $security->sanitizeInput($_POST['ajax_action']);
    
    switch ($action) {
        case 'refresh_status':
            if (!$security->checkRateLimit('refresh_status', 10, 60)) { // 10 requests per minute
                echo json_encode(['error' => 'Too many refresh requests']);
                exit();
            }
            
            $refId = $security->sanitizeInput($_POST['reference_id'], 'text'); // Changed from 'alphanum'
            $refId = preg_replace('/[^A-Za-z0-9\-]/', '', $refId); // Allow hyphens
            
            if (!empty($refId)) {
                $exchange = getExchangeByReferenceId($refId);
                if ($exchange) {
                    echo json_encode(['status' => $exchange['status'], 'updated_at' => $exchange['updated_at']]);
                } else {
                    echo json_encode(['error' => 'Transaction not found']);
                }
            } else {
                echo json_encode(['error' => 'Invalid reference ID']);
            }
            exit();
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            exit();
    }
}

// Include header
include 'templates/header.php';
?>

<!-- Main Content -->
<main class="flex-grow container mx-auto p-4 md:p-6">
    <!-- Security Notice (optional - can be removed if not needed) -->
    <?php if (isset($errors['rate_limit'])): ?>
    <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <?php echo htmlspecialchars($errors['rate_limit']); ?>
    </div>
    <?php endif; ?>
    
    <!-- Transaction Tracking -->
    <div id="transaction-tracker" class="bg-white dark:bg-gray-800 rounded-lg shadow-card overflow-hidden mb-6 section-bg animated-border">
        <div class="track-header p-4 border-b border-gray-200 dark:border-gray-600">
            <h2 class="text-xl font-semibold">
                <i class="fas fa-search mr-2"></i> Track Your Transaction
            </h2>
        </div>
        
        <div class="p-6 section-content">
            <div class="max-w-lg mx-auto">
                <form id="tracking-form" class="flex items-center mb-6" method="get" action="track.php">
                    <input type="text" 
                           id="track-reference-id" 
                           name="ref" 
                           placeholder="Enter your Reference ID (e.g., EB-XXXXXXXX)" 
                           class="flex-grow border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded-l p-2 text-base" 
                           value="<?php echo htmlspecialchars($referenceId); ?>" 
                           maxlength="11"
                           pattern="[A-Z-0-9\-]+"
                           title="Reference ID must contain only letters, numbers, and hyphens"
                           required>
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-r" id="track-btn">
                        <i class="fas fa-search mr-2"></i> Track
                    </button>
                </form>
                
                <!-- Display validation errors -->
                <?php if (!empty($errors['reference_id'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($errors['reference_id']); ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($errors['system'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($errors['system']); ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($referenceId) && $exchange && empty($errors)): ?>
                <div id="tracking-result">
                    <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-4">
                        <div class="grid grid-cols-2 gap-4 border-b border-gray-200 dark:border-gray-600 pb-3 mb-3">
                            <div class="text-gray-600 dark:text-gray-400">Reference ID:</div>
                            <div id="result-reference-id" class="font-semibold"><?php echo htmlspecialchars($exchange['reference_id']); ?></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 border-b border-gray-200 dark:border-gray-600 pb-3 mb-3">
                            <div class="text-gray-600 dark:text-gray-400">Date:</div>
                            <div id="result-date" class="font-semibold"><?php echo date('M d, Y', strtotime($exchange['created_at'])); ?></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 border-b border-gray-200 dark:border-gray-600 pb-3 mb-3">
                            <div class="text-gray-600 dark:text-gray-400">Exchange:</div>
                            <div id="result-exchange" class="font-semibold"><?php echo htmlspecialchars($exchange['from_currency_name']); ?> to <?php echo htmlspecialchars($exchange['to_currency_name']); ?></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4 border-b border-gray-200 dark:border-gray-600 pb-3 mb-3">
                            <div class="text-gray-600 dark:text-gray-400">Amount:</div>
                            <div id="result-amount" class="font-semibold"><?php echo number_format($exchange['send_amount'], 2); ?> <?php echo htmlspecialchars($exchange['from_currency']); ?> → <?php echo number_format($exchange['receive_amount'], 2); ?> <?php echo htmlspecialchars($exchange['to_currency']); ?></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-gray-600 dark:text-gray-400">Status:</div>
                            <div id="result-status">
                                <span class="status-<?php echo $exchange['status']; ?> px-2 py-0.5 rounded text-xs"><?php echo ucfirst($exchange['status']); ?></span>
                                <button id="refresh-status" class="ml-2 text-blue-500 hover:text-blue-700 text-sm" title="Refresh Status">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div id="status-timeline" class="relative ml-6 mb-6">
                        <?php
                        // Define timeline steps based on status
                        $steps = [];
                        
                        // Always add the first step
                        $steps[] = [
                            'time' => date('M d, Y, h:i A', strtotime($exchange['created_at'])),
                            'title' => 'Order created',
                            'description' => 'Exchange request submitted',
                            'completed' => true
                        ];
                        
                        // Add appropriate steps based on status
                        if ($exchange['status'] !== 'pending') {
                            $steps[] = [
                                'time' => date('M d, Y, h:i A', strtotime($exchange['updated_at'])),
                                'title' => 'Payment confirmed',
                                'description' => 'Payment received and verified',
                                'completed' => true
                            ];
                        }
                        
                        if ($exchange['status'] === 'confirmed') {
                            $steps[] = [
                                'time' => date('M d, Y, h:i A', strtotime($exchange['updated_at'])),
                                'title' => 'Exchange completed',
                                'description' => 'Funds sent to customer ' . htmlspecialchars($exchange['to_currency_name']) . ' account',
                                'completed' => true,
                                'last' => true
                            ];
                        } elseif ($exchange['status'] === 'cancelled') {
                            $steps[] = [
                                'time' => date('M d, Y, h:i A', strtotime($exchange['updated_at'])),
                                'title' => 'Exchange cancelled',
                                'description' => 'Transaction has been cancelled',
                                'completed' => true,
                                'last' => true,
                                'error' => true
                            ];
                        } elseif ($exchange['status'] === 'refunded') {
                            $steps[] = [
                                'time' => date('M d, Y, h:i A', strtotime($exchange['updated_at'])),
                                'title' => 'Exchange refunded',
                                'description' => 'Transaction has been refunded',
                                'completed' => true,
                                'last' => true,
                                'warning' => true
                            ];
                        }
                        
                        // Output timeline
                        foreach ($steps as $index => $step):
                        ?>
                        <div class="mb-4 relative">
                            <div class="absolute top-0 left-0 -ml-6 w-3 h-3 rounded-full <?php echo isset($step['last']) ? (isset($step['error']) ? 'bg-red-500' : (isset($step['warning']) ? 'bg-yellow-500' : 'bg-blue-500')) : 'bg-gray-400'; ?>"></div>
                            <?php if ($index < count($steps) - 1): ?>
                            <div class="absolute top-3 left-0 -ml-5 w-1 h-full bg-gray-300 dark:bg-gray-600"></div>
                            <?php endif; ?>
                            <div class="pl-4">
                                <div class="text-xs text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($step['time']); ?></div>
                                <div class="font-semibold <?php echo isset($step['error']) ? 'text-red-500' : (isset($step['warning']) ? 'text-yellow-500' : ''); ?>"><?php echo htmlspecialchars($step['title']); ?></div>
                                <div class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($step['description']); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-blue-800 dark:text-blue-200 mb-2">Need Help?</h3>
                        <p class="text-blue-700 dark:text-blue-300 mb-3">
                            If you have any questions about your transaction, please contact our support team.
                        </p>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', getSetting('contact_whatsapp', '8801869838872')); ?>?text=<?php echo urlencode('Hello! I need help with my transaction: ' . $exchange['reference_id']); ?>" 
                           class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded flex items-center justify-center"
                           target="_blank"
                           rel="noopener noreferrer">
                            <i class="fab fa-whatsapp text-xl mr-2"></i>
                            Contact Operator: <?php echo htmlspecialchars(getSetting('contact_phone', '+8801869838872')); ?>
                        </a>
                    </div>
                </div>
                <?php elseif (!empty($referenceId) && empty($errors)): ?>
                <div id="tracking-not-found">
                    <div class="bg-red-50 dark:bg-red-900 p-4 rounded-lg text-center">
                        <i class="fas fa-exclamation-circle text-red-500 text-3xl mb-3"></i>
                        <h3 class="text-lg font-semibold text-red-700 dark:text-red-300 mb-2">Transaction Not Found</h3>
                        <p class="text-red-600 dark:text-red-400">
                            We couldn't find any transaction with this Reference ID. Please check the ID and try again.
                        </p>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (empty($referenceId) && empty($errors)): ?>
                <div class="text-center text-gray-500 dark:text-gray-400 p-4">
                    <i class="fas fa-info-circle text-xl mb-2"></i>
                    <p>Enter your reference ID to track your transaction status.</p>
                    <p class="text-sm mt-2">Format: EB-XXXXXXXX</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Transactions -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-card overflow-hidden mb-6 section-bg animated-border">
        <div class="table-header rounded-t-lg px-4 py-3 flex justify-between items-center">
            <h2 class="text-xl font-semibold text-white">
                <i class="fas fa-history mr-2"></i> Recent Transactions
            </h2>
        </div>
        
        <div class="overflow-x-auto section-content">
            <table class="w-full">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Reference ID</th>
                        <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Date</th>
                        <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Exchange Direction</th>
                        <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Amount</th>
                        <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th class="py-2 px-4 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    <?php
                    try {
                        $recentExchanges = getRecentExchanges(10);
                        foreach ($recentExchanges as $exchange):
                    ?>
                    <tr class="animate-row hover:bg-gray-50 dark:hover:bg-gray-700 transition duration-150">
                        <td class="py-2 px-4 whitespace-nowrap"><?php echo htmlspecialchars($exchange['reference_id']); ?></td>
                        <td class="py-2 px-4 whitespace-nowrap"><?php echo date('M d, Y', strtotime($exchange['created_at'])); ?></td>
                        <td class="py-2 px-4">
                            <div class="flex items-center">
                                <div class="payment-icon <?php echo htmlspecialchars($exchange['from_bg_class'] ?: 'bg-blue-100 text-blue-500'); ?> w-6 h-6">
                                    <?php if ($exchange['from_logo']): ?>
                                        <img src="<?php echo htmlspecialchars(ASSETS_URL . '/uploads/currencies/' . $exchange['from_logo']); ?>" 
                                             alt="<?php echo htmlspecialchars($exchange['from_currency_name']); ?>" 
                                             class="w-full h-full object-contain">
                                    <?php else: ?>
                                        <i class="<?php echo htmlspecialchars($exchange['from_icon_class'] ?: 'fas fa-money-bill-wave'); ?> text-xs"></i>
                                    <?php endif; ?>
                                </div>
                                <span class="ml-1 text-sm"><?php echo htmlspecialchars($exchange['from_display_name'] ?: $exchange['from_currency']); ?></span>
                                <i class="fas fa-arrow-right mx-2 text-gray-400"></i>
                                <div class="payment-icon <?php echo htmlspecialchars($exchange['to_bg_class'] ?: 'bg-gray-100 text-gray-500'); ?> w-6 h-6">
                                    <?php if ($exchange['to_logo']): ?>
                                        <img src="<?php echo htmlspecialchars(ASSETS_URL . '/uploads/currencies/' . $exchange['to_logo']); ?>" 
                                             alt="<?php echo htmlspecialchars($exchange['to_currency_name']); ?>" 
                                             class="w-full h-full object-contain">
                                    <?php else: ?>
                                        <i class="<?php echo htmlspecialchars($exchange['to_icon_class'] ?: 'fas fa-money-bill-wave'); ?> text-xs"></i>
                                    <?php endif; ?>
                                </div>
                                <span class="ml-1 text-sm"><?php echo htmlspecialchars($exchange['to_display_name'] ?: $exchange['to_currency']); ?></span>
                            </div>
                        </td>
                        <td class="py-2 px-4 whitespace-nowrap">
                            <?php echo number_format($exchange['send_amount'], 2); ?> <?php echo htmlspecialchars($exchange['from_display_name'] ?: $exchange['from_currency']); ?> →
                            <?php echo number_format($exchange['receive_amount'], 2); ?> <?php echo htmlspecialchars($exchange['to_display_name'] ?: $exchange['to_currency']); ?>
                        </td>
                        <td class="py-2 px-4 whitespace-nowrap">
                            <span class="status-<?php echo htmlspecialchars($exchange['status']); ?> px-2 py-0.5 rounded text-xs"><?php echo ucfirst(htmlspecialchars($exchange['status'])); ?></span>
                        </td>
                        <td class="py-2 px-4 whitespace-nowrap">
                            <a href="track.php?ref=<?php echo urlencode($exchange['reference_id']); ?>" class="text-primary hover:underline">
                                <i class="fas fa-search"></i> Track
                            </a>
                            |
                            <a href="receipt.php?ref=<?php echo urlencode($exchange['reference_id']); ?>" class="text-primary hover:underline">
                                <i class="fas fa-receipt"></i> Receipt
                            </a>
                        </td>
                    </tr>
                    <?php 
                        endforeach;
                    } catch (Exception $e) {
                        error_log("Error fetching recent exchanges: " . $e->getMessage());
                    ?>
                    <tr>
                        <td colspan="6" class="py-4 px-4 text-center text-gray-500 dark:text-gray-400">Unable to load recent transactions</td>
                    </tr>
                    <?php } ?>
                    
                    <?php if (isset($recentExchanges) && empty($recentExchanges)): ?>
                    <tr>
                        <td colspan="6" class="py-4 px-4 text-center text-gray-500 dark:text-gray-400">No recent transactions found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- JavaScript for enhanced security and functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // CSRF token for AJAX requests
    const csrfToken = '<?php echo $security->generateCSRFToken(); ?>';
    
    // Form validation
    const form = document.getElementById('tracking-form');
    const input = document.getElementById('track-reference-id');
    const trackBtn = document.getElementById('track-btn');
    
    if (form && input) {
        // Input validation - allow hyphens
        input.addEventListener('input', function() {
            // Remove any characters that aren't alphanumeric or hyphen
            this.value = this.value.replace(/[^A-Za-z0-9\-]/g, '');
            
            // Limit length
            if (this.value.length > 11) {
                this.value = this.value.substring(0, 11);
            }
        });
        
        // Form submission
        form.addEventListener('submit', function(e) {
            const refId = input.value.trim();
            
            if (!refId) {
                e.preventDefault();
                alert('Please enter a reference ID');
                return;
            }
            
            if (refId.length < 6) {
                e.preventDefault();
                alert('Reference ID must be at least 6 characters long');
                return;
            }
            
            if (!/^[A-Za-z0-9\-]+$/.test(refId)) {
                e.preventDefault();
                alert('Reference ID must contain only letters, numbers, and hyphens');
                return;
            }
            
            // Disable button to prevent double submission
            trackBtn.disabled = true;
            trackBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Tracking...';
        });
    }
    
    // Auto-refresh status functionality
    const refreshBtn = document.getElementById('refresh-status');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            const referenceId = '<?php echo addslashes($referenceId); ?>';
            if (!referenceId) return;
            
            // Disable button
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            
            // Make AJAX request
            fetch('track.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax_action=refresh_status&reference_id=${encodeURIComponent(referenceId)}&csrf_token=${encodeURIComponent(csrfToken)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                } else {
                    // Update status display
                    const statusEl = document.querySelector('#result-status span');
                    if (statusEl) {
                        statusEl.className = `status-${data.status} px-2 py-0.5 rounded text-xs`;
                        statusEl.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to refresh status');
            })
            .finally(() => {
                // Re-enable button
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-sync-alt"></i>';
            });
        });
    }
    
    // Security: Prevent form resubmission on page refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
});
</script>

<?php include 'templates/footer.php'; ?>