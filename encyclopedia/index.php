<?php
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Simple debug logging function
function debug_log($message) {
    error_log("DEBUG: " . print_r($message, true));
}

try {
    require_once __DIR__ . '/database.php';
    debug_log("Database included");

    require_once __DIR__ . '/functions.php';
    debug_log("Functions included");

    require_once __DIR__ . '/models/Entry.php';
    debug_log("Entry model included");

    require_once __DIR__ . '/views/components/pagination.php';
    debug_log("Pagination component included");

    $entry = new Entry();
    debug_log("Entry instantiated");

    $error_message = null;

    // Handle delete
    if (isset($_POST['delete']) && isset($_POST['id'])) {
        try {
            $entry->deleteEntry($_POST['id']);
        } catch (Exception $e) {
            $error_message = "Error deleting entry: " . $e->getMessage();
            debug_log("Delete error: " . $e->getMessage());
        }
    }

    // Get source books and status counts
    $source_books = $entry->getSourceBooks();
    debug_log("Source books retrieved");

    $status_counts = $entry->getStatusCounts();
    debug_log("Status counts retrieved");

    // Get current filters and pagination
    $selected_source = $_GET['source_book'] ?? 'ALL';
    $selected_status = $_GET['status'] ?? 'ALL';
    $sort_field = $_GET['sort'] ?? 'title';
    $sort_direction = $_GET['direction'] ?? 'asc';
    $current_page = max(1, intval($_GET['page'] ?? 1));
    $title_search = $_GET['title_search'] ?? '';  // Add this line BEFORE we use it

    debug_log("Title search term: " . $title_search);
    debug_log("Filters prepared: " . json_encode([
        'source' => $selected_source,
        'status' => $selected_status,
        'sort' => $sort_field,
        'direction' => $sort_direction,
        'page' => $current_page,
        'title_search' => $title_search  // Add this to the debug log
    ]));

    // Get filtered entries with pagination
    $result = $entry->getFilteredEntries([
        'source' => $selected_source,
        'status' => $selected_status,
        'sort_field' => $sort_field,
        'sort_direction' => $sort_direction,
        'title_search' => $title_search,
        'alpha' => $_GET['alpha'] ?? null
    ], $current_page);

    debug_log("Number of results: " . count($result['entries']));
    $entries = $result['entries'];
    $pagination = $result['pagination'];
    $total_entries = count($entries);

    // Current URL parameters for pagination
    $current_params = [
        'source_book' => $selected_source,
        'status' => $selected_status,
        'sort' => $sort_field,
        'direction' => $sort_direction,
        'title_search' => $title_search,
        'alpha' => $_GET['alpha'] ?? null
    ];

    // Set up view variables
    $currentView = __DIR__ . '/views/index.view.php';
    $pageTitle = 'DeepState Guide Entries';

    debug_log("About to load view");
    // Load the view
    require __DIR__ . '/views/layout.php';
    debug_log("View loaded");

} catch (Exception $e) {
    debug_log("Fatal error: " . $e->getMessage());
    debug_log("Stack trace: " . $e->getTraceAsString());
    http_response_code(500);
    echo "An error occurred: " . $e->getMessage();
}