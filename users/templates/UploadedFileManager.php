<?php
class UploadedFilesManager {
    private $logFile = '/opt/upload/logs/files-uploaded.json';
    private $db;

    public function __construct($db) {
        $this->db = $db;
        $this->ensureLogDirectory();
    }

    private function ensureLogDirectory() {
        $dir = dirname($this->logFile);
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public function addUpload($data) {
        $uploads = $this->getUploads();
        $data['upload_date'] = date('c');
        array_unshift($uploads, $data); // Add to start of array
        $this->saveUploads($uploads);
    }

    public function removeUpload($id) {
        $uploads = $this->getUploads();
        $uploads = array_filter($uploads, function($upload) use ($id) {
            return $upload['id'] !== $id;
        });
        $this->saveUploads(array_values($uploads));
    }

    public function getUploads() {
        if (!file_exists($this->logFile)) {
            return [];
        }
        $content = file_get_contents($this->logFile);
        if (!$content) {
            return [];
        }
        $data = json_decode($content, true);
        return $data['uploads'] ?? [];
    }

    private function saveUploads($uploads) {
        $data = ['uploads' => $uploads];
        file_put_contents($this->logFile, json_encode($data, JSON_PRETTY_PRINT));
    }

    public function getVideoDetails($videoId) {
        $stmt = $this->db->prepare('
            SELECT id, title, description, filename, state
            FROM videos
            WHERE id = ?
        ');
        $stmt->execute([$videoId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function checkVideoFiles($filename) {
        return [
            'thumbnail' => file_exists("/var/www/html/conspyre.tv/videos/{$filename}/{$filename}.jpg"),
            'transcript' => file_exists("/var/www/html/conspyre.tv/videos/{$filename}/{$filename}.txt")
        ];
    }

    public function getStateDescription($state) {
        $states = [
            'i' => 'Inactive',
            'a' => 'Active',
            'e' => 'Encoding',
            'x' => 'Error',
            'd' => 'Deleted'
        ];
        return $states[$state] ?? 'Unknown';
    }
}