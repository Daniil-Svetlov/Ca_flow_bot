<?php

class Database {
    private static $instance = null;
    private $pdo;

    public function __construct() {
        $this->pdo = new PDO('sqlite:' . __DIR__ . '/../../database.db');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function prepare($sql) {
        return $this->pdo->prepare($sql);
    }
}