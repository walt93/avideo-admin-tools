<!-- templates/main_content.php -->
<div class="container-fluid p-4">
    <!-- Filters Section -->
    <div class="row mb-4">
        <div class="col-md-6">
            <label class="form-label">Filter by Playlist</label>
            <select class="form-select dark-select" id="playlistFilter" onchange="window.location.href = this.value ? `?playlist=${this.value}` : '?'">
                <option value="">All Videos</option>
                <?php foreach ($playlists as $playlist): ?>
                    <option value="<?= $playlist['id'] ?>" <?= isset($_GET['playlist']) && $_GET['playlist'] == $playlist['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($playlist['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Videos Table -->
    <div class="table-responsive">
        <table class="table table-striped table-dark">
            <thead>
                <tr>
                    <th class="col-thumbnail">Thumbnail</th>
                    <th class="col-id">ID</th>
                    <th class="col-created">Created</th>
                    <th class="col-title">Content</th>
                    <th class="col-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($videos['videos'] as $video): ?>
                <?php
                    $mediaFiles = checkMediaFiles($video['filename']);
                    $resolutions = getVideoResolutions($video['filename']);
                    $thumbnailPath = "/var/www/html/conspyre.tv/videos/{$video['filename']}/{$video['filename']}.jpg";
                    $thumbnailUrl = file_exists($thumbnailPath) 
                        ? rtrim(getenv('VIDEO_CDN_BASE_URL'), '/') . "/{$video['filename']}/{$video['filename']}.jpg"
                        : 'path/to/default/thumbnail.jpg'; // Replace with your default thumbnail path
                ?>
                <tr>
                    <td class="col-thumbnail">
                        <img src="<?= htmlspecialchars($thumbnailUrl) ?>" alt="Thumbnail" class="video-thumbnail">
                    </td>
                    <td class="col-id"><?= $video['id'] ?></td>
                    <td class="col-created"><?= date('M j, Y', strtotime($video['created'])) ?></td>
                    <td class="col-title">
                        <div class="cell-content">
                            <div class="video-title">
                                <span class="pure-title"><?= htmlspecialchars($video['title']) ?></span>
                                <?php if ($mediaFiles['has_vtt']): ?>
                                    <span title="View Subtitles" class="file-icon" data-action="view-subtitles"
                                          data-filename="<?= htmlspecialchars($video['filename']) ?>">📝</span>
                                <?php endif; ?>

                                <?php if ($mediaFiles['has_txt']): ?>
                                    <span title="View Transcript" class="file-icon" data-action="view-transcript"
                                          data-filename="<?= htmlspecialchars($video['filename']) ?>">📄</span>
                                <?php endif; ?>
                            </div>
                            <div class="video-description">
                                <?= htmlspecialchars($video['description']) ?>
                            </div>
                        </div>
                    </td>
                    <td class="col-actions">
                        <button class="btn btn-sm btn-outline-primary" data-action="edit"
                                data-video='<?= htmlspecialchars(json_encode(array_merge($video, ['media_files' => $mediaFiles])), ENT_QUOTES) ?>'>
                            Edit
                        </button>

                        <button class="btn btn-sm btn-outline-warning"
                            onclick="quickSanitize(<?= $video['id'] ?>, this)">
                            Sanitize
                        </button>

                        <?php if (!empty($resolutions)): ?>
                        <button class="btn btn-sm btn-outline-success" data-action="play"
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
    </div>

    <!-- Pagination -->
    <nav class="d-flex justify-content-center mt-4">
        <ul class="pagination pagination-dark">
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

            if ($page > 1): ?>
                <li class='page-item'><a class='page-link bg-dark text-light' href='<?= $baseUrl ?>page=1'>«</a></li>
                <?php if ($page - 10 > 0): ?>
                    <li class='page-item'><a class='page-link bg-dark text-light' href='<?= $baseUrl ?>page=<?= $page - 10 ?>'>-10</a></li>
                <?php endif; ?>
            <?php endif;

            if ($page > 1): ?>
                <li class='page-item'><a class='page-link bg-dark text-light' href='<?= $baseUrl ?>page=<?= $page - 1 ?>'>‹</a></li>
            <?php endif;

            for ($i = $start; $i <= $end; $i++): ?>
                <li class='page-item<?= $i === $page ? ' active' : '' ?>'>
                    <a class='page-link bg-dark text-light' href='<?= $baseUrl ?>page=<?= $i ?>'><?= $i ?></a>
                </li>
            <?php endfor;

            if ($page < $videos['pages']): ?>
                <li class='page-item'><a class='page-link bg-dark text-light' href='<?= $baseUrl ?>page=<?= $page + 1 ?>'>›</a></li>
                <?php if ($page + 10 <= $videos['pages']): ?>
                    <li class='page-item'><a class='page-link bg-dark text-light' href='<?= $baseUrl ?>page=<?= $page + 10 ?>'>+10</a></li>
                <?php endif; ?>
                <li class='page-item'><a class='page-link bg-dark text-light' href='<?= $baseUrl ?>page=<?= $videos['pages'] ?>'>»</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</div>

<style>

.col-thumbnail {
    width: 67px; /* 120px height * (9/16) aspect ratio */
}

.video-thumbnail {
    width: 100%;
    height: 120px;
    object-fit: cover;
    border-radius: 4px;
}

/* Ensure the table remains responsive */
@media (max-width: 768px) {
    .col-thumbnail {
        display: none;
    }
}
/* Dark theme specific styles */
.pagination-dark .page-link {
    background-color: #343a40;
    border-color: #454d55;
}

.pagination-dark .page-item.active .page-link {
    background-color: #007bff;
    border-color: #007bff;
}

.dark-select {
    background-color: #343a40;
    color: #fff;
    border-color: #454d55;
}

.table-dark {
    background-color: #343a40;
}

.table-dark td, .table-dark th {
    border-color: #454d55;
}

.btn-outline-primary, .btn-outline-warning, .btn-outline-success {
    color: #fff;
}

.cell-content {
    background-color: #2b3035;
    padding: 10px;
    border-radius: 4px;
}

.video-title {
    font-weight: bold;
    margin-bottom: 5px;
}

.video-description {
    color: #ced4da;
    font-size: 0.9em;
}

.file-icon {
    cursor: pointer;
    margin-left: 5px;
}
</style>
