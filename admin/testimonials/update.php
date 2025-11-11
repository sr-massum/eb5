<?php 

/**
 * ExchangeBridge - Admin Panel Testimonial Update
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

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $name = isset($_POST['name']) ? sanitizeInput($_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitizeInput($_POST['email']) : '';
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 5;
    $message = isset($_POST['message']) ? sanitizeInput($_POST['message']) : '';
    $fromCurrency = isset($_POST['from_currency']) ? sanitizeInput($_POST['from_currency']) : '';
    $toCurrency = isset($_POST['to_currency']) ? sanitizeInput($_POST['to_currency']) : '';
    $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'active';
    
    // Validate form data
    if ($id <= 0) {
        $_SESSION['error_message'] = 'Invalid testimonial ID';
    } elseif (empty($name)) {
        $_SESSION['error_message'] = 'Name is required';
    } elseif (!empty($email) && !isValidEmail($email)) {
        $_SESSION['error_message'] = 'Invalid email address';
    } elseif ($rating < 1 || $rating > 5) {
        $_SESSION['error_message'] = 'Rating must be between 1 and 5';
    } elseif (empty($message)) {
        $_SESSION['error_message'] = 'Testimonial message is required';
    } else {
        // Check if testimonial exists
        $db = Database::getInstance();
        $testimonial = $db->getRow("SELECT * FROM testimonials WHERE id = ?", [$id]);
        
        if (!$testimonial) {
            $_SESSION['error_message'] = 'Testimonial not found';
        } else {
            // Update testimonial
            $result = $db->update('testimonials', [
                'name' => $name,
                'email' => $email,
                'rating' => $rating,
                'message' => $message,
                'from_currency' => $fromCurrency,
                'to_currency' => $toCurrency,
                'status' => $status
            ], 'id = ?', [$id]);
            
            if ($result) {
                $_SESSION['success_message'] = 'Testimonial updated successfully';
            } else {
                $_SESSION['error_message'] = 'Failed to update testimonial';
            }
        }
    }
}

// Redirect back to testimonials list
header("Location: index.php");
exit;