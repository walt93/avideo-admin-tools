<?php
class DatabaseManager {
    private $db;
    private $userId;

    public function __construct($userId = null) {
        $this->userId = $userId;
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

    public function getVideoById($id) {
        try {
            error_log("DatabaseManager: Getting video ID: " . $id);
            $stmt = $this->db->prepare('
                SELECT id, title, filename, status as state, created
                FROM videos
                WHERE id = ?
            ');
            $stmt->execute([$id]);
            $result = $stmt->fetch();
            error_log("DatabaseManager: Result: " . json_encode($result));
            return $result;
        } catch (PDOException $e) {
            error_log("DatabaseManager error: " . $e->getMessage());
            return null;
        }
    }


    // Get playlists for a specific user
    public function getUserPlaylists($userId) {
        $stmt = $this->db->prepare('
            SELECT id, name 
            FROM playlists 
            WHERE users_id = ? 
            ORDER BY name ASC
        ');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // Get videos for a specific user with filters
    public function getUserVideos($filters, $page = 1, $perPage = 25) {
        $whereConditions = [
            "type = 'video'",
            "users_id = ?"
        ];
        $params = [$filters['user_id']];

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
        $query = "SELECT id, title, description, created, filename 
                 FROM videos
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

        // Update video - with user verification
        public function updateVideo($id, $title, $description) {
            if (!$this->userId) {
                error_log("No user ID provided for video update");
                throw new Exception('Access denied: No user ID');
            }

            // First verify the video belongs to the user
            $stmt = $this->db->prepare('
                SELECT users_id
                FROM videos
                WHERE id = ?
            ');
            $stmt->execute([$id]);
            $video = $stmt->fetch();

            if (!$video || $video['users_id'] != $this->userId) {
                error_log("Access denied: video {$id} does not belong to user {$this->userId}");
                throw new Exception('Access denied');
            }

            $stmt = $this->db->prepare('
                UPDATE videos
                SET title = ?, description = ?
                WHERE id = ? AND users_id = ?
            ');
            $stmt->execute([$title, $description, $id, $this->userId]);
            return true;
        }
    }

