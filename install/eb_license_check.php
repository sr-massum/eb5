<?php
/**
 * EB License System Protection File v2.0.0
 * DO NOT MODIFY THIS FILE
 * 
 * This file provides runtime license verification
 * and ensures continuous license compliance.
 */

// Prevent direct access
if(!defined('EB_SCRIPT_RUNNING')) {
    http_response_code(403);
    die('Access denied. This file is part of the EB License System.');
}

/**
 * Verify license status and domain compliance
 */
function eb_verify_license_runtime() {
    $v_file = __DIR__ . '/eb_verification.php';
    
    // Check if verification file exists
    if(!file_exists($v_file)) {
        http_response_code(403);
        die('License verification failed: Verification file not found. Please reinstall or reactivate your license.');
    }
    
    // Load verification data
    try {
        $data = include $v_file;
        if(!is_array($data)) {
            throw new Exception('Invalid verification file format');
        }
    } catch (Exception $e) {
        http_response_code(403);
        die('License verification failed: Corrupted verification file. Please reinstall your license.');
    }
    
    // Check required fields
    $required_fields = ['license_key', 'domain', 'hash', 'status', 'last_check'];
    foreach($required_fields as $field) {
        if(!isset($data[$field])) {
            http_response_code(403);
            die('License verification failed: Missing verification data. Please reinstall your license.');
        }
    }
    
    // Check if license is marked as inactive
    if($data['status'] !== 'active') {
        $reason = isset($data['deactivation_reason']) ? $data['deactivation_reason'] : 'License has been deactivated';
        http_response_code(403);
        die('License Error: ' . $reason . '. Please contact support.');
    }
    
    // Get current domain
    $current_domain = '';
    if (isset($_SERVER['HTTP_HOST'])) {
        $current_domain = $_SERVER['HTTP_HOST'];
    } elseif (isset($_SERVER['SERVER_NAME'])) {
        $current_domain = $_SERVER['SERVER_NAME'];
    }
    
    // Clean domain
    $current_domain = preg_replace('/^www\./i', '', $current_domain);
    $current_domain = preg_replace('/:\d+$/', '', $current_domain);
    $current_domain = strtolower(trim($current_domain));
    
    // Check domain compliance
    if($data['domain'] !== '*' && $data['domain'] !== $current_domain) {
        http_response_code(403);
        die('License Error: Domain mismatch. This license is valid for "' . $data['domain'] . '" but you are accessing from "' . $current_domain . '".');
    }
    
    // Verify file integrity
    $expected_hash = md5($data['license_key'] . $data['domain'] . 'eb_license_system_salt_key_2025');
    if($expected_hash !== $data['hash']) {
        http_response_code(403);
        die('License verification failed: File integrity check failed. Please reinstall your license.');
    }
    
    // Check expiry
    if(isset($data['expires']) && $data['expires'] > 0 && time() > $data['expires']) {
        http_response_code(403);
        die('License Error: Your license has expired on ' . date('Y-m-d', $data['expires']) . '. Please renew your license.');
    }
    
    // Check if server verification is needed
    $last_check = $data['last_check'];
    $current_time = time();
    $check_interval = 3600;
    
    if($current_time - $last_check > $check_interval) {
        // Time for server verification
        $license_key = $data['license_key'];
        $client_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        $post_data = [
            'action' => 'verify',
            'license_key' => $license_key,
            'domain' => $current_domain,
            'ip' => $client_ip,
            'api_key' => 'd7x9HgT2pL5vZwK8qY3rS6mN4jF1aE0b',
            'product' => 'default'
        ];
        
        $server_response = false;
        $api_url = 'https://eb-admin.rf.gd/api.php';
        
        // Try cURL first
        if (function_exists('curl_version')) {
            try {
                $ch = curl_init($api_url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
                curl_setopt($ch, CURLOPT_TIMEOUT, 15);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_USERAGENT, 'EB-License-Protection/2.0.0');
                
                $server_response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if($server_response === false || $http_code !== 200) {
                    $server_response = false;
                }
            } catch (Exception $e) {
                $server_response = false;
            }
        }
        
        // Fallback to file_get_contents
        if($server_response === false && function_exists('file_get_contents')) {
            try {
                $context = stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => 'Content-type: application/x-www-form-urlencoded',
                        'content' => http_build_query($post_data),
                        'timeout' => 15
                    ],
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false
                    ]
                ]);
                
                $server_response = @file_get_contents($api_url, false, $context);
            } catch (Exception $e) {
                $server_response = false;
            }
        }
        
        if($server_response !== false) {
            // Parse server response
            $result = json_decode($server_response, true);
            
            if(isset($result['status']) && $result['status'] === 'success') {
                // Check server license status
                if(isset($result['license_status']) && $result['license_status'] !== 'active') {
                    // License is inactive on server - mark locally and fail
                    $data['status'] = 'inactive';
                    $data['last_check'] = $current_time;
                    $data['deactivation_reason'] = $result['message'] ?? 'License deactivated on server';
                    
                    $content = "<?php\n// EB License System Verification File v2.0.0\n// DO NOT MODIFY THIS FILE\nreturn " . var_export($data, true) . ";\n";
                    @file_put_contents($v_file, $content);
                    
                    http_response_code(403);
                    die('License Error: ' . ($result['message'] ?? 'Your license has been deactivated on the server') . '. Please contact support.');
                }
                
                // Update last check time
                $data['last_check'] = $current_time;
                $data['server_status'] = $result['license_status'] ?? 'active';
                
                $content = "<?php\n// EB License System Verification File v2.0.0\n// DO NOT MODIFY THIS FILE\nreturn " . var_export($data, true) . ";\n";
                @file_put_contents($v_file, $content);
                
            } else {
                // Server validation failed
                if(isset($result['license_status']) && $result['license_status'] !== 'active') {
                    // License is explicitly inactive - no grace period
                    $data['status'] = 'inactive';
                    $data['last_check'] = $current_time;
                    $data['deactivation_reason'] = $result['message'] ?? 'License validation failed';
                    
                    $content = "<?php\n// EB License System Verification File v2.0.0\n// DO NOT MODIFY THIS FILE\nreturn " . var_export($data, true) . ";\n";
                    @file_put_contents($v_file, $content);
                    
                    http_response_code(403);
                    die('License Error: ' . ($result['message'] ?? 'Your license is no longer valid') . '. Please contact support.');
                }
                
                // Check grace period for other errors
                $grace_period = 604800;
                if($current_time - $last_check > $grace_period) {
                    http_response_code(403);
                    die('License Error: License verification failed and grace period expired. Please check your internet connection or contact support.');
                }
            }
        } else {
            // Server unreachable - check grace period
            $grace_period = 604800;
            if($current_time - $last_check > $grace_period) {
                http_response_code(403);
                die('License Error: Unable to contact license server for verification and grace period has expired. Please check your internet connection or contact support.');
            }
        }
    }
    
    return true;
}

// Execute runtime verification
try {
    eb_verify_license_runtime();
} catch (Exception $e) {
    http_response_code(403);
    die('License Error: ' . $e->getMessage());
}

// License verification successful
return true;