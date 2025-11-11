<?php 

/**
 * ExchangeBridge - Write Review Form
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


// Initialize security class
$security = Security::getInstance();

// Check for banned IPs
$security->checkBanStatus();

// Rate limiting for review submissions (5 submissions per 15 minutes)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$security->checkRateLimit('review_submission', 5, 900)) {
        $_SESSION['error_message'] = 'Too many submissions. Please wait 15 minutes before submitting another review.';
        header("Location: write-review.php");
        exit;
    }
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token validation
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!$security->verifyCSRFToken($csrfToken)) {
        $_SESSION['error_message'] = 'Security token validation failed. Please try again.';
        header("Location: write-review.php");
        exit;
    }
    
    // Get and sanitize form data
    $name = $security->sanitizeInput($_POST['name'] ?? '', 'string');
    $email = $security->sanitizeInput($_POST['email'] ?? '', 'email');
    $rating = $security->sanitizeInput($_POST['rating'] ?? 5, 'int');
    $message = $security->antiXSS($_POST['message'] ?? '');
    $fromCurrency = $security->sanitizeInput($_POST['from_currency'] ?? '', 'alphanum');
    $toCurrency = $security->sanitizeInput($_POST['to_currency'] ?? '', 'alphanum');
    
    // Advanced input validation
    $validationRules = [
        'name' => [
            'required' => true,
            'min_length' => 2,
            'max_length' => 100,
            'pattern' => '/^[a-zA-Z\s\.\-\']+$/',
            'pattern_message' => 'Name can only contain letters, spaces, dots, hyphens and apostrophes'
        ],
        'email' => [
            'type' => 'email',
            'max_length' => 255
        ],
        'rating' => [
            'required' => true,
            'type' => 'int',
            'custom' => function($value) {
                return ($value >= 1 && $value <= 5) ? true : 'Rating must be between 1 and 5';
            }
        ],
        'message' => [
            'required' => true,
            'min_length' => 10,
            'max_length' => 1000,
            'custom' => function($value) {
                // Check for spam patterns
                $spamPatterns = [
                    '/\b(buy|click|visit|download)\s+(now|here|this)\b/i',
                    '/\b(free|cheap|discount|offer|deal)\s+(money|cash|price)\b/i',
                    '/https?:\/\/[^\s]+/i', // No URLs allowed
                    '/\b\d{10,}\b/', // No long numbers (phone/card numbers)
                ];
                
                foreach ($spamPatterns as $pattern) {
                    if (preg_match($pattern, $value)) {
                        return 'Message contains prohibited content';
                    }
                }
                return true;
            }
        ],
        'from_currency' => [
            'max_length' => 10,
            'pattern' => '/^[A-Z0-9]*$/',
            'pattern_message' => 'Invalid currency code format'
        ],
        'to_currency' => [
            'max_length' => 10,
            'pattern' => '/^[A-Z0-9]*$/',
            'pattern_message' => 'Invalid currency code format'
        ]
    ];
    
    $formData = [
        'name' => $name,
        'email' => $email,
        'rating' => $rating,
        'message' => $message,
        'from_currency' => $fromCurrency,
        'to_currency' => $toCurrency
    ];
    
    $validationErrors = $security->validateInput($formData, $validationRules);
    
    // Additional security checks
    if (empty($validationErrors)) {
        // Check for duplicate submissions (same name + message in last 24 hours)
        $db = Database::getInstance();
        $duplicateCheck = $db->getValue(
            "SELECT COUNT(*) FROM testimonials WHERE name = ? AND message = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)",
            [$name, $message]
        );
        
        if ($duplicateCheck > 0) {
            $_SESSION['error_message'] = 'You have already submitted a similar review recently. Please wait 24 hours before submitting again.';
        } else {
            // Validate currency codes if provided
            if (!empty($fromCurrency) || !empty($toCurrency)) {
                $validCurrencies = $db->getRows("SELECT code FROM currencies WHERE status = 'active'");
                $validCodes = array_column($validCurrencies, 'code');
                
                if (!empty($fromCurrency) && !in_array($fromCurrency, $validCodes)) {
                    $validationErrors['from_currency'] = 'Invalid from currency selected';
                }
                
                if (!empty($toCurrency) && !in_array($toCurrency, $validCodes)) {
                    $validationErrors['to_currency'] = 'Invalid to currency selected';
                }
            }
        }
    }
    
    // Process form if no errors
    if (empty($validationErrors) && empty($_SESSION['error_message'])) {
        try {
            // Insert testimonial with pending status
            $db = Database::getInstance();
            $testimonialId = $db->insert('testimonials', [
                'name' => $name,
                'email' => !empty($email) ? $email : null,
                'rating' => $rating,
                'message' => $message,
                'from_currency' => !empty($fromCurrency) ? $fromCurrency : null,
                'to_currency' => !empty($toCurrency) ? $toCurrency : null,
                'status' => 'inactive' // Set to inactive for admin approval
            ]);
            
            if ($testimonialId) {
                // Log successful submission
                $security->logSecurityEvent('REVIEW_SUBMITTED', 
                    "Review submitted by: $name from IP: " . $security->getClientIp());
                
                $_SESSION['success_message'] = 'ধন্যবাদ! আপনার রিভিউ সফলভাবে জমা হয়েছে। এটি অনুমোদনের পর প্রকাশিত হবে।';
                
                // Clear form data from session to prevent resubmission
                unset($_SESSION['form_data']);
            } else {
                $_SESSION['error_message'] = 'রিভিউ জমা দিতে সমস্যা হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।';
            }
            
        } catch (Exception $e) {
            // Log the error
            error_log("Review submission error: " . $e->getMessage());
            $security->logSecurityEvent('REVIEW_SUBMISSION_ERROR', 
                "Review submission failed: " . $e->getMessage());
            
            $_SESSION['error_message'] = 'একটি অপ্রত্যাশিত ত্রুটি ঘটেছে। অনুগ্রহ করে পরে আবার চেষ্টা করুন।';
        }
    } else {
        // Store validation errors
        if (!empty($validationErrors)) {
            $_SESSION['validation_errors'] = $validationErrors;
            $_SESSION['form_data'] = $formData; // Preserve form data
        }
    }
    
    // Redirect back to form
    header("Location: write-review.php");
    exit;
}

// Get all currencies for the form (with security validation)
try {
    $currencies = getAllCurrencies();
    // Validate currencies data
    $currencies = array_filter($currencies, function($currency) {
        return isset($currency['code'], $currency['name']) && 
               !empty($currency['code']) && 
               !empty($currency['name']);
    });
} catch (Exception $e) {
    $currencies = [];
    error_log("Error loading currencies: " . $e->getMessage());
}

// Get form data from session (for repopulating form after validation errors)
$formData = $_SESSION['form_data'] ?? [];
$validationErrors = $_SESSION['validation_errors'] ?? [];

// Clear session data
unset($_SESSION['form_data'], $_SESSION['validation_errors']);

// Include header
include 'templates/header.php';
?>

<!-- Main Content -->
<main class="flex-grow container mx-auto p-4 md:p-6">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            <div class="text-center mb-6">
                <h1 class="text-2xl font-bold mb-2">Write a Review</h1>
                <p class="text-gray-600 dark:text-gray-400">Share your experience with Exchange Bridge</p>
            </div>
            
            <!-- Success Message -->
            <?php if (isset($_SESSION['success_message'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Error Message -->
            <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                </div>
            </div>
            <?php endif; ?>
            
            <form action="write-review.php" method="POST" class="space-y-4" id="reviewForm">
                <!-- CSRF Protection -->
                <input type="hidden" name="csrf_token" value="<?php echo $security->generateCSRFToken(); ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" required maxlength="100"
                               value="<?php echo htmlspecialchars($formData['name'] ?? ''); ?>"
                               class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md py-2 px-3 text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary <?php echo isset($validationErrors['name']) ? 'border-red-500' : ''; ?>">
                        <?php if (isset($validationErrors['name'])): ?>
                        <p class="text-red-500 text-xs mt-1"><?php echo $validationErrors['name']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Email (Optional)
                        </label>
                        <input type="email" id="email" name="email" maxlength="255"
                               value="<?php echo htmlspecialchars($formData['email'] ?? ''); ?>"
                               class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md py-2 px-3 text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary <?php echo isset($validationErrors['email']) ? 'border-red-500' : ''; ?>">
                        <?php if (isset($validationErrors['email'])): ?>
                        <p class="text-red-500 text-xs mt-1"><?php echo $validationErrors['email']; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div>
                    <label for="rating" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Rating <span class="text-red-500">*</span>
                    </label>
                    <div class="flex items-center space-x-1" id="star-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <button type="button" class="star-btn text-2xl text-gray-300 hover:text-yellow-400 focus:outline-none" data-rating="<?php echo $i; ?>">
                            <i class="fas fa-star"></i>
                        </button>
                        <?php endfor; ?>
                    </div>
                    <input type="hidden" id="rating" name="rating" value="<?php echo $formData['rating'] ?? 5; ?>">
                    <small class="text-gray-600 dark:text-gray-400">Click stars to rate your experience</small>
                    <?php if (isset($validationErrors['rating'])): ?>
                    <p class="text-red-500 text-xs mt-1"><?php echo $validationErrors['rating']; ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="from_currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            From Currency (Optional)
                        </label>
                        <select id="from_currency" name="from_currency" 
                                class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md py-2 px-3 text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary <?php echo isset($validationErrors['from_currency']) ? 'border-red-500' : ''; ?>">
                            <option value="">Select Currency</option>
                            <?php foreach ($currencies as $currency): ?>
                            <option value="<?php echo htmlspecialchars($currency['code']); ?>" 
                                    <?php echo ($formData['from_currency'] ?? '') === $currency['code'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($currency['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($validationErrors['from_currency'])): ?>
                        <p class="text-red-500 text-xs mt-1"><?php echo $validationErrors['from_currency']; ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <div>
                        <label for="to_currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            To Currency (Optional)
                        </label>
                        <select id="to_currency" name="to_currency" 
                                class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md py-2 px-3 text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary <?php echo isset($validationErrors['to_currency']) ? 'border-red-500' : ''; ?>">
                            <option value="">Select Currency</option>
                            <?php foreach ($currencies as $currency): ?>
                            <option value="<?php echo htmlspecialchars($currency['code']); ?>"
                                    <?php echo ($formData['to_currency'] ?? '') === $currency['code'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($currency['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($validationErrors['to_currency'])): ?>
                        <p class="text-red-500 text-xs mt-1"><?php echo $validationErrors['to_currency']; ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div>
                    <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Your Review <span class="text-red-500">*</span>
                    </label>
                    <textarea id="message" name="message" rows="5" required maxlength="1000"
                              placeholder="Share your experience with our exchange service... (10-1000 characters)"
                              class="w-full bg-gray-50 dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md py-2 px-3 text-gray-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary <?php echo isset($validationErrors['message']) ? 'border-red-500' : ''; ?>"><?php echo htmlspecialchars($formData['message'] ?? ''); ?></textarea>
                    <small class="text-gray-600 dark:text-gray-400">
                        <span id="charCount">0</span>/1000 characters (minimum 10 required)
                    </small>
                    <?php if (isset($validationErrors['message'])): ?>
                    <p class="text-red-500 text-xs mt-1"><?php echo $validationErrors['message']; ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="flex justify-between items-center pt-4">
                    <a href="index.php" class="text-gray-600 dark:text-gray-400 hover:text-primary">
                        <i class="fas fa-arrow-left mr-1"></i> Back to Home
                    </a>
                    <button type="submit" id="submitBtn" class="bg-primary hover:bg-blue-700 text-white px-6 py-2 rounded-md transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-paper-plane mr-1"></i> Submit Review
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const stars = document.querySelectorAll('.star-btn');
    const ratingInput = document.getElementById('rating');
    const messageTextarea = document.getElementById('message');
    const charCount = document.getElementById('charCount');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('reviewForm');
    
    let currentRating = <?php echo $formData['rating'] ?? 5; ?>;
    
    // Set initial rating display
    updateStarDisplay(currentRating);
    updateCharCount();
    
    // Star rating functionality
    stars.forEach(star => {
        star.addEventListener('click', function() {
            currentRating = parseInt(this.dataset.rating);
            ratingInput.value = currentRating;
            updateStarDisplay(currentRating);
        });
        
        star.addEventListener('mouseenter', function() {
            const hoverRating = parseInt(this.dataset.rating);
            updateStarDisplay(hoverRating);
        });
    });
    
    document.getElementById('star-rating').addEventListener('mouseleave', function() {
        updateStarDisplay(currentRating);
    });
    
    // Character count functionality
    messageTextarea.addEventListener('input', updateCharCount);
    
    // Form validation
    form.addEventListener('submit', function(e) {
        const message = messageTextarea.value.trim();
        const name = document.getElementById('name').value.trim();
        
        if (name.length < 2) {
            e.preventDefault();
            alert('Name must be at least 2 characters long');
            return;
        }
        
        if (message.length < 10) {
            e.preventDefault();
            alert('Review message must be at least 10 characters long');
            return;
        }
        
        if (message.length > 1000) {
            e.preventDefault();
            alert('Review message must not exceed 1000 characters');
            return;
        }
        
        // Disable submit button to prevent double submission
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Submitting...';
    });
    
    function updateStarDisplay(rating) {
        stars.forEach((star, index) => {
            if (index < rating) {
                star.classList.remove('text-gray-300');
                star.classList.add('text-yellow-400');
            } else {
                star.classList.remove('text-yellow-400');
                star.classList.add('text-gray-300');
            }
        });
    }
    
    function updateCharCount() {
        const length = messageTextarea.value.length;
        charCount.textContent = length;
        
        // Update color based on length
        if (length < 10) {
            charCount.className = 'text-red-500';
        } else if (length > 900) {
            charCount.className = 'text-yellow-500';
        } else {
            charCount.className = 'text-green-500';
        }
        
        // Enable/disable submit button based on message length
        const isValid = length >= 10 && length <= 1000;
        submitBtn.disabled = !isValid;
    }
    
    // Auto-resize textarea
    messageTextarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = this.scrollHeight + 'px';
    });
});
</script>

<?php include 'templates/footer.php'; ?>