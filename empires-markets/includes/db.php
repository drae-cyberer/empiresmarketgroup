<?php
// Database Connection File
require_once 'config.php';

class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    private $pdo;
    private $error;

    public function __construct() {
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';charset=utf8mb4';
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        );

        try {
            $this->pdo = new PDO($dsn, $this->user, $this->pass, $options);
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            die('Database connection failed: ' . $this->error);
        }
    }

    public function query($query, $params = array()) {
        try {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function select($query, $params = array()) {
        try {
            $stmt = $this->query($query, $params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return false;
        }
    }

    public function selectOne($query, $params = array()) {
        try {
            $stmt = $this->query($query, $params);
            return $stmt->fetch();
        } catch (Exception $e) {
            return false;
        }
    }

    public function insert($query, $params = array()) {
        try {
            $stmt = $this->query($query, $params);
            return $this->pdo->lastInsertId();
        } catch (Exception $e) {
            return false;
        }
    }

    public function update($query, $params = array()) {
        try {
            $stmt = $this->query($query, $params);
            return $stmt->rowCount();
        } catch (Exception $e) {
            return false;
        }
    }

    public function delete($query, $params = array()) {
        try {
            $stmt = $this->query($query, $params);
            return $stmt->rowCount();
        } catch (Exception $e) {
            return false;
        }
    }

    public function count($query, $params = array()) {
        try {
            $stmt = $this->query($query, $params);
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            return false;
        }
    }

    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }

    public function commit() {
        return $this->pdo->commit();
    }

    public function rollback() {
        return $this->pdo->rollback();
    }
}

// Create global database instance
$db = new Database();
?>
