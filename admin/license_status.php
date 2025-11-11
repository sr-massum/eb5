<?php 
/**
 * ExchangeBridge - Admin Panel License Status
 * Integrated with master license system
 *
 * @package     ExchangeBridge
 * @author      Saieed Rahman
 * @copyright   SidMan Solution 2025
 * @version     3.0.0
 */

// Start session
session_start();

// Define access constants
define('ALLOW_ACCESS', true);
define('EB_SCRIPT_RUNNING', true);

// Include the integrated systems
require_once '../includes/license_system.php';
require_once '../includes/security.php';

$pageTitle = 'License Status';
$currentPage = 'license';

// Get license information from the master system
try {
    $licenseSystem = ExchangeBridgeMasterLicenseSystem::getInstance();
    $licenseInfo = $licenseSystem->getLicenseStatus();
    
    // Handle license refresh request
    if ($_POST['action'] ?? '' === 'check_license') {
        $refreshResult = $licenseSystem->verifyLicense();
        if ($refreshResult) {
            $licenseInfo = $licenseSystem->getLicenseStatus();
            echo json_encode(['success' => true, 'message' => 'License verified successfully']);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'License verification failed']);
            exit;
        }
    }
    
} catch (Exception $e) {
    $licenseInfo = [
        'verified' => false,
        'status' => 'Error',
        'error' => $e->getMessage()
    ];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Exchange Bridge Admin</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 8px 8px 0 0; }
        .header h1 { margin: 0; font-size: 2.5em; font-weight: 300; }
        .header p { margin: 10px 0 0 0; opacity: 0.9; }
        .content { padding: 30px; }
        .license-info { background: #f8f9fa; padding: 30px; border-radius: 12px; margin: 20px 0; border-left: 4px solid #667eea; }
        .status-active { color: #28a745; font-weight: bold; font-size: 1.1em; }
        .status-inactive { color: #dc3545; font-weight: bold; font-size: 1.1em; }
        .status-error { color: #fd7e14; font-weight: bold; font-size: 1.1em; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin-top: 20px; }
        .info-card { 
            background: white; 
            padding: 25px; 
            border-radius: 10px; 
            border: 1px solid #e9ecef; 
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .info-card:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .info-card h3 { margin: 0 0 10px 0; color: #495057; font-size: 1.1em; }
        .info-card p { margin: 0; font-size: 1.1em; }
        .btn { 
            display: inline-block; 
            padding: 12px 25px; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            text-decoration: none; 
            border-radius: 6px; 
            border: none; 
            cursor: pointer; 
            margin: 8px; 
            font-size: 1em;
            transition: all 0.3s ease;
        }
        .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
        .btn-secondary { background: linear-gradient(135deg, #6c757d 0%, #495057 100%); }
        .btn-secondary:hover { box-shadow: 0 4px 12px rgba(108, 117, 125, 0.4); }
        .alert { padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid; }
        .alert-warning { background: #fff3cd; border-left-color: #ffc107; color: #856404; }
        .alert-danger { background: #f8d7da; border-left-color: #dc3545; color: #721c24; }
        .alert-success { background: #d4edda; border-left-color: #28a745; color: #155724; }
        .loading { display: none; color: #666; margin: 10px; }
        .verification-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
            text-transform: uppercase;
        }
        .verified { background: #d4edda; color: #155724; }
        .unverified { background: #f8d7da; color: #721c24; }
        .system-info { 
            background: #e9ecef; 
            padding: 20px; 
            border-radius: 8px; 
            margin-top: 20px; 
            font-family: 'Courier New', monospace; 
            font-size: 0.9em; 
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?= htmlspecialchars($pageTitle) ?></h1>
            <p>Exchange Bridge License Management System v3.0.0</p>
        </div>
        
        <div class="content">
            <?php if (isset($licenseInfo['error'])): ?>
                <div class="alert alert-danger">
                    <h3>🚨 License System Error</h3>
                    <p><?= htmlspecialchars($licenseInfo['error']) ?></p>
                    <p><strong>Note:</strong> This indicates a critical system issue that requires immediate attention.</p>
                </div>
            <?php elseif ($licenseInfo): ?>
                <div class="license-info">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 style="margin: 0;">License Information</h2>
                        <span class="verification-badge <?= $licenseInfo['verified'] ? 'verified' : 'unverified' ?>">
                            <?= $licenseInfo['verified'] ? '✓ Verified' : '✗ Unverified' ?>
                        </span>
                    </div>
                    
                    <div class="info-grid">
                        <div class="info-card">
                            <h3>🔑 License Key</h3>
                            <p><?= htmlspecialchars($licenseInfo['license_key']) ?></p>
                        </div>
                        
                        <div class="info-card">
                            <h3>📊 Status</h3>
                            <p class="<?= 
                                $licenseInfo['status'] === 'active' ? 'status-active' : 
                                ($licenseInfo['status'] === 'inactive' ? 'status-inactive' : 'status-error') 
                            ?>">
                                <?= ucfirst(htmlspecialchars($licenseInfo['status'])) ?>
                            </p>
                        </div>
                        
                        <div class="info-card">
                            <h3>🌐 Domain</h3>
                            <p><?= htmlspecialchars($licenseInfo['domain']) ?></p>
                        </div>
                        
                        <div class="info-card">
                            <h3>🕒 Last Check</h3>
                            <p><?= isset($licenseInfo['last_check']) && $licenseInfo['last_check'] ? 
                                date('Y-m-d H:i:s', $licenseInfo['last_check']) : 'Never' ?></p>
                        </div>
                        
                        <div class="info-card">
                            <h3>🔍 Validation Type</h3>
                            <p><?= ucfirst(htmlspecialchars($licenseInfo['validation_type'] ?? 'Unknown')) ?></p>
                        </div>
                        
                        <div class="info-card">
                            <h3>✅ Verification Status</h3>
                            <p class="<?= $licenseInfo['verified'] ? 'status-active' : 'status-inactive' ?>">
                                <?= $licenseInfo['verified'] ? 'System Verified' : 'Verification Failed' ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if (isset($licenseInfo['expires']) && $licenseInfo['expires']): ?>
                        <div class="info-card" style="margin-top: 20px;">
                            <h3>⏰ Expires</h3>
                            <p><?= date('Y-m-d H:i:s', $licenseInfo['expires']) ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="system-info">
                        <strong>System Information:</strong><br>
                        Server Time: <?= date('Y-m-d H:i:s T') ?><br>
                        PHP Version: <?= PHP_VERSION ?><br>
                        System: <?= php_uname('s') . ' ' . php_uname('r') ?><br>
                        License System: Exchange Bridge Master License System v3.0.0
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <h3>⚠️ No License Information Available</h3>
                    <p>License verification system is not accessible. This could indicate:</p>
                    <ul>
                        <li>System installation is incomplete</li>
                        <li>License configuration files are missing</li>
                        <li>Permission issues with config directory</li>
                    </ul>
                    <p><strong>Recommendation:</strong> Please reinstall the script or contact support.</p>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 30px; text-align: center;">
                <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
                <button onclick="checkLicense()" class="btn">🔄 Refresh License Status</button>
                <div class="loading" id="loading">⏳ Checking license...</div>
            </div>
            
            <div id="result" style="margin-top: 20px;"></div>
        </div>
    </div>

    <script>
        function checkLicense() {
            const loadingEl = document.getElementById('loading');
            const resultEl = document.getElementById('result');
            const button = event.target;
            
            loadingEl.style.display = 'block';
            button.disabled = true;
            button.textContent = 'Checking...';
            resultEl.innerHTML = '';
            
            const formData = new FormData();
            formData.append('action', 'check_license');
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                loadingEl.style.display = 'none';
                button.disabled = false;
                button.textContent = '🔄 Refresh License Status';
                
                if (data.success) {
                    resultEl.innerHTML = '<div class="alert alert-success">✅ ' + data.message + '</div>';
                    setTimeout(() => location.reload(), 2000);
                } else {
                    resultEl.innerHTML = '<div class="alert alert-danger">❌ ' + data.message + '</div>';
                }
            })
            .catch(error => {
                loadingEl.style.display = 'none';
                button.disabled = false;
                button.textContent = '🔄 Refresh License Status';
                resultEl.innerHTML = '<div class="alert alert-danger">❌ Error: ' + error + '</div>';
            });
        }
        
        // Auto-refresh every 5 minutes
        setInterval(() => {
            checkLicense();
        }, 300000);
    </script>
</body>
</html>