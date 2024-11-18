<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/functions/config.php';

class ContentManager {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = new Database();
        $this->conn = $this->db->connect();
    }

    public function getSectionContent($section_name) {
        $sql = "SELECT * FROM website_content WHERE section_name = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$section_name]);
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $content = [];
        foreach ($result as $row) {
            $content[$row['content_key']] = $row['content_value'];
        }
        return $content;
    }

    public function updateContent($section, $key, $value) {
        $sql = "UPDATE website_content SET content_value = ? 
                WHERE section_name = ? AND content_key = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$value, $section, $key]);
    }

    public function updateImage($section, $image_key, $file_path) {
        $sql = "UPDATE website_content SET content_value = ? 
                WHERE section_name = ? AND content_key = ?";
        $stmt = $this->conn->prepare($sql);
        return $stmt->execute([$file_path, $section, $image_key]);
    }

    public function getImageCount($section) {
        $sql = "SELECT COUNT(*) as count FROM website_content 
                WHERE section_name = ? AND content_type = 'image' 
                AND content_value != ''";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([$section]);
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }
}