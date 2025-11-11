<?php 

/**
 * ExchangeBridge - Admin Panel Exchange Rates Delete
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

// Check if user has admin role
if (!Auth::isAdmin()) {
    $_SESSION['error_message'] = 'You do not have permission to delete exchange rates';
    header("Location: index.php");
    exit;
}

// Get exchange rate ID from URL
$rateId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Check if exchange rate exists
$db = Database::getInstance();
$rate = $db->getRow("SELECT * FROM exchange_rates WHERE id = ?", [$rateId]);

if (!$rate) {
    $_SESSION['error_message'] = 'Exchange rate not found';
    header("Location: index.php");
    exit;
}

// Delete exchange rate
$result = $db->delete('exchange_rates', 'id = ?', [$rateId]);

if ($result) {
    $_SESSION['success_message'] = 'Exchange rate deleted successfully';
} else {
    $_SESSION['error_message'] = 'Failed to delete exchange rate';
}

// Redirect back to rates list
header("Location: index.php");
exit;