<?php
/**
 *  789a31a5f5e7f1381115f56a6163a7cc
 */

    // Database configuration
    class DBConfig {
        private static $instance = null;
        private $conn;
        public static $host = 'wheatley.cs.up.ac.za';
        public static $user = 'u23524325';//uXXXXXXX
        public static $pass = 'XC7AKR6Q2UUX4PJ3GOHLON555UXIEY3R';//Db_pass
        public static $name = 'u23524325_PRAC5DB';//uXXXXXXX_dbName


        public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new DBConfig();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    }

    
?>