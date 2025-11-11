<?php 

/**
 * ExchangeBridge - Admin Panel Testimonial Delete
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

// Get testimonial ID from URL
$testimonialId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Check if testimonial exists
$db = Database::getInstance();
$testimonial = $db->getRow("SELECT * FROM testimonials WHERE id = ?", [$testimonialId]);

if (!$testimonial) {
    $_SESSION['error_message'] = 'Testimonial not found';
    header("Location: index.php");
    exit;
}

// Delete testimonial
$result = $db->delete('testimonials', 'id = ?', [$testimonialId]);

if ($result) {
    $_SESSION['success_message'] = 'Testimonial deleted successfully';
} else {
    $_SESSION['error_message'] = 'Failed to delete testimonial';
}

// Redirect back to testimonials list
header("Location: index.php");
exit;