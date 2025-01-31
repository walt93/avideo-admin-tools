<?php
// Get user config
$username = basename(dirname($_SERVER['PHP_SELF']));
$configFile = __DIR__ . '/../config/users.json';
// Add error handling for config file reading
if (!file_exists($configFile)) {
    error_log("Config file not found: $configFile");
    die("Configuration error");
}

$configContent = file_get_contents($configFile);
if ($configContent === false) {
    error_log("Unable to read config file: $configFile");
    die("Configuration error");
}

$config = json_decode($configContent, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("JSON parse error in config: " . json_last_error_msg());
    die("Configuration error");
}

// Validate user exists in config
if (!isset($config['users'][$username])) {
    error_log("User not found in config: $username");
    die("Invalid user configuration");
}

$userConfig = $config['users'][$username];

// Default values for profile
$profilePhoto = $userConfig['profile']['photo'] ?? '/default-profile.png';
$displayName = $userConfig['profile']['display_name'] ?? $userConfig['name'];
$socialHandle = $userConfig['profile']['social_handle'] ?? '';
?>

<div class="container">
    <div class="upload-container">
        <div class="logo-container mb-4">
            <img src="/truth_tide_tv.png" alt="Truth Tide TV" class="img-fluid">
        </div>

        <div class="profile-section mb-4">
            <?php if ($profilePhoto): ?>
            <img src="https://conspyre.tv<?= htmlspecialchars($profilePhoto) ?>"
                 alt="<?= htmlspecialchars($displayName) ?>"
                 class="profile-image">
            <?php endif; ?>

            <div class="profile-info">
                <h2><?= htmlspecialchars($displayName) ?></h2>
                <?php if ($socialHandle): ?>
                <p><?= htmlspecialchars($socialHandle) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="upload-card">
            <form id="uploadForm" class="bg-dark p-4 rounded">
                <div class="mb-3">
                    <input type="url" class="form-control form-control-lg" id="videoUrl"
                           placeholder="Enter video URL" required>
                </div>

                <div class="mb-3">
                    <select class="form-select form-select-lg" id="categorySelect" required>
                        <option value="">Select category...</option>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-100">
                    Upload Video
                </button>

                <div class="error-message mt-3" id="errorMessage"></div>
            </form>

            <div class="status-container mt-4 d-none" id="statusContainer">
                <div class="status-text mb-2" id="statusText">Processing...</div>
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                         role="progressbar" id="progressBar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.upload-container {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
}

.profile-section {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
}

.profile-image {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
}

.profile-info h2 {
    margin: 0;
    font-size: 1.5rem;
}

.profile-info p {
    margin: 0;
    color: #6c757d;
}

.upload-card {
    background: rgba(255, 255, 255, 0.02);
    border-radius: 8px;
    padding: 1.5rem;
}

.error-message {
    color: #dc3545;
    margin-top: 0.5rem;
    display: none;
}

.progress {
    height: 0.5rem;
    background-color: rgba(255, 255, 255, 0.1);
}
</style>


<!-- Pass user configuration to JavaScript -->
<!-- Add test script -->
<script>
console.log('Basic script test - if you see this, JavaScript is working');
</script>

<!-- Pass user configuration to JavaScript -->
<script>
console.log('Attempting to set userConfig...');
window.userConfig = <?= json_encode([
    'id' => $userConfig['id'],
    'categories' => $userConfig['categories'],
    'api_key' => $username // Using username as API key for now
]) ?>;
console.log('userConfig set to:', window.userConfig);
</script>

<!-- Initialize the upload functionality -->
<script>
console.log('About to load upload.js...');
</script>
<script src="/management/users/js/upload.js"></script>
<script>
console.log('After upload.js load attempt');
</script>