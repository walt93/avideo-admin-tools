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
            error_log("UploadedFilesManager: Getting details for ID: " . $id);
            $video = $this->db->getVideoById($id);
            error_log("UploadedFilesManager: Got video data: " . json_encode($video));

            if ($video) {
                $files = $this->checkVideoFiles($video['filename']);
                error_log("UploadedFilesManager: File check results: " . json_encode($files));

                $result = array_merge($video, [
                    'files' => $files,
                    'stateDescription' => $this->getStateDescription($video['state'])
                ]);
                error_log("UploadedFilesManager: Returning: " . json_encode($result));
                return $result;
            }
            return null;
        } catch (Exception $e) {
            error_log("UploadedFilesManager error: " . $e->getMessage());
            return null;
        }
    }

    public function checkVideoFiles($filename) {
        // Update base path to match your filesystem structure
        $basePath = "/var/www/html/conspyre.tv/videos/$filename";
        return [
            'thumbnail' => file_exists("$basePath/$filename.jpg"),
            'transcript' => file_exists("$basePath/$filename.txt") ||
                           file_exists("$basePath/{$filename}_ext.txt"),
            'subtitles' => file_exists("$basePath/$filename.vtt") ||
                          file_exists("$basePath/{$filename}_ext.vtt")
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