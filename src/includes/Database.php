<?php

namespace Lib;

use mysqli;
use mysqli_sql_exception;
use Exception;

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        $host = getenv('MYSQL_HOST') ?: 'mysql';
        $user = 'root';
        $pass = 'root_password';
        $db = 'comune_palermo';

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $this->conn = new mysqli($host, $user, $pass, $db);
            $this->conn->set_charset("utf8mb4");
        } catch (mysqli_sql_exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw new Exception("Errore di connessione al database. Riprova più tardi.");
        }
    }

    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    /**
     * Helper for prepared statements
     */
    public function query($sql, $params = [], $types = "") {
        try {
            $stmt = $this->conn->prepare($sql);
            if ($params) {
                if (empty($types)) {
                    $types = str_repeat('s', count($params));
                }
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            return $stmt;
        } catch (mysqli_sql_exception $e) {
            error_log("SQL Error: " . $e->getMessage() . " | SQL: " . $sql);
            throw new Exception("Errore durante l'esecuzione della richiesta.");
        }
    }

    public function fetchAll($sql, $params = [], $types = "") {
        $stmt = $this->query($sql, $params, $types);
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $data;
    }

    public function fetchOne($sql, $params = [], $types = "") {
        $stmt = $this->query($sql, $params, $types);
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        return $data;
    }

    public function lastInsertId() {
        return $this->conn->insert_id;
    }

    public function affectedRows($stmt) {
        return $stmt->affected_rows;
    }
}
