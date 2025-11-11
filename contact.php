<?php 

/**
 * ExchangeBridge - Contact Page
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

// Get contact page content
$page = getPageBySlug('contact');

// Contact form submission
$successMessage = '';
$errorMessage = '';
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting for contact form (20 submissions per 5 minutes)
    if (!$security->checkRateLimit('contact_form', 20, 300)) {
        $errorMessage = 'Too many contact form submissions. Please wait before sending another message.';
    } else {
        // Verify CSRF token
        if (!$security->verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $errorMessage = 'Security token mismatch. Please try again.';
            $security->logSecurityEvent('CSRF_TOKEN_MISMATCH_CONTACT', 
                'CSRF token mismatch on contact form from IP: ' . $security->getClientIp());
        } else {
            // Sanitize and validate input
            $name = $security->sanitizeInput($_POST['name'] ?? '', 'string');
            $email = $security->sanitizeInput($_POST['email'] ?? '', 'email');
            $subject = $security->sanitizeInput($_POST['subject'] ?? '', 'string');
            $message = $security->sanitizeInput($_POST['message'] ?? '', 'string');
            
            // Store form data for repopulation on error
            $formData = [
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => $message
            ];
            
            // Validate form data using security class
            $validationRules = [
                'name' => [
                    'required' => true,
                    'min_length' => 2,
                    'max_length' => 100,
                    'pattern' => '/^[a-zA-Z\s\.\-]+$/',
                    'pattern_message' => 'Name can only contain letters, spaces, dots, and hyphens'
                ],
                'email' => [
                    'required' => true,
                    'type' => 'email',
                    'max_length' => 100
                ],
                'subject' => [
                    'required' => true,
                    'min_length' => 5,
                    'max_length' => 200
                ],
                'message' => [
                    'required' => true,
                    'min_length' => 10,
                    'max_length' => 2000
                ]
            ];
            
            $validationErrors = $security->validateInput($formData, $validationRules);
            
            if (!empty($validationErrors)) {
                $errorMessage = implode('<br>', $validationErrors);
            } else {
                // Additional XSS protection
                $name = $security->antiXSS($name);
                $email = $security->antiXSS($email);
                $subject = $security->antiXSS($subject);
                $message = $security->antiXSS($message);
                
                // Check for spam patterns
                $spamPatterns = [
                    '/\b(viagra|cialis|casino|poker|loan|debt|mortgage|insurance)\b/i',
                    '/\b(click here|free money|make money|work from home)\b/i',
                    '/(http|https|www\.)/i'
                ];
                
                $isSpam = false;
                foreach ($spamPatterns as $pattern) {
                    if (preg_match($pattern, $message) || preg_match($pattern, $subject)) {
                        $isSpam = true;
                        break;
                    }
                }
                
                if ($isSpam) {
                    $errorMessage = 'Your message appears to be spam and cannot be sent.';
                    $security->logSecurityEvent('SPAM_CONTACT_ATTEMPT', 
                        "Spam contact form submission from IP: " . $security->getClientIp());
                } else {
                    try {
                        // Save contact message to database
                        $db = Database::getInstance();
                        $messageId = $db->insert('contact_messages', [
                            'name' => $name,
                            'email' => $email,
                            'subject' => $subject,
                            'message' => $message,
                            'ip_address' => $security->getClientIp(),
                            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                            'status' => 'new',
                            'created_at' => date('Y-m-d H:i:s')
                        ]);
                        
                        if ($messageId) {
                            $successMessage = 'Your message has been sent successfully. We will get back to you soon!';
                            
                            // Log successful contact
                            $security->logSecurityEvent('CONTACT_FORM_SUCCESS', 
                                "Contact form submitted successfully from IP: " . $security->getClientIp());
                            
                            // Clear form data on success
                            $formData = [];
                            
                            // Optional: Send email notification to admin
                            // sendEmailNotification($name, $email, $subject, $message);
                            
                        } else {
                            $errorMessage = 'Failed to send message. Please try again later.';
                        }
                        
                    } catch (Exception $e) {
                        error_log("Contact form error: " . $e->getMessage());
                        $errorMessage = 'An error occurred while sending your message. Please try again later.';
                    }
                }
            }
        }
    }
}

// Include header
include 'templates/header.php';
?>

<!-- Main Content -->
<main class="flex-grow container mx-auto p-4 md:p-6">
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-card overflow-hidden mb-6 section-bg">
        <div class="bg-primary text-white p-4 border-b border-gray-200 dark:border-gray-600">
            <h1 class="text-xl font-semibold text-center">
                <?php echo htmlspecialchars($page['title'] ?? 'Contact Us'); ?>
            </h1>
        </div>
        
        <div class="p-6 section-content">
            <?php if (!empty($successMessage)): ?>
            <div class="bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 p-4 rounded-lg mb-6">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <span><?php echo $successMessage; ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($errorMessage)): ?>
            <div class="bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300 p-4 rounded-lg mb-6">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <span><?php echo $errorMessage; ?></span>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Contact Information -->
                <div>
                    <h2 class="text-xl font-bold mb-4">Get in Touch</h2>
                    
                    <div class="prose dark:prose-invert max-w-none mb-6">
                        <?php 
                        if (!empty($page['content'])) {
                            echo $page['content'];
                        } else {
                            echo '<p>We would love to hear from you! Please feel free to contact us using the form or the contact details below.</p>';
                        }
                        ?>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="bg-primary text-white p-3 rounded-full mr-4">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold">Address</h3>
                                <p class="text-gray-600 dark:text-gray-400"><?php echo nl2br(htmlspecialchars(getSetting('contact_address', 'Dhaka, Bangladesh'))); ?></p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="bg-primary text-white p-3 rounded-full mr-4">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold">Email</h3>
                                <p class="text-gray-600 dark:text-gray-400">
                                    <a href="mailto:<?php echo htmlspecialchars(getSetting('contact_email', 'support@exchangebridge.com')); ?>" class="text-primary hover:underline">
                                        <?php echo htmlspecialchars(getSetting('contact_email', 'support@exchangebridge.com')); ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="bg-primary text-white p-3 rounded-full mr-4">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold">Phone</h3>
                                <p class="text-gray-600 dark:text-gray-400">
                                    <a href="tel:<?php echo htmlspecialchars(getSetting('contact_phone', '+8801869838872')); ?>" class="hover:underline">
                                        <?php echo htmlspecialchars(getSetting('contact_phone', '+8801869838872')); ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="bg-primary text-white p-3 rounded-full mr-4">
                                <i class="fab fa-whatsapp"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold">WhatsApp</h3>
                                <p class="text-gray-600 dark:text-gray-400">
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', getSetting('contact_whatsapp', '8801869838872')); ?>" class="text-green-600 dark:text-green-400 hover:underline" target="_blank" rel="noopener noreferrer">
                                        <?php echo htmlspecialchars(getSetting('contact_phone', '+8801869838872')); ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="bg-primary text-white p-3 rounded-full mr-4">
                                <i class="far fa-clock"></i>
                            </div>
                            <div>
                                <h3 class="font-semibold">Working Hours</h3>
                                <p class="text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars(getSetting('working_hours', '9 am-11.50pm +6')); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contact Form -->
                <div>
                    <h2 class="text-xl font-bold mb-4">Send us a Message</h2>
                    
                    <form action="contact.php" method="post" class="space-y-4" id="contact-form">
                        <!-- CSRF Token -->
                        <input type="hidden" name="csrf_token" value="<?php echo $security->generateCSRFToken(); ?>">
                        
                        <div>
                            <label for="name" class="block text-sm font-medium mb-1">Your Name <span class="text-red-500">*</span></label>
                            <input type="text" 
                                   id="name" 
                                   name="name" 
                                   class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded p-2 text-base" 
                                   value="<?php echo htmlspecialchars($formData['name'] ?? ''); ?>"
                                   maxlength="100"
                                   pattern="[a-zA-Z\s\.\-]+"
                                   title="Name can only contain letters, spaces, dots, and hyphens"
                                   required>
                        </div>
                        
                        <div>
                            <label for="email" class="block text-sm font-medium mb-1">Your Email <span class="text-red-500">*</span></label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded p-2 text-base" 
                                   value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
                                   maxlength="100"
                                   required>
                        </div>
                        
                        <div>
                            <label for="subject" class="block text-sm font-medium mb-1">Subject <span class="text-red-500">*</span></label>
                            <input type="text" 
                                   id="subject" 
                                   name="subject" 
                                   class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded p-2 text-base" 
                                   value="<?php echo htmlspecialchars($formData['subject'] ?? ''); ?>"
                                   maxlength="200"
                                   minlength="5"
                                   required>
                        </div>
                        
                        <div>
                            <label for="message" class="block text-sm font-medium mb-1">Message <span class="text-red-500">*</span></label>
                            <textarea id="message" 
                                      name="message" 
                                      rows="5" 
                                      class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded p-2 text-base" 
                                      maxlength="2000"
                                      minlength="10"
                                      required><?php echo htmlspecialchars($formData['message'] ?? ''); ?></textarea>
                            <div class="text-sm text-gray-500 mt-1">
                                <span id="char-count">0</span>/2000 characters
                            </div>
                        </div>
                        
                        <div>
                            <button type="submit" class="exchange-btn px-6 py-2 rounded-full font-semibold text-white shadow-md hover:shadow-lg" id="submit-btn">
                                <i class="fas fa-paper-plane mr-2"></i> Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<!-- Enhanced Security JavaScript -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('contact-form');
    const nameInput = document.getElementById('name');
    const emailInput = document.getElementById('email');
    const subjectInput = document.getElementById('subject');
    const messageInput = document.getElementById('message');
    const submitBtn = document.getElementById('submit-btn');
    const charCount = document.getElementById('char-count');
    
    // Character counter for message
    if (messageInput && charCount) {
        messageInput.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = length;
            
            if (length > 2000) {
                charCount.style.color = '#EF4444';
            } else if (length > 1800) {
                charCount.style.color = '#F59E0B';
            } else {
                charCount.style.color = '#6B7280';
            }
        });
        
        // Initial count
        charCount.textContent = messageInput.value.length;
    }
    
    // Input validation
    if (nameInput) {
        nameInput.addEventListener('input', function() {
            // Remove invalid characters
            this.value = this.value.replace(/[^a-zA-Z\s\.\-]/g, '');
        });
    }
    
    // Form submission
    if (form) {
        form.addEventListener('submit', function(e) {
            const name = nameInput.value.trim();
            const email = emailInput.value.trim();
            const subject = subjectInput.value.trim();
            const message = messageInput.value.trim();
            
            // Basic client-side validation
            if (name.length < 2) {
                e.preventDefault();
                alert('Name must be at least 2 characters long');
                nameInput.focus();
                return;
            }
            
            if (subject.length < 5) {
                e.preventDefault();
                alert('Subject must be at least 5 characters long');
                subjectInput.focus();
                return;
            }
            
            if (message.length < 10) {
                e.preventDefault();
                alert('Message must be at least 10 characters long');
                messageInput.focus();
                return;
            }
            
            if (message.length > 2000) {
                e.preventDefault();
                alert('Message is too long (maximum 2000 characters)');
                messageInput.focus();
                return;
            }
            
            // Check for suspicious content
            const suspiciousPatterns = [
                /\b(viagra|cialis|casino|poker)\b/i,
                /\b(click here|free money)\b/i
            ];
            
            for (let pattern of suspiciousPatterns) {
                if (pattern.test(message) || pattern.test(subject)) {
                    e.preventDefault();
                    alert('Your message contains content that is not allowed');
                    return;
                }
            }
            
            // Disable submit button to prevent double submission
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Sending...';
        });
    }
    
    // Security: Prevent form resubmission on page refresh
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }
});
</script>

<?php include 'templates/footer.php'; ?>