<?php
namespace Admin;

class WhitelistManager {
    private static $instance = null;
    private $db;

    private function __construct() {
        $this->db = \Config\Database::getInstance()->getConnection();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function addRule($urlPattern, $fileType = null, $description = null) {
        $stmt = $this->db->prepare(
            'INSERT INTO whitelist_rules (url_pattern, file_type, description) VALUES (?, ?, ?)'
        );
        return $stmt->execute([$urlPattern, $fileType, $description]);
    }

    public function updateRule($id, $urlPattern, $fileType = null, $description = null) {
        $stmt = $this->db->prepare(
            'UPDATE whitelist_rules SET url_pattern = ?, file_type = ?, description = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?'
        );
        return $stmt->execute([$urlPattern, $fileType, $description, $id]);
    }

    public function deleteRule($id) {
        $stmt = $this->db->prepare('DELETE FROM whitelist_rules WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function getAllRules() {
        $stmt = $this->db->query('SELECT * FROM whitelist_rules ORDER BY created_at DESC');
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function isUrlAllowed($url) {
        $stmt = $this->db->query('SELECT * FROM whitelist_rules');
        $rules = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($rules as $rule) {
            $pattern = $rule['url_pattern'];
            if (preg_match("#$pattern#", $url)) {
                if (!empty($rule['file_type'])) {
                    $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
                    $allowedTypes = array_map('trim', explode(',', strtolower($rule['file_type'])));
                    return in_array($extension, $allowedTypes);
                }
                return true;
            }
        }
        return false;
    }
}