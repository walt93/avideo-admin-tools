// js/modal_manager.js

// Create the class
class ModalManager {
    constructor() {
        console.log('ModalManager constructor running...');
        const modalElement = document.getElementById('editModal');
        console.log('Modal element:', modalElement);
        this.editModal = new bootstrap.Modal(modalElement);
        this.setupEventListeners();
    }

    setupEventListeners() {
        // Handle ESC key
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && this.editModal._isShown) {
                this.editModal.hide();
            }
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