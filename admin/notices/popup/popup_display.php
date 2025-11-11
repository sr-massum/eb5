<?php 

/**
 * ExchangeBridge - Admin Panel Popup Notice
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
require_once '../../../config/config.php';
require_once '../../../config/verification.php';
require_once '../../../config/license.php';
require_once '../../../includes/db.php';
require_once '../../../includes/functions.php';
require_once '../../../includes/app.php';
require_once '../../../includes/auth.php';
require_once '../../../includes/security.php';


// Function to get active popup notices
function getActivePopupNotices() {
    try {
        $db = Database::getInstance();
        $notices = $db->getRows("SELECT * FROM notices WHERE type = 'popup' AND status = 'active' ORDER BY created_at DESC");
        return $notices;
    } catch (Exception $e) {
        error_log('Error fetching popup notices: ' . $e->getMessage());
        return [];
    }
}

// Function to fix image paths
function getCorrectImagePath($imagePath) {
    if (empty($imagePath)) {
        return '';
    }
    
    // Remove any duplicate path parts
    $imagePath = str_replace(['assets/uploads/notices/assets/uploads/notices/', '../'], '', $imagePath);
    
    // Ensure proper path format
    if (!strpos($imagePath, 'assets/uploads/notices/')) {
        if (strpos($imagePath, 'assets/uploads/') === false) {
            $imagePath = 'assets/uploads/notices/' . $imagePath;
        }
    }
    
    return SITE_URL . '/' . $imagePath;
}

// Get active notices
$popupNotices = getActivePopupNotices();

// Return JSON for AJAX requests
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    
    $notices = [];
    foreach ($popupNotices as $notice) {
        $imagePath = '';
        if (!empty($notice['image_path'])) {
            $imagePath = getCorrectImagePath($notice['image_path']);
        }
        
        $notices[] = [
            'id' => $notice['id'],
            'title' => $notice['title'],
            'content' => $notice['content'],
            'image_path' => $imagePath,
            'created_at' => $notice['created_at']
        ];
    }
    
    echo json_encode(['notices' => $notices]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Popup Notices</title>
    <style>
    /* Popup Notice Styles */
    .popup-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
        backdrop-filter: blur(4px);
        animation: fadeIn 0.3s ease-out;
    }
    
    .popup-content {
        background: white;
        border-radius: 12px;
        padding: 2rem;
        max-width: 90%;
        max-height: 80%;
        overflow-y: auto;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        position: relative;
        animation: slideIn 0.3s ease-out;
        font-family: 'Poppins', Arial, sans-serif;
        line-height: 1.6;
    }
    
    .popup-close {
        position: absolute;
        top: 1rem;
        right: 1rem;
        background: #f8f9fa;
        border: none;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        cursor: pointer;
        font-size: 18px;
        color: #6c757d;
        transition: all 0.2s ease;
        z-index: 10000;
    }
    
    .popup-close:hover {
        background: #e9ecef;
        color: #495057;
        transform: scale(1.1);
    }
    
    .popup-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin-bottom: 1rem;
        color: #333;
        padding-right: 3rem;
    }
    
    .popup-media {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        margin: 1rem 0;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }
    
    .popup-body {
        color: #555;
        font-size: 14px;
    }
    
    .popup-body img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        margin: 10px 0;
    }
    
    .popup-body video {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        margin: 10px 0;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    @keyframes slideIn {
        from { 
            opacity: 0;
            transform: scale(0.8) translateY(-20px);
        }
        to { 
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }
    
    /* Dark mode support */
    @media (prefers-color-scheme: dark) {
        .popup-content {
            background-color: #2d3748;
            color: #f7fafc;
        }
        
        .popup-title {
            color: #f7fafc;
        }
        
        .popup-body {
            color: #e2e8f0;
        }
        
        .popup-close {
            background: #4a5568;
            color: #e2e8f0;
        }
        
        .popup-close:hover {
            background: #2d3748;
            color: #f7fafc;
        }
    }
    
    /* Mobile responsive */
    @media (max-width: 768px) {
        .popup-content {
            margin: 1rem;
            max-width: calc(100% - 2rem);
            padding: 1.5rem;
        }
        
        .popup-title {
            font-size: 1.25rem;
        }
    }
    </style>
</head>
<body>
    <!-- Demo page content -->
    <div style="padding: 2rem; font-family: Arial, sans-serif;">
        <h1>Website with Popup Notices</h1>
        <p>This page will automatically show popup notices when they are active.</p>
        <button onclick="showPopups()" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
            Trigger Popup Notices
        </button>
    </div>

    <script>
    // Popup notice functionality
    let currentNoticeIndex = 0;
    let notices = [];
    let shownNotices = [];

    // Load notices from server
    async function loadNotices() {
        try {
            const response = await fetch('<?php echo $_SERVER['PHP_SELF']; ?>?ajax=1');
            const data = await response.json();
            notices = data.notices || [];
            return notices;
        } catch (error) {
            console.error('Error loading notices:', error);
            return [];
        }
    }

    // Show popup notice
    function showNotice(notice) {
        // Create popup overlay
        const overlay = document.createElement('div');
        overlay.className = 'popup-overlay';
        overlay.onclick = (e) => {
            if (e.target === overlay) {
                closePopup(overlay);
            }
        };

        // Create popup content
        const content = document.createElement('div');
        content.className = 'popup-content';

        // Close button
        const closeBtn = document.createElement('button');
        closeBtn.className = 'popup-close';
        closeBtn.innerHTML = '×';
        closeBtn.onclick = () => closePopup(overlay);

        // Title
        const title = document.createElement('div');
        title.className = 'popup-title';
        title.textContent = notice.title || 'Notice';

        // Body content
        const body = document.createElement('div');
        body.className = 'popup-body';
        body.innerHTML = notice.content || '';

        // Add media if exists
        if (notice.image_path) {
            const extension = notice.image_path.split('.').pop().toLowerCase();
            if (['mp4', 'webm', 'ogg'].includes(extension)) {
                const video = document.createElement('video');
                video.className = 'popup-media';
                video.controls = true;
                video.src = notice.image_path;
                video.onerror = () => {
                    console.error('Failed to load video:', notice.image_path);
                    video.style.display = 'none';
                };
                body.insertBefore(video, body.firstChild);
            } else {
                const img = document.createElement('img');
                img.className = 'popup-media';
                img.src = notice.image_path;
                img.alt = 'Notice Image';
                img.onerror = () => {
                    console.error('Failed to load image:', notice.image_path);
                    img.style.display = 'none';
                };
                body.insertBefore(img, body.firstChild);
            }
        }

        // Assemble popup
        content.appendChild(closeBtn);
        content.appendChild(title);
        content.appendChild(body);
        overlay.appendChild(content);

        // Add to page
        document.body.appendChild(overlay);

        // Mark as shown
        shownNotices.push(notice.id);
        localStorage.setItem('shownNotices', JSON.stringify(shownNotices));
    }

    // Close popup
    function closePopup(overlay) {
        overlay.style.animation = 'fadeOut 0.3s ease-out';
        setTimeout(() => {
            if (overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
            showNextNotice();
        }, 300);
    }

    // Show next notice in queue
    function showNextNotice() {
        if (currentNoticeIndex < notices.length) {
            const notice = notices[currentNoticeIndex];
            currentNoticeIndex++;
            
            // Don't show if already shown today
            if (!shownNotices.includes(notice.id)) {
                setTimeout(() => showNotice(notice), 500);
            } else {
                showNextNotice();
            }
        }
    }

    // Show all popups (for testing)
    async function showPopups() {
        await loadNotices();
        currentNoticeIndex = 0;
        shownNotices = []; // Clear for testing
        showNextNotice();
    }

    // Initialize popup system
    document.addEventListener('DOMContentLoaded', async function() {
        // Load shown notices from localStorage
        const stored = localStorage.getItem('shownNotices');
        if (stored) {
            try {
                shownNotices = JSON.parse(stored);
            } catch (e) {
                shownNotices = [];
            }
        }

        // Clear daily (optional)
        const lastClear = localStorage.getItem('lastClear');
        const today = new Date().toDateString();
        if (lastClear !== today) {
            shownNotices = [];
            localStorage.setItem('shownNotices', JSON.stringify([]));
            localStorage.setItem('lastClear', today);
        }

        // Auto-load and show notices
        await loadNotices();
        if (notices.length > 0) {
            setTimeout(showNextNotice, 1000); // Show after 1 second
        }
    });

    // Add fadeOut animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>