<?php 

/**
 * ExchangeBridge - Admin Panel About Page
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

// Check if user is logged in
if (!Auth::isLoggedIn()) {
    header("Location: ../../login.php");
    exit;
}

// Check if about page already exists
$db = Database::getInstance();
$existingPage = $db->getRow("SELECT id FROM pages WHERE slug = 'about'");
if ($existingPage) {
    $_SESSION['error_message'] = 'About page already exists!';
    header("Location: index.php");
    exit;
}

// Include header
include '../../includes/header.php';
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
</head>

<!-- Enhanced TinyMCE for Bengali/Multilingual support -->
<script src="https://cdn.tiny.cloud/1/hhiyirqkh3fnrmgjs7nq6tpk6nqb62m3vww7smgrz7kjfv6v/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>

<style>
/* Enhanced Bengali font support styles */
@import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@100;200;300;400;500;600;700;800;900&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap');

/* Force UTF-8 encoding for all text */
* {
    -webkit-font-feature-settings: "kern" 1;
    font-feature-settings: "kern" 1;
    text-rendering: optimizeLegibility;
}

/* Primary Bengali font family */
.page-content, .bengali-text {
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'SutonnyMJ', 'Kalpurush', 'Solaimanlipi', Arial, sans-serif !important;
    line-height: 1.8 !important;
    direction: ltr;
    unicode-bidi: embed;
    font-weight: 400;
    font-size: 16px;
}

/* Form controls with Bengali support */
.form-control {
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', Arial, sans-serif !important;
    font-size: 16px !important;
    line-height: 1.8 !important;
    font-weight: 400 !important;
}

textarea.form-control {
    font-size: 16px !important;
    line-height: 1.8 !important;
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', Arial, sans-serif !important;
}

/* Ensure proper rendering */
.bengali-input {
    font-family: 'Noto Sans Bengali', 'Hind Siliguri', Arial, sans-serif !important;
    font-size: 16px !important;
    line-height: 1.8 !important;
    direction: ltr;
    unicode-bidi: embed;
    text-align: left;
}
</style>

<!-- Breadcrumbs -->
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="../../index.php">Dashboard</a>
    </li>
    <li class="breadcrumb-item">
        <a href="../index.php">Pages</a>
    </li>
    <li class="breadcrumb-item">
        <a href="index.php">About Us</a>
    </li>
    <li class="breadcrumb-item active">Add</li>
</ol>

<!-- Page Content -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-plus mr-1"></i> Create About Us Page
    </div>
    <div class="card-body">
        <form action="save.php" method="POST" id="aboutForm" accept-charset="UTF-8">
            <input type="hidden" name="action" value="create">
            
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="title">Page Title *</label>
                        <input type="text" name="title" id="title" class="form-control page-content bengali-input" value="About Us" required placeholder="English/বাংলা টাইটেল লিখুন">
                    </div>
                    
                    <div class="form-group">
                        <label for="content">Page Content (বিষয়বস্তু) *</label>
                        <textarea name="content" id="content" class="form-control page-content bengali-input tinymce-editor" rows="20">
<h2>আমাদের এক্সচেঞ্জ সেবা সম্পর্কে</h2>
<p>আমাদের বিশ্বস্ত ডিজিটাল মুদ্রা বিনিময় প্ল্যাটফর্মে স্বাগতম। আমরা বিভিন্ন ডিজিটাল মুদ্রা এবং পেমেন্ট পদ্ধতির জন্য দ্রুত, নিরাপদ এবং নির্ভরযোগ্য বিনিময় সেবা প্রদান করি।</p>

<h3>আমাদের মিশন</h3>
<p>ঐতিহ্যবাহী এবং ডিজিটাল আর্থিক ব্যবস্থার মধ্যে সেতুবন্ধন তৈরি করে নিরবচ্ছিন্ন এবং নিরাপদ ডিজিটাল মুদ্রা বিনিময় সেবা প্রদান করা।</p>

<h3>কেন আমাদের বেছে নেবেন?</h3>
<ul>
<li>দ্রুত লেনদেন প্রক্রিয়াকরণ</li>
<li>নিরাপদ এবং এনক্রিপ্ট করা লেনদেন</li>
<li>২৪/৭ গ্রাহক সহায়তা</li>
<li>প্রতিযোগিতামূলক বিনিময় হার</li>
<li>একাধিক পেমেন্ট পদ্ধতি</li>
<li>হাজার হাজার গ্রাহকের বিশ্বাস</li>
</ul>

