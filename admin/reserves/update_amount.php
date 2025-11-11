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
    $reserve_id = intval($_POST['id']);
    $amount = floatval($_POST['amount']);
    
    // Validate inputs
    if (empty($reserve_id) || $amount < 0) {
        $_SESSION['error_message'] = 'Please provide valid values.';
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
    
    // Update only the amount
    $result = $db->update('reserves', ['amount' => $amount], 'id = ?', [$reserve_id]);
    
    if ($result) {
        $_SESSION['success_message'] = 'Reserve amount updated successfully.';
    } else {
        $_SESSION['error_message'] = 'Failed to update reserve amount. Please try again.';
    }
} else {
    $_SESSION['error_message'] = 'Invalid request method.';
}

header("Location: index.php");
exit;
?>