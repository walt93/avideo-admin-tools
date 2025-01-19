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

    error_log("Loading data for user_id: " . $USER_ID);

    // Get user-specific playlists and videos
    $playlists = $db->getUserPlaylists($USER_ID);
    error_log("Loaded playlists: " . count($playlists));

    $videos = $db->getUserVideos($filters, $page, $perPage);
    error_log("Loaded videos: " . count($videos['videos']));

    // Debug template paths
    $mainContentPath = __DIR__ . '/templates/main_content.php';
    $editModalPath = __DIR__ . '/templates/modals/edit_modal.php';

    error_log("Main content template path: " . $mainContentPath);
    error_log("Edit modal template path: " . $editModalPath);

    // Verify template files exist
    if (!file_exists($mainContentPath)) {
        throw new Exception("Main content template not found at: " . $mainContentPath);
    }
    if (!file_exists($editModalPath)) {
        throw new Exception("Edit modal template not found at: " . $editModalPath);
    }

    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Content Manager</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
        <style>
            /* Dark theme styling */
            body {
                background-color: #121212;
                color: #e0e0e0;
            }
            /* Additional styles from the original will be included here */
            <?php 
            error_log("Including CSS...");
            if (file_exists(__DIR__ . '/css/styles.css')) {
                include __DIR__ . '/css/styles.css';
            }
            ?>
        </style>
    </head>
    <body>
        <?php
        error_log("Including main_content.php");
        include $mainContentPath;

        error_log("Including edit_modal.php");
        include $editModalPath;
        ?>

        <!-- Video Player Modal -->
        <div class="modal fade" id="videoPlayerModal" tabindex="-1">
            <div class="modal-dialog modal-xl">
                <div class="modal-content bg-dark">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title text-light" id="videoPlayerTitle"></h5>
                        <div class="ms-auto d-flex align-items-center">
                            <select class="form-select form-select-sm me-2" id="videoQualitySelect" style="width: auto;">
                            </select>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                    </div>
                    <div class="modal-body p-0">
                        <video id="videoPlayer" class="w-100" controls>
                            Your browser doesn't support HTML5 video.
                        </video>
                    </div>
                </div>
            </div>
        </div>

        <!-- JavaScript Dependencies -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        
        <!-- Debug output for script loading -->
        <script>
            console.log('Loading JavaScript files...');
        </script>

        <!-- Application Scripts - Using absolute paths -->
        <script src="/management/users/js/modal_manager.js"></script>
        <script src="/management/users/js/ai_handlers.js"></script>

        <!-- Debug output for script initialization -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                console.log('DOM fully loaded');
                if (window.modalManager) {
                    console.log('Modal manager loaded');
                } else {
                    console.error('Modal manager not loaded');
                }
                // Add detailed component checks
                console.log('Available modals:', {
                    editModal: !!document.getElementById('editModal'),
                    videoPlayerModal: !!document.getElementById('videoPlayerModal'),
                    subtitleModal: !!document.getElementById('subtitleModal'),
                    transcriptModal: !!document.getElementById('transcriptModal')
                });
            });
        </script>
    </body>
    </html>
    <?php

} catch (Exception $e) {
    ob_end_clean();
    error_log("Error in metadata.php: " . $e->getMessage());
    displayError("Application error: " . $e->getMessage());
}

// If we got this far, flush the output buffer
ob_end_flush();
?>
