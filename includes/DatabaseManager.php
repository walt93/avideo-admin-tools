<?php
// includes/DatabaseManager.php

class DatabaseManager {
    private $db;

    public function __construct() {
        $this->connect();
    }

    private function connect() {
        $dbPassword = getenv('AVIDEO_DATABASE_PW');
        $dbName = getenv('AVIDEO_DATABASE_NAME');
        $dbUser = getenv('AVIDEO_DATABASE_USER');
        $dbHost = getenv('AVIDEO_DATABASE_HOST');

        // Validate environment variables
        $requiredVars = [
            'AVIDEO_DATABASE_PW' => $dbPassword,
            'AVIDEO_DATABASE_NAME' => $dbName,
            'AVIDEO_DATABASE_USER' => $dbUser,
            'AVIDEO_DATABASE_HOST' => $dbHost
        ];

        foreach ($requiredVars as $name => $value) {
            if ($value === false) {
                throw new Exception("Database environment variable {$name} not set");
            }
        }

        try {
            $this->db = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPassword);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Test connection
            $this->db->query('SELECT 1');
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    public function getTopLevelCategories() {
        $stmt = $this->db->prepare('SELECT id, name FROM categories WHERE parentId = 0 ORDER BY name ASC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getSubcategories($parentId) {
        $stmt = $this->db->prepare('SELECT id, name FROM categories WHERE parentId = ? ORDER BY name ASC');
        $stmt->execute([intval($parentId)]);
        return $stmt->fetchAll();
    }

    public function getPlaylists() {
        $stmt = $this->db->prepare('SELECT id, name FROM playlists WHERE users_id = 1 ORDER BY name ASC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function updateVideo($id, $title, $description) {
        $stmt = $this->db->prepare('UPDATE videos SET title = ?, description = ? WHERE id = ?');
        $stmt->execute([$title, $description, $id]);
        return true;
    }

    public function getVideos($filters, $page = 1, $perPage = 25) {
        $whereConditions = ["type = 'video'"];
        $params = [];

        if (!empty($filters['category'])) {
            $whereConditions[] = "categories_id = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['playlist'])) {
            $whereConditions[] = "id IN (SELECT videos_id FROM playlists_has_videos WHERE playlists_id = ?)";
            $params[] = $filters['playlist'];
        }

        $whereClause = implode(' AND ', $whereConditions);
        $offset = ($page - 1) * $perPage;

        // Get total count
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM videos WHERE $whereClause");
        $countStmt->execute($params);
        $totalVideos = $countStmt->fetchColumn();

        // Get videos
        $query = "SELECT id, title, description, created, filename FROM videos
                  WHERE $whereClause
                  ORDER BY created DESC
                  LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        $videos = $stmt->fetchAll();

        return [
            'videos' => $videos,
            'total' => $totalVideos,
            'pages' => ceil($totalVideos / $perPage)
        ];
    }
}
