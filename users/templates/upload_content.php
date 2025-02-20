<?php
// Get user config
$username = basename(dirname($_SERVER['PHP_SELF']));
$configFile = __DIR__ . '/../config/users.json';
// require_once __DIR__ . '/../templates/UploadedFileManager.php';

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
        <!-- Logo section -->
        <div class="logo-container mb-4">
            <img src="/truth_tide_tv.png" alt="Truth Tide TV" class="img-fluid">
        </div>

        <!-- Profile section -->
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

        <!-- Upload form section -->
        <div class="upload-form-section">
            <form id="uploadForm" class="bg-dark p-4 rounded">
                <h2 class="text-danger mb-3">UPLOAD HERE</h2>
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
            </form>

            <!-- Upload indicator -->
            <div id="uploadingIndicator" class="alert alert-info mt-3 d-none">
                <div class="d-flex align-items-center">
                    <div class="spinner-border spinner-border-sm me-2" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <strong>UPLOADING VIDEO...</strong>
                </div>
            </div>

            <div class="error-message mt-3" id="errorMessage"></div>

            <!-- Status container -->
            <div class="status-container mt-4 d-none" id="statusContainer">
                <div class="status-header mb-2">
                    <div class="d-flex align-items-center">
                        <div class="spinner-border spinner-border-sm me-2" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span class="status-phase">Initializing...</span>
                    </div>
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

        <!-- Recent Uploads section -->
        <div class="uploads-section mt-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="text-light">Recent Uploads</h3>
                <button class="btn btn-outline-light btn-sm" onclick="refreshUploads()">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>

            <div id="uploadsList" class="uploads-list">
                <!-- Uploads will be dynamically inserted here -->
            </div>
        </div>
    </div>
</div>

<style>
.uploads-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.upload-card {
    display: grid;
    grid-template-columns: 180px 1fr auto;
    gap: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    margin-bottom: 0.75rem;
    padding: 0.75rem;
    height: 101px;
}


/* Update upload list card styling */
.upload-card-item {
    display: grid;
    grid-template-columns: 180px 1fr auto;
    gap: 1rem;
    background: rgba(255, 255, 255, 0.05);
    border-radius: 8px;
    overflow: hidden;
}

.upload-thumbnail-container {
    width: 180px;
    height: 101px; /* 16:9 ratio */
    position: relative;
}

.status-container {
    background-color: rgba(13, 202, 240, 0.1);
    border: 1px solid rgba(13, 202, 240, 0.2);
    border-radius: 8px;
    padding: 1rem;
}

.status-phase {
    color: rgba(13, 202, 240, 0.9);
    font-weight: 500;
}

.progress {
    background-color: rgba(13, 202, 240, 0.1);
}

.progress-bar {
    background-color: rgba(13, 202, 240, 0.8);
}


.upload-thumbnail,
.upload-thumbnail.placeholder {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 4px;
}

.upload-content {
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 0;
}

.uploads-section {
    max-width: 1200px;
    margin: 3rem auto 0;
}

.upload-title {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 0.25rem;
}

.upload-title a {
    font-size: 1.1rem;
    text-decoration: none;
}

.upload-meta {
    font-size: 0.85rem;
    color: #888;
}

.upload-status {
    margin: 0;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    font-size: 0.8rem;
    white-space: nowrap;
}

.upload-status.encoding {
    background: rgba(255, 193, 7, 0.2);
    color: #ffc107;
}

.upload-status.active {
    background: rgba(40, 167, 69, 0.2);
    color: #28a745;
}

.upload-status.inactive {
    background: rgba(108, 117, 125, 0.2);
    color: #6c757d;
}

.upload-status.error {
    background: rgba(220, 53, 69, 0.2);
    color: #dc3545;
}

.upload-actions {
    display: flex;
    align-items: center;
    padding-right: 0.5rem;
}

.delete-upload {
    color: #dc3545;
    background: none;
    border: none;
    padding: 0.25rem;
    cursor: pointer;
    opacity: 0.7;
    transition: opacity 0.2s;
}

