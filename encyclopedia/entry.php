<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/models/Entry.php';

$entry_model = new Entry();
$error_message = null;
$entry = null;
$footnotes = [];

// Get existing entry if we're editing
if (isset($_GET['id'])) {
    $entry = $entry_model->getById($_GET['id']);
    if ($entry) {
        $footnotes = $entry['footnotes'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $entry_model->save($_POST);
        header('Location: index.php');
        exit;
    } catch (Exception $e) {
        $error_message = "Error: " . $e->getMessage();
    }
}

// Set up view variables
$currentView = __DIR__ . '/views/entry.view.php';
$pageTitle = $entry ? 'Edit Entry' : 'New Entry';

// Load the view
require __DIR__ . '/views/layout.php';