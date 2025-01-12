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

    if (!file_exists($path)) {
        error_log("Subtitle file not found: $path");
        return null;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        error_log("Failed to read subtitle file: $path");
        return null;
    }

    // Log file being processed
    error_log("Processing VTT file: $path");

    // Format VTT content
    $lines = explode("\n", $content);
    $formatted = [];
    $currentEntry = [
        'timestamp' => null,
        'text' => []
    ];

    foreach ($lines as $lineNum => $line) {
        $line = trim($line);

        // Skip WEBVTT header and empty lines
        if ($line === 'WEBVTT' || empty($line)) {
            continue;
        }

        // Skip numeric lines (could be subtitle numbers)
        if (is_numeric($line)) {
            continue;
        }

        // Check for timestamp line - handling multiple formats
        if (preg_match('/^\d{2}:\d{2}[.,]\d{3}\s*-->\s*\d{2}:\d{2}[.,]\d{3}/', $line) ||    // 00:00.000 --> 00:24.800
            preg_match('/^\d{2}:\d{2}\s*-->\s*\d{2}:\d{2}/', $line) ||                       // 00:00 --> 00:24
            preg_match('/^\d{2}:\d{2}:\d{2}[.,]\d{3}\s*-->\s*\d{2}:\d{2}:\d{2}[.,]\d{3}/', $line) ||  // 00:00:00.000 --> 00:00:00.000
            preg_match('/^\d{2}:\d{2}:\d{2}\s*-->\s*\d{2}:\d{2}:\d{2}/', $line)) {          // 00:00:00 --> 00:00:00

            // If we have a complete previous entry, add it to formatted output
            if ($currentEntry['timestamp'] && !empty($currentEntry['text'])) {
                $formatted[] = $currentEntry['timestamp'] . "\n" . implode(" ", $currentEntry['text']);
            }

            // Start new entry
            $currentEntry = [
                'timestamp' => $line,
                'text' => []
            ];
        }
        // If not a timestamp and not empty, it's subtitle text
        else if ($line !== "") {
            $line = preg_replace('/<[^>]+>/', '', $line);
            if (trim($line) !== "") {
                $currentEntry['text'][] = $line;
            }
        }
    }

    // Add the last entry if exists
    if ($currentEntry['timestamp'] && !empty($currentEntry['text'])) {
        $formatted[] = $currentEntry['timestamp'] . "\n" . implode(" ", $currentEntry['text']);
    }

    $result = implode("\n\n", $formatted);

    // Log if we got no formatted output despite having content
    if (empty($result) && !empty($content)) {
        error_log("Warning: No formatted output generated from non-empty subtitle file: $path");
        error_log("Original content length: " . strlen($content));
    }

    return $result;
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

        // Remove any existing multiple newlines
        $content = preg_replace('/\n+/', ' ', $content);

        // Remove excess whitespace
        $content = preg_replace('/\s+/', ' ', $content);

        // Split into sentences (basic splitting - handles common sentence endings)
        $sentences = preg_split('/(?<=[.!?])\s+/', $content, -1, PREG_SPLIT_NO_EMPTY);

        // Group sentences into paragraphs (3-4 sentences per paragraph)
        $paragraphs = array();
        $current_paragraph = array();
        $sentences_per_paragraph = rand(3, 4); // Vary paragraph length slightly for natural feel

        foreach ($sentences as $sentence) {
            $current_paragraph[] = $sentence;

            // When we reach our target sentence count or if sentence ends with multiple punctuation marks
            // (like "!!!" or "???"), create a new paragraph
            if (count($current_paragraph) >= $sentences_per_paragraph ||
                preg_match('/[.!?]{2,}$/', $sentence)) {
                $paragraphs[] = implode(' ', $current_paragraph);
                $current_paragraph = array();
                $sentences_per_paragraph = rand(3, 4); // Randomize next paragraph length
            }
        }

        // Add any remaining sentences as the last paragraph
        if (!empty($current_paragraph)) {
            $paragraphs[] = implode(' ', $current_paragraph);
        }

        // Join paragraphs with double newlines
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
