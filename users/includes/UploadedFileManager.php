<?php
class UploadedFilesManager {
    private $db;
    private $uploadsFile;

    public function __construct($db) {
        $this->db = $db;
        // Get current user's directory from the script path
        $userDir = dirname($_SERVER['SCRIPT_FILENAME']);
        $this->uploadsFile = $userDir . '/uploads.json';

        // Create uploads file if it doesn't exist
        if (!file_exists($this->uploadsFile)) {
            file_put_contents($this->uploadsFile, json_encode(['uploads' => []]));
        }
    }

    public function addUpload($data) {
        $uploads = $this->getUploads();

        // Add new upload with timestamp
        $uploads[] = array_merge($data, [
            'upload_date' => date('Y-m-d H:i:s'),
            'status' => 'pending'
        ]);

        // Save back to file
        $this->saveUploads($uploads);
    }

    public function getUploads() {
        $content = file_get_contents($this->uploadsFile);
        $data = json_decode($content, true);
        return $data['uploads'] ?? [];
    }

    private function saveUploads($uploads) {
        file_put_contents($this->uploadsFile, json_encode(['uploads' => $uploads], JSON_PRETTY_PRINT));
    }

    public function removeUpload($id) {
        $uploads = $this->getUploads();
        $uploads = array_filter($uploads, function($upload) use ($id) {
            return $upload['id'] != $id;
        });
        $this->saveUploads(array_values($uploads));
    }

    public function getVideoDetails($id) {
        try {
            $video = $this->db->getVideoById($id);
            if ($video) {
                return array_merge($video, [
                    'files' => $this->checkVideoFiles($video['filename']),
                    'stateDescription' => $this->getStateDescription($video['state'])
                ]);
            }
            return null;
        } catch (Exception $e) {
            error_log("Error getting video details: " . $e->getMessage());
            return null;
        }
    }

    public function checkVideoFiles($filename) {
        $basePath = "/var/www/html/conspyre.tv/videos/$filename/$filename";
        return [
            'thumbnail' => file_exists($basePath . '.jpg'),
            'transcript' => file_exists($basePath . '.txt') || file_exists($basePath . '_ext.txt'),
            'subtitles' => file_exists($basePath . '.vtt') || file_exists($basePath . '_ext.vtt')
        ];
    }

    public function getStateDescription($state) {
        $states = [
            'e' => 'Encoding',
            'a' => 'Active',
            'i' => 'Inactive',
            'x' => 'Error'
        ];
        return $states[$state] ?? 'Unknown';
    }
}