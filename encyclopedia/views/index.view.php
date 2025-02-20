<div class="header-section">
    <div class="header-top">
        <h1>DeepState Guide Entries</h1>
        <div class="stats-panels">
            <!-- Source Books Panel -->
            <div class="stats-panel">
                <h3>Sources</h3>
                <div class="stat-item <?php echo $selected_source === 'ALL' ? 'active' : ''; ?>">
                    <a href="<?php echo buildUrl(['source_book' => 'ALL']); ?>">
                        All Sources
                        <span class="stat-count"><?php echo array_sum(array_column($source_books, 'count')); ?></span>
                    </a>
                </div>
                <?php foreach ($source_books as $source): ?>
                <div class="stat-item <?php echo $selected_source === $source['source_book'] ? 'active' : ''; ?>">
                    <a href="<?php echo buildUrl(['source_book' => $source['source_book']]); ?>">
                        <?php echo h($source['source_book']); ?>
                        <span class="stat-count"><?php echo $source['count']; ?></span>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Status Panel -->
            <div class="stats-panel">
                <h3>Status</h3>
                <div class="stat-item <?php echo $selected_status === 'ALL' ? 'active' : ''; ?>">
                    <a href="<?php echo buildUrl(['status' => 'ALL']); ?>">
                        All Statuses
                        <span class="stat-count"><?php echo array_sum(array_column($status_counts, 'count')); ?></span>
                    </a>
                </div>
                <?php foreach ($status_counts as $status): ?>
                <div class="stat-item <?php echo $selected_status === $status['status'] ? 'active' : ''; ?>">
                    <a href="<?php echo buildUrl(['status' => $status['status']]); ?>">
                        <?php echo ucfirst($status['status']); ?>
                        <span class="stat-count"><?php echo $status['count']; ?></span>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Recent Edits Panel -->
            <div class="stats-panel">
                <h3>Recent Edits</h3>
                <?php foreach ($recent_edits as $edit): ?>
                <div class="recent-edit">
                    <span title="<?php echo h($edit['title']); ?>">
                        <?php echo h(strlen($edit['title']) > 30 ? substr($edit['title'], 0, 27) . '...' : $edit['title']); ?>
                    </span>
                    <a href="entry.php?id=<?php echo $edit['id']; ?>" class="action-icon" title="Edit">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                        </svg>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<a href="entry.php" class="add-new">Add New Entry</a>

<?php if (isset($error_message)): ?>
    <div class="message error"><?php echo h($error_message); ?></div>
<?php endif; ?>

<div class="alpha-filter">
    <div class="alpha-buttons">
        <?php
        $letters = range('A', 'Z');
        $active_letters = isset($_GET['alpha']) ? explode(',', $_GET['alpha']) : [];

        foreach ($letters as $letter):
        ?>
            <button
                class="alpha-button <?php echo in_array($letter, $active_letters) ? 'active' : ''; ?>"
                data-letter="<?php echo $letter; ?>"
                type="button"
            ><?php echo $letter; ?></button>
        <?php endforeach; ?>
    </div>
    <?php if (!empty($active_letters)): ?>
        <button class="alpha-clear" type="button" title="Clear letter filters">×</button>
    <?php endif; ?>
</div>

