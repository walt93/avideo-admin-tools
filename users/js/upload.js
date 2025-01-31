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
    console.log('Form submitted');

    const url = document.getElementById('videoUrl').value;
    const categoryData = JSON.parse(document.getElementById('categorySelect').value);
    const errorMessage = document.getElementById('errorMessage');
    const statusContainer = document.getElementById('statusContainer');
    const progressBar = document.getElementById('progressBar');

    console.log('Status container element:', statusContainer);
    console.log('Progress bar element:', progressBar);
    console.log('Status container initial classes:', statusContainer.className);

    // Reset states
    errorMessage.style.display = 'none';
    progressBar.style.width = '0%';
    statusContainer.classList.remove('d-none');

    console.log('Status container classes after show:', statusContainer.className);

    try {
        console.log('Making API request to:', `${API_BASE_URL}/api/v1/upload`);
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
        console.log('Upload response:', data);
        pollStatus(data.task_id);
    } catch (error) {
        console.error('Upload error:', error);
        errorMessage.textContent = 'Error uploading video. Please try again.';
        errorMessage.style.display = 'block';
        statusContainer.classList.add('d-none');
    }
});

// Poll upload status
async function pollStatus(taskId) {
    console.log('Starting status polling for task:', taskId);

    const statusText = document.getElementById('statusText');
    const progressBar = document.getElementById('progressBar');
    const errorMessage = document.getElementById('errorMessage');
    const statusContainer = document.getElementById('statusContainer');

    console.log('Status elements:', {
        statusText,
        progressBar,
        statusContainer
    });

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
            console.log('Status response:', data);

            // Update progress bar
            if (data.total > 0) {
                const percentage = (data.current / data.total) * 100;
                console.log('Setting progress to:', percentage + '%');
                progressBar.style.width = `${percentage}%`;
                progressBar.setAttribute('aria-valuenow', percentage);
            }

            // Update status message
            const message = data.status_message || data.status;
            console.log('Setting status text to:', message);
            statusText.textContent = message;

            // Handle completion or failure
            if (data.status === 'completed') {
                console.log('Upload completed');
                clearInterval(pollInterval);
                statusText.textContent = 'Upload complete!';
                progressBar.style.width = '100%';

                // Reset form after delay
                setTimeout(() => {
                    statusContainer.classList.add('d-none');
                    document.getElementById('uploadForm').reset();
                }, 3000);
            } else if (data.status === 'failed') {
                console.log('Upload failed');
                clearInterval(pollInterval);
                errorMessage.textContent = data.status_message || 'Upload failed';
                errorMessage.style.display = 'block';
                statusText.textContent = 'Upload failed';
                statusContainer.classList.add('d-none');
            }
        } catch (error) {
            console.error('Status polling error:', error);
            clearInterval(pollInterval);
            errorMessage.textContent = 'Error checking upload status';
            errorMessage.style.display = 'block';
            statusContainer.classList.add('d-none');
        }
    }, 2000);  // Poll every 2 seconds
}

// Initialize the page
loadCategories();

// Debug check for elements
console.log('Initial elements check:', {
    form: document.getElementById('uploadForm'),
    statusContainer: document.getElementById('statusContainer'),
    statusText: document.getElementById('statusText'),
    progressBar: document.getElementById('progressBar')
});