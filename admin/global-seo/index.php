<?php 

/**
 * ExchangeBridge - Admin Panel SEO
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
require_once '../../includes/seo-functions.php';

// Check if user is logged in, if not redirect to login page
if (!Auth::isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

$user = Auth::getUser();
$db = Database::getInstance();

// Helper functions
function getRootPathLocal() {
    $possiblePaths = [
        $_SERVER['DOCUMENT_ROOT'] ?? '',
        realpath(dirname(__FILE__) . '/../../'),
        realpath(dirname(__FILE__) . '/../../../'),
        dirname(dirname(dirname(__FILE__)))
    ];
    
    foreach ($possiblePaths as $path) {
        if (!empty($path) && is_dir($path)) {
            if (file_exists($path . '/index.php') || file_exists($path . '/index.html')) {
                return rtrim($path, '/\\');
            }
        }
    }
    
    $fallbackPath = $_SERVER['DOCUMENT_ROOT'] ?? dirname(dirname(dirname(__FILE__)));
    return rtrim($fallbackPath, '/\\');
}

function getBaseUrlLocal() {
    $baseUrl = getSetting('site_url', '');
    if (empty($baseUrl)) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $baseUrl = $protocol . $_SERVER['HTTP_HOST'];
    }
    return rtrim($baseUrl, '/');
}

// Enhanced sitemap generation with blog posts
function generateSitemapLocal() {
    try {
        $db = Database::getInstance();
        
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        $baseUrl = getSetting('site_url', getBaseUrlLocal());
        $baseUrl = rtrim($baseUrl, '/');
        
        // Homepage
        $sitemap .= '  <url>' . "\n";
        $sitemap .= '    <loc>' . htmlspecialchars($baseUrl) . '/</loc>' . "\n";
        $sitemap .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
        $sitemap .= '    <changefreq>daily</changefreq>' . "\n";
        $sitemap .= '    <priority>1.0</priority>' . "\n";
        $sitemap .= '  </url>' . "\n";
        
        // Static pages
        $staticPages = [
            'about.php' => ['changefreq' => 'monthly', 'priority' => '0.8'],
            'contact.php' => ['changefreq' => 'monthly', 'priority' => '0.7'],
            'faq.php' => ['changefreq' => 'weekly', 'priority' => '0.6'],
            'track.php' => ['changefreq' => 'daily', 'priority' => '0.9'],
            'blog.php' => ['changefreq' => 'daily', 'priority' => '0.8']
        ];
        
        $rootPath = getRootPathLocal();
        foreach ($staticPages as $page => $settings) {
            if (file_exists($rootPath . '/' . $page)) {
                $sitemap .= '  <url>' . "\n";
                $sitemap .= '    <loc>' . htmlspecialchars($baseUrl) . '/' . htmlspecialchars($page) . '</loc>' . "\n";
                $sitemap .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
                $sitemap .= '    <changefreq>' . $settings['changefreq'] . '</changefreq>' . "\n";
                $sitemap .= '    <priority>' . $settings['priority'] . '</priority>' . "\n";
                $sitemap .= '  </url>' . "\n";
            }
        }
        
        // Blog posts - Enhanced with single post URLs
        if ($db) {
            try {
                $stmt = $db->getConnection()->query("SHOW TABLES LIKE 'blog_posts'");
                if ($stmt && $stmt->rowCount() > 0) {
                    $posts = $db->getRows("SELECT slug, created_at, updated_at FROM blog_posts WHERE status = 'published' ORDER BY created_at DESC LIMIT 1000");
                    
                    foreach ($posts as $post) {
                        if (!empty($post['slug'])) {
                            $lastmod = $post['updated_at'] ? $post['updated_at'] : $post['created_at'];
                            
                            // Add blog-single.php URL
                            $sitemap .= '  <url>' . "\n";
                            $sitemap .= '    <loc>' . htmlspecialchars($baseUrl) . '/blog-single.php?slug=' . htmlspecialchars($post['slug']) . '</loc>' . "\n";
                            $sitemap .= '    <lastmod>' . date('Y-m-d', strtotime($lastmod)) . '</lastmod>' . "\n";
                            $sitemap .= '    <changefreq>weekly</changefreq>' . "\n";
                            $sitemap .= '    <priority>0.7</priority>' . "\n";
                            $sitemap .= '  </url>' . "\n";
                            
                            // Add SEO-friendly URL if available
                            if (file_exists($rootPath . '/blog/' . $post['slug'] . '.php') || file_exists($rootPath . '/blog/' . $post['slug'] . '/index.php')) {
                                $sitemap .= '  <url>' . "\n";
                                $sitemap .= '    <loc>' . htmlspecialchars($baseUrl) . '/blog/' . htmlspecialchars($post['slug']) . '</loc>' . "\n";
                                $sitemap .= '    <lastmod>' . date('Y-m-d', strtotime($lastmod)) . '</lastmod>' . "\n";
                                $sitemap .= '    <changefreq>weekly</changefreq>' . "\n";
                                $sitemap .= '    <priority>0.7</priority>' . "\n";
                                $sitemap .= '  </url>' . "\n";
                            }
                        }
                    }
                }
            } catch (PDOException $e) {
                error_log('Blog posts query failed: ' . $e->getMessage());
            }
        }
        
        $sitemap .= '</urlset>';
        
        $sitemapPath = $rootPath . '/sitemap.xml';
        
        if (!is_dir($rootPath)) {
            throw new Exception('Root directory does not exist: ' . $rootPath);
        }
        
        $uploadsDir = $rootPath . '/assets/uploads';
        if (!is_dir($uploadsDir)) {
            @mkdir($uploadsDir, 0755, true);
        }
        
        if (!is_writable($rootPath)) {
            @chmod($rootPath, 0755);
        }
        
        $result = @file_put_contents($sitemapPath, $sitemap, LOCK_EX);
        
        if ($result === false) {
            $altPath = dirname(__FILE__) . '/../../sitemap.xml';
            $result = @file_put_contents($altPath, $sitemap, LOCK_EX);
            
            if ($result === false) {
                throw new Exception('Failed to write sitemap file. Please check file permissions for: ' . $rootPath);
            } else {
                $sitemapPath = $altPath;
            }
        }
        
        if (!file_exists($sitemapPath) || filesize($sitemapPath) == 0) {
            throw new Exception('Sitemap file was not created successfully or is empty');
        }
        
        @chmod($sitemapPath, 0644);
        
        return $sitemapPath;
        
    } catch (Exception $e) {
        error_log('Sitemap generation error: ' . $e->getMessage());
        throw $e;
    }
}

// Generate robots.txt
function generateRobotsTxtLocal() {
    try {
        $baseUrl = getSetting('site_url', getBaseUrlLocal());
        $baseUrl = rtrim($baseUrl, '/');
        
        $robots = "User-agent: *\n";
        $robots .= "Allow: /\n";
        $robots .= "\n";
        $robots .= "# Disallow admin and sensitive directories\n";
        $robots .= "Disallow: /admin/\n";
        $robots .= "Disallow: /includes/\n";
        $robots .= "Disallow: /vendor/\n";
        $robots .= "Disallow: /config/\n";
        $robots .= "Disallow: /*.sql\n";
        $robots .= "Disallow: /*.log\n";
        $robots .= "\n";
        $robots .= "# Sitemap location\n";
        $robots .= "Sitemap: " . $baseUrl . "/sitemap.xml\n";
        
        $rootPath = getRootPathLocal();
        $robotsPath = $rootPath . '/robots.txt';
        
        $result = @file_put_contents($robotsPath, $robots, LOCK_EX);
        
        if ($result === false) {
            $altPath = dirname(__FILE__) . '/../../robots.txt';
            $result = @file_put_contents($altPath, $robots, LOCK_EX);
            
            if ($result === false) {
                throw new Exception('Failed to write robots.txt file. Please check file permissions.');
            } else {
                $robotsPath = $altPath;
            }
        }
        
        @chmod($robotsPath, 0644);
        
        return $robotsPath;
        
    } catch (Exception $e) {
        error_log('Robots.txt generation error: ' . $e->getMessage());
        throw $e;
    }
}

// Handle file uploads to SEO directory
function handleFileUploadLocal($file, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/x-icon', 'image/vnd.microsoft.icon']) {
    try {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . ($file['error'] ?? 'Unknown error'));
        }
        
        $fileType = $file['type'];
        if (!in_array($fileType, $allowedTypes)) {
            throw new Exception('Invalid file type. Allowed types: ' . implode(', ', $allowedTypes));
        }
        
        $uploadPath = getRootPathLocal() . '/assets/uploads/seo/';
        if (!is_dir($uploadPath)) {
            if (!mkdir($uploadPath, 0755, true)) {
                throw new Exception('Failed to create upload directory');
            }
        }
        
        $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $fileName = 'seo_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
        $fullUploadPath = $uploadPath . $fileName;
        
        if (!move_uploaded_file($file['tmp_name'], $fullUploadPath)) {
            throw new Exception('Failed to move uploaded file');
        }
        
        @chmod($fullUploadPath, 0644);
        
        // Log to media table
        try {
            $db = Database::getInstance();
            $user = Auth::getUser();
            $mediaData = [
                'filename' => $fileName,
                'original_name' => $file['name'],
                'file_path' => 'assets/uploads/seo/' . $fileName,
                'file_size' => $file['size'],
                'mime_type' => $file['type'],
                'file_type' => 'image',
                'uploaded_by' => $user['id'] ?? 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $db->insert('media', $mediaData);
        } catch (Exception $e) {
            error_log('Media logging failed: ' . $e->getMessage());
        }
        
        return 'assets/uploads/seo/' . $fileName;
        
    } catch (Exception $e) {
        error_log('File upload error: ' . $e->getMessage());
        throw $e;
    }
}

// Handle media selection from existing library
function handleMediaSelection($mediaId) {
    try {
        $db = Database::getInstance();
        $media = $db->getRow("SELECT * FROM media WHERE id = ?", [$mediaId]);
        
        if (!$media) {
            throw new Exception('Media file not found');
        }
        
        $sourcePath = getRootPathLocal() . '/' . $media['file_path'];
        if (!file_exists($sourcePath)) {
            throw new Exception('Source media file does not exist');
        }
        
        // Copy to SEO directory
        $uploadDir = getRootPathLocal() . '/assets/uploads/seo/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $extension = pathinfo($media['file_path'], PATHINFO_EXTENSION);
        $newFileName = 'seo_' . time() . '_' . rand(1000, 9999) . '.' . $extension;
        $targetPath = $uploadDir . $newFileName;
        
        if (copy($sourcePath, $targetPath)) {
            @chmod($targetPath, 0644);
            
            // Log new media entry
            $user = Auth::getUser();
            $newMediaData = [
                'filename' => $newFileName,
                'original_name' => $media['original_name'],
                'file_path' => 'assets/uploads/seo/' . $newFileName,
                'file_size' => $media['file_size'],
                'mime_type' => $media['mime_type'],
                'file_type' => $media['file_type'],
                'uploaded_by' => $user['id'] ?? 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $db->insert('media', $newMediaData);
            
            return 'assets/uploads/seo/' . $newFileName;
        } else {
            throw new Exception('Failed to copy media file');
        }
        
    } catch (Exception $e) {
        error_log('Media selection error: ' . $e->getMessage());
        throw $e;
    }
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            throw new Exception('Invalid CSRF token. Please refresh the page and try again.');
        }
        
        // Handle sitemap generation
        if (isset($_POST['generate_sitemap'])) {
            $sitemapPath = generateSitemapLocal();
            $_SESSION['success_message'] = 'Sitemap generated successfully at: ' . $sitemapPath;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
        
        // Handle robots.txt generation
        elseif (isset($_POST['generate_robots'])) {
            $robotsPath = generateRobotsTxtLocal();
            $_SESSION['success_message'] = 'Robots.txt generated successfully at: ' . $robotsPath;
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
        
        // Handle SEO settings save
        elseif (isset($_POST['save_seo_settings'])) {
            $settingsUpdated = 0;
            $settingsFailed = 0;
            
            // Basic SEO settings
            $seoSettings = [
                'seo_meta_title' => $_POST['seo_meta_title'] ?? '',
                'seo_meta_description' => $_POST['seo_meta_description'] ?? '',
                'seo_meta_keywords' => $_POST['seo_meta_keywords'] ?? '',
                'seo_canonical_url' => $_POST['seo_canonical_url'] ?? '',
                'site_url' => $_POST['site_url'] ?? '',
                'site_logo_text' => $_POST['site_logo_text'] ?? '',
                'google_analytics_code' => $_POST['google_analytics_code'] ?? '',
                'google_site_verification' => $_POST['google_site_verification'] ?? '',
                'bing_site_verification' => $_POST['bing_site_verification'] ?? '',
                'yandex_site_verification' => $_POST['yandex_site_verification'] ?? '',
                'pinterest_site_verification' => $_POST['pinterest_site_verification'] ?? '',
                'open_graph_enabled' => isset($_POST['open_graph_enabled']) ? '1' : '0',
                'twitter_cards_enabled' => isset($_POST['twitter_cards_enabled']) ? '1' : '0',
                'structured_data_enabled' => isset($_POST['structured_data_enabled']) ? '1' : '0',
                'sitemap_enabled' => isset($_POST['sitemap_enabled']) ? '1' : '0',
                'robots_txt_enabled' => isset($_POST['robots_txt_enabled']) ? '1' : '0',
                'seo_og_title' => $_POST['seo_og_title'] ?? '',
                'seo_og_description' => $_POST['seo_og_description'] ?? '',
                'seo_twitter_title' => $_POST['seo_twitter_title'] ?? '',
                'seo_twitter_description' => $_POST['seo_twitter_description'] ?? '',
                'business_type' => $_POST['business_type'] ?? 'FinancialService',
                'contact_phone' => $_POST['contact_phone'] ?? '',
                'contact_email' => $_POST['contact_email'] ?? '',
                'address_street' => $_POST['address_street'] ?? '',
                'address_city' => $_POST['address_city'] ?? '',
                'address_state' => $_POST['address_state'] ?? '',
                'address_postal' => $_POST['address_postal'] ?? '',
                'address_country' => $_POST['address_country'] ?? '',
                'social_facebook' => $_POST['social_facebook'] ?? '',
                'social_twitter' => $_POST['social_twitter'] ?? '',
                'social_instagram' => $_POST['social_instagram'] ?? '',
                'social_linkedin' => $_POST['social_linkedin'] ?? '',
                'schema_organization' => isset($_POST['schema_organization']) ? '1' : '0',
                'schema_website' => isset($_POST['schema_website']) ? '1' : '0',
                'schema_breadcrumbs' => isset($_POST['schema_breadcrumbs']) ? '1' : '0'
            ];
            
            foreach ($seoSettings as $key => $value) {
                if (updateSetting($key, $value)) {
                    $settingsUpdated++;
                } else {
                    $settingsFailed++;
                    error_log('Failed to update setting: ' . $key);
                }
            }
            
            // Handle file uploads and media selection
            if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] === UPLOAD_ERR_OK) {
                try {
                    $faviconPath = handleFileUploadLocal($_FILES['site_favicon']);
                    if (updateSetting('site_favicon', $faviconPath)) {
                        $settingsUpdated++;
                    } else {
                        $settingsFailed++;
                    }
                } catch (Exception $e) {
                    $_SESSION['error_message'] = 'Favicon upload failed: ' . $e->getMessage();
                }
            } elseif (isset($_POST['selected_favicon_media']) && !empty($_POST['selected_favicon_media'])) {
                try {
                    $faviconPath = handleMediaSelection($_POST['selected_favicon_media']);
                    if (updateSetting('site_favicon', $faviconPath)) {
                        $settingsUpdated++;
                    } else {
                        $settingsFailed++;
                    }
                } catch (Exception $e) {
                    $_SESSION['error_message'] = 'Favicon selection failed: ' . $e->getMessage();
                }
            }
            
            if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
                try {
                    $logoPath = handleFileUploadLocal($_FILES['site_logo']);
                    if (updateSetting('site_logo', $logoPath)) {
                        $settingsUpdated++;
                    } else {
                        $settingsFailed++;
                    }
                } catch (Exception $e) {
                    $_SESSION['error_message'] = 'Logo upload failed: ' . $e->getMessage();
                }
            } elseif (isset($_POST['selected_logo_media']) && !empty($_POST['selected_logo_media'])) {
                try {
                    $logoPath = handleMediaSelection($_POST['selected_logo_media']);
                    if (updateSetting('site_logo', $logoPath)) {
                        $settingsUpdated++;
                    } else {
                        $settingsFailed++;
                    }
                } catch (Exception $e) {
                    $_SESSION['error_message'] = 'Logo selection failed: ' . $e->getMessage();
                }
            }
            
            if (isset($_FILES['seo_og_image']) && $_FILES['seo_og_image']['error'] === UPLOAD_ERR_OK) {
                try {
                    $ogImagePath = handleFileUploadLocal($_FILES['seo_og_image']);
                    if (updateSetting('seo_og_image', $ogImagePath)) {
                        $settingsUpdated++;
                    } else {
                        $settingsFailed++;
                    }
                } catch (Exception $e) {
                    $_SESSION['error_message'] = 'OG image upload failed: ' . $e->getMessage();
                }
            } elseif (isset($_POST['selected_og_media']) && !empty($_POST['selected_og_media'])) {
                try {
                    $ogImagePath = handleMediaSelection($_POST['selected_og_media']);
                    if (updateSetting('seo_og_image', $ogImagePath)) {
                        $settingsUpdated++;
                    } else {
                        $settingsFailed++;
                    }
                } catch (Exception $e) {
                    $_SESSION['error_message'] = 'OG image selection failed: ' . $e->getMessage();
                }
            }
            
            if ($settingsUpdated > 0) {
                $_SESSION['success_message'] = "SEO settings updated successfully! ($settingsUpdated settings updated)";
                if ($settingsFailed > 0) {
                    $_SESSION['success_message'] .= " ($settingsFailed settings failed to update)";
                }
            } else {
                $_SESSION['error_message'] = 'No settings were updated. Please check the error logs.';
            }
            
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = 'Error: ' . $e->getMessage();
        error_log('Form processing error: ' . $e->getMessage());
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Check for success message
$successMessage = '';
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Check for error message
$errorMessage = '';
if (isset($_SESSION['error_message'])) {
    $errorMessage = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Get media files for selection
$mediaFiles = $db->getRows("SELECT * FROM media WHERE file_type = 'image' ORDER BY created_at DESC LIMIT 100");

// Use SEO score from seo-functions.php
$seoScore = calculateSEOScore();

// Include header
include '../includes/header.php';
?>

<style>
.seo-score-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    font-weight: bold;
    margin: 0 auto;
}
.score-excellent { background: #28a745; color: white; }
.score-good { background: #ffc107; color: white; }
.score-poor { background: #dc3545; color: white; }

/* Media selector styles */
.media-selector {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 10px;
    background-color: #f8f9fa;
}

