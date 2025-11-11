<?php 

/**
 * ExchangeBridge - Admin Panel Reserve Money Update
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

// Check if user is logged in
if (!Auth::isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reserve_id = intval($_POST['reserve_id']);
    $currency_code = sanitizeInput($_POST['currency_code']);
    $amount = floatval($_POST['amount']);
    $min_amount = floatval($_POST['min_amount']);
    $max_amount = floatval($_POST['max_amount']);
    $auto_update = isset($_POST['auto_update']) ? 1 : 0;
    
    // Validate inputs
    if (empty($reserve_id) || empty($currency_code) || $amount < 0 || $min_amount < 0 || $max_amount < 0) {
        $_SESSION['error_message'] = 'Please fill all fields with valid values.';
        header("Location: index.php");
        exit;
    }
    
    if ($max_amount <= $min_amount) {
        $_SESSION['error_message'] = 'Maximum amount must be greater than minimum amount.';
        header("Location: index.php");
        exit;
    }
    
    // Check if reserve exists
    $db = Database::getInstance();
    $existingReserve = $db->getRow("SELECT * FROM reserves WHERE id = ?", [$reserve_id]);
    
    if (!$existingReserve) {
        $_SESSION['error_message'] = 'Reserve not found.';
        header("Location: index.php");
        exit;
    }
    
    // Update reserve
    $updateData = [
        'amount' => $amount,
        'min_amount' => $min_amount,
        'max_amount' => $max_amount,
        'auto_update' => $auto_update
    ];
    
    $result = $db->update('reserves', $updateData, 'id = ?', [$reserve_id]);
    
    if ($result) {
        $_SESSION['success_message'] = 'Reserve updated successfully.';
    } else {
        $_SESSION['error_message'] = 'Failed to update reserve. Please try again.';
    }
} else {
    $_SESSION['error_message'] = 'Invalid request method.';
}

header("Location: index.php");
exit;
?>