// js/modal_manager.js

// Create the class
class ModalManager {
    constructor() {
        this.editModal = new bootstrap.Modal(document.getElementById('editModal'));
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

    hideEditModal() {
        this.editModal.hide();
    }
}

// Initialize and expose the instance
modalManager = new ModalManager();

// Expose the save function globally
saveVideo = function() {
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('id', document.getElementById('videoId').value);
    formData.append('title', document.getElementById('videoTitle').value);
    formData.append('description', document.getElementById('videoDescription').value);

    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            modalManager.hideEditModal();
            window.location.reload();
        } else if (data.error) {
            alert('Error saving: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error saving:', error);
        alert('Error saving video. Please try again.');
    });
};
