<!-- templates/main_content.php -->
<div class="container-fluid p-4">
    <!-- Filters Section -->
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

    <!-- Videos Table -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th class="col-thumbnail">Thumbnail</th>
                <th class="col-id">ID</th>
                <th class="col-created">Created</th>
                <th class="col-title">Content</th>
                <th class="col-actions">Files / Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($videos['videos'] as $video): ?>
            <?php
                $mediaFiles = checkMediaFiles($video['filename']);
                $resolutions = getVideoResolutions($video['filename']);
                $thumbnailUrl = "https://conspyre.tv/videos/{$video['filename']}/{$video['filename']}.jpg";
            ?>
            <tr>
                <td class="col-thumbnail">
                    <img src="<?= htmlspecialchars($thumbnailUrl) ?>" 
                         alt="Thumbnail" 
                         class="video-thumbnail" 
                         width="240" 
                         height="135">
                </td>
                <td class="col-id"><?= $video['id'] ?></td>
                <td class="col-created"><?= date('M j, Y', strtotime($video['created'])) ?></td>
                <td class="col-title">
                    <div class="cell-content">
                        <div class="video-title">
                            <span class="pure-title"><?= htmlspecialchars($video['title']) ?></span>
                            <!-- Subtitle icon -->
                            <?php if ($mediaFiles['has_vtt']): ?>
                                <span title="View Subtitles" class="file-icon" data-action="view-subtitles"
                                      data-filename="<?= htmlspecialchars($video['filename']) ?>" style="cursor: pointer;">📝</span>
                            <?php endif; ?>

                            <!-- transcript icon -->
                            <?php if ($mediaFiles['has_txt']): ?>
                                <span title="View Transcript" class="file-icon" data-action="view-transcript"
                                      data-filename="<?= htmlspecialchars($video['filename']) ?>" style="cursor: pointer;">📄</span>
                            <?php endif; ?>
                        </div>
                        <div class="video-description">
                            <?= htmlspecialchars($video['description']) ?>
                        </div>
                    </div>
                <td class="col-actions">
                    <!-- Edit button -->
                    <button class="btn btn-sm btn-primary" data-action="edit"
                            data-video='<?= htmlspecialchars(json_encode(array_merge($video, ['media_files' => $mediaFiles])), ENT_QUOTES) ?>'>
                        Edit
                    </button>

                    <!-- Sanitize button remains the same as it uses a different mechanism -->
                    <button class="btn btn-sm btn-warning"
                        onclick="quickSanitize(<?= $video['id'] ?>, this)">
                        Sanitize
                    </button>

                    <!-- Play button -->
                    <?php if (!empty($resolutions)): ?>
                    <button class="btn btn-sm btn-success" data-action="play"
                            data-video='<?= htmlspecialchars(json_encode([
                                'title' => $video['title'],
                                'filename' => $video['filename'],
                                'resolutions' => $resolutions
                            ]), ENT_QUOTES) ?>'>
                        <i class="bi bi-play-fill"></i> Play
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <nav class="d-flex justify-content-center mt-4">
        <ul class="pagination">
            <?php
            $range = 5;
            $start = max(1, $page - $range);
            $end = min($videos['pages'], $page + $range);

            $urlParams = $_GET;
            unset($urlParams['page']);
            $baseUrl = '?' . http_build_query($urlParams);
            if (!empty($urlParams)) {
                $baseUrl .= '&';
            }

            // First page and previous chunk
            if ($page > 1): ?>
                <li class='page-item'><a class='page-link' href='<?= $baseUrl ?>page=1'>«</a></li>
                <?php if ($page - 10 > 0): ?>
                    <li class='page-item'><a class='page-link' href='<?= $baseUrl ?>page=<?= $page - 10 ?>'>-10</a></li>
                <?php endif; ?>
            <?php endif;

            // Previous page
            if ($page > 1): ?>
                <li class='page-item'><a class='page-link' href='<?= $baseUrl ?>page=<?= $page - 1 ?>'>‹</a></li>
            <?php endif;

            // Page numbers
            for ($i = $start; $i <= $end; $i++): ?>
                <li class='page-item<?= $i === $page ? ' active' : '' ?>'>
                    <a class='page-link' href='<?= $baseUrl ?>page=<?= $i ?>'><?= $i ?></a>
                </li>
            <?php endfor;

            // Next page
            if ($page < $videos['pages']): ?>
                <li class='page-item'><a class='page-link' href='<?= $baseUrl ?>page=<?= $page + 1 ?>'>›</a></li>
            <?php endif;

            // Next chunk and last page
            if ($page < $videos['pages']): ?>
                <?php if ($page + 10 <= $videos['pages']): ?>
                    <li class='page-item'><a class='page-link' href='<?= $baseUrl ?>page=<?= $page + 10 ?>'>+10</a></li>
                <?php endif; ?>
                <li class='page-item'><a class='page-link' href='<?= $baseUrl ?>page=<?= $videos['pages'] ?>'>»</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</div>
