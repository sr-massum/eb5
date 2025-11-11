<?php 

/**
 * ExchangeBridge - Admin Panel SEO Preview
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

$baseUrl = getSetting('site_url', 'https://yourdomain.com');
$baseUrl = rtrim($baseUrl, '/');
?>
<!DOCTYPE html>
<html lang="<?php echo getSetting('site_language', 'en'); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars(getSetting('seo_meta_title', 'Preview Page')); ?></title>
    
    <?php echo generateSEOTags(); ?>
    <?php echo generateStructuredDataScripts(); ?>
    
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f8f9fa;
        }
        .preview-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-height: 60px;
            margin-bottom: 10px;
        }
        .tagline {
            color: #666;
            font-style: italic;
        }
        .seo-info {
            background: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .seo-info h3 {
            margin-top: 0;
            color: #495057;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            background: #007bff;
            color: white;
            border-radius: 3px;
            font-size: 0.8em;
            margin: 2px;
        }
        .badge.success { background: #28a745; }
        .badge.warning { background: #ffc107; color: #212529; }
        .badge.danger { background: #dc3545; }
        .social-links {
            margin: 20px 0;
        }
        .social-links a {
            margin: 0 10px;
            text-decoration: none;
            color: #007bff;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="preview-card">
        <div class="header">
            <?php if (!empty(getSetting('site_logo', ''))): ?>
                <img src="<?php echo $baseUrl; ?>/<?php echo getSetting('site_logo', ''); ?>" alt="Logo" class="logo">
            <?php endif; ?>
            <h1><?php echo htmlspecialchars(getSetting('site_logo_text', 'Your Site')); ?></h1>
            <?php if (!empty(getSetting('site_tagline', ''))): ?>
                <p class="tagline"><?php echo htmlspecialchars(getSetting('site_tagline', '')); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="seo-info">
            <h3>SEO Configuration Preview</h3>
            <p><strong>Meta Title:</strong> <?php echo htmlspecialchars(getSetting('seo_meta_title', 'Not set')); ?></p>
            <p><strong>Meta Description:</strong> <?php echo htmlspecialchars(getSetting('seo_meta_description', 'Not set')); ?></p>
            <p><strong>Language:</strong> <?php echo getSetting('site_language', 'en'); ?></p>
            <p><strong>Region:</strong> <?php echo getSetting('site_region', 'Not set'); ?></p>
            
            <div style="margin-top: 15px;">
                <strong>Features Enabled:</strong><br>
                <?php if (getSetting('open_graph_enabled', '1') === '1'): ?>
                    <span class="badge success">Open Graph</span>
                <?php endif; ?>
                <?php if (getSetting('twitter_cards_enabled', '1') === '1'): ?>
                    <span class="badge success">Twitter Cards</span>
                <?php endif; ?>
                <?php if (getSetting('structured_data_enabled', '1') === '1'): ?>
                    <span class="badge success">Schema Markup</span>
                <?php endif; ?>
                <?php if (getSetting('sitemap_enabled', '1') === '1'): ?>
                    <span class="badge success">XML Sitemap</span>
                <?php endif; ?>
                <?php if (!empty(getSetting('google_analytics_id', ''))): ?>
                    <span class="badge success">Google Analytics</span>
                <?php endif; ?>
            </div>
        </div>
        
        <h2>Welcome to Our Site</h2>
        <p>This is a preview page to demonstrate how your SEO settings are applied. The page includes all the meta tags, structured data, and tracking codes you've configured in the admin panel.</p>
        
        <h3>Sample Content</h3>
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.</p>
        
        <?php 
        $socialLinks = array_filter([
            'Facebook' => getSetting('social_facebook', ''),
            'Twitter' => getSetting('social_twitter', ''),
            'Instagram' => getSetting('social_instagram', ''),
            'LinkedIn' => getSetting('social_linkedin', '')
        ]);
        
        if (!empty($socialLinks)): ?>
        <div class="social-links">
            <strong>Follow Us:</strong>
            <?php foreach ($socialLinks as $platform => $url): ?>
                <a href="<?php echo htmlspecialchars($url); ?>" target="_blank"><?php echo $platform; ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <h4>SEO Test Links:</h4>
            <ul>
                <li><a href="<?php echo $baseUrl; ?>/sitemap.xml" target="_blank">View XML Sitemap</a></li>
                <li><a href="<?php echo $baseUrl; ?>/robots.txt" target="_blank">View Robots.txt</a></li>
                <li><a href="https://developers.facebook.com/tools/debug/?q=<?php echo urlencode($baseUrl . '/admin/global-seo/preview.php'); ?>" target="_blank">Test Open Graph on Facebook</a></li>
                <li><a href="https://cards-dev.twitter.com/validator?url=<?php echo urlencode($baseUrl . '/admin/global-seo/preview.php'); ?>" target="_blank">Test Twitter Cards</a></li>
                <li><a href="https://search.google.com/test/rich-results?url=<?php echo urlencode($baseUrl . '/admin/global-seo/preview.php'); ?>" target="_blank">Test Rich Results on Google</a></li>
            </ul>
        </div>
    </div>
    
    <div class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(getSetting('site_logo_text', 'Your Site')); ?>. All rights reserved.</p>
        <?php if (!empty(getSetting('site_author', ''))): ?>
            <p>Created by <?php echo htmlspecialchars(getSetting('site_author', '')); ?></p>
        <?php endif; ?>
    </div>
    
    <?php echo generateAnalyticsCode(); ?>
    
    <!-- Google Tag Manager (noscript) -->
    <?php if (!empty(getSetting('google_tag_manager_id', ''))): ?>
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo getSetting('google_tag_manager_id', ''); ?>"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <?php endif; ?>
</body>
</html>