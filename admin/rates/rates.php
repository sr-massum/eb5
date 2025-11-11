<?php 

/**
 * ExchangeBridge - Admin Panel Exchange Rates
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
        // Get and sanitize form data
        $fromCurrency = isset($_POST['from_currency']) ? sanitizeInput($_POST['from_currency']) : '';
        $toCurrency = isset($_POST['to_currency']) ? sanitizeInput($_POST['to_currency']) : '';
        $rate = isset($_POST['rate']) ? floatval($_POST['rate']) : 0;
        $status = isset($_POST['status']) ? sanitizeInput($_POST['status']) : 'active';
        $displayOnHomepage = isset($_POST['display_on_homepage']) ? 1 : 0;
        $weBuy = isset($_POST['we_buy']) ? floatval($_POST['we_buy']) : 0;
        $weSell = isset($_POST['we_sell']) ? floatval($_POST['we_sell']) : 0;
        
        // Validate form data
        if (empty($fromCurrency)) {
            $_SESSION['error_message'] = 'From currency is required';
        } elseif (empty($toCurrency)) {
            $_SESSION['error_message'] = 'To currency is required';
        } elseif ($fromCurrency === $toCurrency) {
            $_SESSION['error_message'] = 'From and To currencies cannot be the same';
        } elseif ($rate <= 0) {
            $_SESSION['error_message'] = 'Rate must be greater than zero';
        } else {
            $db = Database::getInstance();
            
            // Check if the exchange rate already exists
            $existingRate = $db->getRow(
                "SELECT * FROM exchange_rates WHERE from_currency = ? AND to_currency = ?", 
                [$fromCurrency, $toCurrency]
            );
            
            if ($existingRate) {
                $_SESSION['error_message'] = 'Exchange rate already exists for this currency pair';
            } else {
                // Prepare data for insertion - only include new fields if they exist in database
                $insertData = [
                    'from_currency' => $fromCurrency,
                    'to_currency' => $toCurrency,
                    'rate' => $rate,
                    'status' => $status
                ];
                
                // Check if new columns exist and add them
                try {
                    $checkColumns = $db->query("SHOW COLUMNS FROM exchange_rates LIKE 'display_on_homepage'");
                    if ($checkColumns && $checkColumns->rowCount() > 0) {
                        $insertData['display_on_homepage'] = $displayOnHomepage;
                        $insertData['we_buy'] = $weBuy;
                        $insertData['we_sell'] = $weSell;
                    }
                } catch (Exception $e) {
                    error_log("Error checking columns: " . $e->getMessage());
                    // Continue without new columns
                }
                
                // Insert exchange rate
                try {
                    $rateId = $db->insert('exchange_rates', $insertData);
                    
                    if ($rateId) {
                        $_SESSION['success_message'] = 'Exchange rate added successfully';
                    } else {
                        $_SESSION['error_message'] = 'Failed to add exchange rate. Please try again.';
                    }
                } catch (Exception $e) {
                    error_log("Error inserting exchange rate: " . $e->getMessage());
                    $_SESSION['error_message'] = 'Failed to add exchange rate. Database error occurred.';
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error processing exchange rate form: " . $e->getMessage());
        $_SESSION['error_message'] = 'An error occurred while processing your request. Please try again.';
    }
} else {
    $_SESSION['error_message'] = 'Invalid request method.';
}

// Redirect back to index
header("Location: index.php");
exit;
?>