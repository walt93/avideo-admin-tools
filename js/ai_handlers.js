// js/ai_handlers.js

async function generateFromTranscript() {
    const modalBody = document.querySelector('.modal-body');
    const description = document.getElementById('videoDescription');
    const filename = document.getElementById('videoFilename').value;
    
    await generateWithLoading(async () => {
        const formData = new FormData();
        formData.append('action', 'generate_description');
        formData.append('type', 'transcript');
        formData.append('filename', filename);
        
        return await makeRequest(formData);
    });
}



async function rewriteExisting() {
    const title = document.getElementById('videoTitle').value;
    const description = document.getElementById('videoDescription').value;
    
    await generateWithLoading(async () => {
        const formData = new FormData();
        formData.append('action', 'generate_description');
        formData.append('type', 'rewrite');
        formData.append('title', title);
        formData.append('description', description);
        
        return await makeRequest(formData);
    });
}

async function generateEventStyle() {
    const title = document.getElementById('videoTitle').value;
    const description = document.getElementById('videoDescription').value;
    
    await generateWithLoading(async () => {
        const formData = new FormData();
        formData.append('action', 'generate_description');
        formData.append('type', 'event');
        formData.append('title', title);
        formData.append('description', description);
        
        return await makeRequest(formData);
    });
}

async function quickSanitize(videoId, button) {
    const row = button.closest('tr');
    const cellContent = row.querySelector('.cell-content');
    const descriptionDiv = row.querySelector('.video-description');
    const originalDescription = descriptionDiv.textContent;

    // Disable the entire row and show loading
    row.classList.add('processing');

    // Create and append loading overlay
    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'loading-overlay';
    loadingOverlay.innerHTML = `
        <div>
            <div class="spinner mb-2"></div>
            <div class="description-loading">Sanitizing description...</div>
        </div>
    `;
    cellContent.appendChild(loadingOverlay);

    // Disable all buttons in the row
    row.querySelectorAll('button').forEach(btn => btn.disabled = true);

    try {
        const formData = new FormData();
        formData.append('action', 'sanitize');
        formData.append('description', originalDescription);

        const data = await makeRequest(formData);

        if (!data.success) {
            throw new Error(data.error);
        }

        // Update loading message
        loadingOverlay.querySelector('.description-loading').textContent = 'Saving changes...';

        // Save the sanitized description
        const saveFormData = new FormData();
        saveFormData.append('action', 'update');
        saveFormData.append('id', videoId);
        saveFormData.append('title', row.querySelector('.pure-title').textContent.trim());
        saveFormData.append('description', data.sanitized);

        const saveData = await makeRequest(saveFormData);
        if (!saveData.success) {
            throw new Error('Failed to save sanitized description');
        }

        // Update the description in the list
        descriptionDiv.textContent = data.sanitized;

    } catch (error) {
        console.error('Error sanitizing description:', error);
        alert('Error: ' + error.message);
    } finally {
        // Remove loading overlay
        loadingOverlay.remove();
        // Re-enable the row
        row.classList.remove('processing');
        // Re-enable all buttons
        row.querySelectorAll('button').forEach(btn => btn.disabled = false);
    }
}


async function sanitizeDescription() {
    const description = document.getElementById('videoDescription');
    const sanitizeButton = document.querySelector('.modal-footer .btn-warning');
    const saveButton = document.querySelector('.modal-footer .btn-primary');
    const modalBody = document.querySelector('.modal-body');

    await generateWithLoading(async () => {
        const formData = new FormData();
        formData.append('action', 'sanitize');
        formData.append('description', description.value);
        
        return await makeRequest(formData);
    });
}



// Utility function for showing loading state during generation
async function generateWithLoading(generationFunc) {
    const modalBody = document.querySelector('.modal-body');
    const description = document.getElementById('videoDescription');
    const buttons = document.querySelectorAll('.modal-footer button');
    
    // Disable all buttons and add loading overlay
    buttons.forEach(btn => btn.disabled = true);
    description.disabled = true;

    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'loading-overlay';
    loadingOverlay.innerHTML = `
        <div>
            <div class="spinner mb-2"></div>
            <div class="description-loading">Processing...</div>
        </div>
    `;
    modalBody.appendChild(loadingOverlay);

    try {
        const data = await generationFunc();
        
        if (!data.success) {
            throw new Error(data.error || 'Processing failed');
        }

        // Update the description field with the generated/processed content
        description.value = data.description || data.sanitized;

    } catch (error) {
        console.error('Error:', error);
        alert('Error: ' + error.message);
    } finally {
        // Remove loading overlay
        loadingOverlay.remove();
        // Re-enable all controls
        buttons.forEach(btn => btn.disabled = false);
        description.disabled = false;
    }
}



// Utility function for making requests
async function makeRequest(formData) {
    const response = await fetch(window.location.href, {
        method: 'POST',
        body: formData
    });
    return await response.json();
}
