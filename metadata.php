<?php
// Start with bare error handling before anything else
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to display errors in HTML
function displayError($message) {
    echo "<!DOCTYPE html><html><body><h1>Error</h1><pre>$message</pre></body></html>";
    exit;
}

// Basic variables we'll need throughout
$db = null;

// Connect to database - if this fails, we want to know immediately
try {
    $dbPassword = getenv('AVIDEO_DATABASE_PW');
    if ($dbPassword === false) {
        displayError('Database password environment variable AVIDEO_DATABASE_PW not set');
    }
    $dbName = getenv('AVIDEO_DATABASE_NAME');
    if ($dbName === false) {
        displayError('Database password environment variable AVIDEO_DATABASE_NAME not set');
    }
    $dbUser = getenv('AVIDEO_DATABASE_USER');
    if ($dbUser === false) {
        displayError('Database password environment variable AVIDEO_DATABASE_USER not set');
    }
    $dbHost = getenv('AVIDEO_DATABASE_HOST');
    if ($dbHost === false) {
        displayError('Database password environment variable AVIDEO_DATABASE_HOST not set');
    }

    $db = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPassword);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    displayError("Database connection failed: " . $e->getMessage());
}

// Test database connection with a simple query
try {
    $stmt = $db->query('SELECT 1');
} catch (PDOException $e) {
    displayError("Database test query failed: " . $e->getMessage());
}

// Handle AJAX write description request
// Add this to your PHP handlers section
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'generate_description') {
        error_log("DEBUG: Hit generate_description endpoint with POST data: " . print_r($_POST, true));
        header('Content-Type: application/json');
      
        // Debug logging
        error_log("Generate description request received for filename: " . $_POST['filename']);

        $filename = $_POST['filename'];
        $transcript_path = "/var/www/html/conspyre.tv/videos/{$filename}/{$filename}.txt";

        if (!file_exists($transcript_path)) {
           error_log("Transcript file not found: " . $transcript_path);
            echo json_encode(['success' => false, 'error' => 'Transcript file not found']);
            exit;
        }

        error_log("Reading transcript from: " . $transcript_path);
        $transcript = file_get_contents($transcript_path);
        if ($transcript === false) {
            error_log("Failed to read transcript file");
            echo json_encode(['success' => false, 'error' => 'Failed to read transcript file']);
            exit;
        }
        $word_count = str_word_count($transcript);

        // Sample strategy: Take first ~500 words and last ~200 words
        // This gives context but doesn't overwhelm
        $words = str_word_count($transcript, 1); // get array of words
        $start_sample = implode(' ', array_slice($words, 0, 500));
        $end_sample = implode(' ', array_slice($words, -200));
        $content_sample = $start_sample . "\n[...]\n" . $end_sample;

$prompt = <<<EOT
You are writing for Truth Tide TV, a channel dedicated to exposing suppressed truths and shining light on issues the mainstream media misrepresents or ignores.

Context: When covering medical topics, we emphasize:
- The dangers of experimental genetic technologies
- Corporate/government collusion in medical mandates
- Defense of bodily autonomy and informed consent
- Exposing corruption in regulatory bodies
- Real-world harm from medical experimentation

Write a single compelling 250-character max description (including spaces) that:
1. Warns viewers about the real implications
2. Uses strong, direct language that acknowledges the gravity of the situation
3. Connects to broader patterns of medical overreach
4. Avoids neutral/promotional framing of dangerous developments

Below is a sample from the content - write a description that aligns with our editorial stance:

{$content_sample}

Return ONLY the description, no explanations. Must be under 250 characters.
EOT;

        $data = [
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are Truth Tide TV\'s lead editor. You write hard-hitting descriptions that expose concerning developments and wake people up to hidden truths.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.3,
            'max_tokens' => 100  // 250 chars is roughly 50-75 tokens, add some buffer
        ];
  
        $openaiKey = getenv('OPENAI_API_KEY');
        if (!$openaiKey) {
            echo json_encode(['success' => false, 'error' => 'OpenAI API key not configured']);
            exit;
        }

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $openaiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($data)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            echo json_encode(['success' => false, 'error' => 'OpenAI API request failed']);
            exit;
        }

        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            echo json_encode(['success' => true, 'description' => $result['choices'][0]['message']['content']]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid response from OpenAI API']);
        }
        exit;
    }
    // Handle AJAX sanitize request
    if ($_POST['action'] === 'sanitize') {
        header('Content-Type: application/json');

        $openaiKey = getenv('OPENAI_API_KEY');
        if (!$openaiKey) {
            echo json_encode(['success' => false, 'error' => 'OpenAI API key not configured']);
            exit;
        }

        $description = $_POST['description'] ?? '';

$prompt = <<<EOT
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
{$description}

Return ONLY the formatted text with no explanations.
EOT;

        $data = [
            'model' => 'gpt-4-0125-preview',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful assistant that sanitizes and formats video descriptions.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.3
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $openaiKey,
                'Content-Type: application/json'
            ],
            CURLOPT_POSTFIELDS => json_encode($data)
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            echo json_encode(['success' => false, 'error' => 'OpenAI API request failed']);
            exit;
        }

        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            echo json_encode(['success' => true, 'sanitized' => $result['choices'][0]['message']['content']]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid response from OpenAI API']);
        }
        exit;
    }
}

