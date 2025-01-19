<?php
// Load the UserManager
require_once __DIR__ . '/../includes/UserManager.php';

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

        /* ... rest of your existing styles ... */
    </style>
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