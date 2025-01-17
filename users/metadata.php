<?php
// Ensure USER_ID is set from the including script
if (!isset($USER_ID)) {
    die('Access denied: No user specified');
}

require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/includes/UserManager.php';

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

    // Handle POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        header('Content-Type: application/json');

        switch ($_POST['action']) {
            case 'update':
                try {
                    $db->updateVideo($_POST['id'], $_POST['title'], $_POST['description']);
                    echo json_encode(['success' => true]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
                exit;

            case 'generate_description':
                require_once __DIR__ . '/ai_handlers.php';
                handleDescriptionGeneration($_POST);
                exit;

            case 'sanitize':
                require_once __DIR__ . '/ai_handlers.php';
                handleSanitize($_POST);
                exit;
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

        .container-fluid {
            background-color: #1e1e1e;
        }

        .table {
            color: #e0e0e0;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: #252525;
        }

        .table-striped tbody tr:nth-of-type(even) {
            background-color: #1e1e1e;
        }

        .modal-content {
            background-color: #2d2d2d;
            color: #e0e0e0;
        }

        .form-control, .form-select {
            background-color: #3d3d3d;
            border-color: #404040;
            color: #e0e0e0;
        }

        .form-control:focus, .form-select:focus {
            background-color: #454545;
            border-color: #505050;
            color: #e0e0e0;
        }

        .modal-header, .modal-footer {
            border-color: #404040;
        }

        .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        /* Additional styles from the original */
        <?php include '../../css/styles.css'; ?>
    </style>
</head>
<body>
    <?php include 'templates/main_content.php'; ?>
    <?php include 'modals/edit_modal.php'; ?>

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
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Subtitles</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Transcript</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <pre class="transcript-content"></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/modal_manager.js"></script>
    <script src="js/ai_handlers.js"></script>
</body>
</html>
<?php
ob_end_flush();
?>
