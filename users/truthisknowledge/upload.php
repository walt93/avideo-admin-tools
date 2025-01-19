<?php
if (!isset($USER_ID)) {
    die('Access denied: No user specified');
}

require_once __DIR__ . '/../includes/init.php';
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Content</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
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