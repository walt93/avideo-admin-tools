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

// Helper function to check for video file resolutions
function getVideoResolutions($filename) {
    $basePath = '/var/www/html/conspyre.tv/videos/' . $filename . '/' . $filename;
    $resolutions = [];
    $cdnBase = 'https://truthtide.net/';

    // Check for base file
    if (file_exists($basePath . '.mp4')) {
        $resolutions['original'] = $cdnBase . $filename . '.mp4';
    }

    // Check for resolution variants
    $res_variants = ['1080', '720', '540', '480', '360', '240'];
    foreach ($res_variants as $res) {
        $testPath = $basePath . '_' . $res . '.mp4';
        if (file_exists($testPath)) {
            $resolutions[$res] = $cdnBase . $filename . '_' . $res . '.mp4';
        }
    }

    // Also check for _ext variant
    if (file_exists($basePath . '_ext.mp4')) {
        $resolutions['ext'] = $cdnBase . $filename . '_ext.mp4';
    }

    return $resolutions;
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
