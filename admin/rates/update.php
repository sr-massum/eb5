<?php 

/**
 * ExchangeBridge - Admin Panel Exchange Rates Update
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
    $rate = isset($_POST['rate']) ? floatval($_POST['rate']) : 0;
    $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'active';
    
    // Validate form data
    if ($id <= 0) {
        $_SESSION['error_message'] = 'Invalid exchange rate ID';
    } elseif ($rate <= 0) {
        $_SESSION['error_message'] = 'Rate must be greater than zero';
    } else {
        // Check if the exchange rate exists
        $db = Database::getInstance();
        $existingRate = $db->getRow("SELECT * FROM exchange_rates WHERE id = ?", [$id]);
        
        if (!$existingRate) {
            $_SESSION['error_message'] = 'Exchange rate not found';
        } else {
            // Update exchange rate
            $result = $db->update('exchange_rates', [
                'rate' => $rate,
                'status' => $status
            ], 'id = ?', [$id]);
            
            if ($result) {
                $_SESSION['success_message'] = 'Exchange rate updated successfully';
            } else {
                $_SESSION['error_message'] = 'Failed to update exchange rate';
            }
        }
    }
}

// Redirect back to rates list
header("Location: index.php");
exit;