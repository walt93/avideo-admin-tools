<?php
// Maximum error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Log that we've entered init.php
error_log("Entering init.php");

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
        error_log("Missing required environment variable: " . $var);
        die("Required environment variable {$var} is not set");
    }
}

error_log("Environment variables validated");

// Function to display errors in HTML
function displayError($message) {
    error_log("Error displayed: " . $message);
    echo "<!DOCTYPE html><html><body><h1>Error</h1><pre>" . htmlspecialchars($message) . "</pre></body></html>";
    exit;
}

// Helper functions for file operations
function getSubtitleContent($filename) {
    error_log("Getting subtitle content for: " . $filename);
    $path = "/var/www/html/conspyre.tv/videos/{$filename}/{$filename}.vtt";
    if (!file_exists($path)) {
        $path = "/var/www/html/conspyre.tv/videos/{$filename}/{$filename}_ext.vtt";
    }

    if (!file_exists($path)) {
        error_log("Subtitle file not found: " . $path);
        return null;
    }

    return file_get_contents($path);
}

function getTranscriptContent($filename) {
    error_log("Getting transcript content for: " . $filename);
    $path = "/var/www/html/conspyre.tv/videos/{$filename}/{$filename}.txt";
    if (!file_exists($path)) {
        $path = "/var/www/html/conspyre.tv/videos/{$filename}/{$filename}_ext.txt";
    }

    if (!file_exists($path)) {
        error_log("Transcript file not found: " . $path);
        return null;
    }

    return file_get_contents($path);
}

function getVideoResolutions($filename) {
    error_log("Getting video resolutions for: " . $filename);
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

    error_log("Found resolutions: " . json_encode($resolutions));
    return $resolutions;
}

function checkMediaFiles($filename) {
    error_log("Checking media files for: " . $filename);
    $basePath = '/var/www/html/conspyre.tv/videos/' . $filename . '/' . $filename;
    return [
        'has_vtt' => file_exists($basePath . '.vtt'),
        'has_txt' => file_exists($basePath . '.txt')
    ];
}

// Load required files
error_log("Including DatabaseManager.php");
require_once __DIR__ . '/DatabaseManager.php';

// Initialize database connection
try {
    error_log("Initializing database connection");
    $db = new DatabaseManager();
    error_log("Database connection initialized successfully");
} catch (Exception $e) {
    error_log("Database connection failed: " . $e->getMessage());
    displayError("Database error: " . $e->getMessage());
}

error_log("init.php completed successfully");
