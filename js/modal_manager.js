// js/modal_manager.js
(function(window) {
    'use strict';

    window.ModalManager = class ModalManager {
        constructor() {

            // Initialize all modals
            this.initializeModals();
            this.setupEventListeners();
            this.setupVideoPlayerEvents();
        }

        initializeModals() {
            // Initialize all modals with error handling
            try {
                this.editModal = new bootstrap.Modal(document.getElementById('editModal'));
                this.playerModal = new bootstrap.Modal(document.getElementById('videoPlayerModal'));
                this.subtitleModal = new bootstrap.Modal(document.getElementById('subtitleModal'));
                this.transcriptModal = new bootstrap.Modal(document.getElementById('transcriptModal'));
            } catch (error) {
                console.error('Error initializing modals:', error);
            }
        }

        setupEventListeners() {
            // Global click handler for all modal-related actions
            document.addEventListener('click', (event) => {
                const target = event.target.closest('[data-action]');
                if (!target) return;

                const action = target.dataset.action;

                try {
                    switch (action) {
                        case 'edit':
                            const editData = JSON.parse(target.dataset.video);
                            this.showEditModal(editData);
                            break;

                        case 'play':
                            const playData = JSON.parse(target.dataset.video);
                            this.showVideoPlayer(playData);
                            break;

                        case 'view-subtitles':
                            const subtitlesFilename = target.dataset.filename;
                            this.showSubtitles(subtitlesFilename);
                            break;

                        case 'view-transcript':
                            const transcriptFilename = target.dataset.filename;
                            this.showTranscript(transcriptFilename);
                            break;
                    }
                } catch (error) {
                    console.error('Error handling action:', action, error);
                }
            });
        }

        setupVideoPlayerEvents() {
            const qualitySelect = document.getElementById('videoQualitySelect');
            const videoPlayer = document.getElementById('videoPlayer');

            if (qualitySelect && videoPlayer) {
                qualitySelect.addEventListener('change', (e) => {
                    const currentTime = videoPlayer.currentTime;
                    const wasPlaying = !videoPlayer.paused;
                    videoPlayer.src = e.target.value;
                    videoPlayer.currentTime = currentTime;
                    if (wasPlaying) videoPlayer.play();
                });

                const playerModal = document.getElementById('videoPlayerModal');
                playerModal.addEventListener('hidden.bs.modal', () => {
                    videoPlayer.pause();
                    videoPlayer.src = '';
                });
            }
        }

        showEditModal(video) {
            try {
                const videoId = document.getElementById('videoId');
                const videoTitle = document.getElementById('videoTitle');
                const videoDescription = document.getElementById('videoDescription');
                const videoFilename = document.getElementById('videoFilename');

                if (videoId && videoTitle && videoDescription && videoFilename) {
                    videoId.value = video.id;
                    videoTitle.value = video.title;
                    videoDescription.value = video.description;
                    videoFilename.value = video.filename;
                }

                this.editModal.show();
            } catch (error) {
                console.error('Error showing edit modal:', error);
            }
        }

        async showSubtitles(filename) {
            try {
                const response = await fetch('?action=get_subtitles&filename=' + encodeURIComponent(filename));
                const data = await response.json();

                if (data.success) {
                    const contentElement = document.querySelector('.subtitle-content');
                    if (contentElement) {
                        contentElement.textContent = data.content;
                        this.subtitleModal.show();
                    }
                } else {
                    throw new Error(data.error || 'Failed to load subtitles');
                }
            } catch (error) {
                console.error('Error showing subtitles:', error);
                alert('Error loading subtitles: ' + error.message);
            }
        }

        async showTranscript(filename) {
            try {
                const response = await fetch('?action=get_transcript&filename=' + encodeURIComponent(filename));
                const data = await response.json();

                if (data.success) {
                    const contentElement = document.querySelector('.transcript-content');
                    if (contentElement) {
                        contentElement.textContent = data.content;
                        this.transcriptModal.show();
                    }
                } else {
                    throw new Error(data.error || 'Failed to load transcript');
                }
            } catch (error) {
                console.error('Error showing transcript:', error);
                alert('Error loading transcript: ' + error.message);
            }
        }

        showVideoPlayer(videoData) {
            try {
                const qualitySelect = document.getElementById('videoQualitySelect');
                const videoPlayer = document.getElementById('videoPlayer');
                const playerTitle = document.getElementById('videoPlayerTitle');

                if (!qualitySelect || !videoPlayer || !playerTitle) {
                    throw new Error('Required video player elements not found');
                }

                playerTitle.textContent = videoData.title;
                qualitySelect.innerHTML = '';

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
            } catch (error) {
                console.error('Error showing video player:', error);
                alert('Error playing video: ' + error.message);
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
                } else {
                    throw new Error(data.error || 'Failed to save video');
                }
            } catch (error) {
                console.error('Error saving video:', error);
                alert('Error saving video: ' + error.message);
            }
        }
    };

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', () => {
        window.modalManager = new ModalManager();
    });

})(window);