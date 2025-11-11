<?php 

/**
 * ExchangeBridge - Admin Panel Reserve Money Add 
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
    try {
        // Sanitize and validate inputs
        $currency_code = isset($_POST['currency_code']) ? sanitizeInput($_POST['currency_code']) : '';
        $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $min_amount = isset($_POST['min_amount']) ? floatval($_POST['min_amount']) : 0;
        $max_amount = isset($_POST['max_amount']) ? floatval($_POST['max_amount']) : 0;
        $auto_update = isset($_POST['auto_update']) ? 1 : 0;
        
        // Validate inputs
        if (empty($currency_code)) {
            $_SESSION['error_message'] = 'Please select a currency.';
            header("Location: index.php");
            exit;
        }
        
        if ($amount < 0 || $min_amount < 0 || $max_amount < 0) {
            $_SESSION['error_message'] = 'All amounts must be greater than or equal to zero.';
            header("Location: index.php");
            exit;
        }
        
        if ($max_amount <= $min_amount) {
            $_SESSION['error_message'] = 'Maximum amount must be greater than minimum amount.';
            header("Location: index.php");
            exit;
        }
        
        // Check if currency exists
        try {
            $currency = getCurrencyByCode($currency_code);
            if (!$currency) {
                $_SESSION['error_message'] = 'Selected currency does not exist.';
                header("Location: index.php");
                exit;
            }
        } catch (Exception $e) {
            error_log("Error checking currency: " . $e->getMessage());
            $_SESSION['error_message'] = 'Error validating currency. Please try again.';
            header("Location: index.php");
            exit;
        }
        
        // Check if reserve already exists for this currency
        $db = Database::getInstance();
        $existingReserve = $db->getRow("SELECT id FROM reserves WHERE currency_code = ?", [$currency_code]);
        
        if ($existingReserve) {
            $_SESSION['error_message'] = 'Reserve for this currency already exists.';
            header("Location: index.php");
            exit;
        }
        
        // Insert new reserve
        $reserveData = [
            'currency_code' => $currency_code,
            'amount' => $amount,
            'min_amount' => $min_amount,
            'max_amount' => $max_amount,
            'auto_update' => $auto_update
        ];
        
        $result = $db->insert('reserves', $reserveData);
        
        if ($result) {
            $_SESSION['success_message'] = 'Reserve added successfully.';
        } else {
            $_SESSION['error_message'] = 'Failed to add reserve. Please try again.';
        }
        
    } catch (Exception $e) {
        error_log("Error adding reserve: " . $e->getMessage());
        $_SESSION['error_message'] = 'An error occurred while adding the reserve. Please try again.';
    }
} else {
    $_SESSION['error_message'] = 'Invalid request method.';
}

header("Location: index.php");
exit;
?>