<table>
    <thead>
        <tr>
            <th colspan="6" class="title-filter-header">
                <div class="title-filter-wrapper">
                    <input type="text"
                           id="titleFilter"
                           class="title-filter"
                           placeholder="Filter by title..."
                           value="<?php echo h($_GET['title_search'] ?? ''); ?>">
                    <?php if (isset($_GET['title_search']) && $_GET['title_search'] !== ''): ?>
                    <button type="button" id="clearTitleFilter" class="clear-filter" title="Clear filter">×</button>
                    <?php endif; ?>
                </div>
            </th>
        </tr>
        <tr>
            <th class="sort-header <?php echo $sort_field === 'title' ? 'active-sort' : ''; ?>"
                onclick="window.location.href='<?php echo buildUrl([
                    'sort' => 'title',
                    'direction' => ($sort_field === 'title' && $sort_direction === 'asc') ? 'desc' : 'asc'
                ]); ?>'">
                Title
                <span class="sort-icon"><?php echo getSortIcon('title', $sort_field, $sort_direction); ?></span>
            </th>
            <th>
                <div class="filter-container">
                    <div class="filter-header">
                        Source
                        <svg class="filter-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 4h18M6 9h12M9 14h6M11 19h2"/>
                        </svg>
                    </div>
                    <div class="filter-menu">
                        <div class="filter-option <?php echo $selected_source === 'ALL' ? 'active' : ''; ?>">
                            <a href="<?php echo buildUrl(['source_book' => 'ALL']); ?>" style="text-decoration: none; color: inherit; display: flex; justify-content: space-between; width: 100%;">
                                <span>All Sources</span>
                                <span class="count-badge"><?php echo array_sum(array_column($source_books, 'count')); ?></span>
                            </a>
                        </div>
                        <?php foreach ($source_books as $source): ?>
                        <div class="filter-option <?php echo $selected_source === $source['source_book'] ? 'active' : ''; ?>">
                            <a href="<?php echo buildUrl(['source_book' => $source['source_book']]); ?>" style="text-decoration: none; color: inherit; display: flex; justify-content: space-between; width: 100%;">
                                <span><?php echo h($source['source_book']); ?></span>
                                <span class="count-badge"><?php echo $source['count']; ?></span>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </th>
            <th>
                <div class="filter-container">
                    <div class="filter-header">
                        Status
                        <svg class="filter-icon" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 4h18M6 9h12M9 14h6M11 19h2"/>
                        </svg>
                    </div>
                    <div class="filter-menu">
                        <div class="filter-option <?php echo $selected_status === 'ALL' ? 'active' : ''; ?>">
                            <a href="<?php echo buildUrl(['status' => 'ALL']); ?>" style="text-decoration: none; color: inherit; display: flex; justify-content: space-between; width: 100%;">
                                <span>All Statuses</span>
                                <span class="count-badge"><?php echo array_sum(array_column($status_counts, 'count')); ?></span>
                            </a>
                        </div>
                        <?php foreach ($status_counts as $status): ?>
                        <div class="filter-option <?php echo $selected_status === $status['status'] ? 'active' : ''; ?>">
                            <a href="<?php echo buildUrl(['status' => $status['status']]); ?>" style="text-decoration: none; color: inherit; display: flex; justify-content: space-between; width: 100%;">
                                <span><?php echo ucfirst($status['status']); ?></span>
                                <span class="count-badge"><?php echo $status['count']; ?></span>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </th>
            <th>References</th>
            <th class="sort-header <?php echo $sort_field === 'updated_at' ? 'active-sort' : ''; ?>"
                onclick="window.location.href='<?php echo buildUrl([
                    'sort' => 'updated_at',
                    'direction' => ($sort_field === 'updated_at' && $sort_direction === 'asc') ? 'desc' : 'asc'
                ]); ?>'">
                Last Updated
                <span class="sort-icon"><?php echo getSortIcon('updated_at', $sort_field, $sort_direction); ?></span>
            </th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($entries as $entry): ?>
        <tr>
            <td><?php echo h($entry['title']); ?></td>
            <td class="source-book"><?php echo h($entry['source_book'] ?? 'Unspecified'); ?></td>
            <td><span class="status-<?php echo $entry['status']; ?>"><?php echo ucfirst($entry['status']); ?></span></td>
            <td><?php echo $entry['footnote_count']; ?></td>
            <td><?php echo date('Y-m-d H:i', strtotime($entry['updated_at'])); ?></td>
            <td class="actions">
                <a href="entry.php?id=<?php echo $entry['id']; ?>" class="action-icon" title="Edit">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                    </svg>
                </a>
                <form class="delete-form" method="POST" onsubmit="return confirm('Are you sure you want to delete this entry?');">
                    <input type="hidden" name="id" value="<?php echo $entry['id']; ?>">
                    <button type="submit" name="delete" class="action-button delete-button" title="Delete">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<script>
document.getElementById('clearTitleFilter')?.addEventListener('click', function() {
    // Clear the input
    document.getElementById('titleFilter').value = '';

    // Get current URL parameters
    const urlParams = new URLSearchParams(window.location.search);

    // Remove the title_search parameter
    urlParams.delete('title_search');

    // Reset to first page
    urlParams.set('page', '1');

    // Navigate to the new URL
    window.location.href = '?' + urlParams.toString();
});
</script>

<script>
let filterTimeout = null;

document.getElementById('titleFilter').addEventListener('input', function(e) {
    // Clear any existing timeout
    if (filterTimeout) {
        clearTimeout(filterTimeout);
    }

    // Set a new timeout to prevent too many requests
    filterTimeout = setTimeout(function() {
        // Get current URL parameters
        const urlParams = new URLSearchParams(window.location.search);

        // Update or add the title_search parameter
        if (e.target.value) {
            urlParams.set('title_search', e.target.value);
        } else {
            urlParams.delete('title_search');
        }

        // Reset to first page when filtering
        urlParams.set('page', '1');

        // Navigate to the new URL
        window.location.href = '?' + urlParams.toString();
    }, 300); // 300ms delay
});

function handleSort(field) {
    const urlParams = new URLSearchParams(window.location.search);
    const currentSort = urlParams.get('sort');
    const currentDirection = urlParams.get('direction');

    urlParams.set('sort', field);
    if (field === currentSort) {
        urlParams.set('direction', currentDirection === 'asc' ? 'desc' : 'asc');
    } else {
        urlParams.set('direction', 'asc');
    }

    window.location.href = '?' + urlParams.toString();
}
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const alphaButtons = document.querySelectorAll('.alpha-button');
    const clearButton = document.querySelector('.alpha-clear');

    // Get current URL parameters
    const urlParams = new URLSearchParams(window.location.search);

    // Handle letter button clicks
    alphaButtons.forEach(button => {
        button.addEventListener('click', function() {
            const letter = this.dataset.letter;
            let activeLetters = urlParams.get('alpha') ?
                urlParams.get('alpha').split(',') : [];

            if (this.classList.contains('active')) {
                // Remove letter if already active
                activeLetters = activeLetters.filter(l => l !== letter);
            } else {
                // Add letter if not active
                activeLetters.push(letter);
            }

            // Update URL
            if (activeLetters.length > 0) {
                urlParams.set('alpha', activeLetters.join(','));
            } else {
                urlParams.delete('alpha');
            }

            // Reset to first page
            urlParams.set('page', '1');

            // Navigate to new URL
            window.location.href = '?' + urlParams.toString();
        });
    });

    // Handle clear button click
    if (clearButton) {
        clearButton.addEventListener('click', function() {
            urlParams.delete('alpha');
            urlParams.set('page', '1');
            window.location.href = '?' + urlParams.toString();
        });
    }
});
</script>

<?php renderPagination($pagination, $current_params); ?>
