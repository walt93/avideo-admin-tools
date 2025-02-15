<?php
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
        <label for="content">Content:</label>
        <textarea id="content" name="content" required><?php
            echo $entry ? h($entry['content']) : '';
        ?></textarea>
    </div>

    <div class="form-group">
        <label for="source_book">Source Book (optional):</label>
        <div class="source-book-inputs">
            <input type="text" id="source_book" name="source_book"
                   value="<?php echo $entry ? h($entry['source_book']) : ''; ?>">
            <select id="source_book_select" onChange="document.getElementById('source_book').value = this.value">
                <option value="">Select existing source...</option>
                <?php foreach ($sourceBooks as $source): ?>
                    <option value="<?php echo h($source['source_book']); ?>">
                        <?php echo h($source['source_book']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
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

<script>
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