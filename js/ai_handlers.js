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

function handleSanitize($post) {
    if (!isset($post['description'])) {
        echo json_encode(['success' => false, 'error' => 'Description required']);
        return;
    }

    try {
        $openaiKey = getenv('OPENAI_API_KEY');
        if (!$openaiKey) {
            echo json_encode(['success' => false, 'error' => 'OpenAI API key not configured']);
            return;
        }

        $data = [
            'model' => 'gpt-4-0125-preview',
            'messages' => [
            [
                'role' => 'system',
            'content' => 'You are a helpful assistant that sanitizes and formats video descriptions.'
    ],
        [
            'role' => 'user',
            'content' => <<<EOT
            You must preserve the exact meaning and information from the original text. Your only tasks are:
1. Fix basic capitalization (beginning of sentences)
        2. Add correct punctuation if missing
            3. Remove emojis
        4. Remove email addresses and URLs
        5. Split into paragraphs where appropriate

        Rules:
            - DO NOT rewrite or paraphrase any content
        - DO NOT add or remove information
        - DO NOT change word choice
        - DO NOT remove hashtags
        - DO NOT "improve" the text
        - Keep line breaks where they exist in the original
        - Preserve ALL original terminology and phrasing
        - If something looks like a formatting error but you're not sure, leave it as is

        Original text:
        {$post['description']}

        Return ONLY the formatted text with no explanations.
            EOT
    ]
    ],
        'temperature' => 0.3
    ];

        $result = call_openai_api($data);
        echo json_encode(['success' => true, 'sanitized' => $result]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function handleDescriptionGeneration($post) {
    try {
        $description = '';

        switch ($post['type']) {
            case 'transcript':
                if (!isset($post['filename'])) {
                    throw new Exception('Filename not provided');
                }
                $description = generate_from_transcript($post['filename']);
                break;

            case 'rewrite':
                if (!isset($post['title']) || !isset($post['description'])) {
                    throw new Exception('Title and description required');
                }
                $description = rewrite_existing($post['title'], $post['description']);
                break;

            case 'event':
                if (!isset($post['title']) || !isset($post['description'])) {
                    throw new Exception('Title and description required');
                }
                $description = generate_event_style($post['title'], $post['description']);
                break;

            default:
                throw new Exception('Invalid generation type');
        }

        echo json_encode(['success' => true, 'description' => $description]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

// Utility function for making requests
async function makeRequest(formData) {
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
