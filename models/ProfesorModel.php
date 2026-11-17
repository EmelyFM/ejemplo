<?php
require_once __DIR__ . '/Model.php';

class ProfesorModel extends Model {

    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT p.id, p.especialidad, u.id as usuario_id, u.nombre, u.Apellidos, u.Correo
                FROM `Profesor` p
                LEFT JOIN `Usuario` u ON u.Profesor_id = p.id
                WHERE p.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function obtenerPorUsuarioId(int $usuarioId): ?array {
        $sql = "SELECT p.id, p.especialidad, u.id as usuario_id, u.nombre, u.Apellidos, u.Correo
                FROM `Profesor` p
                INNER JOIN `Usuario` u ON u.Profesor_id = p.id
                WHERE u.id = :uid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $usuarioId]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function obtenerTodos(): array {
        $sql = "SELECT p.id, p.especialidad, u.id as usuario_id, u.nombre, u.Apellidos, u.Correo,
                       (SELECT COUNT(*) FROM `Cursos` c WHERE c.Profesor_id = p.id) as total_cursos
                FROM `Profesor` p
                LEFT JOIN `Usuario` u ON u.Profesor_id = p.id
                ORDER BY p.id DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function crear(string $especialidad): int {
        $stmt = $this->db->prepare("INSERT INTO `Profesor` (`especialidad`) VALUES (:esp)");
        $stmt->execute([':esp' => trim($especialidad)]);
        return (int)$this->db->lastInsertId();
    }

    public function actualizar(int $id, string $especialidad): bool {
        $stmt = $this->db->prepare("UPDATE `Profesor` SET `especialidad` = :esp WHERE `id` = :id");
        return $stmt->execute([':esp' => trim($especialidad), ':id' => $id]);
    }

    public function eliminar(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM `Profesor` WHERE `id` = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function contar(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM `Profesor`")->fetchColumn();
    }
}
