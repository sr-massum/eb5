<?php 

/**
 * ExchangeBridge - Admin Panel Exchange Wizard
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

// Check if user has admin role for certain settings
$isAdmin = Auth::isAdmin();

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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process each setting
    $settings = [
        // Step 1 - Exchange Form Settings
        'wizard_title' => isset($_POST['wizard_title']) ? sanitizeInput($_POST['wizard_title']) : '',
        'wizard_subtitle' => isset($_POST['wizard_subtitle']) ? sanitizeInput($_POST['wizard_subtitle']) : '',
        'wizard_heading' => isset($_POST['wizard_heading']) ? sanitizeInput($_POST['wizard_heading']) : '',
        'wizard_footer_text' => isset($_POST['wizard_footer_text']) ? sanitizeInput($_POST['wizard_footer_text']) : '',
        'send_section_label' => isset($_POST['send_section_label']) ? sanitizeInput($_POST['send_section_label']) : '',
        'receive_section_label' => isset($_POST['receive_section_label']) ? sanitizeInput($_POST['receive_section_label']) : '',
        'currency_select_label' => isset($_POST['currency_select_label']) ? sanitizeInput($_POST['currency_select_label']) : '',
        'amount_input_label' => isset($_POST['amount_input_label']) ? sanitizeInput($_POST['amount_input_label']) : '',
        'receive_amount_label' => isset($_POST['receive_amount_label']) ? sanitizeInput($_POST['receive_amount_label']) : '',
        'continue_button_text' => isset($_POST['continue_button_text']) ? sanitizeInput($_POST['continue_button_text']) : '',
        
        // Step 2 - Contact Information Settings
        'contact_step_title' => isset($_POST['contact_step_title']) ? sanitizeInput($_POST['contact_step_title']) : '',
        'name_field_label' => isset($_POST['name_field_label']) ? sanitizeInput($_POST['name_field_label']) : '',
        'email_field_label' => isset($_POST['email_field_label']) ? sanitizeInput($_POST['email_field_label']) : '',
        'phone_field_label' => isset($_POST['phone_field_label']) ? sanitizeInput($_POST['phone_field_label']) : '',
        'address_field_label' => isset($_POST['address_field_label']) ? sanitizeInput($_POST['address_field_label']) : '',
        'address_help_text' => isset($_POST['address_help_text']) ? sanitizeInput($_POST['address_help_text']) : '',
        'back_button_text' => isset($_POST['back_button_text']) ? sanitizeInput($_POST['back_button_text']) : '',
        'continue_step2_text' => isset($_POST['continue_step2_text']) ? sanitizeInput($_POST['continue_step2_text']) : '',
        
        // Step 3 - Confirmation Settings
        'confirmation_title' => isset($_POST['confirmation_title']) ? sanitizeInput($_POST['confirmation_title']) : '',
        'reference_id_title' => isset($_POST['reference_id_title']) ? sanitizeInput($_POST['reference_id_title']) : '',
        'reference_id_message' => isset($_POST['reference_id_message']) ? sanitizeInput($_POST['reference_id_message']) : '',
        'exchange_details_title' => isset($_POST['exchange_details_title']) ? sanitizeInput($_POST['exchange_details_title']) : '',
        'payment_details_title' => isset($_POST['payment_details_title']) ? sanitizeInput($_POST['payment_details_title']) : '',
        'payment_instruction' => isset($_POST['payment_instruction']) ? sanitizeInput($_POST['payment_instruction']) : '',
        'after_payment_message' => isset($_POST['after_payment_message']) ? sanitizeInput($_POST['after_payment_message']) : '',
        'next_steps_title' => isset($_POST['next_steps_title']) ? sanitizeInput($_POST['next_steps_title']) : '',
        'whatsapp_contact_message' => isset($_POST['whatsapp_contact_message']) ? sanitizeInput($_POST['whatsapp_contact_message']) : '',
        'whatsapp_button_text' => isset($_POST['whatsapp_button_text']) ? sanitizeInput($_POST['whatsapp_button_text']) : '',
        'final_instruction' => isset($_POST['final_instruction']) ? sanitizeInput($_POST['final_instruction']) : '',
        'view_receipt_text' => isset($_POST['view_receipt_text']) ? sanitizeInput($_POST['view_receipt_text']) : '',
        'complete_button_text' => isset($_POST['complete_button_text']) ? sanitizeInput($_POST['complete_button_text']) : '',
        'next_todo_text' => isset($_POST['next_todo_text']) ? sanitizeInput($_POST['next_todo_text']) : '',
        
        // Form Labels
        'send_label_text' => isset($_POST['send_label_text']) ? sanitizeInput($_POST['send_label_text']) : '',
        'receive_label_text' => isset($_POST['receive_label_text']) ? sanitizeInput($_POST['receive_label_text']) : '',
        'rate_label_text' => isset($_POST['rate_label_text']) ? sanitizeInput($_POST['rate_label_text']) : '',
        'datetime_label_text' => isset($_POST['datetime_label_text']) ? sanitizeInput($_POST['datetime_label_text']) : '',
        'status_label_text' => isset($_POST['status_label_text']) ? sanitizeInput($_POST['status_label_text']) : '',
        
        // Status Texts
        'pending_status' => isset($_POST['pending_status']) ? sanitizeInput($_POST['pending_status']) : '',
        'confirmed_status' => isset($_POST['confirmed_status']) ? sanitizeInput($_POST['confirmed_status']) : '',
        'cancelled_status' => isset($_POST['cancelled_status']) ? sanitizeInput($_POST['cancelled_status']) : '',
        
        // Validation Messages
        'min_amount_error' => isset($_POST['min_amount_error']) ? sanitizeInput($_POST['min_amount_error']) : '',
        'invalid_email_error' => isset($_POST['invalid_email_error']) ? sanitizeInput($_POST['invalid_email_error']) : '',
        'required_fields_error' => isset($_POST['required_fields_error']) ? sanitizeInput($_POST['required_fields_error']) : '',
        'rate_unavailable_error' => isset($_POST['rate_unavailable_error']) ? sanitizeInput($_POST['rate_unavailable_error']) : '',
        'amount_required_error' => isset($_POST['amount_required_error']) ? sanitizeInput($_POST['amount_required_error']) : '',
        
        // Success Messages
        'exchange_success_message' => isset($_POST['exchange_success_message']) ? sanitizeInput($_POST['exchange_success_message']) : '',
        'copy_success_message' => isset($_POST['copy_success_message']) ? sanitizeInput($_POST['copy_success_message']) : '',
        
        // Style Settings
        'wizard_font_family' => isset($_POST['wizard_font_family']) ? sanitizeInput($_POST['wizard_font_family']) : '',
        'wizard_primary_color' => isset($_POST['wizard_primary_color']) ? sanitizeInput($_POST['wizard_primary_color']) : '',
        'wizard_progress_bar_color' => isset($_POST['wizard_progress_bar_color']) ? sanitizeInput($_POST['wizard_progress_bar_color']) : '',
        'wizard_border_radius' => isset($_POST['wizard_border_radius']) ? sanitizeInput($_POST['wizard_border_radius']) : '',
        
        // Feature Settings
        'enable_animations' => isset($_POST['enable_animations']) ? 'yes' : 'no',
        'enable_sound_effects' => isset($_POST['enable_sound_effects']) ? 'yes' : 'no',
        'auto_save_progress' => isset($_POST['auto_save_progress']) ? 'yes' : 'no',
        
        // Exchange Settings
        'minimum_exchange_amount' => isset($_POST['minimum_exchange_amount']) ? (float)$_POST['minimum_exchange_amount'] : 5
    ];
    
    // Update each setting
    $db = Database::getInstance();
    $success = true;
    
    foreach ($settings as $key => $value) {
        if (!updateSetting($key, $value)) {
            $success = false;
        }
    }
    
    if ($success) {
        $_SESSION['success_message'] = 'Exchange Wizard settings updated successfully';
    } else {
        $_SESSION['error_message'] = 'Failed to update some settings';
    }
    
    // Redirect to refresh page
    header("Location: index.php");
    exit;
}

// Include header
include '../includes/header.php';
?>

<!-- Breadcrumbs-->
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="../index.php">Dashboard</a>
    </li>
    <li class="breadcrumb-item active">Exchange Wizard</li>
</ol>

<!-- Page Content -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-magic mr-1"></i> Exchange Wizard Settings
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
        
        <form action="index.php" method="post">
            <ul class="nav nav-tabs" id="wizardTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="step1-tab" data-toggle="tab" href="#step1" role="tab" aria-controls="step1" aria-selected="true">
                        <i class="fas fa-exchange-alt mr-1"></i> Step 1 - Exchange Form
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="step2-tab" data-toggle="tab" href="#step2" role="tab" aria-controls="step2" aria-selected="false">
                        <i class="fas fa-user mr-1"></i> Step 2 - Contact Info
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="step3-tab" data-toggle="tab" href="#step3" role="tab" aria-controls="step3" aria-selected="false">
                        <i class="fas fa-check-circle mr-1"></i> Step 3 - Confirmation
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="labels-tab" data-toggle="tab" href="#labels" role="tab" aria-controls="labels" aria-selected="false">
                        <i class="fas fa-tags mr-1"></i> Labels & Messages
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="styling-tab" data-toggle="tab" href="#styling" role="tab" aria-controls="styling" aria-selected="false">
                        <i class="fas fa-paint-brush mr-1"></i> Styling & Features
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="exchange-settings-tab" data-toggle="tab" href="#exchange-settings" role="tab" aria-controls="exchange-settings" aria-selected="false">
                        <i class="fas fa-cog mr-1"></i> Exchange Settings
                    </a>
                </li>
            </ul>
            
            <div class="tab-content mt-4" id="wizardTabContent">
                <!-- Step 1 Settings -->
                <div class="tab-pane fade show active" id="step1" role="tabpanel" aria-labelledby="step1-tab">
                    <h5 class="mb-3"><i class="fas fa-exchange-alt text-primary mr-2"></i>Step 1 - Exchange Form Settings</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="wizard_title">Wizard Title</label>
                                <input type="text" class="form-control" id="wizard_title" name="wizard_title" value="<?php echo htmlspecialchars(getSetting('wizard_title', 'Fast Exchange in Minutes')); ?>">
                                <small class="form-text text-muted">Main heading at the top of the wizard</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="wizard_subtitle">Wizard Subtitle</label>
                                <input type="text" class="form-control" id="wizard_subtitle" name="wizard_subtitle" value="<?php echo htmlspecialchars(getSetting('wizard_subtitle', 'Minimum Exchange $5 Dollar')); ?>">
                                <small class="form-text text-muted">Subtitle text displayed below the main title</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="wizard_heading">Exchange Section Heading</label>
                                <input type="text" class="form-control" id="wizard_heading" name="wizard_heading" value="<?php echo htmlspecialchars(getSetting('wizard_heading', 'Start Exchange')); ?>">
                                <small class="form-text text-muted">Heading above the exchange form</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="send_section_label">Send Section Label</label>
                                <input type="text" class="form-control" id="send_section_label" name="send_section_label" value="<?php echo htmlspecialchars(getSetting('send_section_label', 'SEND')); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="receive_section_label">Receive Section Label</label>
                                <input type="text" class="form-control" id="receive_section_label" name="receive_section_label" value="<?php echo htmlspecialchars(getSetting('receive_section_label', 'RECEIVE')); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="currency_select_label">Currency Selection Label</label>
                                <input type="text" class="form-control" id="currency_select_label" name="currency_select_label" value="<?php echo htmlspecialchars(getSetting('currency_select_label', 'Select Exchange Currency')); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="amount_input_label">Amount Input Label</label>
                                <input type="text" class="form-control" id="amount_input_label" name="amount_input_label" value="<?php echo htmlspecialchars(getSetting('amount_input_label', 'Enter Exchange Amount')); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="receive_amount_label">Receive Amount Label</label>
                                <input type="text" class="form-control" id="receive_amount_label" name="receive_amount_label" value="<?php echo htmlspecialchars(getSetting('receive_amount_label', 'You\'ll Get this Amount')); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="continue_button_text">Continue Button Text</label>
                                <input type="text" class="form-control" id="continue_button_text" name="continue_button_text" value="<?php echo htmlspecialchars(getSetting('continue_button_text', 'Continue to Next Step')); ?>">
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-group">
                                <label for="wizard_footer_text">Footer Instructions</label>
                                <textarea class="form-control" id="wizard_footer_text" name="wizard_footer_text" rows="3"><?php echo htmlspecialchars(getSetting('wizard_footer_text', 'When ordering, give your mobile phone number and when you buy dollars from us, you must send money from your bKash/rocket/cash number. If you send money from other number, your order will be canceled and the rest of the money will be refunded.')); ?></textarea>
                                <small class="form-text text-muted">Important instructions shown at the bottom of step 1</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Step 2 Settings -->
                <div class="tab-pane fade" id="step2" role="tabpanel" aria-labelledby="step2-tab">
                    <h5 class="mb-3"><i class="fas fa-user text-primary mr-2"></i>Step 2 - Contact Information Settings</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="contact_step_title">Contact Step Title</label>
                                <input type="text" class="form-control" id="contact_step_title" name="contact_step_title" value="<?php echo htmlspecialchars(getSetting('contact_step_title', 'Provide Your Contact Details')); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="name_field_label">Name Field Label</label>
                                <input type="text" class="form-control" id="name_field_label" name="name_field_label" value="<?php echo htmlspecialchars(getSetting('name_field_label', 'Full Name')); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="email_field_label">Email Field Label</label>
                                <input type="text" class="form-control" id="email_field_label" name="email_field_label" value="<?php echo htmlspecialchars(getSetting('email_field_label', 'Email Address')); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="phone_field_label">Phone Field Label</label>
                                <input type="text" class="form-control" id="phone_field_label" name="phone_field_label" value="<?php echo htmlspecialchars(getSetting('phone_field_label', 'Phone Number')); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="address_field_label">Payment Address Label</label>
                                <input type="text" class="form-control" id="address_field_label" name="address_field_label" value="<?php echo htmlspecialchars(getSetting('address_field_label', 'Payment Address')); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="back_button_text">Back Button Text</label>
                                <input type="text" class="form-control" id="back_button_text" name="back_button_text" value="<?php echo htmlspecialchars(getSetting('back_button_text', 'Back')); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="continue_step2_text">Continue Button Text (Step 2)</label>
                                <input type="text" class="form-control" id="continue_step2_text" name="continue_step2_text" value="<?php echo htmlspecialchars(getSetting('continue_step2_text', 'Continue')); ?>">
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-group">
                                <label for="address_help_text">Payment Address Help Text</label>
                                <textarea class="form-control" id="address_help_text" name="address_help_text" rows="2"><?php echo htmlspecialchars(getSetting('address_help_text', 'This is the address where you want to receive your funds.')); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Step 3 Settings -->
                <div class="tab-pane fade" id="step3" role="tabpanel" aria-labelledby="step3-tab">
                    <h5 class="mb-3"><i class="fas fa-check-circle text-primary mr-2"></i>Step 3 - Confirmation Settings</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="confirmation_title">Confirmation Step Title</label>
                                <input type="text" class="form-control" id="confirmation_title" name="confirmation_title" value="<?php echo htmlspecialchars(getSetting('confirmation_title', 'Confirm Your Exchange')); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="reference_id_title">Reference ID Title</label>
                                <input type="text" class="form-control" id="reference_id_title" name="reference_id_title" value="<?php echo htmlspecialchars(getSetting('reference_id_title', 'Exchange Reference ID')); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="exchange_details_title">Exchange Details Title</label>
                                <input type="text" class="form-control" id="exchange_details_title" name="exchange_details_title" value="<?php echo htmlspecialchars(getSetting('exchange_details_title', 'Exchange Details')); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="payment_details_title">Payment Details Title</label>
                                <input type="text" class="form-control" id="payment_details_title" name="payment_details_title" value="<?php echo htmlspecialchars(getSetting('payment_details_title', 'Payment Details')); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="next_steps_title">Next Steps Title</label>
                                <input type="text" class="form-control" id="next_steps_title" name="next_steps_title" value="<?php echo htmlspecialchars(getSetting('next_steps_title', 'Next Steps')); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="whatsapp_button_text">WhatsApp Button Text</label>
                                <input type="text" class="form-control" id="whatsapp_button_text" name="whatsapp_button_text" value="<?php echo htmlspecialchars(getSetting('whatsapp_button_text', 'Contact Operator')); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="view_receipt_text">View Receipt Button Text</label>
                                <input type="text" class="form-control" id="view_receipt_text" name="view_receipt_text" value="<?php echo htmlspecialchars(getSetting('view_receipt_text', 'View Receipt')); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="complete_button_text">Complete Button Text</label>
                                <input type="text" class="form-control" id="complete_button_text" name="complete_button_text" value="<?php echo htmlspecialchars(getSetting('complete_button_text', 'Complete Exchange')); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="next_todo_text">Next To Do Text</label>
                                <input type="text" class="form-control" id="next_todo_text" name="next_todo_text" value="<?php echo htmlspecialchars(getSetting('next_todo_text', 'Next To Do')); ?>">
                                <small class="form-text text-muted">Text shown below the complete button</small>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="form-group">
                                <label for="reference_id_message">Reference ID Message</label>
                                <textarea class="form-control" id="reference_id_message" name="reference_id_message" rows="2"><?php echo htmlspecialchars(getSetting('reference_id_message', 'আপনার এক্সচেঞ্জ স্ট্যাটাস ট্র্যাক করার জন্য এই "Reference ID" সংরক্ষণ করে রাখুন।')); ?></textarea>
                                <small class="form-text text-muted">Message shown below the reference ID (center aligned)</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="payment_instruction">Payment Instruction</label>
                                <textarea class="form-control" id="payment_instruction" name="payment_instruction" rows="2"><?php echo htmlspecialchars(getSetting('payment_instruction', 'আপনার লেনদেন শুরু করতে এই অ্যাকাউন্টে {amount} BDT/USD সেন্ড করুন:')); ?></textarea>
                                <small class="form-text text-muted">Use {amount} as placeholder for dynamic amount</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="after_payment_message">After Payment Message</label>
                                <textarea class="form-control" id="after_payment_message" name="after_payment_message" rows="2"><?php echo htmlspecialchars(getSetting('after_payment_message', 'পেমেন্ট পাঠানোর পর আপনার "Reference ID" নিয়ে WhatsApp-এ আমাদের অপারেটরের সাথে যোগাযোগ করুন।')); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="whatsapp_contact_message">WhatsApp Contact Message</label>
                                <textarea class="form-control" id="whatsapp_contact_message" name="whatsapp_contact_message" rows="2"><?php echo htmlspecialchars(getSetting('whatsapp_contact_message', 'আপনার এক্সচেঞ্জ অর্ডার সম্পন্ন করতে হোয়াটসঅ্যাপে আমাদের অপারেটরের সাথে যোগাযোগ করুন:')); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="final_instruction">Final Instruction</label>
                                <textarea class="form-control" id="final_instruction" name="final_instruction" rows="2"><?php echo htmlspecialchars(getSetting('final_instruction', 'যোগাযোগ করার সময় আপনার রেফারেন্স আইডি দিন এবং লেনদেন সম্পন্ন করতে অপারেটরের নির্দেশনা অনুসরণ করুন।')); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Labels & Messages -->
                <div class="tab-pane fade" id="labels" role="tabpanel" aria-labelledby="labels-tab">
                    <h5 class="mb-3"><i class="fas fa-tags text-primary mr-2"></i>Labels & Messages</h5>
                    
                    <!-- Form Labels -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Form Labels</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="send_label_text">You Send Label</label>
                                        <input type="text" class="form-control" id="send_label_text" name="send_label_text" value="<?php echo htmlspecialchars(getSetting('send_label_text', 'You Send:')); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="receive_label_text">You Receive Label</label>
                                        <input type="text" class="form-control" id="receive_label_text" name="receive_label_text" value="<?php echo htmlspecialchars(getSetting('receive_label_text', 'You Receive:')); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="rate_label_text">Exchange Rate Label</label>
                                        <input type="text" class="form-control" id="rate_label_text" name="rate_label_text" value="<?php echo htmlspecialchars(getSetting('rate_label_text', 'Exchange Rate:')); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="datetime_label_text">Date & Time Label</label>
                                        <input type="text" class="form-control" id="datetime_label_text" name="datetime_label_text" value="<?php echo htmlspecialchars(getSetting('datetime_label_text', 'Date and Time:')); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="status_label_text">Status Label</label>
                                        <input type="text" class="form-control" id="status_label_text" name="status_label_text" value="<?php echo htmlspecialchars(getSetting('status_label_text', 'Status:')); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status Texts -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Status Texts</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="pending_status">Pending Status Text</label>
                                        <input type="text" class="form-control" id="pending_status" name="pending_status" value="<?php echo htmlspecialchars(getSetting('pending_status', 'Pending')); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="confirmed_status">Confirmed Status Text</label>
                                        <input type="text" class="form-control" id="confirmed_status" name="confirmed_status" value="<?php echo htmlspecialchars(getSetting('confirmed_status', 'Confirmed')); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label for="cancelled_status">Cancelled Status Text</label>
                                        <input type="text" class="form-control" id="cancelled_status" name="cancelled_status" value="<?php echo htmlspecialchars(getSetting('cancelled_status', 'Cancelled')); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Validation Messages -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Validation Messages</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="min_amount_error">Minimum Amount Error</label>
                                        <input type="text" class="form-control" id="min_amount_error" name="min_amount_error" value="<?php echo htmlspecialchars(getSetting('min_amount_error', 'দয়া করে সর্বনিম্ন ৫ ডলার পরিমাণ লিখুন')); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="invalid_email_error">Invalid Email Error</label>
                                        <input type="text" class="form-control" id="invalid_email_error" name="invalid_email_error" value="<?php echo htmlspecialchars(getSetting('invalid_email_error', 'দয়া করে সঠিক ই-মেইল ঠিকানা দিন')); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="amount_required_error">Amount Required Error</label>
                                        <input type="text" class="form-control" id="amount_required_error" name="amount_required_error" value="<?php echo htmlspecialchars(getSetting('amount_required_error', 'দয়া করে এক্সচেঞ্জ এমাউন্ট প্রবেশ করুন')); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="required_fields_error">Required Fields Error</label>
                                        <input type="text" class="form-control" id="required_fields_error" name="required_fields_error" value="<?php echo htmlspecialchars(getSetting('required_fields_error', 'দয়া করে সব প্রয়োজনীয় ঘর পূরণ করুন')); ?>">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="rate_unavailable_error">Rate Unavailable Error</label>
                                        <input type="text" class="form-control" id="rate_unavailable_error" name="rate_unavailable_error" value="<?php echo htmlspecialchars(getSetting('rate_unavailable_error', 'এই কারেন্সির জন্য এক্সচেঞ্জ রেট পাওয়া যাচ্ছে না')); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Success Messages -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Success Messages</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="exchange_success_message">Exchange Success Message</label>
                                        <input type="text" class="form-control" id="exchange_success_message" name="exchange_success_message" value="<?php echo htmlspecialchars(getSetting('exchange_success_message', 'আপনার এক্সচেঞ্জ অর্ডার সফলভাবে জমা হয়েছে!')); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="copy_success_message">Copy Success Message</label>
                                        <input type="text" class="form-control" id="copy_success_message" name="copy_success_message" value="<?php echo htmlspecialchars(getSetting('copy_success_message', 'অ্যাকাউন্ট নাম্বার ক্লিপবোর্ডে কপি হয়েছে')); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Styling & Features -->
                <div class="tab-pane fade" id="styling" role="tabpanel" aria-labelledby="styling-tab">
                    <h5 class="mb-3"><i class="fas fa-paint-brush text-primary mr-2"></i>Styling & Features</h5>
                    
                    <!-- Styling Options -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Styling Options</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="wizard_font_family">Font Family</label>
                                        <select class="form-control" id="wizard_font_family" name="wizard_font_family">
                                            <option value="poppins" <?php echo getSetting('wizard_font_family', 'hind_siliguri') === 'poppins' ? 'selected' : ''; ?>>Poppins (English)</option>
                                            <option value="roboto" <?php echo getSetting('wizard_font_family') === 'roboto' ? 'selected' : ''; ?>>Roboto (English)</option>
                                            <option value="hind_siliguri" <?php echo getSetting('wizard_font_family', 'hind_siliguri') === 'hind_siliguri' ? 'selected' : ''; ?>>Hind Siliguri (Bangla)</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="wizard_primary_color">Primary Color</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="wizard_primary_color" name="wizard_primary_color" value="<?php echo htmlspecialchars(getSetting('wizard_primary_color', '#5dffde')); ?>">
                                            <div class="input-group-append">
                                                <span class="input-group-text p-0">
                                                    <input type="color" class="border-0" style="width: 40px; height: 30px;" 
                                                        value="<?php echo htmlspecialchars(getSetting('wizard_primary_color', '#5dffde')); ?>"
                                                        onchange="document.getElementById('wizard_primary_color').value = this.value;">
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="wizard_progress_bar_color">Progress Bar Color</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="wizard_progress_bar_color" name="wizard_progress_bar_color" value="<?php echo htmlspecialchars(getSetting('wizard_progress_bar_color', '#285FB7')); ?>">
                                            <div class="input-group-append">
                                                <span class="input-group-text p-0">
                                                    <input type="color" class="border-0" style="width: 40px; height: 30px;" 
                                                        value="<?php echo htmlspecialchars(getSetting('wizard_progress_bar_color', '#285FB7')); ?>"
                                                        onchange="document.getElementById('wizard_progress_bar_color').value = this.value;">
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="wizard_border_radius">Border Radius</label>
                                        <select class="form-control" id="wizard_border_radius" name="wizard_border_radius">
                                            <option value="none" <?php echo getSetting('wizard_border_radius') === 'none' ? 'selected' : ''; ?>>None</option>
                                            <option value="small" <?php echo getSetting('wizard_border_radius') === 'small' ? 'selected' : ''; ?>>Small</option>
                                            <option value="medium" <?php echo getSetting('wizard_border_radius', 'medium') === 'medium' ? 'selected' : ''; ?>>Medium</option>
                                            <option value="large" <?php echo getSetting('wizard_border_radius') === 'large' ? 'selected' : ''; ?>>Large</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Feature Options -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Feature Options</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="enable_animations" name="enable_animations" <?php echo getSetting('enable_animations', 'yes') === 'yes' ? 'checked' : ''; ?>>
                                            <label class="custom-control-label" for="enable_animations">Enable Step Animations</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="enable_sound_effects" name="enable_sound_effects" <?php echo getSetting('enable_sound_effects', 'no') === 'yes' ? 'checked' : ''; ?>>
                                            <label class="custom-control-label" for="enable_sound_effects">Enable Sound Effects</label>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="auto_save_progress" name="auto_save_progress" <?php echo getSetting('auto_save_progress', 'no') === 'yes' ? 'checked' : ''; ?>>
                                            <label class="custom-control-label" for="auto_save_progress">Auto-save Progress</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Exchange Settings -->
                <div class="tab-pane fade" id="exchange-settings" role="tabpanel" aria-labelledby="exchange-settings-tab">
                    <h5 class="mb-3"><i class="fas fa-cog text-primary mr-2"></i>Exchange Settings</h5>
                    
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">Amount Settings</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="minimum_exchange_amount">Minimum Exchange Amount (USD)</label>
                                        <input type="number" class="form-control" id="minimum_exchange_amount" name="minimum_exchange_amount" value="<?php echo htmlspecialchars(getSetting('minimum_exchange_amount', '5')); ?>" min="1" step="0.01">
                                        <small class="form-text text-muted">Minimum amount required for exchange (in USD equivalent)</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group mt-4">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-save mr-1"></i> Save Exchange Wizard Settings
                </button>
                <a href="<?php echo SITE_URL; ?>" target="_blank" class="btn btn-info btn-lg ml-2">
                    <i class="fas fa-eye mr-1"></i> Preview Changes
                </a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>