.media-item {
    display: inline-block;
    margin: 5px;
    cursor: pointer;
    border: 2px solid transparent;
    border-radius: 4px;
    padding: 5px;
    position: relative;
    transition: all 0.3s ease;
}

.media-item:hover {
    border-color: #007bff;
    transform: scale(1.05);
}

.media-item.selected {
    border-color: #28a745;
    background-color: #e8f5e8;
}

.media-item img {
    width: 60px;
    height: 45px;
    object-fit: cover;
    border-radius: 4px;
    display: block;
}

.media-item-name {
    font-size: 9px;
    text-align: center;
    margin-top: 2px;
    color: #666;
    word-break: break-all;
}

.upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
    background-color: #f8f9fa;
    transition: all 0.3s ease;
    margin-bottom: 10px;
}

.upload-area:hover {
    border-color: #007bff;
    background-color: #e7f3ff;
}

.upload-area.dragover {
    border-color: #28a745;
    background-color: #e8f5e8;
}
</style>

<!-- Breadcrumbs-->
<ol class="breadcrumb">
    <li class="breadcrumb-item">
        <a href="../index.php">Dashboard</a>
    </li>
    <li class="breadcrumb-item active">Global SEO Settings</li>
</ol>

<!-- Page Content -->
<div class="row mb-4">
    <div class="col-md-8">
        <h1 class="h3 mb-0">Global SEO Settings</h1>
        <p class="text-muted">Manage your website's search engine optimization settings</p>
    </div>
    <div class="col-md-4">
        <div class="text-center">
            <div class="seo-score-circle <?php 
                echo $seoScore >= 80 ? 'score-excellent' : 
                    ($seoScore >= 60 ? 'score-good' : 'score-poor'); 
            ?>">
                <?php echo $seoScore; ?>%
            </div>
            <small class="text-muted">SEO Score</small>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (!empty($successMessage)): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="fas fa-check-circle mr-1"></i>
    <?php echo htmlspecialchars($successMessage); ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<?php if (!empty($errorMessage)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="fas fa-exclamation-circle mr-1"></i>
    <?php echo htmlspecialchars($errorMessage); ?>
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
        <span aria-hidden="true">&times;</span>
    </button>
</div>
<?php endif; ?>

<!-- SEO Settings Form -->
<form method="POST" enctype="multipart/form-data" id="seoForm">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <input type="hidden" id="selected_favicon_media" name="selected_favicon_media">
    <input type="hidden" id="selected_logo_media" name="selected_logo_media">
    <input type="hidden" id="selected_og_media" name="selected_og_media">
    
    <!-- Basic SEO Settings -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-search mr-1"></i> Basic SEO Settings
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="seo_meta_title">Meta Title</label>
                        <input type="text" class="form-control" id="seo_meta_title" name="seo_meta_title" 
                               value="<?php echo htmlspecialchars(getSetting('seo_meta_title', '')); ?>" 
                               maxlength="60" placeholder="Meta Title">
                        <small class="form-text text-muted">Recommended: 50-60 characters</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="site_url">Site URL</label>
                        <input type="url" class="form-control" id="site_url" name="site_url" 
                               value="<?php echo htmlspecialchars(getSetting('site_url', '')); ?>" 
                               placeholder="https://yoursite.com">
                        <small class="form-text text-muted">Your website's primary URL</small>
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="seo_meta_description">Meta Description</label>
                <textarea class="form-control" id="seo_meta_description" name="seo_meta_description" 
                          rows="3" maxlength="160" placeholder="Meta Description"><?php echo htmlspecialchars(getSetting('seo_meta_description', '')); ?></textarea>
                <small class="form-text text-muted">Recommended: 150-160 characters</small>
            </div>
            
            <div class="form-group">
                <label for="seo_meta_keywords">Meta Keywords</label>
                <textarea class="form-control" id="seo_meta_keywords" name="seo_meta_keywords" 
                          rows="2" placeholder="keyword1, keyword2, keyword3"><?php echo htmlspecialchars(getSetting('seo_meta_keywords', '')); ?></textarea>
                <small class="form-text text-muted">Comma-separated keywords (optional)</small>
            </div>
            
            <div class="form-group">
                <label for="seo_canonical_url">Canonical URL</label>
                <input type="url" class="form-control" id="seo_canonical_url" name="seo_canonical_url" 
                       value="<?php echo htmlspecialchars(getSetting('seo_canonical_url', '')); ?>" 
                       placeholder="https://yoursite.com">
                <small class="form-text text-muted">Leave empty to use current URL</small>
            </div>
        </div>
    </div>

    <!-- Site Branding -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-palette mr-1"></i> Site Branding
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="site_logo_text">Site Logo Text</label>
                <input type="text" class="form-control" id="site_logo_text" name="site_logo_text" 
                       value="<?php echo htmlspecialchars(getSetting('site_logo_text', '')); ?>" 
                       placeholder="Your Site Name">
            </div>
            
            <!-- Favicon Upload/Selection -->
            <div class="form-group">
                <label>Favicon</label>
                <div class="upload-area" id="favicon-upload-area">
                    <input type="file" id="site_favicon" name="site_favicon" accept="image/x-icon,image/png,image/gif" style="display: none;">
                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                    <p class="mb-2">Upload new favicon or select from library</p>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('site_favicon').click();">
                        Upload New
                    </button>
                </div>
                
                <?php if (count($mediaFiles) > 0): ?>
                <div class="mt-2">
                    <label class="form-label">Or select from Media Library:</label>
                    <div class="media-selector" id="favicon-media-selector">
                        <?php foreach ($mediaFiles as $media): ?>
                            <?php $mediaUrl = SITE_URL . '/' . $media['file_path']; ?>
                            <div class="media-item" data-id="<?php echo $media['id']; ?>" data-type="favicon">
                                <img src="<?php echo $mediaUrl; ?>" alt="<?php echo htmlspecialchars($media['original_name']); ?>" 
                                     onerror="this.style.display='none'">
                                <div class="media-item-name">
                                    <?php echo mb_substr($media['original_name'], 0, 10); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (getSetting('site_favicon')): ?>
                    <div class="mt-2">
                        <small class="text-muted">Current: <?php echo htmlspecialchars(getSetting('site_favicon')); ?></small>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Logo Upload/Selection -->
            <div class="form-group">
                <label>Site Logo</label>
                <div class="upload-area" id="logo-upload-area">
                    <input type="file" id="site_logo" name="site_logo" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;">
                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                    <p class="mb-2">Upload new logo or select from library</p>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('site_logo').click();">
                        Upload New
                    </button>
                </div>
                
                <?php if (count($mediaFiles) > 0): ?>
                <div class="mt-2">
                    <label class="form-label">Or select from Media Library:</label>
                    <div class="media-selector" id="logo-media-selector">
                        <?php foreach ($mediaFiles as $media): ?>
                            <?php $mediaUrl = SITE_URL . '/' . $media['file_path']; ?>
                            <div class="media-item" data-id="<?php echo $media['id']; ?>" data-type="logo">
                                <img src="<?php echo $mediaUrl; ?>" alt="<?php echo htmlspecialchars($media['original_name']); ?>" 
                                     onerror="this.style.display='none'">
                                <div class="media-item-name">
                                    <?php echo mb_substr($media['original_name'], 0, 10); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if (getSetting('site_logo')): ?>
                    <div class="mt-2">
                        <small class="text-muted">Current: <?php echo htmlspecialchars(getSetting('site_logo')); ?></small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Analytics & Verification -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-chart-line mr-1"></i> Analytics & Verification
        </div>
        <div class="card-body">
            <div class="form-group">
                <label for="google_analytics_code">Google Analytics Code</label>
                <textarea class="form-control" id="google_analytics_code" name="google_analytics_code" 
                          rows="4" placeholder="Paste your Google Analytics tracking code here"><?php echo htmlspecialchars(getSetting('google_analytics_code', '')); ?></textarea>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="google_site_verification">Google Site Verification</label>
                        <input type="text" class="form-control" id="google_site_verification" name="google_site_verification" 
                               value="<?php echo htmlspecialchars(getSetting('google_site_verification', '')); ?>" 
                               placeholder="Google verification code">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="bing_site_verification">Bing Site Verification</label>
                        <input type="text" class="form-control" id="bing_site_verification" name="bing_site_verification" 
                               value="<?php echo htmlspecialchars(getSetting('bing_site_verification', '')); ?>" 
                               placeholder="Bing verification code">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="yandex_site_verification">Yandex Site Verification</label>
                        <input type="text" class="form-control" id="yandex_site_verification" name="yandex_site_verification" 
                               value="<?php echo htmlspecialchars(getSetting('yandex_site_verification', '')); ?>" 
                               placeholder="Yandex verification code">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="pinterest_site_verification">Pinterest Site Verification</label>
                        <input type="text" class="form-control" id="pinterest_site_verification" name="pinterest_site_verification" 
                               value="<?php echo htmlspecialchars(getSetting('pinterest_site_verification', '')); ?>" 
                               placeholder="Pinterest verification code">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Social Media & Open Graph -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-share-alt mr-1"></i> Social Media & Open Graph
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="open_graph_enabled" name="open_graph_enabled" 
                               <?php echo getSetting('open_graph_enabled', '1') === '1' ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="open_graph_enabled">
                            Enable Open Graph Meta Tags
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="twitter_cards_enabled" name="twitter_cards_enabled" 
                               <?php echo getSetting('twitter_cards_enabled', '1') === '1' ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="twitter_cards_enabled">
                            Enable Twitter Cards
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="seo_og_title">Open Graph Title</label>
                        <input type="text" class="form-control" id="seo_og_title" name="seo_og_title" 
                               value="<?php echo htmlspecialchars(getSetting('seo_og_title', '')); ?>" 
                               placeholder="Open Graph Title">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="seo_twitter_title">Twitter Title</label>
                        <input type="text" class="form-control" id="seo_twitter_title" name="seo_twitter_title" 
                               value="<?php echo htmlspecialchars(getSetting('seo_twitter_title', '')); ?>" 
                               placeholder="Twitter Title">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="seo_og_description">Open Graph Description</label>
                        <textarea class="form-control" id="seo_og_description" name="seo_og_description" 
                                  rows="3" placeholder="Open Graph Description"><?php echo htmlspecialchars(getSetting('seo_og_description', '')); ?></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="seo_twitter_description">Twitter Description</label>
                        <textarea class="form-control" id="seo_twitter_description" name="seo_twitter_description" 
                                  rows="3" placeholder="Twitter Description"><?php echo htmlspecialchars(getSetting('seo_twitter_description', '')); ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- OG Image Upload/Selection -->
            <div class="form-group">
                <label>Open Graph Image</label>
                <div class="upload-area" id="og-upload-area">
                    <input type="file" id="seo_og_image" name="seo_og_image" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;">
                    <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                    <p class="mb-2">Upload new OG image or select from library</p>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('seo_og_image').click();">
                        Upload New
                    </button>
                </div>
                
                <?php if (count($mediaFiles) > 0): ?>
                <div class="mt-2">
                    <label class="form-label">Or select from Media Library:</label>
                    <div class="media-selector" id="og-media-selector">
                        <?php foreach ($mediaFiles as $media): ?>
                            <?php $mediaUrl = SITE_URL . '/' . $media['file_path']; ?>
                            <div class="media-item" data-id="<?php echo $media['id']; ?>" data-type="og">
                                <img src="<?php echo $mediaUrl; ?>" alt="<?php echo htmlspecialchars($media['original_name']); ?>" 
                                     onerror="this.style.display='none'">
                                <div class="media-item-name">
                                    <?php echo mb_substr($media['original_name'], 0, 10); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <small class="form-text text-muted">Recommended size: 1200x630 pixels</small>
                <?php if (getSetting('seo_og_image')): ?>
                    <div class="mt-2">
                        <small class="text-muted">Current: <?php echo htmlspecialchars(getSetting('seo_og_image')); ?></small>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="social_facebook">Facebook URL</label>
                        <input type="url" class="form-control" id="social_facebook" name="social_facebook" 
                               value="<?php echo htmlspecialchars(getSetting('social_facebook', '')); ?>" 
                               placeholder="https://facebook.com/yourpage">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="social_twitter">Twitter URL</label>
                        <input type="url" class="form-control" id="social_twitter" name="social_twitter" 
                               value="<?php echo htmlspecialchars(getSetting('social_twitter', '')); ?>" 
                               placeholder="https://twitter.com/youraccount">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="social_instagram">Instagram URL</label>
                        <input type="url" class="form-control" id="social_instagram" name="social_instagram" 
                               value="<?php echo htmlspecialchars(getSetting('social_instagram', '')); ?>" 
                               placeholder="https://instagram.com/youraccount">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="social_linkedin">LinkedIn URL</label>
                        <input type="url" class="form-control" id="social_linkedin" name="social_linkedin" 
                               value="<?php echo htmlspecialchars(getSetting('social_linkedin', '')); ?>" 
                               placeholder="https://linkedin.com/company/yourcompany">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Schema Markup -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-code mr-1"></i> Schema Markup
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="structured_data_enabled" name="structured_data_enabled" 
                               <?php echo getSetting('structured_data_enabled', '1') === '1' ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="structured_data_enabled">
                            Enable Structured Data
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="schema_organization" name="schema_organization" 
                               <?php echo getSetting('schema_organization', '1') === '1' ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="schema_organization">
                            Organization Schema
                        </label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="schema_website" name="schema_website" 
                               <?php echo getSetting('schema_website', '1') === '1' ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="schema_website">
                            Website Schema
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="custom-control custom-checkbox mb-3">
                <input type="checkbox" class="custom-control-input" id="schema_breadcrumbs" name="schema_breadcrumbs" 
                       <?php echo getSetting('schema_breadcrumbs', '1') === '1' ? 'checked' : ''; ?>>
                <label class="custom-control-label" for="schema_breadcrumbs">
                    Breadcrumbs Schema
                </label>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="business_type">Business Type</label>
                        <select class="form-control" id="business_type" name="business_type">
                            <option value="FinancialService" <?php echo getSetting('business_type', 'FinancialService') === 'FinancialService' ? 'selected' : ''; ?>>Financial Service</option>
                            <option value="LocalBusiness" <?php echo getSetting('business_type', 'FinancialService') === 'LocalBusiness' ? 'selected' : ''; ?>>Local Business</option>
                            <option value="Corporation" <?php echo getSetting('business_type', 'FinancialService') === 'Corporation' ? 'selected' : ''; ?>>Corporation</option>
                            <option value="Organization" <?php echo getSetting('business_type', 'FinancialService') === 'Organization' ? 'selected' : ''; ?>>Organization</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="contact_phone">Contact Phone</label>
                        <input type="tel" class="form-control" id="contact_phone" name="contact_phone" 
                               value="<?php echo htmlspecialchars(getSetting('contact_phone', '')); ?>" 
                               placeholder="+1-234-567-8900">
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="contact_email">Contact Email</label>
                <input type="email" class="form-control" id="contact_email" name="contact_email" 
                       value="<?php echo htmlspecialchars(getSetting('contact_email', '')); ?>" 
                       placeholder="contact@yoursite.com">
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="address_street">Street Address</label>
                        <input type="text" class="form-control" id="address_street" name="address_street" 
                               value="<?php echo htmlspecialchars(getSetting('address_street', '')); ?>" 
                               placeholder="123 Main Street">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="address_city">City</label>
                        <input type="text" class="form-control" id="address_city" name="address_city" 
                               value="<?php echo htmlspecialchars(getSetting('address_city', '')); ?>" 
                               placeholder="New York">
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="address_state">State</label>
                        <input type="text" class="form-control" id="address_state" name="address_state" 
                               value="<?php echo htmlspecialchars(getSetting('address_state', '')); ?>" 
                               placeholder="NY">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="address_postal">Postal Code</label>
                        <input type="text" class="form-control" id="address_postal" name="address_postal" 
                               value="<?php echo htmlspecialchars(getSetting('address_postal', '')); ?>" 
                               placeholder="10001">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="address_country">Country</label>
                        <input type="text" class="form-control" id="address_country" name="address_country" 
                               value="<?php echo htmlspecialchars(getSetting('address_country', '')); ?>" 
                               placeholder="United States">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Sitemap & Robots -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-sitemap mr-1"></i> Sitemap & Robots
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="sitemap_enabled" name="sitemap_enabled" 
                               <?php echo getSetting('sitemap_enabled', '1') === '1' ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="sitemap_enabled">
                            Enable Sitemap Generation
                        </label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="robots_txt_enabled" name="robots_txt_enabled" 
                               <?php echo getSetting('robots_txt_enabled', '1') === '1' ? 'checked' : ''; ?>>
                        <label class="custom-control-label" for="robots_txt_enabled">
                            Enable Robots.txt Generation
                        </label>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <button type="submit" name="generate_sitemap" class="btn btn-outline-success btn-block">
                        <i class="fas fa-sitemap mr-1"></i> Generate Sitemap
                    </button>
                </div>
                <div class="col-md-6">
                    <button type="submit" name="generate_robots" class="btn btn-outline-success btn-block">
                        <i class="fas fa-robot mr-1"></i> Generate Robots.txt
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="card mb-4">
        <div class="card-body text-center">
            <button type="submit" name="save_seo_settings" class="btn btn-primary btn-lg" id="saveBtn">
                <i class="fas fa-save mr-1"></i> Save SEO Settings
            </button>
        </div>
    </div>
</form>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<script>
$(document).ready(function() {
    // Media selection functionality
    $('.media-item').click(function() {
        const type = $(this).data('type');
        const mediaId = $(this).data('id');
        
        // Clear other selections of the same type
        $(`[data-type="${type}"]`).removeClass('selected');
        $(this).addClass('selected');
        
        // Set the hidden input
        $(`#selected_${type}_media`).val(mediaId);
        
        // Clear file input
        $(`#site_${type}, #seo_og_image`).val('');
    });
    
    // Clear media selection when file input is used
    $('#site_favicon, #site_logo, #seo_og_image').change(function() {
        const inputId = $(this).attr('id');
        let type = '';
        
        if (inputId === 'site_favicon') type = 'favicon';
        else if (inputId === 'site_logo') type = 'logo';
        else if (inputId === 'seo_og_image') type = 'og';
        
        if (type) {
            $(`[data-type="${type}"]`).removeClass('selected');
            $(`#selected_${type}_media`).val('');
        }
    });
    
    // Drag and drop functionality
    function setupDragDrop(uploadAreaId, fileInputId) {
        const uploadArea = document.getElementById(uploadAreaId);
        const fileInput = document.getElementById(fileInputId);
        
        if (!uploadArea || !fileInput) return;
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                
                // Clear media library selection
                let type = '';
                if (fileInputId === 'site_favicon') type = 'favicon';
                else if (fileInputId === 'site_logo') type = 'logo';
                else if (fileInputId === 'seo_og_image') type = 'og';
                
                if (type) {
                    $(`[data-type="${type}"]`).removeClass('selected');
                    $(`#selected_${type}_media`).val('');
                }
            }
        });
        
        uploadArea.addEventListener('click', function() {
            fileInput.click();
        });
    }
    
    // Setup drag and drop for all upload areas
    setupDragDrop('favicon-upload-area', 'site_favicon');
    setupDragDrop('logo-upload-area', 'site_logo');
    setupDragDrop('og-upload-area', 'seo_og_image');
    
    // Character counters
    function updateCharacterCount(element, maxLength) {
        const currentLength = element.value.length;
        const helpText = element.parentElement.querySelector('.form-text');
        if (helpText && helpText.textContent.includes('characters')) {
            helpText.textContent = `${currentLength}/${maxLength} characters - ` + helpText.textContent.split(' - ')[1];
            if (currentLength > maxLength) {
                helpText.classList.add('text-danger');
            } else {
                helpText.classList.remove('text-danger');
            }
        }
    }
    
    $('#seo_meta_title').on('input', function() {
        updateCharacterCount(this, 60);
    });
    
    $('#seo_meta_description').on('input', function() {
        updateCharacterCount(this, 160);
    });
    
    // Form validation
    $('#seoForm').on('submit', function(e) {
        const submitType = e.originalEvent.submitter.name;
        
        if (submitType === 'save_seo_settings') {
            // Show loading state
            $('#saveBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Saving...');
        }
        
        // Validate URL fields
        const urlFields = ['site_url', 'seo_canonical_url', 'social_facebook', 'social_twitter', 'social_instagram', 'social_linkedin'];
        let isValid = true;
        
        urlFields.forEach(function(fieldId) {
            const field = document.getElementById(fieldId);
            if (field && field.value.trim()) {
                try {
                    new URL(field.value);
                    field.classList.remove('is-invalid');
                } catch (e) {
                    field.classList.add('is-invalid');
                    isValid = false;
                }
            }
        });
        
        // Validate email field
        const emailField = document.getElementById('contact_email');
        if (emailField && emailField.value.trim()) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailField.value)) {
                emailField.classList.add('is-invalid');
                isValid = false;
            } else {
                emailField.classList.remove('is-invalid');
            }
        }
        
        if (!isValid && submitType === 'save_seo_settings') {
            e.preventDefault();
            $('#saveBtn').prop('disabled', false).html('<i class="fas fa-save mr-1"></i> Save SEO Settings');
            alert('Please fix the validation errors before saving.');
            return false;
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>