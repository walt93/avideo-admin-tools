<?php
header('Content-Type: application/json');

// Configuration
define('VIDEOS_BASE_PATH', '/var/www/html/conspyre.tv/videos/');
define('ALLOWED_EXTENSIONS', ['vtt', 'txt', 'json']);

// Error handling
function sendError($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

// Validate API key
$headers = getallheaders();
$apiKey = getenv('AVIDEO_ENDPOINT_API_KEY');

error_log("Received API Key: " . ($headers['X-Api-Key'] ?? 'none'));
error_log("Expected API Key: " . $apiKey);

if (!isset($headers['X-Api-Key']) || $headers['X-Api-Key'] !== $apiKey) {
    sendError('Unauthorized', 401);
}

// Database connection
try {
    $dbHost = getenv('AVIDEO_DATABASE_HOST');
    $dbName = getenv('AVIDEO_DATABASE_NAME');
    $dbUser = getenv('AVIDEO_DATABASE_USER');
    $dbPassword = getenv('AVIDEO_DATABASE_PW');

    $pdo = new PDO(
        "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4",
        $dbUser,
        $dbPassword,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    sendError('Database connection failed', 500);
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

// Validate file upload
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    sendError('No file uploaded or upload error');
}

// Get and validate filename from request
$filename = $_POST['filename'] ?? '';
if (empty($filename)) {
    sendError('Filename is required');
}

// Extract extension and validate
$extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
if (!in_array($extension, ALLOWED_EXTENSIONS)) {
    sendError('Invalid file extension. Only .vtt, .txt and .json files are allowed');
}

// Verify video exists in database
try {
    $stmt = $pdo->prepare('SELECT filename FROM videos WHERE filename = ?');
    $stmt->execute([$filename]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$video) {
        sendError('Video not found in database');
    }
} catch (PDOException $e) {
    sendError('Database error', 500);
}

// Create directory if it doesn't exist
$uploadDir = VIDEOS_BASE_PATH . $filename;
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        sendError('Failed to create directory', 500);
    }
}

// Construct target path
$targetPath = $uploadDir . '/' . $filename . '.' . $extension;

// Move uploaded file
if (!move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
    sendError('Failed to save file', 500);
}

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'File uploaded successfully',
    'path' => $targetPath
]);

