<?php
// includes/init.php

// Basic error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to display errors in HTML
function displayError($message) {
    echo "<!DOCTYPE html><html><body><h1>Error</h1><pre>$message</pre></body></html>";
    exit;
}

// Helper function to check for subtitle and transcript files
function checkMediaFiles($filename) {
    $basePath = '/var/www/html/conspyre.tv/videos/' . $filename . '/' . $filename;
    return [
        'has_vtt' => file_exists($basePath . '.vtt'),
        'has_txt' => file_exists($basePath . '.txt')
    ];
}

// Load required files
require_once __DIR__ . '/DatabaseManager.php';
require_once __DIR__ . '/../ai_handlers.php';

// Initialize database connection
try {
    $db = new DatabaseManager();
} catch (Exception $e) {
    displayError($e->getMessage());
}
