<?php
header('Content-Type: application/javascript');

$file = $_GET['file'] ?? '';
$jsDir = __DIR__ . '/js/';

// Sanitize the filename
$file = basename($file);

if (empty($file) || !file_exists($jsDir . $file)) {
    header('HTTP/1.1 404 Not Found');
    exit('console.error("JS file not found");');
}

readfile($jsDir . $file);
