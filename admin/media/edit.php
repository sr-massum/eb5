<?php 

/**
 * ExchangeBridge - Admin Panel Media 
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
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Unauthorized']));
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'Media ID required']));
}

$mediaId = (int)$_GET['id'];
$db = Database::getInstance();

$media = $db->getRow("SELECT * FROM media WHERE id = ?", [$mediaId]);

if (!$media) {
    http_response_code(404);
    exit(json_encode(['success' => false, 'message' => 'Media not found']));
}

header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'media' => $media
]);
?>