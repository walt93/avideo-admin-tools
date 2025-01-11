<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/CategoryManager.php';

// Start capturing output in case of errors later
ob_start();

try {
    // Handle AJAX requests
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');
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

    <!-- JavaScript Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Application JavaScript -->
    <script src="/management/js/modal_manager.js"></script>
    <script src="/management/js/category_navigation.js"></script>
    <script src="/management/js/ai_handlers.js"></script>
</body>
</html>
<?php
// If we got this far, flush the output buffer
ob_end_flush();
?>