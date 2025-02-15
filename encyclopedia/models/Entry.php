<?php
class Entry {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getSourceBooks() {
        return $this->db->query("
            SELECT
                COALESCE(source_book, 'Unspecified') as source_book,
                COUNT(*) as count
            FROM entries
            GROUP BY source_book
            ORDER BY source_book
        ")->fetchAll();
    }

    public function getStatusCounts() {
        return $this->db->query("
            SELECT
                status,
                COUNT(*) as count
            FROM entries
            GROUP BY status
        ")->fetchAll();
    }

    public function deleteEntry($id) {
        try {
            $this->db->beginTransaction();
            $this->db->query("DELETE FROM footnotes WHERE entry_id = ?", [$id]);
            $this->db->query("DELETE FROM entries WHERE id = ?", [$id]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getFilteredEntries($filters) {
        $allowed_sort_fields = ['title', 'updated_at'];
        if (!in_array($filters['sort_field'], $allowed_sort_fields)) {
            $filters['sort_field'] = 'title';
        }

        $query = "
            SELECT
                e.*,
                COUNT(f.id) as footnote_count
            FROM entries e
            LEFT JOIN footnotes f ON e.id = f.entry_id
            WHERE 1=1
        ";

        $params = [];

        if ($filters['source'] !== 'ALL') {
            if ($filters['source'] === 'Unspecified') {
                $query .= " AND e.source_book IS NULL";
            } else {
                $query .= " AND e.source_book = :source_book";
                $params['source_book'] = $filters['source'];
            }
        }

        if ($filters['status'] !== 'ALL') {
            $query .= " AND e.status = :status";
            $params['status'] = $filters['status'];
        }

        $query .= " GROUP BY e.id ORDER BY {$filters['sort_field']} {$filters['sort_direction']}";

        return $this->db->query($query, $params)->fetchAll();
    }

    public function getById($id) {
        $entry = $this->db->query("SELECT * FROM entries WHERE id = ?", [$id])->fetch();

        if ($entry) {
            $entry['footnotes'] = $this->db->query(
                "SELECT * FROM footnotes WHERE entry_id = ? ORDER BY ordering",
                [$id]
            )->fetchAll();
        }

        return $entry;
    }

    public function save($data) {
        try {
            $this->db->beginTransaction();

            $title = trim($data['title']);
            $slug = $this->createSlug($title);

            if (isset($data['id'])) {
                // Update existing entry
                $this->db->query("
                    UPDATE entries
                    SET title = :title,
                        slug = :slug,
                        content = :content,
                        source_book = :source_book,
                        status = :status
                    WHERE id = :id
                ", [
                    ':title' => $title,
                    ':slug' => $slug,
                    ':content' => $data['content'],
                    ':source_book' => $data['source_book'] ?: null,
                    ':status' => $data['status'],
                    ':id' => $data['id']
                ]);

                $this->db->query("DELETE FROM footnotes WHERE entry_id = ?", [$data['id']]);
                $entry_id = $data['id'];
            } else {
                // Insert new entry
                $this->db->query("
                    INSERT INTO entries (title, slug, content, source_book, status)
                    VALUES (:title, :slug, :content, :source_book, :status)
                ", [
                    ':title' => $title,
                    ':slug' => $slug,
                    ':content' => $data['content'],
                    ':source_book' => $data['source_book'] ?: null,
                    ':status' => $data['status']
                ]);

                $entry_id = $this->db->getConnection()->lastInsertId();
            }

            // Handle footnotes
            if (!empty($data['footnotes'])) {
                $footnotes = explode("\n", trim($data['footnotes']));
                foreach ($footnotes as $index => $footnote) {
                    if (trim($footnote)) {
                        $this->db->query("
                            INSERT INTO footnotes (entry_id, content, ordering)
                            VALUES (:entry_id, :content, :ordering)
                        ", [
                            ':entry_id' => $entry_id,
                            ':content' => trim($footnote),
                            ':ordering' => $index + 1
                        ]);
                    }
                }
            }

            $this->db->commit();
            return $entry_id;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function createSlug($str) {
        $str = strtolower(trim($str));
        $str = preg_replace('/[^a-z0-9-]/', '-', $str);
        $str = preg_replace('/-+/', "-", $str);
        return trim($str, '-');
    }
}