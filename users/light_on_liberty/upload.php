<?php
// Load the UserManager
require_once __DIR__ . '/../includes/UserManager.php';
require_once __DIR__ . '/../includes/UploadedFileManager.php';

try {
    // Get username from directory name
    $username = basename(__DIR__);
    error_log("Username from directory: " . $username);

    // Initialize UserManager
    $userManager = new UserManager();
    error_log("UserManager initialized");

    // Verify access
    if (!$userManager->verifyAccess($username)) {
        error_log("Access denied for user: " . $username);
        header('HTTP/1.0 403 Forbidden');
        exit('Access denied');
    }

    // Set the user ID for the metadata page
    $USER_ID = $userManager->getUserId($username);
    error_log("Got user ID: " . $USER_ID);

    if (!$USER_ID) {
        throw new Exception('Invalid user configuration');
    }

    require_once __DIR__ . '/../includes/init.php';
} catch (Exception $e) {
    error_log("Error in upload.php: " . $e->getMessage());
    exit("An error occurred: " . $e->getMessage());
}
// Add this new code HERE, before the DOCTYPE html line:
$uploadManager = new UploadedFilesManager($db);

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    switch ($_GET['action']) {
        case 'add_upload':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id'])) {
                echo json_encode(['success' => false, 'error' => 'No ID provided']);
                exit;
            }

            $uploadManager->addUpload($data);
            echo json_encode(['success' => true]);
            exit;

        case 'get_uploads':
            $uploads = $uploadManager->getUploads();
            $videoDetails = [];
            foreach ($uploads as $upload) {
                $details = $uploadManager->getVideoDetails($upload['id']);
                if ($details) {
                    $videoDetails[$upload['id']] = $details;
                }
            }

            echo json_encode([
                'success' => true,
                'uploads' => $uploads,
                'videoDetails' => $videoDetails
            ]);
            exit;

        case 'remove_upload':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!isset($data['id'])) {
                echo json_encode(['success' => false, 'error' => 'No ID provided']);
                exit;
            }

            $uploadManager->removeUpload($data['id']);
            echo json_encode(['success' => true]);
            exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Content</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #121212;
            color: #e0e0e0;
            min-height: 100vh;
        }

        .upload-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Logo styling */
        .logo-container {
            text-align: center;
            padding: 1rem 0;
        }

        .logo-container img {
            max-width: 200px;
            height: auto;
        }

        /* Form controls */
        .form-control, .form-select {
            background-color: #1e1e1e;
            border: 1px solid #333;
            color: #e0e0e0;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            background-color: #2a2a2a;
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            color: #fff;
        }

        .form-control::placeholder {
            color: #666;
        }

        /* Button styling */
        .btn-primary {
            background-color: #0d6efd;
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background-color: #0b5ed7;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        /* Status container */
        .status-container {
            background-color: #1a1a1a;
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .status-text {
            color: #8f8f8f;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        /* Progress bar customization */
        .progress {
            background-color: #2a2a2a;
            height: 8px;
            border-radius: 4px;
            overflow: hidden;
        }

        .progress-bar {
            background-color: #0d6efd;
            transition: width 0.3s ease;
        }

        /* Error message styling */
        .error-message {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            padding: 0.75rem;
            border-radius: 4px;
            margin-top: 1rem;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .upload-container {
                padding: 10px;
            }

            .btn-primary {
                padding: 0.5rem 1rem;
            }

            .form-control, .form-select {
                font-size: 16px; /* Prevents zoom on mobile */
            }
        }

        /* Animation for status updates */
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .status-container.show {
            animation: fadeIn 0.3s ease forwards;
        }    </style>
</head>
<body>
    <?php
    // Include navigation
    require_once __DIR__ . '/../templates/nav.php';

    // Include upload content
    require_once __DIR__ . '/../templates/upload_content.php';
    ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>