<h3>আমাদের দল</h3>
<p>আমাদের অভিজ্ঞ আর্থিক এবং প্রযুক্তি বিশেষজ্ঞদের দল আপনার লেনদেনগুলি দ্রুত এবং নিরাপদে প্রক্রিয়া করার জন্য ২৪ ঘণ্টা কাজ করে। আমরা আমাদের গ্রাহকদের সর্বোত্তম সেবা প্রদানে প্রতিশ্রুতিবদ্ধ।</p>

<h3>নিরাপত্তা ও সম্মতি</h3>
<p>আমরা নিরাপত্তাকে গুরুত্ব সহকারে নিই এবং আপনার তহবিল ও ব্যক্তিগত তথ্য রক্ষার জন্য শিল্প-মানের ব্যবস্থা প্রয়োগ করি। সকল লেনদেন এনক্রিপ্ট করা এবং সন্দেহজনক কার্যকলাপের জন্য পর্যবেক্ষণ করা হয়।</p>
                        </textarea>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="status">Status (অবস্থা)</label>
                        <select name="status" id="status" class="form-control">
                            <option value="active" selected>Active (সক্রিয়)</option>
                            <option value="inactive">Inactive (নিষ্ক্রিয়)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" name="show_in_menu" id="show_in_menu" class="custom-control-input" checked>
                            <label class="custom-control-label" for="show_in_menu">
                                Show in Navigation Menu (নেভিগেশন মেনুতে দেখান)
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="menu_order">Menu Order (মেনু ক্রম)</label>
                        <input type="number" name="menu_order" id="menu_order" class="form-control" value="1" min="0">
                    </div>
                    
                    <hr>
                    
                    <h6>SEO Settings (এসইও সেটিংস)</h6>
                    
                    <div class="form-group">
                        <label for="meta_title">Meta Title (মেটা শিরোনাম)</label>
                        <input type="text" name="meta_title" id="meta_title" class="form-control page-content bengali-input" 
                               value="About Us - Your Trusted Exchange Platform" maxlength="60">
                        <small class="form-text text-muted">Recommended: 50-60 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="meta_description">Meta Description (মেটা বিবরণ)</label>
                        <textarea name="meta_description" id="meta_description" class="form-control page-content bengali-input" rows="3" maxlength="160">Learn about our mission to provide secure and reliable digital currency exchange services with competitive rates.</textarea>
                        <small class="form-text text-muted">Recommended: 150-160 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="meta_keywords">Meta Keywords (মেটা কীওয়ার্ড)</label>
                        <input type="text" name="meta_keywords" id="meta_keywords" class="form-control page-content bengali-input" 
                               value="about us, exchange service, digital currency, crypto exchange, secure trading">
                        <small class="form-text text-muted">Comma-separated keywords</small>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Create About Page
                </button>
                <a href="index.php" class="btn btn-secondary ml-2">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Include jQuery and Bootstrap -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
