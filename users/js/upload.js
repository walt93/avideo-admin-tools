// Configuration from PHP
const API_KEY = window.userConfig.api_key;
const API_BASE_URL = 'https://api.conspyre.tv';

// Initialize categories from user config
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

// Handle form submission
document.getElementById('uploadForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const url = document.getElementById('videoUrl').value;
    const categoryData = JSON.parse(document.getElementById('categorySelect').value);
    const errorMessage = document.getElementById('errorMessage');
    const statusContainer = document.getElementById('statusContainer');
    const progressBar = document.getElementById('progressBar');

    // Reset states
    errorMessage.style.display = 'none';
    progressBar.style.width = '0%';
    statusContainer.classList.remove('d-none');

    try {
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

        if (!response.ok) {
            throw new Error('Upload failed');
        }

        const data = await response.json();
        pollStatus(data.task_id);
    } catch (error) {
        errorMessage.textContent = 'Error uploading video. Please try again.';
        errorMessage.style.display = 'block';
        statusContainer.classList.add('d-none');
    }
});

// Poll upload status
async function pollStatus(taskId) {
    const statusText = document.getElementById('statusText');
    const progressBar = document.getElementById('progressBar');
    const errorMessage = document.getElementById('errorMessage');
    const statusContainer = document.getElementById('statusContainer');

    const pollInterval = setInterval(async () => {
        try {
            const response = await fetch(`${API_BASE_URL}/api/v1/upload/status/${taskId}`, {
                headers: {
                    'X-API-Key': API_KEY
                }
            });

            if (!response.ok) {
                throw new Error('Status check failed');
            }

            const data = await response.json();

            // Update progress bar
            if (data.total > 0) {
                const percentage = (data.current / data.total) * 100;
                progressBar.style.width = `${percentage}%`;
                progressBar.setAttribute('aria-valuenow', percentage);
            }

            // Update status message
            statusText.textContent = data.status_message || data.status;

            // Handle completion or failure
            if (data.status === 'completed') {
                clearInterval(pollInterval);
                statusText.textContent = 'Upload complete!';
                progressBar.style.width = '100%';

                // Reset form after delay
                setTimeout(() => {
                    statusContainer.classList.add('d-none');
                    document.getElementById('uploadForm').reset();
                }, 3000);
            } else if (data.status === 'failed') {
                clearInterval(pollInterval);
                errorMessage.textContent = data.status_message || 'Upload failed';
                errorMessage.style.display = 'block';
                statusText.textContent = 'Upload failed';
                statusContainer.classList.add('d-none');
            }
        } catch (error) {
            clearInterval(pollInterval);
            errorMessage.textContent = 'Error checking upload status';
            errorMessage.style.display = 'block';
            statusContainer.classList.add('d-none');
        }
    }, 2000);  // Poll every 2 seconds
}

// Initialize the page
loadCategories();