<!-- modals/edit_modal.php -->
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

                <!-- Replace single AI button with dropdown -->
                <div class="dropdown d-inline-block">
                    <button class="btn btn-success dropdown-toggle" type="button" id="aiOptionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        AI Description 🤖
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="aiOptionsDropdown">
                        <li>
                            <button class="dropdown-item" type="button" id="transcriptOption" onclick="generateFromTranscript()">
                                <div class="d-flex flex-column">
                                    <span>Generate from Transcript</span>
                                    <small class="text-muted">Uses video transcript to create description</small>
                                </div>
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item" type="button" onclick="rewriteExisting()">
                                <div class="d-flex flex-column">
                                    <span>Rewrite Current</span>
                                    <small class="text-muted">Rewrites existing description in channel voice</small>
                                </div>
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item" type="button" onclick="generateEventStyle()">
                                <div class="d-flex flex-column">
                                    <span>Event Style</span>
                                    <small class="text-muted">Focuses on dates, locations, and event details</small>
                                </div>
                            </button>
                        </li>
                    </ul>
                </div>
                <!-- Video Player Modal -->
                <div class="modal fade" id="videoPlayerModal" tabindex="-1">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content bg-dark">
                            <div class="modal-header border-secondary">
                                <h5 class="modal-title text-light" id="videoPlayerTitle"></h5>
                                <div class="ms-auto d-flex align-items-center">
                                    <select class="form-select form-select-sm me-2" id="videoQualitySelect" style="width: auto;">
                                    </select>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                            </div>
                            <div class="modal-body p-0">
                                <video id="videoPlayer" class="w-100" controls>
                                    Your browser doesn't support HTML5 video.
                                </video>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn btn-primary" onclick="saveVideo()">Save</button>
            </div>
        </div>
    </div>
</div>
