<?php
require_once __DIR__ . '/../conexion.php';

abstract class Model {
    protected PDO $db;
    protected static ?PDO $sharedDb = null;

    public function __construct() {
        if (self::$sharedDb === null) {
            $conexion = new Conexion();
            self::$sharedDb = $conexion->conectarPDO();
        }
        $this->db = self::$sharedDb;
    }

    public function beginTransaction(): bool {
        if (!$this->db->inTransaction()) {
            return $this->db->beginTransaction();
        }
        return false;
    }

    public function commit(): bool {
        if ($this->db->inTransaction()) {
            return $this->db->commit();
        }
        return false;
    }

    public function rollBack(): bool {
        if ($this->db->inTransaction()) {
            return $this->db->rollBack();
        }
        return false;
    }

    public function lastInsertId(): string {
        return $this->db->lastInsertId();
    }
}
