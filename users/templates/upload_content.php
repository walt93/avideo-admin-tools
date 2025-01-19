<?php
// Get user config
$username = basename(dirname($_SERVER['PHP_SELF']));
$configFile = __DIR__ . '/../config/users.json';
$config = json_decode(file_get_contents($configFile), true);
$userConfig = $config['users'][$username];
?>

<div class="container">
    <div class="upload-container">
        <div class="logo-container">
            <img src="/truth_tide_tv.png" alt="Truth Tide TV" class="img-fluid">
        </div>

        <div class="profile-section">
            <img src="https://conspyre.xyz<?= htmlspecialchars($userConfig['profile']['photo']) ?>"
                 alt="<?= htmlspecialchars($userConfig['profile']['display_name']) ?>"
                 class="profile-image">
            <div class="profile-info">
                <h2><?= htmlspecialchars($userConfig['profile']['display_name']) ?></h2>
                <p><?= htmlspecialchars($userConfig['profile']['social_handle']) ?></p>
            </div>
        </div>

        <div class="upload-card">
            <form id="uploadForm">
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

                <div class="error-message" id="errorMessage"></div>
            </form>

            <div class="status-container" id="statusContainer">
                <div class="status-text" id="statusText">Processing...</div>
                <div class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                         role="progressbar" id="progressBar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pass user configuration to JavaScript -->
<script>
window.userConfig = <?= json_encode([
    'id' => $userConfig['id'],
    'categories' => $userConfig['categories'],
    'api_key' => $username // Using username as API key for now
]) ?>;
</script>

<!-- Initialize the upload functionality -->
<script src="/management/users/js/upload.js"></script>