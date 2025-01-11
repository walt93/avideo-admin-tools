// js/modal_manager.js

// Create the class
// Create the class
class ModalManager {
    constructor() {
        console.log('ModalManager constructor running...');
        const modalElement = document.getElementById('editModal');
        const playerModalElement = document.getElementById('videoPlayerModal');
        console.log('Modal elements:', modalElement, playerModalElement);

        this.editModal = new bootstrap.Modal(modalElement);
        this.playerModal = new bootstrap.Modal(playerModalElement);
        this.subtitleModal = new bootstrap.Modal(document.getElementById('subtitleModal'));
        this.transcriptModal = new bootstrap.Modal(document.getElementById('transcriptModal'));

        this.setupEventListeners();
        this.setupVideoPlayerEvents();
    }

    setupEventListeners() {
        // Handle ESC key
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && this.editModal._isShown) {
                this.editModal.hide();
            }
        });
    }

    setupVideoPlayerEvents() {
        const qualitySelect = document.getElementById('videoQualitySelect');
        const videoPlayer = document.getElementById('videoPlayer');

        qualitySelect.addEventListener('change', (e) => {
            const currentTime = videoPlayer.currentTime;
            const wasPlaying = !videoPlayer.paused;
            videoPlayer.src = e.target.value;
            videoPlayer.currentTime = currentTime;
            if (wasPlaying) videoPlayer.play();
        });

        // Clean up video when modal is closed
        document.getElementById('videoPlayerModal').addEventListener('hidden.bs.modal', () => {
            videoPlayer.pause();
            videoPlayer.src = '';
        });
    }

    showEditModal(video) {
        console.log('showEditModal called with:', video);
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
        const qualitySelect = document.getElementById('videoQualitySelect');
        const videoPlayer = document.getElementById('videoPlayer');
        const playerTitle = document.getElementById('videoPlayerTitle');

        // Set title
        playerTitle.textContent = videoData.title;

        // Clear and populate quality options
        qualitySelect.innerHTML = '';

        // Sort resolutions by quality (highest first)
        const resOrder = ['1080', '720', '540', '480', '360', '240', 'ext', 'original'];
        const sortedResolutions = Object.entries(videoData.resolutions)
            .sort(([resA], [resB]) => {
                return resOrder.indexOf(resA) - resOrder.indexOf(resB);
            });

        sortedResolutions.forEach(([quality, path]) => {
            const option = document.createElement('option');
            option.value = path;
            option.textContent = quality === 'original' ? 'Original' :
                quality === 'ext' ? 'Extended' :
                    `${quality}p`;
            qualitySelect.appendChild(option);
        });

        // Set initial video source to highest quality
        videoPlayer.src = sortedResolutions[0][1];

        this.playerModal.show();
    }

    async showSubtitles(filename) {
        try {
            const response = await fetch('?action=get_subtitles&filename=' + encodeURIComponent(filename));
            const data = await response.json();

            if (data.success) {
                document.querySelector('.subtitle-content').textContent = data.content;
                this.subtitleModal.show();
            } else {
                alert('Error loading subtitles: ' + data.error);
            }
        } catch (error) {
            console.error('Error loading subtitles:', error);
            alert('Error loading subtitles. Please try again.');
        }
    }

    async showTranscript(filename) {
        try {
            const response = await fetch('?action=get_transcript&filename=' + encodeURIComponent(filename));
            const data = await response.json();

            if (data.success) {
                document.querySelector('.transcript-content').textContent = data.content;
                this.transcriptModal.show();
            } else {
                alert('Error loading transcript: ' + data.error);
            }
        } catch (error) {
            console.error('Error loading transcript:', error);
            alert('Error loading transcript. Please try again.');
        }
    }


    async saveVideo() {
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
            } else if (data.error) {
                alert('Error saving: ' + data.error);
            }
        } catch (error) {
            console.error('Error saving:', error);
            alert('Error saving video. Please try again.');
        }
    }
}

// Single initialization when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - initializing ModalManager...');
    window.modalManager = new ModalManager();
    console.log('ModalManager initialized:', window.modalManager);
});

// Global save function
window.saveVideo = function() {
    if (window.modalManager) {
        window.modalManager.saveVideo();
    } else {
        console.error('Modal manager not initialized');
    }
};