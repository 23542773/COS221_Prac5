<?php
class DBConfig {
    private static $instance = null;
    private $conn;
    
    public static $host = 'wheatley.cs.up.ac.za';
    public static $user = 'u23524325';
    public static $pass = 'XC7AKR6Q2UUX4PJ3GOHLON555UXIEY3R';
    public static $name = 'u23524325_PRAC5DB';

    private function __construct() {
        try {
            // Create PDO connection
            $this->conn = new PDO(
                "mysql:host=" . self::$host . ";dbname=" . self::$name,
                self::$user,
                self::$pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (PDOException $e) {
            throw new Exception("PDO Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new DBConfig();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    } 
} 
?>
