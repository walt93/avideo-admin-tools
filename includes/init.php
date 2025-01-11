<?php
// includes/init.php

// Basic error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Validate required environment variables
$requiredEnvVars = [
    'VIDEO_CDN_BASE_URL',
    'AVIDEO_DATABASE_PW',
    'AVIDEO_DATABASE_NAME',
    'AVIDEO_DATABASE_USER',
    'AVIDEO_DATABASE_HOST'
];

foreach ($requiredEnvVars as $var) {
    if (getenv($var) === false) {
        die("Required environment variable {$var} is not set");
    }
}

// Function to display errors in HTML
function displayError($message) {
    echo "<!DOCTYPE html><html><body><h1>Error</h1><pre>$message</pre></body></html>";
    exit;
}

// Helper function to get video formatted subtitles with timecode
function getSubtitleContent($filename) {
    $path = "/var/www/html/conspyre.tv/videos/{$filename}/{$filename}.vtt";
    if (!file_exists($path)) {
        $path = "/var/www/html/conspyre.tv/videos/{$filename}/{$filename}_ext.vtt";
    }

    if (file_exists($path)) {
        $content = file_get_contents($path);
        // Format VTT content
        $lines = explode("\n", $content);
        $formatted = [];
        $time = null;

        foreach ($lines as $line) {
            // Skip WEBVTT header and empty lines
            if (trim($line) === 'WEBVTT' || empty(trim($line))) {
                continue;
            }
            // Timestamp line
            if (preg_match('/^\d{2}:\d{2}:\d{2}/', $line)) {
                $time = $line;
            }
            // Text line with corresponding timestamp
            else if ($time && trim($line)) {
                $formatted[] = $time . "\n" . trim($line);
                $time = null;
            }
        }
        return implode("\n\n", $formatted);
    }
    return null;
}

// Helper function to get video transcript
function getTranscriptContent($filename) {
    $path = "/var/www/html/conspyre.tv/videos/{$filename}/{$filename}.txt";
    if (!file_exists($path)) {
        $path = "/var/www/html/conspyre.tv/videos/{$filename}/{$filename}_ext.txt";
    }

    if (file_exists($path)) {
        $content = file_get_contents($path);

        // Clean up the content
        $content = trim($content);

        // Replace multiple newlines with double newlines to create paragraphs
        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        // Split into paragraphs and clean each paragraph
        $paragraphs = explode("\n\n", $content);
        $paragraphs = array_map(function($p) {
            // Remove excess whitespace within paragraphs
            return preg_replace('/\s+/', ' ', trim($p));
        }, $paragraphs);

        // Rejoin with proper paragraph spacing
        return implode("\n\n", $paragraphs);
    }
    return null;
}

// Helper function to check for video file resolutions
function getVideoResolutions($filename) {
    $basePath = '/var/www/html/conspyre.tv/videos/' . $filename . '/' . $filename;
    $resolutions = [];
    $cdnBase = rtrim(getenv('VIDEO_CDN_BASE_URL'), '/') . '/';

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
