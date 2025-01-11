// js/modal_manager.js
(function(window) {
    'use strict';

    console.log('modal_manager.js loading...');

    window.ModalManager = class ModalManager {
        constructor() {
            console.log('ModalManager constructor running...');

            // Initialize modals
            this.editModal = new bootstrap.Modal(document.getElementById('editModal'));
            this.playerModal = new bootstrap.Modal(document.getElementById('videoPlayerModal'));
            this.subtitleModal = new bootstrap.Modal(document.getElementById('subtitleModal'));
            this.transcriptModal = new bootstrap.Modal(document.getElementById('transcriptModal'));

            this.setupEventListeners();
            this.setupVideoPlayerEvents();

            console.log('ModalManager initialized with modals:', {
                editModal: this.editModal,
                playerModal: this.playerModal,
                subtitleModal: this.subtitleModal,
                transcriptModal: this.transcriptModal
            });
        }

        setupEventListeners() {
            // Global click handler for all modal-related actions
            document.addEventListener('click', (event) => {
                const target = event.target.closest('[data-action]');
                if (!target) return;

                console.log('Click handler - action:', target.dataset.action);
                const action = target.dataset.action;

                switch (action) {
                    case 'edit':
                        try {
                            const videoData = JSON.parse(target.dataset.video);
                            console.log('Edit clicked with data:', videoData);
                            this.showEditModal(videoData);
                        } catch (error) {
                            console.error('Error parsing video data for edit:', error);
                        }
                        break;

                    case 'play':
                        try {
                            const videoData = JSON.parse(target.dataset.video);
                            console.log('Play clicked with data:', videoData);
                            this.showVideoPlayer(videoData);
                        } catch (error) {
                            console.error('Error parsing video data for play:', error);
                        }
                        break;

                    case 'view-subtitles':
                        const subtitleFilename = target.dataset.filename;
                        console.log('View subtitles clicked for:', subtitleFilename);
                        this.showSubtitles(subtitleFilename);
                        break;

                    case 'view-transcript':
                        const transcriptFilename = target.dataset.filename;
                        console.log('View transcript clicked for:', transcriptFilename);
                        this.showTranscript(transcriptFilename);
                        break;
                }
            });

            // ESC key handler
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    if (this.editModal._isShown) this.editModal.hide();
                    if (this.playerModal._isShown) this.playerModal.hide();
                    if (this.subtitleModal._isShown) this.subtitleModal.hide();
                    if (this.transcriptModal._isShown) this.transcriptModal.hide();
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

                document.getElementById('videoPlayerModal').addEventListener('hidden.bs.modal', () => {
                    console.log('Video player modal closed');
                    videoPlayer.pause();
                    videoPlayer.src = '';
                });
            }
        }

        showEditModal(video) {
            console.log('Showing edit modal for:', video);

            const videoId = document.getElementById('videoId');
            const videoTitle = document.getElementById('videoTitle');
            const videoDescription = document.getElementById('videoDescription');
            const videoFilename = document.getElementById('videoFilename');
            const transcriptOption = document.getElementById('transcriptOption');

            if (!videoId || !videoTitle || !videoDescription || !videoFilename) {
                console.error('Required edit form elements not found');
                return;
            }

            videoId.value = video.id;
            videoTitle.value = video.title;
            videoDescription.value = video.description;
            videoFilename.value = video.filename;

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

        async showSubtitles(filename) {
            console.log('Loading subtitles for:', filename);
            try {
                const response = await fetch('?action=get_subtitles&filename=' + encodeURIComponent(filename));
                const data = await response.json();

                if (data.success) {
                    const contentElement = document.querySelector('.subtitle-content');
                    if (contentElement) {
                        contentElement.textContent = data.content;
                        this.subtitleModal.show();
                    } else {
                        console.error('Subtitle content element not found');
                    }
                } else {
                    console.error('Error loading subtitles:', data.error);
                    alert('Error loading subtitles: ' + data.error);
                }
            } catch (error) {
                console.error('Error loading subtitles:', error);
                alert('Error loading subtitles. Please try again.');
            }
        }

        async showTranscript(filename) {
            console.log('Loading transcript for:', filename);
            try {
                const response = await fetch('?action=get_transcript&filename=' + encodeURIComponent(filename));
                const data = await response.json();

                if (data.success) {
                    const contentElement = document.querySelector('.transcript-content');
                    if (contentElement) {
                        contentElement.textContent = data.content;
                        this.transcriptModal.show();
                    } else {
                        console.error('Transcript content element not found');
                    }
                } else {
                    console.error('Error loading transcript:', data.error);
                    alert('Error loading transcript: ' + data.error);
                }
            } catch (error) {
                console.error('Error loading transcript:', error);
                alert('Error loading transcript. Please try again.');
            }
        }

        showVideoPlayer(videoData) {
            console.log('Setting up video player for:', videoData);

            const qualitySelect = document.getElementById('videoQualitySelect');
            const videoPlayer = document.getElementById('videoPlayer');
            const playerTitle = document.getElementById('videoPlayerTitle');

            if (!qualitySelect || !videoPlayer || !playerTitle) {
                console.error('Required video player elements not found');
                return;
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
    };

    console.log('modal_manager.js loaded');
})(window);