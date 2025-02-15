<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/models/Entry.php';

$entry = new Entry();
$error_message = null;

// Handle delete
if (isset($_POST['delete']) && isset($_POST['id'])) {
    try {
        $entry->deleteEntry($_POST['id']);
    } catch (Exception $e) {
        $error_message = "Error deleting entry: " . $e->getMessage();
    }
}

// Get source books and status counts
$source_books = $entry->getSourceBooks();
$status_counts = $entry->getStatusCounts();

// Get current filters
$selected_source = $_GET['source_book'] ?? 'ALL';
$selected_status = $_GET['status'] ?? 'ALL';
$sort_field = $_GET['sort'] ?? 'title';
$sort_direction = $_GET['direction'] ?? 'asc';

// Get filtered entries
$entries = $entry->getFilteredEntries([
    'source' => $selected_source,
    'status' => $selected_status,
    'sort_field' => $sort_field,
    'sort_direction' => $sort_direction
]);

$total_entries = count($entries);

// Set up view variables
$currentView = __DIR__ . '/views/index.view.php';
$pageTitle = 'DeepState Guide Entries';

// Load the view
require __DIR__ . '/views/layout.php';