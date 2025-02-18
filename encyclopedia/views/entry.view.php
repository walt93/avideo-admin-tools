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
                 <button type="button" id="sentimentBtn" class="sentiment-btn">🎯 Sentiment</button>
                 <button type="button" id="rewriteBtn" class="rewrite-btn">🤖 Rewrite</button>
                 <div class="model-selector">
                     <button type="button" id="modelSelectBtn" class="model-select-btn">
                         <span id="currentModel">gpt-4o (16,384)</span>
                         <span class="caret">▼</span>
                     </button>
                     <div class="model-dropdown">
                        <!-- OpenAI Models -->
                        <div class="model-section">OpenAI</div>
                        <div class="model-option" data-model="gpt-4o" data-tokens="16384">GPT-4o (128K/16K)</div>
                        <div class="model-option" data-model="gpt-4o-mini" data-tokens="16384">GPT-4o Mini (128K/16K)</div>
                 <!--   <div class="model-option" data-model="o1" data-tokens="100000">GPT-4 o1 (200K/100K)</div> -->
                        <div class="model-option" data-model="o1-mini" data-tokens="65536">GPT-4 o1 Mini (128K/65K)</div>
                 <!--   <div class="model-option" data-model="o3-mini" data-tokens="100000">GPT-4 o3 Mini (200K/100K)</div> -->
                        <div class="model-option" data-model="o1-preview" data-tokens="32768">GPT-4 o1 Preview (128K/32K)</div>

                        <div class="model-separator"></div>

                        <!-- Anthropic Models -->
                        <div class="model-section">Anthropic</div>
                        <div class="model-option" data-model="claude-3-5-sonnet-20241022" data-tokens="200000" data-provider="anthropic">Claude 3.5 Sonnet (200K)</div>
                        <div class="model-option" data-model="claude-3-5-haiku-20241022" data-tokens="200000" data-provider="anthropic">Claude 3.5 Haiku (200K)</div>
                        <div class="model-separator"></div>

                        <!-- Groq Production Models -->
                        <div class="model-section">Groq Production</div>
                        <div class="model-option" data-model="gemma2-9b-it" data-tokens="8192" data-provider="groq">Gemma-2 9B (8K/8K)</div>
                        <div class="model-option" data-model="llama-3.3-70b-versatile" data-tokens="32768" data-provider="groq">Llama-3 70B (128K/32K)</div>
                        <div class="model-option" data-model="llama-3.1-8b-instant" data-tokens="8192" data-provider="groq">Llama-3 8B Fast (128K/8K)</div>
                        <div class="model-option" data-model="mixtral-8x7b-32768" data-tokens="32768" data-provider="groq">Mixtral 56B (32K/32K)</div>

                        <div class="model-separator"></div>

                        <!-- Groq Development Models -->
                        <div class="model-section">Groq Development</div>
                        <div class="model-option" data-model="qwen-2.5-32b" data-tokens="8192" data-provider="groq">Qwen-2 32B (128K/8K)</div>
                        <div class="model-option" data-model="deepseek-r1-distill-qwen-32b" data-tokens="16384" data-provider="groq">DeepSeek/Qwen 32B (128K/16K)</div>
                        <div class="model-option" data-model="deepseek-r1-distill-llama-70b-specdec" data-tokens="16384" data-provider="groq">DeepSeek/Llama 70B Spec (128K/16K)</div>
                        <div class="model-option" data-model="deepseek-r1-distill-llama-70b" data-tokens="16384" data-provider="groq">DeepSeek/Llama 70B (128K/16K)</div>
                        <div class="model-option" data-model="llama-3.2-1b-preview" data-tokens="8192" data-provider="groq">Llama-3 1B Preview (128K/8K)</div>
                        <div class="model-option" data-model="llama-3.2-3b-preview" data-tokens="8192" data-provider="groq">Llama-3 3B Preview (128K/8K)</div>
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
            <div id="contentSentimentSection" class="sentiment-section" style="display: none;">
                <div class="sentiment-analysis-wrapper">
                    <h3>Content Sentiment Analysis</h3>
                    <div id="contentSentimentResults"></div>
                </div>
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
            <div id="rewriteSentimentSection" class="sentiment-section" style="display: none;">
                <div class="sentiment-analysis-wrapper">
                    <h3>Rewrite Sentiment Analysis</h3>
                    <div id="rewriteSentimentResults"></div>
                </div>
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

        try {
            const selectedModel = localStorage.getItem('selectedModel') || 'gpt-4o';
            const selectedTokens = parseInt(localStorage.getItem('selectedTokens') || '16384');
            const provider = document.querySelector(`.model-option[data-model="${selectedModel}"]`)?.dataset.provider || 'openai';

            const response = await fetch('api/rewrite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    content: contentArea.value,
                    model: selectedModel,
                    max_tokens: selectedTokens,
                    provider: provider
                })
            });

            if (!response.ok) {
                const errorData = await response.json();
                throw new Error(errorData.error || 'Unknown error occurred');
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
<script>
async function analyzeSentiment(content, entryId = null, targetElement, statusElement = null) {
    try {
        console.log('Starting sentiment analysis...');
        const selectedModel = localStorage.getItem('selectedModel') || 'gpt-4o';
        const selectedTokens = parseInt(localStorage.getItem('selectedTokens') || '16384');
        const provider = document.querySelector(`.model-option[data-model="${selectedModel}"]`)?.dataset.provider || 'openai';

        if (statusElement) {
            statusElement.textContent = '🎯 Analyzing sentiment...';
        }

        console.log('Sending request to analyze_sentiment.php...');
        const response = await fetch('api/analyze_sentiment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                content: content,
                entry_id: entryId,
                model: selectedModel,
                max_tokens: selectedTokens,
                provider: provider
            })
        });

        console.log('Response received:', response.status);

        if (!response.ok) {
            const errorData = await response.json();
            console.error('API error:', errorData);
            throw new Error(errorData.error || 'Unknown error occurred');
        }

        const result = await response.json();
        console.log('Analysis result:', result);

        const sentimentSection = targetElement.closest('.sentiment-section');
        sentimentSection.style.display = 'block';

        // Create a new SentimentAnalysis instance and render
        const analyzer = new SentimentAnalysis(result.sentiment_data);
        targetElement.innerHTML = analyzer.render();

        return result;
    } catch (error) {
        console.error('Sentiment analysis error:', error);
        alert('Error during sentiment analysis: ' + error.message);
        return null;
    } finally {
        if (statusElement) {
            statusElement.textContent = '';
        }
    }
}

// Update the sentiment button click handler
document.getElementById('sentimentBtn').addEventListener('click', async function() {
    console.log('Sentiment button clicked');
    const contentArea = document.getElementById('content');
    const entryId = document.querySelector('input[name="id"]')?.value;
    const resultsDiv = document.getElementById('contentSentimentResults');
    const overlay = document.getElementById('rewriteOverlay');

    if (!overlay.querySelector('.overlay-message')) {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'overlay-message';
        overlay.appendChild(messageDiv);
    }

    const statusDiv = overlay.querySelector('.status-text') ||
                     document.createElement('div');
    statusDiv.className = 'status-text';

    if (!overlay.querySelector('.status-text')) {
        overlay.querySelector('.overlay-message').appendChild(statusDiv);
    }

    overlay.style.display = 'flex';

    try {
        console.log('Starting analysis...');
        await analyzeSentiment(
            contentArea.value,
            entryId,
            resultsDiv,
            statusDiv
        );
        console.log('Analysis complete');
    } catch (error) {
        console.error('Error in click handler:', error);
    } finally {
        overlay.style.display = 'none';
    }
});
</script>