<?php
require_once __DIR__ . '/includes/init.php';

// Start capturing output in case of errors later
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

        // Handle other AJAX requests
        if (isset($_GET['ajax'])) {
            $categoryManager = new CategoryManager($db);

            if ($_GET['ajax'] === 'subcategories' && isset($_GET['parent'])) {
                echo json_encode($db->getSubcategories($_GET['parent']));
                exit;
            }

            if ($_GET['ajax'] === 'category_path' && isset($_GET['id'])) {
                echo json_encode($categoryManager->getCategoryPath($_GET['id']));
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

    // Get filters
    $filters = [
        'category' => $_GET['category'] ?? null,
        'playlist' => $_GET['playlist'] ?? null
    ];

    // Get data for page
    $topLevelCategories = $db->getTopLevelCategories();
    $playlists = $db->getPlaylists();
    $videos = $db->getVideos($filters, $page, $perPage);

} catch (Exception $e) {
    ob_end_clean();
    displayError("Application error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Video CMS Editor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        <?php include 'css/styles.css'; ?>
    </style>
</head>
<body>
    <?php
    // Include main content template
    include 'templates/main_content.php';

    // Include modals
    include 'modals/edit_modal.php';
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
                    <pre class="transcript-content" style="max-height: 70vh; overflow-y: auto;"></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Application JavaScript with error checking -->
    <script src="js/modal_manager.js"></script>
    <script src="js/category_navigation.js"></script>
    <script src="js/ai_handlers.js"></script>

    <!-- Initialization script -->
    <script>
    console.log('Checking script loading...');

    // Function to retry initialization
    function initializeModalManager() {
        console.log('Attempting to initialize ModalManager...');

        if (typeof ModalManager === 'undefined') {
            console.error('ModalManager class not loaded yet, retrying in 100ms');
            setTimeout(initializeModalManager, 100);
            return;
        }

        try {
            console.log('ModalManager class found, creating instance');
            if (!window.modalManager) {
                window.modalManager = new ModalManager();
                console.log('ModalManager initialized:', window.modalManager);
            }
        } catch (error) {
            console.error('Error initializing ModalManager:', error);
        }
    }

    // Start initialization when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeModalManager);
    } else {
        initializeModalManager();
    }
    </script>
<!-- Test if ModalManager loaded -->
<script>
console.log('ModalManager class available:', typeof ModalManager !== 'undefined');
console.log('window.modalManager available:', typeof window.modalManager !== 'undefined');
</script>

<!-- Add this after the existing test script -->
<script>
// Test direct event binding on specific elements
document.querySelectorAll('[data-action="view-subtitles"]').forEach(el => {
    console.log('Found subtitle element:', el);
    el.addEventListener('click', function(e) {
        console.log('Direct subtitle click handler');
    });
});

document.querySelectorAll('[data-action="view-transcript"]').forEach(el => {
    console.log('Found transcript element:', el);
    el.addEventListener('click', function(e) {
        console.log('Direct transcript click handler');
    });
});
</script>

<!-- Add this right before the closing </body> tag -->
<script>
console.log('Setting up test click handler...');

document.addEventListener('click', function(event) {
    const target = event.target.closest('[data-action]');
    if (target) {
        console.log('Click detected on element with data-action:', {
            action: target.dataset.action,
            element: target,
            dataset: target.dataset
        });
    }
});

console.log('Bootstrap version:', bootstrap.Modal.VERSION);

</script>
<!-- Add this right before the closing </body> tag -->
<script>
console.log('Testing modal initialization...');

// Function to initialize and test a modal
function testModal(elementId, description) {
    const element = document.getElementById(elementId);
    console.log(`Testing ${description} modal:`, {
        element: element,
        bootstrapModal: new bootstrap.Modal(element)
    });
    return new bootstrap.Modal(element);
}

// Initialize each modal separately with error catching
try {
    const editModal = testModal('editModal', 'edit');
    const playerModal = testModal('videoPlayerModal', 'player');
    const subtitleModal = testModal('subtitleModal', 'subtitle');
    const transcriptModal = testModal('transcriptModal', 'transcript');

    // Test showing a modal directly
    console.log('Attempting to show edit modal directly...');
    editModal.show();
} catch (error) {
    console.error('Error initializing modals:', error);
}
</script>
</body>
</html>
<?php
// If we got this far, flush the output buffer
ob_end_flush();
?>