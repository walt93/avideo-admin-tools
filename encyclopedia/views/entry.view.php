<div class="back-nav">
    <a href="index.php">← Back</a>
</div>

<h1><?php echo $entry ? 'Edit' : 'New'; ?> Entry</h1>

<?php if (isset($error_message)): ?>
    <div class="message error"><?php echo h($error_message); ?></div>
<?php endif; ?>

<form method="POST" action="" class="entry-form">
    <?php if ($entry): ?>
        <input type="hidden" name="id" value="<?php echo $entry['id']; ?>">
    <?php endif; ?>

    <div class="form-group">
        <label for="title">Topic/Title:</label>
        <input type="text" id="title" name="title" required
               value="<?php echo $entry ? h($entry['title']) : ''; ?>">
    </div>

    <div class="form-group">
        <div class="content-header">
            <label for="content">Content:</label>
            <div class="rewrite-controls">
                <button type="button" id="rewriteBtn" class="rewrite-btn">🤖 Rewrite</button>
                <div class="model-selector">
                    <button type="button" id="modelSelectBtn" class="model-select-btn">
                        <span id="currentModel">gpt-4o (16,384)</span>
                        <span class="caret">▼</span>
                    </button>
                    <div class="model-dropdown">
                        <div class="model-option" data-model="gpt-4o" data-tokens="16384">gpt-4o (16,384)</div>
                        <div class="model-option" data-model="gpt-4o-mini" data-tokens="16384">gpt-4o-mini (16,384)</div>
                        <div class="model-option" data-model="o1" data-tokens="100000">o1 (100,000)</div>
                        <div class="model-option" data-model="o1-mini" data-tokens="65563">o1-mini (65,563)</div>
                        <div class="model-option" data-model="o3-mini" data-tokens="100000">o3-mini (100,000)</div>
                        <div class="model-option" data-model="o1-preview" data-tokens="32768">o1-preview (32,768)</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="content-wrapper">
            <textarea id="content" name="content" required><?php
                echo $entry ? h($entry['content']) : '';
            ?></textarea>
            <div class="text-stats">
                <span id="contentWordCount">0 words</span>
                <span class="stat-divider">|</span>
                <span id="contentCharCount">0 characters</span>
            </div>
        </div>
    </div>

    <div id="aiRewriteSection" class="form-group" style="display: none;">
        <label for="aiRewrite">AI Rewrite:</label>
        <div class="content-wrapper">
            <textarea id="aiRewrite" readonly></textarea>
            <div class="text-stats">
                <span id="aiWordCount">0 words</span>
                <span class="stat-divider">|</span>
                <span id="aiCharCount">0 characters</span>
            </div>
        </div>
        <button type="button" id="useAiBtn" class="use-ai-btn">Use AI Rewrite</button>
    </div>

    <div class="form-group">
        <label for="source_book">Source:</label>
        <div class="source-book-inputs">
            <select id="source_book_select" onChange="document.getElementById('source_book').value = this.value">
                <option value="">Select existing source...</option>
                <?php foreach ($sourceBooks as $source): ?>
                    <option value="<?php echo h($source['source_book']); ?>">
                        <?php echo h($source['source_book']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="text"
                   id="source_book"
                   name="source_book"
                   placeholder="ENTER SOURCE BOOK NAME"
                   value="<?php echo $entry ? h($entry['source_book']) : ''; ?>">
        </div>
    </div>

    <div class="form-group">
        <label for="status">Status:</label>
        <select id="status" name="status">
            <option value="draft" <?php echo ($entry && $entry['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
            <option value="review" <?php echo ($entry && $entry['status'] == 'review') ? 'selected' : ''; ?>>Review</option>
            <option value="published" <?php echo ($entry && $entry['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
        </select>
    </div>

    <div class="form-group">
        <label for="footnotes">References (one per line):</label>
        <textarea id="footnotes" name="footnotes"><?php
            if ($footnotes) {
                echo h(implode("\n", array_map(function($f) {
                    return $f['content'];
                }, $footnotes)));
            }
        ?></textarea>
    </div>

    <div class="actions">
        <a href="index.php" class="back-link">Back to List</a>
        <button type="submit">Save Entry</button>
    </div>
</form>

<div id="rewriteOverlay" class="rewrite-overlay">
    🤖 AI Rewriting in Progress...
</div>

<script>
    // Word and character count functions
    function updateWordCount(text) {
        return text.trim().split(/\s+/).filter(word => word.length > 0).length;
    }

    function updateCharCount(text) {
        return text.length;
    }

    function updateStats(text, wordId, charId) {
        document.getElementById(wordId).textContent = updateWordCount(text) + ' words';
        document.getElementById(charId).textContent = updateCharCount(text) + ' characters';
    }

    // Initialize and update content stats
    const contentArea = document.getElementById('content');
    updateStats(contentArea.value, 'contentWordCount', 'contentCharCount');
    contentArea.addEventListener('input', () => {
        updateStats(contentArea.value, 'contentWordCount', 'contentCharCount');
    });

    // Store source book in local storage on form submission
    document.querySelector('form').addEventListener('submit', function() {
        const sourceBook = document.getElementById('source_book').value;
        if (sourceBook) {
            localStorage.setItem('lastUsedSourceBook', sourceBook);
        }
    });

    // Pre-populate source book from local storage if this is a new entry
    <?php if (!$entry): ?>
    document.addEventListener('DOMContentLoaded', function() {
        const lastUsedSourceBook = localStorage.getItem('lastUsedSourceBook');
        if (lastUsedSourceBook) {
            document.getElementById('source_book').value = lastUsedSourceBook;

            // Also select the matching option in the dropdown if it exists
            const select = document.getElementById('source_book_select');
            for (let option of select.options) {
                if (option.value === lastUsedSourceBook) {
                    option.selected = true;
                    break;
                }
            }
        }
    });
    <?php endif; ?>

    // Add rewrite functionality
    document.getElementById('rewriteBtn').addEventListener('click', async function() {
        const contentArea = document.getElementById('content');
        const statusSelect = document.getElementById('status');
        const overlay = document.getElementById('rewriteOverlay');
        const aiSection = document.getElementById('aiRewriteSection');
        const aiRewrite = document.getElementById('aiRewrite');

        // Show overlay
        overlay.style.display = 'flex';

        const selectedModel = localStorage.getItem('selectedModel') || 'gpt-4o';
        const selectedTokens = parseInt(localStorage.getItem('selectedTokens') || '16384');

        try {
            const response = await fetch('api/rewrite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    content: contentArea.value,
                    model: selectedModel,
                    max_tokens: selectedTokens
                })
            });

            if (!response.ok) {
                throw new Error('Rewrite failed');
            }

            const result = await response.json();

            // Show AI rewrite section and update content
            aiSection.style.display = 'block';
            aiRewrite.value = result.content;
            updateStats(result.content, 'aiWordCount', 'aiCharCount');

            // Upgrade status (draft -> review -> published)
            if (statusSelect.value === 'draft') {
                statusSelect.value = 'review';
            } else if (statusSelect.value === 'review') {
                statusSelect.value = 'published';
            }

        } catch (error) {
            alert('Error during rewrite: ' + error.message);
        } finally {
            // Hide overlay
            overlay.style.display = 'none';
        }
    });

    // Model selector functionality
    const modelSelector = document.querySelector('.model-selector');
    const modelSelectBtn = document.getElementById('modelSelectBtn');
    const currentModelSpan = document.getElementById('currentModel');

    // Load saved model from localStorage or use default
    const savedModel = localStorage.getItem('selectedModel') || 'gpt-4o';
    const savedTokens = localStorage.getItem('selectedTokens') || '16384';
    currentModelSpan.textContent = `${savedModel} (${Number(savedTokens).toLocaleString()})`;

    // Toggle dropdown
    modelSelectBtn.addEventListener('click', () => {
        modelSelector.classList.toggle('active');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
        if (!modelSelector.contains(e.target)) {
            modelSelector.classList.remove('active');
        }
    });

    // Handle model selection
    document.querySelectorAll('.model-option').forEach(option => {
        const model = option.dataset.model;
        const tokens = option.dataset.tokens;

        // Mark initially selected option
        if (model === savedModel) {
            option.classList.add('selected');
        }

        option.addEventListener('click', () => {
            // Update selection
            document.querySelectorAll('.model-option').forEach(opt => opt.classList.remove('selected'));
            option.classList.add('selected');

            // Update button text
            currentModelSpan.textContent = option.textContent;

            // Save to localStorage
            localStorage.setItem('selectedModel', model);
            localStorage.setItem('selectedTokens', tokens);

            // Close dropdown
            modelSelector.classList.remove('active');
        });
    });

    // Handle "Use AI Rewrite" button
    document.getElementById('useAiBtn').addEventListener('click', function() {
        const contentArea = document.getElementById('content');
        const aiRewrite = document.getElementById('aiRewrite');
        const aiSection = document.getElementById('aiRewriteSection');

        // Copy AI rewrite to content area
        contentArea.value = aiRewrite.value;
        updateStats(contentArea.value, 'contentWordCount', 'contentCharCount');

        // Hide AI section
        aiSection.style.display = 'none';
    });
</script>