// Enhanced TinyMCE initialization with Bengali support (same as blog)
function initTinyMCE(selector) {
    tinymce.init({
        selector: selector,
        height: 500,
        language: 'en',
        entity_encoding: 'raw',
        encoding: 'UTF-8',
        plugins: 'advlist autolink lists link image charmap print preview anchor searchreplace visualblocks code fullscreen insertdatetime media table paste help wordcount emoticons',
        toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | link image media | code fullscreen | emoticons | help',
        content_style: `
            @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@100;200;300;400;500;600;700;800;900&display=swap');
            @import url('https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap');
            body { 
                font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'SutonnyMJ', 'Kalpurush', Arial, sans-serif !important; 
                font-size: 16px !important; 
                line-height: 1.8 !important;
                direction: ltr;
                unicode-bidi: embed;
                font-weight: 400;
                text-rendering: optimizeLegibility;
                -webkit-font-feature-settings: "kern" 1;
                font-feature-settings: "kern" 1;
            }
            p { 
                font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'SutonnyMJ', 'Kalpurush', Arial, sans-serif !important; 
                line-height: 1.8 !important; 
                font-size: 16px !important;
                font-weight: 400;
            }
            strong, b { 
                font-weight: 700 !important; 
                font-family: 'Noto Sans Bengali', 'Hind Siliguri', Arial, sans-serif !important;
            }
            h1, h2, h3, h4, h5, h6 {
                font-family: 'Noto Sans Bengali', 'Hind Siliguri', 'Poppins', Arial, sans-serif !important;
                font-weight: 600 !important;
                line-height: 1.6 !important;
            }
            li {
                font-family: 'Noto Sans Bengali', 'Hind Siliguri', Arial, sans-serif !important;
                line-height: 1.8 !important;
            }
        `,
        font_formats: 'Noto Sans Bengali=Noto Sans Bengali; Hind Siliguri=Hind Siliguri; SutonnyMJ=SutonnyMJ; Kalpurush=Kalpurush; Poppins=Poppins; Arial=arial,helvetica,sans-serif; Times New Roman=times new roman,times,serif;',
        formats: {
            bold: {inline: 'strong'},
            italic: {inline: 'em'},
            underline: {inline: 'u'},
            strikethrough: {inline: 'strike'}
        },
        setup: function (editor) {
            editor.on('change', function () {
                editor.save();
            });
            
            // Set default font and encoding
            editor.on('init', function() {
                editor.getBody().style.fontFamily = "'Noto Sans Bengali', 'Hind Siliguri', 'SutonnyMJ', 'Kalpurush', Arial, sans-serif";
                editor.getBody().style.fontSize = "16px";
                editor.getBody().style.lineHeight = "1.8";
                editor.getBody().style.direction = "ltr";
                editor.getBody().style.unicodeBidi = "embed";
                editor.getBody().style.textRendering = "optimizeLegibility";
            });
        },
        style_formats: [
            {title: 'Bengali Text', inline: 'span', styles: {'font-family': "'Noto Sans Bengali', 'Hind Siliguri', 'SutonnyMJ', Arial, sans-serif", 'font-weight': '400', 'font-size': '16px'}},
            {title: 'English Text', inline: 'span', styles: {'font-family': "'Poppins', 'Roboto', Arial, sans-serif", 'font-weight': '400', 'font-size': '14px'}},
            {title: 'Bold Bengali', inline: 'strong', styles: {'font-family': "'Noto Sans Bengali', 'Hind Siliguri', Arial, sans-serif", 'font-weight': '700'}},
            {title: 'Bold English', inline: 'strong', styles: {'font-family': "'Poppins', 'Roboto', Arial, sans-serif", 'font-weight': '700'}},
            {title: 'Bengali Heading', block: 'h3', styles: {'font-family': "'Noto Sans Bengali', 'Hind Siliguri', Arial, sans-serif", 'font-weight': '600'}}
        ],
        menubar: 'file edit view insert format tools table help',
        toolbar_mode: 'sliding',
        contextmenu: 'link image table',
        image_advtab: true,
        image_caption: true,
        quickbars_selection_toolbar: 'bold italic | quicklink h2 h3 blockquote quickimage quicktable',
        noneditable_noneditable_class: 'mceNonEditable',
        toolbar_sticky: true,
        autosave_ask_before_unload: true,
        autosave_interval: '30s',
        autosave_prefix: '{path}{query}-{id}-',
        autosave_restore_when_empty: false,
        autosave_retention: '2m',
        paste_data_images: true,
        paste_as_text: false,
        paste_preprocess: function(plugin, args) {
            // Preserve Bengali characters during paste
            args.content = args.content;
        },
        init_instance_callback: function(editor) {
            // Ensure Bengali characters are properly handled
            editor.getBody().style.fontFamily = "'Noto Sans Bengali', 'Hind Siliguri', 'SutonnyMJ', 'Kalpurush', Arial, sans-serif";
        }
    });
}

$(document).ready(function() {
    // Set document encoding
    document.charset = "UTF-8";
    
    // Initialize TinyMCE
    initTinyMCE('.tinymce-editor');
    
    // Form submission
    $('#aboutForm').on('submit', function() {
        tinymce.triggerSave();
    });
    
    // Ensure proper Bengali character handling in inputs
    $('.bengali-input').on('input', function() {
        // Force proper encoding
        this.value = this.value;
    });
});
</script>

<?php include '../../includes/footer.php'; ?>