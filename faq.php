<?php 

/**
 * ExchangeBridge - FAQ Page
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

// Get FAQ page content
$page = getPageBySlug('faq');

// Include header
include 'templates/header.php';
?>

<!-- Main Content -->
<main class="flex-grow container mx-auto p-4 md:p-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-card overflow-hidden mb-6 section-bg">
        <div class="bg-primary text-white p-4 border-b border-gray-200 dark:border-gray-600">
            <h1 class="text-xl font-semibold text-center">
                <?php echo htmlspecialchars($page['title'] ?? 'Frequently Asked Questions'); ?>
            </h1>
        </div>
        
        <div class="p-6 section-content">
            <div class="prose dark:prose-invert max-w-none">
                <?php 
                if (!empty($page['content'])) {
                    echo $page['content'];
                } else {
                    // Default FAQ content if none is set
                    ?>
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-semibold">What is Exchange Bridge?</h3>
                            <p>Exchange Bridge is a platform that allows you to exchange various currencies and payment methods quickly and securely. We support exchanges between traditional currencies, cryptocurrencies, and popular payment systems.</p>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-semibold">How long does an exchange take?</h3>
                            <p>Most exchanges are processed within 5-30 minutes after payment confirmation. In some cases, especially for cryptocurrencies that require multiple confirmations, it may take longer.</p>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-semibold">What are your fees?</h3>
                            <p>Our fees are built into the exchange rates. We don't charge any additional fees on top of the displayed exchange rate.</p>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-semibold">How do I track my exchange?</h3>
                            <p>After submitting an exchange, you'll receive a unique reference ID. You can use this ID in the "Track" section of our website to check the status of your exchange.</p>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-semibold">What if I made a mistake in my payment details?</h3>
                            <p>If you made a mistake in your payment details, please contact our support team immediately via WhatsApp with your reference ID and correct information.</p>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-semibold">Do you have a minimum or maximum exchange amount?</h3>
                            <p>Yes, the minimum exchange amount is $1 or equivalent. Maximum amounts may vary depending on our reserves and the currencies involved. Contact support for large exchanges.</p>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-semibold">Is my personal information safe?</h3>
                            <p>Yes, we take data security very seriously. We use encryption to protect your personal information and don't share it with third parties except when necessary to complete your exchange.</p>
                        </div>
                        
                        <div>
                            <h3 class="text-lg font-semibold">How can I contact support?</h3>
                            <p>You can contact our support team via WhatsApp at <?php echo getSetting('contact_phone', '+8801869838872'); ?> or by email at <?php echo getSetting('contact_email', 'support@exchangebridge.com'); ?>. We're available during our working hours: <?php echo getSetting('working_hours', '9 am-11.50pm +6'); ?>.</p>
                        </div>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
</main>

<?php include 'templates/footer.php'; ?>