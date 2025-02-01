FUCK YOU
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

document.getElementById('uploadForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    // Show status immediately
    document.getElementById('statusContainer').style.display = 'block';
    document.getElementById('statusText').textContent = 'Starting upload...';

    const url = document.getElementById('videoUrl').value;
    const categoryData = JSON.parse(document.getElementById('categorySelect').value);

    try {
        const response = await fetch(`${API_BASE_URL}/xapi/v1/upload`, {
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
        pollStatus(data.task_id);
    } catch (error) {
        document.getElementById('errorMessage').style.display = 'block';
        document.getElementById('statusContainer').style.display = 'none';
    }
});

function pollStatus(taskId) {
    const interval = setInterval(async () => {
        try {
            const response = await fetch(`${API_BASE_URL}/xapi/v1/upload/status/${taskId}`, {
                headers: {'X-API-Key': API_KEY}
            });

            const data = await response.json();

            // Update status
            document.getElementById('statusText').textContent = data.status_message || data.status;

            if (data.status === 'completed') {
                clearInterval(interval);
                setTimeout(() => {
                    document.getElementById('statusContainer').style.display = 'none';
                    document.getElementById('uploadForm').reset();
                }, 3000);
            }
        } catch (error) {
            clearInterval(interval);
            document.getElementById('statusContainer').style.display = 'none';
        }
    }, 2000);
}

loadCategories();