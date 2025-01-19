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

async function generateSpeakerStyle() {
    // Create and show a Bootstrap modal dialog for speaker input
    const modalHtml = `
        <div class="modal fade" id="speakerInputModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Enter Speaker Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="speakerName" class="form-label">Speaker's Name</label>
                            <input type="text" class="form-control" id="speakerName" placeholder="Enter speaker's full name">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="processSpeakerDescription()">Generate</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Add modal to document if it doesn't exist
    if (!document.getElementById('speakerInputModal')) {
        document.body.insertAdjacentHTML('beforeend', modalHtml);
    }

    // Show the modal
    const speakerModal = new bootstrap.Modal(document.getElementById('speakerInputModal'));
    speakerModal.show();
}

async function processSpeakerDescription() {
    const speakerName = document.getElementById('speakerName').value.trim();
    if (!speakerName) {
        alert('Please enter a speaker name');
        return;
    }

    // Hide the speaker input modal
    const speakerModal = bootstrap.Modal.getInstance(document.getElementById('speakerInputModal'));
    speakerModal.hide();

    const title = document.getElementById('videoTitle').value;
    const description = document.getElementById('videoDescription').value;

    await generateWithLoading(async () => {
        const formData = new FormData();
        formData.append('action', 'generate_description');
        formData.append('type', 'speaker');
        formData.append('title', title);
        formData.append('description', description);
        formData.append('speaker', speakerName);

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

    // Show loading state
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
            throw new Error(data.error || 'Sanitization failed');
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

        // Strip quotation marks from the beginning and end of the generated text
        let generatedText = data.description || data.sanitized;
        generatedText = generatedText.replace(/^["']|["']$/g, '');

        // Update the description field with the processed content
        description.value = generatedText;

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
    // Use current page URL as the endpoint
    const response = await fetch(window.location.href, {
        method: 'POST',
        body: formData
    });

    // Debug: Log the raw response
    const responseText = await response.text();
    console.log('Raw server response:', responseText);

    try {
        // Try to parse the text as JSON
        return JSON.parse(responseText);
    } catch (error) {
        console.error('Failed to parse server response as JSON:', responseText);
        throw new Error('Server returned invalid JSON: ' + error.message);
    }
}
