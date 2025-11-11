<?php 

/**
 * ExchangeBridge - Admin Panel Delete Currency
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
    $_SESSION['error_message'] = 'You do not have permission to delete currencies';
    header("Location: index.php");
    exit;
}

// Get currency ID from URL
$currencyId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Check if currency exists
$db = Database::getInstance();
$currency = $db->getRow("SELECT * FROM currencies WHERE id = ?", [$currencyId]);

if (!$currency) {
    $_SESSION['error_message'] = 'Currency not found';
    header("Location: index.php");
    exit;
}

// Check if currency is used in exchanges
$usedInExchanges = $db->getValue(
    "SELECT COUNT(*) FROM exchanges WHERE from_currency = ? OR to_currency = ?", 
    [$currency['code'], $currency['code']]
);

if ($usedInExchanges > 0) {
    $_SESSION['error_message'] = 'Cannot delete currency because it is used in exchanges';
    header("Location: index.php");
    exit;
}

// Check if currency is used in exchange rates
$usedInRates = $db->getValue(
    "SELECT COUNT(*) FROM exchange_rates WHERE from_currency = ? OR to_currency = ?", 
    [$currency['code'], $currency['code']]
);

if ($usedInRates > 0) {
    $_SESSION['error_message'] = 'Cannot delete currency because it is used in exchange rates';
    header("Location: index.php");
    exit;
}

// Check if currency is used in reserves
$usedInReserves = $db->getValue(
    "SELECT COUNT(*) FROM reserves WHERE currency_code = ?", 
    [$currency['code']]
);

if ($usedInReserves > 0) {
    $_SESSION['error_message'] = 'Cannot delete currency because it is used in reserves';
    header("Location: index.php");
    exit;
}

// Delete currency
$result = $db->delete('currencies', 'id = ?', [$currencyId]);

if ($result) {
    // Delete currency logo if exists
    if (!empty($currency['logo'])) {
        $logoPath = '../../assets/uploads/currencies/' . $currency['logo'];
        if (file_exists($logoPath)) {
            unlink($logoPath);
        }
    }
    
    $_SESSION['success_message'] = 'Currency deleted successfully';
} else {
    $_SESSION['error_message'] = 'Failed to delete currency';
}

// Redirect back to currencies list
header("Location: index.php");
exit;