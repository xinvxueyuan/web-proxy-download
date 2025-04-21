<?php
namespace Config;

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $dbPath = __DIR__ . '/../data/proxy.db';
        $dbDir = dirname($dbPath);
        
        // 确保数据目录存在
        if (!file_exists($dbDir)) {
            mkdir($dbDir, 0755, true);
        }
        
        // 创建数据库连接
        $this->pdo = new \PDO("sqlite:$dbPath");
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        
        // 检查核心表是否存在，如果不存在则执行初始化脚本
        $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='admins'");
        if ($stmt->fetch() === false) {
            $this->initDatabase();
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->pdo;
    }

    private function initDatabase() {
        $sql = file_get_contents(__DIR__ . '/../init.sql');
        $this->pdo->exec($sql);
    }
}