.delete-upload:hover {
    opacity: 1;
}

/* Style for the upload form section */
.upload-form-section {
    max-width: 600px;
    margin: 0 auto;
    background: rgba(255, 255, 255, 0.02);
    border-radius: 8px;
    padding: 1.5rem;
}

#uploadForm {
    width: 100%;
}

#uploadForm input,
#uploadForm select {
    width: 100%;
    margin-bottom: 1rem;
}
.transcript-badge {
    position: absolute;
    bottom: 0.5rem;
    left: 0.5rem;
    background: rgba(0, 0, 0, 0.7);
    color: #fff;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
}

.upload-container {
    max-width: 1200px;
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

.error-message {
    color: #dc3545;
    margin-top: 0.5rem;
    display: none;
}

.progress {
    height: 0.5rem;
    background-color: rgba(255, 255, 255, 0.1);
}

.status-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #e0e0e0;
}

.status-phase {
    font-weight: 500;
}

.download-details {
    font-size: 0.9em;
    color: #adb5bd;
}

.progress-bar {
    position: relative;
    overflow: visible;
    background-color: #0d6efd;
    transition: width 0.5s ease;
}

.progress-text {
    position: absolute;
    right: 5px;
    color: white;
    font-size: 0.75rem;
    line-height: 0.75rem;
}

.status-message {
    font-size: 0.85em;
    color: #adb5bd;
}
</style>

<script>
window.userConfig = <?= json_encode([
    'id' => $userConfig['id'],
    'categories' => $userConfig['categories'],
    'api_key' => $username // Using username as API key for now
]) ?>;

// Constants
const API_BASE_URL = 'https://api.conspyre.tv';
const API_KEY = window.userConfig.api_key;

// Initialize categories
function loadCategories() {
    const categories = window.userConfig.categories;
    const select = document.getElementById('categorySelect');
    categories.forEach(category => {
        const option = document.createElement('option');
        option.value = JSON.stringify({
            categories_id: category.categories_id,
            users_id: window.userConfig.id
        });
        option.textContent = category.name;
        select.appendChild(option);
    });
}

