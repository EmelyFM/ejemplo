<?php
require_once __DIR__ . '/Model.php';

class RolModel extends Model {

    public function obtenerTodos(): array {
        return $this->db->query("SELECT id, Rol FROM `Roles` ORDER BY id ASC")->fetchAll();
    }

    public function obtenerPorId(int $id): ?array {
        $stmt = $this->db->prepare("SELECT id, Rol FROM `Roles` WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }
}
