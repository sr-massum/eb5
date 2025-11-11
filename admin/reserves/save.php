<?php 

/**
 * ExchangeBridge - Admin Panel Reserve Money Save
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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reserve_id = isset($_POST['reserve_id']) ? (int)$_POST['reserve_id'] : 0;
    $currency_code = sanitizeInput($_POST['currency_code']);
    $amount = (float)$_POST['amount'];
    $min_amount = (float)$_POST['min_amount'];
    $max_amount = (float)$_POST['max_amount'];
    $auto_update = isset($_POST['auto_update']) ? 1 : 0;
    
    $db = Database::getInstance();
    
    try {
        if ($reserve_id > 0) {
            // Update existing reserve
            $success = $db->update('reserves', [
                'currency_code' => $currency_code,
                'amount' => $amount,
                'min_amount' => $min_amount,
                'max_amount' => $max_amount,
                'auto_update' => $auto_update,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$reserve_id]);
            
            $_SESSION['success_message'] = 'Reserve updated successfully!';
        } else {
            // Check if reserve already exists for this currency
            $existing = $db->getRow("SELECT id FROM reserves WHERE currency_code = ?", [$currency_code]);
            
            if ($existing) {
                // Update existing
                $success = $db->update('reserves', [
                    'amount' => $amount,
                    'min_amount' => $min_amount,
                    'max_amount' => $max_amount,
                    'auto_update' => $auto_update,
                    'updated_at' => date('Y-m-d H:i:s')
                ], 'currency_code = ?', [$currency_code]);
                
                $_SESSION['success_message'] = 'Reserve updated successfully!';
            } else {
                // Create new reserve
                $success = $db->insert('reserves', [
                    'currency_code' => $currency_code,
                    'amount' => $amount,
                    'min_amount' => $min_amount,
                    'max_amount' => $max_amount,
                    'auto_update' => $auto_update,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                $_SESSION['success_message'] = 'Reserve added successfully!';
            }
        }
        
        if (!$success) {
            $_SESSION['error_message'] = 'Failed to save reserve!';
        }
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
    }
}

header("Location: index.php");
exit;
?>