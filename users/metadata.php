<?php
// Ensure user ID is set
if (!isset($USER_ID)) {
    die('Access denied: No user specified');
}

// Debug path resolution
error_log("Current directory: " . __DIR__);
error_log("Looking for init.php in: " . __DIR__ . '/includes/init.php');

// Include required files with absolute path
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/DatabaseManager.php';

// Start capturing output for error handling
ob_start();

try {
    // Handle AJAX requests and actions
    if (isset($_GET['ajax']) || isset($_GET['action'])) {
        header('Content-Type: application/json');

        // Handle file content requests
        if (isset($_GET['action'])) {
            switch ($_GET['action']) {
                case 'get_subtitles':
                    if (!isset($_GET['filename'])) {
                        echo json_encode(['success' => false, 'error' => 'Filename required']);
                        exit;
                    }
                    $content = getSubtitleContent($_GET['filename']);
                    if ($content !== null) {
                        echo json_encode(['success' => true, 'content' => $content]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Subtitle file not found']);
                    }
                    exit;

                case 'get_transcript':
                    if (!isset($_GET['filename'])) {
                        echo json_encode(['success' => false, 'error' => 'Filename required']);
                        exit;
                    }
                    $content = getTranscriptContent($_GET['filename']);
                    if ($content !== null) {
                        echo json_encode(['success' => true, 'content' => $content]);
                    } else {
                        echo json_encode(['success' => false, 'error' => 'Transcript file not found']);
                    }
                    exit;
            }
        }
    }

    // Get page parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 25;

    // Get filters - only playlist filter for user content
    $filters = [
        'playlist' => $_GET['playlist'] ?? null,
        'user_id' => $USER_ID // Add user ID filter
    ];

    // Get user-specific playlists and videos
    $playlists = $db->getUserPlaylists($USER_ID);
    $videos = $db->getUserVideos($filters, $page, $perPage);

} catch (Exception $e) {
    ob_end_clean();
    displayError("Application error: " . $e->getMessage());
}
?>
