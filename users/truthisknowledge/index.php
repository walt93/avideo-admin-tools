<?php
// Strict error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Load the UserManager
require_once __DIR__ . '/../includes/UserManager.php';

try {
    // Get username from directory name
    $username = basename(__DIR__);
    
    // Initialize UserManager
    $userManager = new UserManager();
    
    // Verify access
    if (!$userManager->verifyAccess($username)) {
        // Log failed access attempt
        error_log("Failed access attempt for user: $username from IP: " . $_SERVER['REMOTE_ADDR']);
        
        // Return 403 Forbidden
        header('HTTP/1.0 403 Forbidden');
        exit('Access denied');
    }
    
    // Set the user ID for the metadata page
    $USER_ID = $userManager->getUserId($username);
    
    if (!$USER_ID) {
        throw new Exception('Invalid user configuration');
    }
    
    // Include the main metadata page
    require_once __DIR__ . '/../metadata.php';
    
} catch (Exception $e) {
    // Log the error
    error_log("Error in user index.php: " . $e->getMessage());
    
    // Show generic error message
    header('HTTP/1.0 500 Internal Server Error');
    exit('An error occurred. Please contact support.');
}