// Helper function to check for subtitle and transcript files
function checkMediaFiles($filename) {
    $basePath = '/var/www/html/conspyre.tv/videos/' . $filename . '/' . $filename;
    return [
        'has_vtt' => file_exists($basePath . '.vtt'),
        'has_txt' => file_exists($basePath . '.txt')
    ];
}

// Helper function to build category tree
function buildCategoryTree($parentId = 0) {
    global $db;
    try {
        $stmt = $db->prepare('SELECT id, name FROM categories WHERE parentId = ? ORDER BY name ASC');
        $stmt->execute([$parentId]);
        $categories = $stmt->fetchAll();

        $result = [];
        foreach ($categories as $category) {
            $children = buildCategoryTree($category['id']);
            $result[] = [
                'id' => $category['id'],
                'name' => $category['name'],
                'children' => $children
            ];
        }
        return $result;
    } catch (PDOException $e) {
        displayError("Category tree query failed: " . $e->getMessage());
    }
}

// Start capturing output in case of errors later
ob_start();

try {
    // Handle AJAX requests for subcategories
    if (isset($_GET['ajax'])) {
        header('Content-Type: application/json');

        if ($_GET['ajax'] === 'subcategories' && isset($_GET['parent'])) {
            $stmt = $db->prepare('SELECT id, name FROM categories WHERE parentId = ? ORDER BY name ASC');
            $stmt->execute([intval($_GET['parent'])]);
            echo json_encode($stmt->fetchAll());
            exit;
        }

        if ($_GET['ajax'] === 'category_path' && isset($_GET['id'])) {
            $path = [];
            $categoryId = intval($_GET['id']);

            // Traverse up the category tree
            while ($categoryId > 0) {
                $stmt = $db->prepare('SELECT id, name, parentId FROM categories WHERE id = ?');
                $stmt->execute([$categoryId]);
                $category = $stmt->fetch();

                if ($category) {
                    array_unshift($path, [
                        'id' => $category['id'],
                        'name' => $category['name']
                    ]);
                    $categoryId = $category['parentId'];
                } else {
                    break;
                }
            }

            echo json_encode($path);
            exit;
        }
    }

    // Get top-level categories for initial dropdown
    $stmt = $db->prepare('SELECT id, name FROM categories WHERE parentId = 0 ORDER BY name ASC');
    $stmt->execute();
    $topLevelCategories = $stmt->fetchAll();

    // Get playlists, sorted alphabetically
    $playlistStmt = $db->prepare('SELECT id, name FROM playlists WHERE users_id = 1 ORDER BY name ASC');
    $playlistStmt->execute();
    $playlists = $playlistStmt->fetchAll();

    // Handle AJAX update
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    error_log("DEBUG: Hit update endpoint with POST data: " . print_r($_POST, true));
      try {
          $stmt = $db->prepare('UPDATE videos SET title = ?, description = ? WHERE id = ?');
          $stmt->execute([$_POST['title'], $_POST['description'], $_POST['id']]);
          header('Content-Type: application/json');
          echo json_encode(['success' => true]);
          exit;
      } catch (PDOException $e) {
          error_log("DEBUG: Database error during update: " . $e->getMessage());
          header('Content-Type: application/json');
          echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
          exit;
      }
    }

    // Pagination
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $perPage = 25;
    $offset = ($page - 1) * $perPage;

    // Build query based on filters
    $whereConditions = ["type = 'video'"];
    $params = [];

    if (!empty($_GET['category'])) {
        $whereConditions[] = "categories_id = ?";
        $params[] = $_GET['category'];
    }

    if (!empty($_GET['playlist'])) {
        $whereConditions[] = "id IN (SELECT videos_id FROM playlists_has_videos WHERE playlists_id = ?)";
        $params[] = $_GET['playlist'];
    }

    $whereClause = implode(' AND ', $whereConditions);

    // Get total count for pagination
    $countStmt = $db->prepare("SELECT COUNT(*) FROM videos WHERE $whereClause");
    $countStmt->execute($params);
    $totalVideos = $countStmt->fetchColumn();
    $totalPages = ceil($totalVideos / $perPage);

    // Get videos
    $query = "SELECT id, title, description, created, filename FROM videos
              WHERE $whereClause
              ORDER BY created DESC
              LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $videos = $stmt->fetchAll();
    // Add file presence info to each video
    foreach ($videos as &$video) {
        $video['media_files'] = checkMediaFiles($video['filename']);
    }


} catch (Exception $e) {
    ob_end_clean();
    displayError("Application error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Video CMS Editor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Add to your existing style section */
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 100;
        }
        
        .cell-content {
            position: relative; /* For the loading overlay positioning */
        }
        
        .description-loading {
            color: #666;
            font-style: italic;
        }
        
        /* Spinner animation */
        .spinner {
            width: 24px;
            height: 24px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Disabled state for the entire row */
        tr.processing {
            opacity: 0.7;
            pointer-events: none;
        }
        
        .file-icon {
            margin-left: 4px;
            cursor: help;
            opacity: 0.7;
        }
        .table-responsive {
            overflow-x: auto;
            max-width: 100%;
        }
        .table-wrapper {
            min-width: 800px;
            max-width: 100%;
        }
        .table {
            table-layout: fixed;
            width: 100%;
        }
        .col-id { width: 70px; }
        .col-created { width: 120px; }
        .col-actions { width: 80px; }
        .col-title, .col-description {
            min-width: 200px;
        }
        .video-title {
            font-weight: 500;
            margin-bottom: 4px;
        }
        .video-description {
          color: #666;
          font-size: 0.9em;
          padding: 8px 0;
          white-space: pre-wrap;  /* This preserves line breaks */
        }
        .cell-content {
            padding: 8px 0;
        }
        .category-dropdowns {
            display: flex;
            gap: 1rem;
        }
        .category-dropdowns select {
            flex: 1;
        }
        .btn-group {
            display: flex;
            gap: 4px;
        }
        .col-actions {
            width: 140px;  /* Increased to accommodate both buttons */
.pagination {
    flex-wrap: wrap;
    gap: 2px;
}
.pagination .page-link {
    min-width: 40px;
    text-align: center;
}
.col-actions span {
    margin-right: 4px;
    cursor: help;
}
.col-actions {
    width: 160px;  /* Increased to accommodate icons */
}
    </style>
</head>
<body>
    <div class="container-fluid p-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <label class="form-label">Filter by Category</label>
                <div class="category-dropdowns">
                    <select class="form-select" id="categoryLevel1" onchange="loadSubcategories(1, this.value)">
                        <option value="">Select Category...</option>
                        <?php foreach ($topLevelCategories as $category): ?>
                            <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="form-select" id="categoryLevel2" disabled onchange="loadSubcategories(2, this.value)">
                        <option value="">Select Subcategory...</option>
                    </select>
                    <select class="form-select" id="categoryLevel3" disabled onchange="loadSubcategories(3, this.value)">
                        <option value="">Select Subcategory...</option>
                    </select>
                </div>
            </div>
            <div class="col-md-4">
                <label class="form-label">Filter by Playlist</label>
                <select class="form-select" id="playlistFilter" onchange="window.location.href = this.value ? `?playlist=${this.value}` : '?'">
                    <option value="">All Playlists</option>
                    <?php foreach ($playlists as $playlist): ?>
                        <option value="<?= $playlist['id'] ?>" <?= isset($_GET['playlist']) && $_GET['playlist'] == $playlist['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($playlist['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th class="col-id">ID</th>
                    <th class="col-created">Created</th>
                    <th class="col-title">Content</th>
                    <th class="col-actions">Files / Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($videos as $video): ?>
                <tr>
                    <td class="col-id"><?= $video['id'] ?></td>
                    <td class="col-created"><?= date('M j, Y', strtotime($video['created'])) ?></td>
                    <td class="col-title">
                        <div class="cell-content">
                          <div class="video-title">
                              <span class="pure-title"><?= htmlspecialchars($video['title']) ?></span>
                              <?php 
                                  if ($video['media_files']['has_vtt']) {
                                      echo '<span title="Has Subtitles" class="file-icon">📝</span>';
                                  }
                                  if ($video['media_files']['has_txt']) {
                                      echo '<span title="Has Transcript" class="file-icon">📄</span>';
                                  }
                              ?>    
                          </div>
                          <div class="video-description"><?= htmlspecialchars($video['description']) ?></div>
                        </div>
                    </td>
                    <td class="col-actions">
                        <button class="btn btn-sm btn-primary"
                                onclick="editVideo(<?= htmlspecialchars(json_encode($video)) ?>)">
                            Edit
                        </button>
                        <button class="btn btn-sm btn-warning"
                                onclick="quickSanitize(<?= $video['id'] ?>, this)">
                            Sanitize
                        </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <!-- Pagination -->
<nav class="d-flex justify-content-center mt-4">
    <ul class="pagination">
        <?php
        // Calculate range and display logic
        $range = 5; // Show 5 pages before and after current page
        $start = max(1, $page - $range);
        $end = min($totalPages, $page + $range);

        // Build the current URL without page parameter
        $urlParams = $_GET;
        unset($urlParams['page']);
        $baseUrl = '?' . http_build_query($urlParams);
        if (!empty($urlParams)) {
            $baseUrl .= '&';
        }

        // First page and previous chunk
        if ($page > 1) {
            echo "<li class='page-item'><a class='page-link' href='{$baseUrl}page=1'>«</a></li>";
            if ($page - 10 > 0) {
                echo "<li class='page-item'><a class='page-link' href='{$baseUrl}page=" . ($page - 10) . "'>-10</a></li>";
            }
        }

        // Previous page
        if ($page > 1) {
            echo "<li class='page-item'><a class='page-link' href='{$baseUrl}page=" . ($page - 1) . "'>‹</a></li>";
        }

        // Page numbers
        for ($i = $start; $i <= $end; $i++) {
            $active = $i === $page ? ' active' : '';
            echo "<li class='page-item{$active}'><a class='page-link' href='{$baseUrl}page={$i}'>{$i}</a></li>";
        }

        // Next page
        if ($page < $totalPages) {
            echo "<li class='page-item'><a class='page-link' href='{$baseUrl}page=" . ($page + 1) . "'>›</a></li>";
        }

        // Next chunk and last page
        if ($page < $totalPages) {
            if ($page + 10 <= $totalPages) {
                echo "<li class='page-item'><a class='page-link' href='{$baseUrl}page=" . ($page + 10) . "'>+10</a></li>";
            }
            echo "<li class='page-item'><a class='page-link' href='{$baseUrl}page={$totalPages}'>»</a></li>";
        }
        ?>
    </ul>
</nav>
    </div>

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Video</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm">
                        <input type="hidden" id="videoId">
                        <input type="hidden" id="videoFilename">
                        <div class="mb-3">
                            <label class="form-label">Title</label>
                            <input type="text" class="form-control" id="videoTitle" maxlength="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" id="videoDescription" rows="12" style="font-family: monospace;"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" onclick="sanitizeDescription()">Sanitize</button>
                    <button type="button" id="generateBtn" class="btn btn-success" onclick="generateDescription()">AI Description 🤖</button>
                    <button type="button" class="btn btn-primary" onclick="saveVideo()">Save</button>
                </div>
            </div>
        </div>
    </div>
    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let editModal;

        document.addEventListener('DOMContentLoaded', function() {
            editModal = new bootstrap.Modal(document.getElementById('editModal'));

            // Handle ESC key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape' && editModal._isShown) {
                    editModal.hide();
                }
            });

            // If there's a category filter active, load the appropriate dropdowns
            const urlParams = new URLSearchParams(window.location.search);
            const categoryId = urlParams.get('category');
            if (categoryId) {
                loadActiveCategoryPath(categoryId);
            }
        });

        async function loadActiveCategoryPath(categoryId) {
            try {
                const response = await fetch(`?ajax=category_path&id=${categoryId}`);
                const path = await response.json();

                if (path.length > 0) {
                    // Load each level sequentially
                    for (let i = 0; i < path.length; i++) {
                        const select = document.getElementById(`categoryLevel${i + 1}`);
                        select.value = path[i].id;
                        if (i < path.length - 1) {
                            await loadSubcategories(i + 1, path[i].id);
                        }
                    }
                }
            } catch (error) {
                console.error('Error loading category path:', error);
            }
        }


        async function loadSubcategories(level, parentId) {
            if (!parentId) {
                // If no parent selected, disable and clear lower levels
                for (let i = level + 1; i <= 3; i++) {
                    const select = document.getElementById(`categoryLevel${i}`);
                    select.innerHTML = '<option value="">Select Subcategory...</option>';
                    select.disabled = true;
                }
                // Update URL to remove category filter
                if (level === 1) {
                    window.location.href = '?';
                }
                return;
            }

            try {
                const response = await fetch(`?ajax=subcategories&parent=${parentId}`);
                const categories = await response.json();

                // Populate the next level
                const nextLevel = level + 1;
                if (nextLevel <= 3) {
                    const select = document.getElementById(`categoryLevel${nextLevel}`);
                    select.innerHTML = '<option value="">Select Subcategory...</option>';
                    categories.forEach(cat => {
                        select.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
                    });
                    select.disabled = false;

                    // Disable and clear any levels below
                    for (let i = nextLevel + 1; i <= 3; i++) {
                        const select = document.getElementById(`categoryLevel${i}`);
                        select.innerHTML = '<option value="">Select Subcategory...</option>';
                        select.disabled = true;
                    }
                }

                // If this is the final selection or there are no subcategories, update the filter
                if (nextLevel > 3 || categories.length === 0) {
                    updateCategoryFilter(parentId);
                }
            } catch (error) {
                console.error('Error loading subcategories:', error);
            }
        }

        function updateCategoryFilter(categoryId) {
            // Update URL with selected category
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.delete('playlist'); // Clear playlist filter when changing category
            urlParams.set('category', categoryId);
            window.location.href = `?${urlParams.toString()}`;
        }

        function editVideo(video) {
            document.getElementById('videoId').value = video.id;
            document.getElementById('videoTitle').value = video.title; // Use the direct title from video object
            document.getElementById('videoDescription').value = video.description;
            document.getElementById('videoFilename').value = video.filename;
            
            // Enable/disable AI Description button based on transcript availability
            const generateBtn = document.getElementById('generateBtn');
            generateBtn.disabled = !video.media_files.has_txt;
            if (!video.media_files.has_txt) {
                generateBtn.title = 'No transcript available';
            } else {
                generateBtn.title = 'Generate AI description from transcript';
            }
            
            editModal.show();
        }

        function saveVideo() {
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('id', document.getElementById('videoId').value);
            formData.append('title', document.getElementById('videoTitle').value);
            formData.append('description', document.getElementById('videoDescription').value);

            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    editModal.hide();
                    window.location.reload();
                } else if (data.error) {
                    alert('Error saving: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error saving:', error);
                alert('Error saving video. Please try again.');
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

        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error);
        }

        // Update loading message
        loadingOverlay.querySelector('.description-loading').textContent = 'Saving changes...';

        // Save the sanitized description
        const saveFormData = new FormData();
        saveFormData.append('action', 'update');
        saveFormData.append('id', videoId);
        saveFormData.append('title', row.querySelector('.video-title').textContent);
        saveFormData.append('description', data.sanitized);

        const saveResponse = await fetch(window.location.href, {
            method: 'POST',
            body: saveFormData
        });

        const saveData = await saveResponse.json();
        console.log("Save response:", saveData);  // Add this line
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

    // Disable buttons and add loading overlay
    sanitizeButton.disabled = true;
    saveButton.disabled = true;
    description.disabled = true;

    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'loading-overlay';
    loadingOverlay.innerHTML = `
        <div>
            <div class="spinner mb-2"></div>
            <div class="description-loading">Sanitizing description...</div>
        </div>
    `;
    modalBody.appendChild(loadingOverlay);

    try {
        const formData = new FormData();
        formData.append('action', 'sanitize');
        formData.append('description', description.value);

        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error);
        }

        // Update the description field with the sanitized content
        description.value = data.sanitized;

    } catch (error) {
        console.error('Error sanitizing description:', error);
        alert('Error sanitizing description: ' + error.message);
    } finally {
        // Remove loading overlay
        loadingOverlay.remove();
        // Re-enable all controls
        sanitizeButton.disabled = false;
        saveButton.disabled = false;
        description.disabled = false;
    }
}

async function generateDescription() {
    const modalBody = document.querySelector('.modal-body');
    const description = document.getElementById('videoDescription');
    const filename = document.getElementById('videoFilename').value;
    const buttons = document.querySelectorAll('.modal-footer button');
    
    // Disable all buttons and add loading overlay
    buttons.forEach(btn => btn.disabled = true);
    description.disabled = true;

    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'loading-overlay';
    loadingOverlay.innerHTML = `
        <div>
            <div class="spinner mb-2"></div>
            <div class="description-loading">Writing news-style description...</div>
        </div>
    `;
    modalBody.appendChild(loadingOverlay);

    try {
        const formData = new FormData();
        formData.append('action', 'generate_description');
        formData.append('filename', filename);

        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Failed to generate description');
        }

        // Update the description field with the generated content
        description.value = data.description;

    } catch (error) {
        console.error('Error generating description:', error);
        alert('Error: ' + error.message);
    } finally {
        // Remove loading overlay
        loadingOverlay.remove();
        // Re-enable all controls
        buttons.forEach(btn => btn.disabled = false);
        description.disabled = false;
    }
}
    </script>
</body>
</html>
<?php
// If we got this far, flush the output buffer
ob_end_flush();
?>
