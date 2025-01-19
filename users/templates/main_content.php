<!-- templates/main_content.php -->
<div class="container-fluid p-4">
    <!-- Header with count -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 text-light">
            <?= isset($videos['current_filter']) ? htmlspecialchars($videos['current_filter']) : 'All Videos' ?>
            <span class="ms-2 badge bg-secondary">
                <?= number_format($videos['total']) ?> video<?= $videos['total'] !== 1 ? 's' : '' ?>
            </span>
        </h4>

        <!-- Playlist filter -->
        <?php if (!empty($playlists)): ?>
        <div style="width: 300px;">
            <select class="form-select dark-select" id="playlistFilter"
                    onchange="window.location.href = this.value ? `?playlist=${this.value}` : '?'">
                <option value="">All Videos</option>
                <?php foreach ($playlists as $playlist): ?>
                    <option value="<?= $playlist['id'] ?>"
                            <?= (isset($_GET['playlist']) && $_GET['playlist'] == $playlist['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string)$playlist['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
    </div>

    <!-- Videos Table -->
    <div class="table-responsive">
        <table class="table table-striped table-dark">
            <thead>
                <tr>
                    <th class="col-id">ID</th>
                    <th class="col-created">Created</th>
                    <th class="col-title">Content</th>
                    <th class="col-actions">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($videos['videos'])): ?>
                    <tr>
                        <td colspan="4" class="text-center py-4">
                            <div class="text-muted">
                                <i class="bi bi-camera-video me-2"></i>
                                No videos found
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($videos['videos'] as $video): ?>
                    <?php
                        $mediaFiles = checkMediaFiles($video['filename']);
                        $resolutions = getVideoResolutions($video['filename']);
                    ?>
                    <tr>
                        <td class="col-id"><?= htmlspecialchars((string)$video['id']) ?></td>
                        <td class="col-created"><?= date('M j, Y', strtotime($video['created'])) ?></td>
                        <td class="col-title">
                            <div class="cell-content">
                                <div class="video-title">
                                    <span class="pure-title"><?= htmlspecialchars((string)$video['title']) ?></span>
                                    <?php if ($mediaFiles['has_vtt']): ?>
                                        <span title="View Subtitles" class="file-icon" data-action="view-subtitles"
                                              data-filename="<?= htmlspecialchars((string)$video['filename']) ?>">📝</span>
                                    <?php endif; ?>

                                    <?php if ($mediaFiles['has_txt']): ?>
                                        <span title="View Transcript" class="file-icon" data-action="view-transcript"
                                              data-filename="<?= htmlspecialchars((string)$video['filename']) ?>">📄</span>
                                    <?php endif; ?>
                                </div>
                                <div class="video-description">
                                    <?= htmlspecialchars((string)$video['description']) ?>
                                </div>
                            </div>
                        </td>
                        <td class="col-actions">
                            <button class="btn btn-sm btn-outline-primary" data-action="edit"
                                    data-video='<?= htmlspecialchars(json_encode(array_merge($video, ['media_files' => $mediaFiles])), ENT_QUOTES) ?>'>
                                Edit
                            </button>

                            <button class="btn btn-sm btn-outline-warning"
                                onclick="quickSanitize(<?= (int)$video['id'] ?>, this)">
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
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($videos['pages'] > 1): ?>
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
            ?>

            <?php if ($page > 1): ?>
                <li class='page-item'><a class='page-link bg-dark text-light' href='<?= $baseUrl ?>page=1'>«</a></li>
                <?php if ($page - 10 > 0): ?>
                    <li class='page-item'><a class='page-link bg-dark text-light' href='<?= $baseUrl ?>page=<?= $page - 10 ?>'>-10</a></li>
                <?php endif; ?>
                <li class='page-item'><a class='page-link bg-dark text-light' href='<?= $baseUrl ?>page=<?= $page - 1 ?>'>‹</a></li>
            <?php endif; ?>

            <?php for ($i = $start; $i <= $end; $i++): ?>
                <li class='page-item<?= $i === $page ? ' active' : '' ?>'>
                    <a class='page-link bg-dark text-light' href='<?= $baseUrl ?>page=<?= $i ?>'><?= $i ?></a>
                </li>
            <?php endfor; ?>

            <?php if ($page < $videos['pages']): ?>
                <li class='page-item'><a class='page-link bg-dark text-light' href='<?= $baseUrl ?>page=<?= $page + 1 ?>'>›</a></li>
                <?php if ($page + 10 <= $videos['pages']): ?>
                    <li class='page-item'><a class='page-link bg-dark text-light' href='<?= $baseUrl ?>page=<?= $page + 10 ?>'>+10</a></li>
                <?php endif; ?>
                <li class='page-item'><a class='page-link bg-dark text-light' href='<?= $baseUrl ?>page=<?= $videos['pages'] ?>'>»</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<style>
.badge {
    font-weight: normal;
    font-size: 0.85em;
}

.table-dark {
    --bs-table-bg: #1a1a1a;
    --bs-table-striped-bg: #252525;
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

.dark-select {
    background-color: #2b3035;
    color: #fff;
    border-color: #454d55;
}

.dark-select:focus {
    background-color: #2b3035;
    color: #fff;
    border-color: #454d55;
    box-shadow: 0 0 0 0.25rem rgba(66, 70, 73, 0.5);
}
</style>