<?php 

/**
 * ExchangeBridge - Exchange Receipt
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


// Get reference ID from URL
$referenceId = isset($_GET['ref']) ? sanitizeInput($_GET['ref']) : '';
$exchange = null;

if (!empty($referenceId)) {
    $exchange = getExchangeByReferenceId($referenceId);
}

// Include header
include 'templates/header.php';
?>

<!-- Include html2pdf library -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<!-- Main Content -->
<main class="flex-grow container mx-auto p-4 md:p-6">
    <?php if ($exchange): ?>
    <div class="max-w-4xl mx-auto">
        <!-- Receipt Container -->
        <div id="receipt" class="bg-white rounded-lg shadow-lg overflow-hidden mb-6 relative" style="background-color: <?php echo getSetting('receipt_bg_color', '#ffffff'); ?>;">
            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 rotate-[-30deg] text-[140px] font-extrabold opacity-20 pointer-events-none" style="color: <?php echo getSetting('receipt_watermark_color', '#f3f4f6'); ?>;">
                <?php echo getSetting('receipt_watermark_text', 'EXCHANGE'); ?>
            </div>
            
            <!-- Receipt Header -->
            <div class="text-white p-4 flex justify-between items-center" style="background-color: <?php echo getSetting('receipt_header_color', '#1e3a8a'); ?>;">
                <div class="flex items-center">
                    <?php if (getSetting('receipt_logo_enabled', 'no') === 'yes' && !empty(getSetting('receipt_logo_path'))): ?>
                        <div class="w-12 h-12 mr-3 flex items-center justify-center">
                            <img src="<?php echo SITE_URL; ?>/assets/uploads/media/<?php echo getSetting('receipt_logo_path'); ?>" 
                                 alt="Logo" class="max-w-full max-h-full object-contain rounded">
                        </div>
                    <?php else: ?>
                        <div class="w-10 h-10 bg-white bg-opacity-10 rounded-lg flex items-center justify-center mr-3 border border-white border-opacity-20">
                            <i class="fas fa-exchange-alt" style="color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>;"></i>
                        </div>
                    <?php endif; ?>
                    <div>
                        <div class="text-xl font-bold"><?php echo getSetting('receipt_site_name', getSetting('site_name', SITE_NAME)); ?></div>
                        <div class="text-xs text-gray-300"><?php echo getSetting('receipt_site_tagline', getSetting('site_tagline', 'Exchange Taka Globally')); ?></div>
                    </div>
                </div>
                
                <div class="bg-white bg-opacity-10 p-3 rounded-lg border-l-3" style="border-color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>;">
                    <div class="flex items-center text-base font-bold">
                        <i class="fas fa-fingerprint mr-2" style="color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>;"></i>
                        <span><?php echo htmlspecialchars($exchange['reference_id']); ?></span>
                    </div>
                    <div class="text-xs text-gray-300">
                        <div class="flex items-center">
                            <i class="far fa-calendar-alt mr-2" style="color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>;"></i>
                            <span><?php echo date('M d, Y', strtotime($exchange['created_at'])); ?></span>
                        </div>
                        <div class="flex items-center">
                            <i class="far fa-clock mr-2" style="color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>;"></i>
                            <span><?php echo date('h:i A', strtotime($exchange['created_at'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Receipt Content -->
            <div class="p-4 flex flex-col md:flex-row gap-4" style="background-color: <?php echo getSetting('receipt_content_bg_color', '#f8fafc'); ?>;">
                <!-- Customer Details -->
                <div class="flex-1 rounded-lg p-4 text-white" style="background-color: <?php echo getSetting('receipt_section_color', '#1e40af'); ?>;">
                    <div class="text-base font-bold border-b border-white border-opacity-10 pb-2 mb-3 flex items-center">
                        <i class="fas fa-user-circle mr-2" style="color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>;"></i> 
                        <?php echo getSetting('receipt_customer_details_title', 'Customer Details'); ?>
                    </div>
                    
                    <div class="flex items-center mb-3">
                        <i class="fas fa-user mr-2" style="color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>;"></i>
                        <span class="text-sm text-gray-300 w-20"><?php echo getSetting('receipt_name_label', 'Name'); ?>:</span>
                        <span class="text-sm"><?php echo htmlspecialchars($exchange['customer_name']); ?></span>
                    </div>
                    
                    <div class="flex items-center mb-3">
                        <i class="fas fa-envelope mr-2" style="color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>;"></i>
                        <span class="text-sm text-gray-300 w-20"><?php echo getSetting('receipt_email_label', 'Email'); ?>:</span>
                        <span class="text-sm"><?php echo htmlspecialchars($exchange['customer_email']); ?></span>
                    </div>
                    
                    <div class="flex items-center mb-3">
                        <i class="fas fa-phone mr-2" style="color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>;"></i>
                        <span class="text-sm text-gray-300 w-20"><?php echo getSetting('receipt_phone_label', 'Phone'); ?>:</span>
                        <span class="text-sm"><?php echo htmlspecialchars($exchange['customer_phone']); ?></span>
                    </div>
                    
                    <div class="flex items-center">
                        <i class="fas fa-map-marker-alt mr-2" style="color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>;"></i>
                        <span class="text-sm text-gray-300 w-20"><?php echo getSetting('receipt_address_label', 'Address'); ?>:</span>
                        <span class="text-sm"><?php echo htmlspecialchars($exchange['payment_address']); ?></span>
                    </div>
                </div>
                
                <!-- Exchange Details -->
                <div class="flex-1 rounded-lg p-4 text-white" style="background-color: <?php echo getSetting('receipt_section_color', '#1e40af'); ?>;">
                    <div class="text-base font-bold border-b border-white border-opacity-10 pb-2 mb-3 flex items-center">
                        <i class="fas fa-money-bill-wave mr-2" style="color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>;"></i> 
                        <?php echo getSetting('receipt_exchange_details_title', 'Exchange Details'); ?>
                    </div>
                    
                    <div class="flex items-center mb-3">
                        <i class="fas fa-money-bill-wave mr-2" style="color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>;"></i>
                        <span class="text-sm text-gray-300 w-20"><?php echo getSetting('receipt_from_label', 'From'); ?>:</span>
                        <span class="text-sm"><?php echo htmlspecialchars($exchange['from_currency_name']); ?></span>
                    </div>
                    
                    <div class="flex items-center mb-3">
                        <i class="fas fa-wallet mr-2" style="color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>;"></i>
                        <span class="text-sm text-gray-300 w-20"><?php echo getSetting('receipt_to_label', 'To'); ?>:</span>
                        <span class="text-sm"><?php echo htmlspecialchars($exchange['to_currency_name']); ?></span>
                    </div>
                    
                    <div class="flex items-center mb-3">
                        <i class="fas fa-exchange-alt mr-2" style="color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>;"></i>
                        <span class="text-sm text-gray-300 w-20"><?php echo getSetting('receipt_rate_label', 'Rate'); ?>:</span>
                        <span class="text-sm"><?php echo number_format($exchange['exchange_rate'], 2); ?> <?php echo $exchange['from_currency']; ?>/<?php echo $exchange['to_currency']; ?></span>
                    </div>
                    
                    <!-- Send Amount Highlight Box -->
                    <div class="p-3 rounded-lg border-l-4 mb-3" style="background: rgba(255, 255, 255, 0.1); border-color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>;">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-arrow-up mr-2" style="color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>;"></i>
                                <span class="text-sm font-medium"><?php echo getSetting('receipt_sent_label', 'Sent'); ?>:</span>
                            </div>
                            <span class="text-lg font-bold" style="color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>;">
                                <?php echo number_format($exchange['send_amount'], 2); ?> <?php echo $exchange['from_currency']; ?>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Receive Amount Highlight Box -->
                    <div class="p-3 rounded-lg border-l-4" style="background: rgba(255, 255, 255, 0.1); border-color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>;">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <i class="fas fa-arrow-down mr-2" style="color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>;"></i>
                                <span class="text-sm font-medium"><?php echo getSetting('receipt_received_label', 'Received'); ?>:</span>
                            </div>
                            <span class="text-lg font-bold" style="color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>;">
                                <?php echo number_format($exchange['receive_amount'], 2); ?> <?php echo $exchange['to_currency']; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Receipt Footer -->
            <div class="text-white p-3 flex flex-col md:flex-row justify-between" style="background-color: <?php echo getSetting('receipt_footer_color', '#1e3a8a'); ?>;">
                <div class="flex items-center mb-2 md:mb-0">
                    <div class="w-7 h-7 bg-white bg-opacity-10 rounded-full flex items-center justify-center mr-2 text-xs">
                        <i class="fas fa-globe" style="color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>;"></i>
                    </div>
                    <div>
                        <div class="text-[8px] uppercase font-semibold tracking-wider" style="color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>"><?php echo getSetting('receipt_website_label', 'Website'); ?></div>
                        <div class="text-xs"><?php echo getSetting('receipt_website', getSetting('site_url', SITE_URL)); ?></div>
                    </div>
                </div>
                
                <div class="flex items-center mb-2 md:mb-0">
                    <div class="w-7 h-7 bg-white bg-opacity-10 rounded-full flex items-center justify-center mr-2 text-xs">
                        <i class="fas fa-envelope" style="color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>;"></i>
                    </div>
                    <div>
                        <div class="text-[8px] uppercase font-semibold tracking-wider" style="color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>"><?php echo getSetting('receipt_email_footer_label', 'Email Address'); ?></div>
                        <div class="text-xs"><?php echo getSetting('receipt_email', getSetting('contact_email', 'support@exchangebridge.com')); ?></div>
                    </div>
                </div>
                
                <div class="flex items-center">
                    <div class="w-7 h-7 bg-white bg-opacity-10 rounded-full flex items-center justify-center mr-2 text-xs">
                        <i class="fas fa-phone-alt" style="color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>;"></i>
                    </div>
                    <div>
                        <div class="text-[8px] uppercase font-semibold tracking-wider" style="color: <?php echo getSetting('receipt_accent_color', '#fde047'); ?>"><?php echo getSetting('receipt_phone_footer_label', 'Contact Number'); ?></div>
                        <div class="text-xs"><?php echo getSetting('receipt_phone', getSetting('contact_phone', '+8801869838872')); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex justify-center gap-4">
            <button id="download-pdf-btn" class="bg-green-600 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-green-700 transition-colors">
                <i class="fas fa-download"></i> Download PDF
            </button>
            <button id="print-btn" class="bg-blue-900 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-blue-800 transition-colors">
                <i class="fas fa-print"></i> Print Receipt
            </button>
            <a href="track.php?ref=<?php echo urlencode($exchange['reference_id']); ?>" class="bg-yellow-500 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-yellow-400 transition-colors">
                <i class="fas fa-search"></i> Track Transaction
            </a>
            <a href="index.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-gray-400 transition-colors">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>
    <?php else: ?>
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 text-center">
        <div class="text-red-500 text-5xl mb-4">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
        <h2 class="text-2xl font-bold mb-2">Receipt Not Found</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            We couldn't find a receipt with the reference ID: <?php echo htmlspecialchars($referenceId); ?>
        </p>
        <div class="flex justify-center gap-4">
            <a href="track.php" class="bg-blue-500 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-blue-400 transition-colors">
                <i class="fas fa-search"></i> Track Another Transaction
            </a>
            <a href="index.php" class="bg-gray-500 text-white px-4 py-2 rounded-lg flex items-center gap-2 hover:bg-gray-400 transition-colors">
                <i class="fas fa-home"></i> Back to Home
            </a>
        </div>
    </div>
    <?php endif; ?>
</main>

<script>
// Download PDF
document.getElementById('download-pdf-btn').addEventListener('click', function() {
    const element = document.getElementById('receipt');
    const opt = {
        margin: 0.5,
        filename: 'exchange-receipt-<?php echo htmlspecialchars($exchange['reference_id'] ?? 'unknown'); ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, useCORS: true },
        jsPDF: { unit: 'in', format: 'a4', orientation: 'portrait' }
    };
    
    // Show loading state
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating PDF...';
    this.disabled = true;
    
    html2pdf().set(opt).from(element).save().finally(() => {
        // Reset button state
        this.innerHTML = '<i class="fas fa-download"></i> Download PDF';
        this.disabled = false;
    });
});

// Print receipt
document.getElementById('print-btn').addEventListener('click', function() {
    window.print();
});
</script>

<?php include 'templates/footer.php'; ?>