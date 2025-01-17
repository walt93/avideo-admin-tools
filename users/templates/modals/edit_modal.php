<!-- modals/edit_modal.php -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-light">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Edit Video</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editForm">
                    <input type="hidden" id="videoId">
                    <input type="hidden" id="videoFilename">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control bg-dark text-light border-secondary" id="videoTitle" maxlength="100">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control bg-dark text-light border-secondary" id="videoDescription" rows="12" style="font-family: monospace;"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-warning" onclick="sanitizeDescription()">Sanitize</button>

                <div class="dropdown d-inline-block">
                    <button class="btn btn-outline-success dropdown-toggle" type="button" id="aiOptionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        AI Description 🤖
                    </button>
                    <ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="aiOptionsDropdown">
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
                        <li>
                            <button class="dropdown-item" type="button" onclick="generateSpeakerStyle()">
                                <div class="d-flex flex-column">
                                    <span>Lecture/Presentation</span>
                                    <small class="text-muted">Generate description for a known speaker</small>
                                </div>
                            </button>
                        </li>
                    </ul>
                </div>

                <button type="button" class="btn btn-outline-primary" onclick="saveVideo()">Save</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Dark theme modal styles */
.modal-content {
    box-shadow: 0 0 20px rgba(0,0,0,0.5);
}

.dropdown-menu-dark {
    background-color: #343a40;
    border-color: #454d55;
}

.dropdown-item {
    color: #fff;
}

.dropdown-item:hover {
    background-color: #454d55;
}

.dropdown-item small {
    color: #adb5bd !important;
}

.form-control:focus {
    background-color: #454d55;
    border-color: #545b62;
    color: #fff;
}

/* Loading overlay dark theme */
.loading-overlay {
    background-color: rgba(0,0,0,0.8);
    color: #fff;
}

.spinner {
    border-color: #fff;
    border-right-color: transparent;
}
</style>
