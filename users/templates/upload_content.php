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

            <div class="status-container mt-4" id="statusContainer" style="display: none;">
                <div class="status-header mb-2">
                    <span class="status-phase">Initializing...</span>
                    <div class="download-details" style="display: none;">
                        <span class="download-speed">0 MB/s</span> |
                        <span class="download-size">0 MB</span> downloaded
                    </div>
                </div>
                <div class="progress mb-2">
                    <div class="progress-bar progress-bar-striped progress-bar-animated"
                         role="progressbar" style="width: 0%;" id="progressBar">
                        <span class="progress-text">0%</span>
                    </div>
                </div>
                <div class="status-message text-muted small" id="statusText"></div>
            </div>

        </div>
    </div>
</div>

<script>
document.getElementById('uploadForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    // Show status container
    const statusContainer = document.getElementById('statusContainer');
    const statusPhase = document.querySelector('.status-phase');
    const downloadDetails = document.querySelector('.download-details');
    const downloadSpeed = document.querySelector('.download-speed');
    const downloadSize = document.querySelector('.download-size');
    const progressBar = document.getElementById('progressBar');
    const progressText = progressBar.querySelector('.progress-text');
    const statusText = document.getElementById('statusText');

    statusContainer.style.display = 'block';

    const url = document.getElementById('videoUrl').value;
    const categoryData = JSON.parse(document.getElementById('categorySelect').value);

    try {
        // Initial API call
        statusPhase.textContent = 'Starting download...';

        const response = await fetch(`${API_BASE_URL}/api/v1/upload`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-API-Key': API_KEY
            },
            body: JSON.stringify({
                url: url,
                category_id: categoryData.categories_id,
                users_id: categoryData.users_id
            })
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Upload failed');
        }

        // Start polling for status
        const pollInterval = setInterval(async () => {
            try {
                const statusResponse = await fetch(`${API_BASE_URL}/api/v1/upload/status/${data.task_id}`, {
                    headers: {'X-API-Key': API_KEY}
                });

                const statusData = await statusResponse.json();

                // Update UI based on status
                statusPhase.textContent = statusData.phase || 'Processing...';

                if (statusData.download_progress) {
                    downloadDetails.style.display = 'block';
                    downloadSpeed.textContent = `${statusData.download_progress.speed} MB/s`;
                    downloadSize.textContent = `${statusData.download_progress.downloaded} MB`;

                    const percent = statusData.download_progress.percent || 0;
                    progressBar.style.width = `${percent}%`;
                    progressText.textContent = `${percent}%`;
                }

                statusText.textContent = statusData.status_message || '';

                if (statusData.status === 'completed') {
                    clearInterval(pollInterval);
                    statusPhase.textContent = 'Upload completed!';
                    downloadDetails.style.display = 'none';

                    // Reset form after 3 seconds
                    setTimeout(() => {
                        statusContainer.style.display = 'none';
                        e.target.reset();
                    }, 3000);
                } else if (statusData.status === 'failed') {
                    clearInterval(pollInterval);
                    throw new Error(statusData.error || 'Upload failed');
                }
            } catch (error) {
                clearInterval(pollInterval);
                throw error;
            }
        }, 1000);

    } catch (error) {
        console.error('Upload error:', error);
        statusPhase.textContent = 'Error';
        statusText.textContent = error.message;
        downloadDetails.style.display = 'none';
        progressBar.classList.add('bg-danger');
    }
});
</script>

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
<script>
window.userConfig = <?= json_encode([
    'id' => $userConfig['id'],
    'categories' => $userConfig['categories'],
    'api_key' => $username // Using username as API key for now
]) ?>;
</script>
