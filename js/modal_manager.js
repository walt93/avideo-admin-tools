// js/modal_manager.js

class ModalManager {
    constructor() {
        console.log('ModalManager constructor running...');

        // Debug: Log modal elements as we find them
        const modalElement = document.getElementById('editModal');
        const playerModalElement = document.getElementById('videoPlayerModal');
        const subtitleModalElement = document.getElementById('subtitleModal');
        const transcriptModalElement = document.getElementById('transcriptModal');

        console.log('Found modal elements:', {
            editModal: modalElement,
            playerModal: playerModalElement,
            subtitleModal: subtitleModalElement,
            transcriptModal: transcriptModalElement
        });

        // Initialize Bootstrap modals
        this.editModal = modalElement ? new bootstrap.Modal(modalElement) : null;
        this.playerModal = playerModalElement ? new bootstrap.Modal(playerModalElement) : null;
        this.subtitleModal = subtitleModalElement ? new bootstrap.Modal(subtitleModalElement) : null;
        this.transcriptModal = transcriptModalElement ? new bootstrap.Modal(transcriptModalElement) : null;

        this.setupEventListeners();
        this.setupVideoPlayerEvents();
    }

    setupEventListeners() {
        // Restore original click handlers for buttons
        document.addEventListener('click', (event) => {
            const target = event.target;
            console.log('Click detected on:', target);

            // Edit button handler
            if (target.matches('[data-action="edit"]') || target.closest('[data-action="edit"]')) {
                console.log('Edit button clicked');
                const videoData = JSON.parse(target.closest('[data-action="edit"]').dataset.video);
                this.showEditModal(videoData);
            }

            // Play button handler
            if (target.matches('[data-action="play"]') || target.closest('[data-action="play"]')) {
                console.log('Play button clicked');
                const videoData = JSON.parse(target.closest('[data-action="play"]').dataset.video);
                this.showVideoPlayer(videoData);
            }

            // Subtitle button handler
            if (target.matches('[data-action="view-subtitles"]')) {
                console.log('Subtitle button clicked');
                const filename = target.dataset.filename;
                this.showSubtitles(filename);
            }

            // Transcript button handler
            if (target.matches('[data-action="view-transcript"]')) {
                console.log('Transcript button clicked');
                const filename = target.dataset.filename;
                this.showTranscript(filename);
            }
        });

        // Handle ESC key
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && this.editModal && this.editModal._isShown) {
                this.editModal.hide();
            }
        });
    }

    setupVideoPlayerEvents() {
        const qualitySelect = document.getElementById('videoQualitySelect');
        const videoPlayer = document.getElementById('videoPlayer');

        if (qualitySelect && videoPlayer) {
            qualitySelect.addEventListener('change', (e) => {
                console.log('Quality changed:', e.target.value);
                const currentTime = videoPlayer.currentTime;
                const wasPlaying = !videoPlayer.paused;
                videoPlayer.src = e.target.value;
                videoPlayer.currentTime = currentTime;
                if (wasPlaying) videoPlayer.play();
            });

            // Clean up video when modal is closed
            const playerModal = document.getElementById('videoPlayerModal');
            if (playerModal) {
                playerModal.addEventListener('hidden.bs.modal', () => {
                    console.log('Video player modal closed');
                    videoPlayer.pause();
                    videoPlayer.src = '';
                });
            }
        }
    }

    async showSubtitles(filename) {
        console.log('Showing subtitles for:', filename);
        try {
            const response = await fetch('?action=get_subtitles&filename=' + encodeURIComponent(filename));
            const data = await response.json();

            if (data.success && this.subtitleModal) {
                document.querySelector('.subtitle-content').textContent = data.content;
                this.subtitleModal.show();
            } else {
                console.error('Error loading subtitles:', data.error);
                alert('Error loading subtitles: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error loading subtitles:', error);
            alert('Error loading subtitles. Please try again.');
        }
    }

    async showTranscript(filename) {
        console.log('Showing transcript for:', filename);
        try {
            const response = await fetch('?action=get_transcript&filename=' + encodeURIComponent(filename));
            const data = await response.json();

            if (data.success && this.transcriptModal) {
                document.querySelector('.transcript-content').textContent = data.content;
                this.transcriptModal.show();
            } else {
                console.error('Error loading transcript:', data.error);
                alert('Error loading transcript: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error loading transcript:', error);
            alert('Error loading transcript. Please try again.');
        }
    }

    showEditModal(video) {
        console.log('Showing edit modal for:', video);
        if (!this.editModal) {
            console.error('Edit modal not initialized');
            return;
        }

        document.getElementById('videoId').value = video.id;
        document.getElementById('videoTitle').value = video.title;
        document.getElementById('videoDescription').value = video.description;
        document.getElementById('videoFilename').value = video.filename;

        // Configure AI options based on available files
        const transcriptOption = document.getElementById('transcriptOption');
        if (transcriptOption) {
            if (!video.media_files.has_txt) {
                transcriptOption.classList.add('disabled');
                transcriptOption.title = 'No transcript available';
            } else {
                transcriptOption.classList.remove('disabled');
                transcriptOption.title = '';
            }
        }

        this.editModal.show();
    }

    showVideoPlayer(videoData) {
        console.log('Showing video player for:', videoData);
        if (!this.playerModal) {
            console.error('Player modal not initialized');
            return;
        }

        const qualitySelect = document.getElementById('videoQualitySelect');
        const videoPlayer = document.getElementById('videoPlayer');
        const playerTitle = document.getElementById('videoPlayerTitle');

        playerTitle.textContent = videoData.title;
        qualitySelect.innerHTML = '';

        // Sort resolutions
        const resOrder = ['1080', '720', '540', '480', '360', '240', 'ext', 'original'];
        const sortedResolutions = Object.entries(videoData.resolutions)
            .sort(([resA], [resB]) => resOrder.indexOf(resA) - resOrder.indexOf(resB));

        sortedResolutions.forEach(([quality, path]) => {
            const option = document.createElement('option');
            option.value = path;
            option.textContent = quality === 'original' ? 'Original' :
                quality === 'ext' ? 'Extended' : `${quality}p`;
            qualitySelect.appendChild(option);
        });

        videoPlayer.src = sortedResolutions[0][1];
        this.playerModal.show();
    }

    async saveVideo() {
        console.log('Saving video...');
        try {
            const formData = new FormData();
            formData.append('action', 'update');
            formData.append('id', document.getElementById('videoId').value);
            formData.append('title', document.getElementById('videoTitle').value);
            formData.append('description', document.getElementById('videoDescription').value);

            const response = await fetch(window.location.href, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.editModal.hide();
                window.location.reload();
            } else {
                console.error('Error saving:', data.error);
                alert('Error saving: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error saving:', error);
            alert('Error saving video. Please try again.');
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - initializing ModalManager...');
    window.modalManager = new ModalManager();
    console.log('ModalManager initialized:', window.modalManager);
});

// Global save function
window.saveVideo = function() {
    console.log('Global saveVideo called');
    if (window.modalManager) {
        window.modalManager.saveVideo();
    } else {
        console.error('Modal manager not initialized');
    }
};