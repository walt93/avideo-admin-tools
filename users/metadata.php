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
    // Handle AJAX requests and actions first
    if (isset($_POST['action']) || isset($_GET['action'])) {
        header('Content-Type: application/json');

        // Handle POST actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            switch ($_POST['action']) {
                case 'update':
                    try {
                        $db->updateVideo($_POST['id'], $_POST['title'], $_POST['description']);
                        echo json_encode(['success' => true]);
                    } catch (Exception $e) {
                        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    }
                    exit;

                case 'sanitize':
                    try {
                        error_log("Sanitize request received: " . print_r($_POST, true));
                        require_once __DIR__ . '/includes/ai_handlers.php';
                        $result = handleSanitize($_POST);
                        error_log("Sanitize result: " . print_r($result, true));
                        echo json_encode($result);
                    } catch (Exception $e) {
                        error_log("Sanitize error: " . $e->getMessage());
                        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    }
                    exit;

                case 'generate_description':
                    require_once __DIR__ . '/includes/ai_handlers.php';
                    handleDescriptionGeneration($_POST);
                    exit;
            }
        }

        // Handle GET actions
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

    // Regular page load - continue with normal processing
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 25;

    // Get filters
    $filters = [
        'playlist' => $_GET['playlist'] ?? null,
        'user_id' => $USER_ID
    ];

    error_log("Loading data for user_id: " . $USER_ID);

    // Get user-specific playlists and videos
    $playlists = $db->getUserPlaylists($USER_ID);
    error_log("Loaded playlists: " . count($playlists));

    $videos = $db->getUserVideos($filters, $page, $perPage);
    error_log("Loaded videos: " . count($videos['videos']));

    // Only continue with HTML output for non-AJAX requests
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

        <!-- Subtitle Viewer Modal -->
        <div class="modal fade" id="subtitleModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content bg-dark text-light">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title">Subtitles</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <pre class="subtitle-content" style="max-height: 70vh; overflow-y: auto;"></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Transcript Viewer Modal -->
        <div class="modal fade" id="transcriptModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content bg-dark text-light">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title">Transcript</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <pre class="transcript-content" style="max-height: 70vh; overflow-y: auto;"></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Modal -->
        <div class="modal fade" id="editModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content bg-dark text-light">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title">Edit Video</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="editForm">
                            <input type="hidden" id="videoId">
                            <input type="hidden" id="videoFilename">
                            <div class="mb-3">
                                <label class="form-label">Title</label>
                                <input type="text" class="form-control bg-dark text-light border-secondary" id="videoTitle" maxlength="100">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea class="form-control bg-dark text-light border-secondary" id="videoDescription" rows="12" style="font-family: monospace;"></textarea>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-outline-warning" onclick="sanitizeDescription()">Sanitize</button>
                        <button type="button" class="btn btn-outline-primary" onclick="saveVideo()">Save</button>
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
        if (isset($_POST['action']) || isset($_GET['action'])) {
            // AJAX error response
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        } else {
            // Regular page error
            displayError("Application error: " . $e->getMessage());
        }
    }

    // If we got this far, flush the output buffer
    ob_end_flush();
    ?>