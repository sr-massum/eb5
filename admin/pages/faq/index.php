<?php 

/**
 * ExchangeBridge - Admin Panel FAQ Page
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
require_once '../../../config/config.php';
require_once '../../../config/verification.php';
require_once '../../../config/license.php';
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/app.php';
require_once '../../../includes/auth.php';
require_once '../../../includes/security.php';

// Set UTF-8 encoding headers
header('Content-Type: text/html; charset=UTF-8');
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// Check if user is logged in
if (!Auth::isLoggedIn()) {
    header("Location: " . ADMIN_URL . "/login.php");
    exit;
}

$user = Auth::getUser();
$db = Database::getInstance();

// Set MySQL connection to UTF-8
$db->query("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");

// Handle form submissions
if ($_POST && isset($_POST['action'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        
        if ($_POST['action'] === 'update_faq_page') {
            // Update page content and SEO
            $title = sanitizeInput($_POST['title']);
            $meta_title = sanitizeInput($_POST['meta_title']);
            $meta_description = sanitizeInput($_POST['meta_description']);
            $meta_keywords = sanitizeInput($_POST['meta_keywords']);
            
            // Handle featured image upload for SEO
            $featured_image = '';
            if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                $uploadResult = uploadFile($_FILES['featured_image'], '../../../assets/uploads/seo/', ['jpg', 'jpeg', 'png', 'webp'], 2 * 1024 * 1024);
                if ($uploadResult['success']) {
                    $featured_image = $uploadResult['filename'];
                } else {
                    $_SESSION['error_message'] = 'Image upload failed: ' . $uploadResult['message'];
                }
            }
            
            $updateData = [
                'title' => $title,
                'meta_title' => $meta_title,
                'meta_description' => $meta_description,
                'meta_keywords' => $meta_keywords,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($featured_image) {
                $updateData['featured_image'] = $featured_image;
            }
            
            $result = $db->update('pages', $updateData, 'slug = ?', ['faq']);
            
            if ($result && !isset($_SESSION['error_message'])) {
                $_SESSION['success_message'] = 'FAQ page updated successfully!';
            } elseif (!isset($_SESSION['error_message'])) {
                $_SESSION['error_message'] = 'Failed to update FAQ page.';
            }
            
        } elseif ($_POST['action'] === 'update_faqs') {
            // Update FAQ questions and answers
            $questions = $_POST['questions'] ?? [];
            $answers = $_POST['answers'] ?? [];
            $ids = $_POST['faq_ids'] ?? [];
            
            $faqContent = '';
            $faqHtml = '<div class="space-y-6">';
            
            for ($i = 0; $i < count($questions); $i++) {
                if (!empty($questions[$i]) && !empty($answers[$i])) {
                    $question = sanitizeInput($questions[$i]);
                    $answer = sanitizeInput($answers[$i]);
                    
                    $faqHtml .= '<div>';
                    $faqHtml .= '<h3 class="text-lg font-semibold">' . $question . '</h3>';
                    $faqHtml .= '<p>' . nl2br($answer) . '</p>';
                    $faqHtml .= '</div>';
                }
            }
            
            $faqHtml .= '</div>';
            
            // Update page content with new FAQ content
            $result = $db->update('pages', ['content' => $faqHtml, 'updated_at' => date('Y-m-d H:i:s')], 'slug = ?', ['faq']);
            
            if ($result) {
                $_SESSION['success_message'] = 'FAQ content updated successfully!';
            } else {
                $_SESSION['error_message'] = 'Failed to update FAQ content.';
            }
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } else {
        $_SESSION['error_message'] = 'Invalid security token. Please try again.';
    }
}

// Get FAQ page content
$faqPage = getPageBySlug('faq');
if (!$faqPage) {
    // Create default FAQ page
    $db->insert('pages', [
        'slug' => 'faq',
        'title' => 'Frequently Asked Questions',
        'content' => '',
        'meta_title' => 'FAQ - ' . getSetting('site_name', 'Exchange Bridge'),
        'meta_description' => 'Find answers to frequently asked questions about our services.',
        'status' => 'active'
    ]);
    $faqPage = getPageBySlug('faq');
}

// Parse existing FAQ content
$existingFaqs = [];
if (!empty($faqPage['content'])) {
    // Extract Q&A from HTML content
    preg_match_all('/<h3[^>]*>(.*?)<\/h3>\s*<p>(.*?)<\/p>/s', $faqPage['content'], $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        $existingFaqs[] = [
            'question' => strip_tags($match[1]),
            'answer' => strip_tags(str_replace('<br>', "\n", $match[2]))
        ];
    }
}

// If no existing FAQs, set default ones
if (empty($existingFaqs)) {
    $existingFaqs = [
        ['question' => 'What is ' . getSetting('site_name', 'Exchange Bridge') . '?', 'answer' => 'We are a platform that allows you to exchange various currencies and payment methods quickly and securely.'],
        ['question' => 'How long does an exchange take?', 'answer' => 'Most exchanges are processed within 5-30 minutes after payment confirmation.'],
        ['question' => 'What are your fees?', 'answer' => 'Our fees are built into the exchange rates. We don\'t charge any additional fees on top of the displayed exchange rate.'],
        ['question' => 'How do I track my exchange?', 'answer' => 'After submitting an exchange, you\'ll receive a unique reference ID. You can use this ID to track your exchange status.'],
        ['question' => 'How can I contact support?', 'answer' => 'You can contact our support team via WhatsApp at ' . getSetting('contact_phone', '+8801869838872') . ' or email at ' . getSetting('contact_email', 'support@exchangebridge.com') . '.']
    ];
}

// Check for messages
$successMessage = '';
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$errorMessage = '';
if (isset($_SESSION['error_message'])) {
    $errorMessage = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Include header
include '../../includes/header.php';
?>

<style>
.page-content {
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', Arial, sans-serif !important;
    line-height: 1.8 !important;
    font-weight: 400;
}

.form-control {
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', Arial, sans-serif !important;
    font-size: 16px !important;
    line-height: 1.8 !important;
}

.bengali-input {
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', Arial, sans-serif !important;
    font-size: 16px !important;
    line-height: 1.8 !important;
}

.faq-item {
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 15px;
    background-color: #f8f9fa;
}

.faq-item:hover {
    background-color: #e9ecef;
}

.featured-image-preview {
    max-width: 300px;
    max-height: 200px;
    border-radius: 8px;
    margin-top: 10px;
}
</style>

<!-- Breadcrumbs -->
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="<?php echo ADMIN_URL; ?>/index.php">Dashboard</a>
    </li>
    <li class="breadcrumb-item">
        <a href="<?php echo ADMIN_URL; ?>/pages/">Pages</a>
    </li>
    <li class="breadcrumb-item active">Edit FAQ Page</li>
</ol>

<!-- Page Content -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-question-circle mr-1"></i> Edit FAQ Page
        <a href="<?php echo SITE_URL; ?>/faq.php" target="_blank" class="btn btn-info btn-sm float-right ml-2">
            <i class="fas fa-external-link-alt"></i> Preview Page
        </a>
        <a href="<?php echo ADMIN_URL; ?>/pages/" class="btn btn-secondary btn-sm float-right">
            <i class="fas fa-arrow-left"></i> Back to Pages
        </a>
    </div>
    <div class="card-body">
        <?php if (!empty($successMessage)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <!-- Page Settings Form -->
        <form method="POST" action="" enctype="multipart/form-data" accept-charset="UTF-8" id="faqPageForm">
            <input type="hidden" name="action" value="update_faq_page">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-cog mr-2"></i>Page Settings (পেইজ সেটিংস)</h6>
                </div>
                <div class="card-body">
                    <div class="form-group">
                        <label for="title">Page Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="title" class="form-control page-content bengali-input" 
                               value="<?php echo htmlspecialchars($faqPage['title'] ?? 'Frequently Asked Questions', ENT_QUOTES, 'UTF-8'); ?>" 
                               required placeholder="Enter page title">
                    </div>
                    
                    <!-- SEO Settings -->
                    <div class="row">
                        <div class="col-md-8">
                            <div class="form-group">
                                <label for="meta_title">Meta Title</label>
                                <input type="text" name="meta_title" id="meta_title" class="form-control page-content bengali-input" 
                                       value="<?php echo htmlspecialchars($faqPage['meta_title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                       placeholder="FAQ - Your Site Name">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="featured_image">Featured Image (SEO Only)</label>
                                <input type="file" name="featured_image" id="featured_image" class="form-control-file" accept="image/*">
                                <small class="form-text text-muted">For SEO/Social Media. Max: 2MB.</small>
                                <?php if (!empty($faqPage['featured_image'])): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo SITE_URL; ?>/assets/uploads/seo/<?php echo htmlspecialchars($faqPage['featured_image']); ?>" 
                                             alt="Current Featured Image" class="featured-image-preview img-thumbnail">
                                        <br><small class="text-muted">Current SEO image</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="meta_description">Meta Description</label>
                        <textarea name="meta_description" id="meta_description" class="form-control page-content bengali-input" 
                                  rows="3" placeholder="Find answers to frequently asked questions"><?php echo htmlspecialchars($faqPage['meta_description'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="meta_keywords">Meta Keywords</label>
                        <input type="text" name="meta_keywords" id="meta_keywords" class="form-control page-content bengali-input" 
                               value="<?php echo htmlspecialchars($faqPage['meta_keywords'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                               placeholder="faq, questions, answers, help, support">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>Update Page Settings
                    </button>
                </div>
            </div>
        </form>

        <!-- FAQ Content Form -->
        <form method="POST" action="" accept-charset="UTF-8" id="faqContentForm">
            <input type="hidden" name="action" value="update_faqs">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-list mr-2"></i>FAQ Questions & Answers (প্রশ্ন ও উত্তর)</h6>
                    <button type="button" class="btn btn-success btn-sm float-right" onclick="addFaqItem()">
                        <i class="fas fa-plus mr-1"></i>Add New FAQ
                    </button>
                </div>
                <div class="card-body">
                    <div id="faq-container">
                        <?php foreach ($existingFaqs as $index => $faq): ?>
                        <div class="faq-item" data-index="<?php echo $index; ?>">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h6 class="mb-0">FAQ #<?php echo $index + 1; ?></h6>
                                <button type="button" class="btn btn-danger btn-sm" onclick="removeFaqItem(this)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            
                            <div class="form-group">
                                <label>Question (প্রশ্ন) <span class="text-danger">*</span></label>
                                <input type="text" name="questions[]" class="form-control page-content bengali-input" 
                                       value="<?php echo htmlspecialchars($faq['question'], ENT_QUOTES, 'UTF-8'); ?>" 
                                       required placeholder="Enter your question here">
                            </div>
                            
                            <div class="form-group">
                                <label>Answer (উত্তর) <span class="text-danger">*</span></label>
                                <textarea name="answers[]" class="form-control page-content bengali-input" rows="4" 
                                          required placeholder="Enter the answer here"><?php echo htmlspecialchars($faq['answer'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save mr-2"></i>Update FAQ Content
                        </button>
                        <button type="button" class="btn btn-success btn-lg ml-2" onclick="addFaqItem()">
                            <i class="fas fa-plus mr-2"></i>Add New FAQ
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
let faqCounter = <?php echo count($existingFaqs); ?>;

function addFaqItem() {
    faqCounter++;
    
    const faqHtml = `
        <div class="faq-item" data-index="${faqCounter}">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h6 class="mb-0">FAQ #${faqCounter}</h6>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeFaqItem(this)">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            
            <div class="form-group">
                <label>Question (প্রশ্ন) <span class="text-danger">*</span></label>
                <input type="text" name="questions[]" class="form-control page-content bengali-input" 
                       required placeholder="Enter your question here">
            </div>
            
            <div class="form-group">
                <label>Answer (উত্তর) <span class="text-danger">*</span></label>
                <textarea name="answers[]" class="form-control page-content bengali-input" rows="4" 
                          required placeholder="Enter the answer here"></textarea>
            </div>
        </div>
    `;
    
    document.getElementById('faq-container').insertAdjacentHTML('beforeend', faqHtml);
    updateFaqNumbers();
}

function removeFaqItem(button) {
    if (document.querySelectorAll('.faq-item').length <= 1) {
        alert('আপনার কমপক্ষে একটি FAQ রাখতে হবে।');
        return;
    }
    
    button.closest('.faq-item').remove();
    updateFaqNumbers();
}

function updateFaqNumbers() {
    const faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach((item, index) => {
        const heading = item.querySelector('h6');
        heading.textContent = `FAQ #${index + 1}`;
        item.setAttribute('data-index', index);
    });
}

// Form validation
document.getElementById('faqContentForm').addEventListener('submit', function(e) {
    const questions = document.getElementsByName('questions[]');
    const answers = document.getElementsByName('answers[]');
    
    let hasEmptyFields = false;
    
    for (let i = 0; i < questions.length; i++) {
        if (!questions[i].value.trim() || !answers[i].value.trim()) {
            hasEmptyFields = true;
            break;
        }
    }
    
    if (hasEmptyFields) {
        e.preventDefault();
        alert('অনুগ্রহ করে সকল প্রশ্ন ও উত্তর পূরণ করুন।');
        return false;
    }
});

document.getElementById('faqPageForm').addEventListener('submit', function(e) {
    const title = document.getElementById('title').value.trim();
    
    if (!title) {
        e.preventDefault();
        alert('অনুগ্রহ করে পেইজ টাইটেল দিন।');
        return false;
    }
});
</script>

<?php include '../../includes/footer.php'; ?>