// Form submission handler
document.getElementById('uploadForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    // Show uploading indicator
    const statusContainer = document.getElementById('statusContainer');
    const statusPhase = document.querySelector('.status-phase');
    const downloadDetails = document.querySelector('.download-details');
    const downloadSpeed = document.querySelector('.download-speed');
    const downloadSize = document.querySelector('.download-size');
    const progressBar = document.getElementById('progressBar');
    const progressText = progressBar.querySelector('.progress-text');
    const statusText = document.getElementById('statusText');
    const errorMessage = document.getElementById('errorMessage');

    // Clear any previous error messages
    errorMessage.style.display = 'none';
    errorMessage.textContent = '';

    statusContainer.classList.remove('d-none');

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
        console.log('API response:', data);

        if (!data.task_id) {
            throw new Error(data.error || 'Upload failed');
        }

        // Start polling for status
        const pollInterval = setInterval(async () => {
            try {
                const statusResponse = await fetch(`${API_BASE_URL}${data.status_url}`, {
                    headers: {'X-API-Key': API_KEY}
                });

                const statusData = await statusResponse.json();
                console.log('Status response:', statusData);

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
                        console.log('Upload completed, status data:', statusData);  // Debug log

                        try {
                            // Add to uploads log
                            const uploadData = {
                                id: statusData.result.video_id,  // Changed from statusData.video_id
                                url: url,
                                title: statusData.result.title || url,  // Get title from result
                                description: statusData.result.description || '',
                                category: document.querySelector('#categorySelect option:checked').textContent,
                                category_id: categoryData.categories_id
                            };

                            console.log('Attempting to save upload data:', uploadData);

                            const saveResponse = await fetch('?action=add_upload', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify(uploadData)
                            });

                            const saveResult = await saveResponse.json();
                            console.log('Save result:', saveResult);

                            if (!saveResult.success) {
                                console.error('Failed to save upload:', saveResult.error);
                            } else {
                                // Refresh the uploads list
                                loadUploads();
                            }
                        } catch (error) {
                            console.error('Error saving upload:', error);
                        }

                        statusPhase.textContent = 'Upload completed!';
                        downloadDetails.style.display = 'none';

                        // Reset form after 3 seconds
                        setTimeout(() => {
                            statusContainer.classList.add('d-none');
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

        // Show error message
        errorMessage.textContent = error.message;
        errorMessage.style.display = 'block';
    }
});

// Load categories when the page loads
loadCategories();
</script>

<script>
let refreshInterval;

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

function createUploadCard(upload, videoDetails) {
    const hasFiles = videoDetails?.files || {};
    const hasTranscript = hasFiles.transcript;
    const stateClass = videoDetails?.state ? {
        'e': 'encoding',
        'a': 'active',
        'i': 'inactive',
        'x': 'error'
    }[videoDetails.state] || 'inactive' : 'pending';

    return `
        <div class="upload-card" data-id="${upload.id}">
            <div class="upload-thumbnail-container">
                ${hasFiles.thumbnail
                    ? `<img src="/videos/${videoDetails.filename}/${videoDetails.filename}.jpg"
                         class="upload-thumbnail" alt="Video thumbnail">`
                    : `<div class="upload-thumbnail placeholder">
                         <i class="bi bi-film"></i>
                       </div>`
                }
            </div>

            <div class="upload-content">
                <div class="upload-title">
                    <a href="https://conspyre.tv/v/${upload.id}" target="_blank" class="text-light">
                        ${upload.title}
                    </a>
                    <div class="upload-status ${stateClass}">
                        <i class="bi bi-circle-fill me-1"></i>
                        ${videoDetails?.stateDescription || upload.status}
                    </div>
                </div>
                <div class="upload-meta">
                    Uploaded: ${formatDate(upload.upload_date)}<br>
                    Category: ${upload.category}
                </div>
            </div>

            <div class="upload-actions">
                <button onclick="removeUpload('${upload.id}')" class="delete-upload"
                        title="Remove from list">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
    `;
}

async function loadUploads() {
    try {
        console.log('Fetching uploads...');
        const response = await fetch('?action=get_uploads');
        const responseText = await response.text();
        console.log('Raw uploads response:', responseText);

        try {
            const data = JSON.parse(responseText);
            console.log('Parsed uploads data:', data);

            if (!data.success) {
                throw new Error(data.error);
            }

            const uploadsList = document.getElementById('uploadsList');
            // Reverse the array before mapping
            uploadsList.innerHTML = data.uploads.reverse().map(upload =>
                createUploadCard(upload, data.videoDetails[upload.id])
            ).join('');

        } catch (parseError) {
            console.error('Error parsing uploads response:', parseError);
            console.log('Response that failed to parse:', responseText);
            throw parseError;
        }

    } catch (error) {
        console.error('Error loading uploads:', error);
    }
}

function refreshUploads() {
    loadUploads();
}

async function removeUpload(id) {
    if (!confirm('Remove this upload from the list?')) {
        return;
    }

    try {
        const response = await fetch('?action=remove_upload', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id })
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.error);
        }

        // Find and remove the correct card
        const card = document.querySelector(`.upload-card[data-id="${id}"]`);
        if (card) {
            card.remove();
        } else {
            // If we can't find the card, refresh the whole list
            await loadUploads();
        }

    } catch (error) {
        console.error('Error removing upload:', error);
        alert('Failed to remove upload: ' + error.message);
    }
}

// Start periodic refresh
document.addEventListener('DOMContentLoaded', () => {
    loadUploads();
    refreshInterval = setInterval(loadUploads, 30000);
});

// Clean up interval when leaving page
window.addEventListener('beforeunload', () => {
    clearInterval(refreshInterval);
});
</script>