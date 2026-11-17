<?php
require_once __DIR__ . '/Model.php';

class EstudianteModel extends Model {

    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT e.id, e.matricula, e.grado, e.grupo,
                       u.id as usuario_id, u.nombre, u.Apellidos, u.Correo
                FROM `Estudiantes` e
                LEFT JOIN `Usuario` u ON u.Estudiantes_id = e.id
                WHERE e.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function obtenerPorUsuarioId(int $usuarioId): ?array {
        $sql = "SELECT e.id, e.matricula, e.grado, e.grupo,
                       u.id as usuario_id, u.nombre, u.Apellidos, u.Correo
                FROM `Estudiantes` e
                INNER JOIN `Usuario` u ON u.Estudiantes_id = e.id
                WHERE u.id = :uid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':uid' => $usuarioId]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function obtenerPorMatricula(string $matricula): ?array {
        $sql = "SELECT e.id, e.matricula, e.grado, e.grupo,
                       u.id as usuario_id, u.nombre, u.Apellidos, u.Correo
                FROM `Estudiantes` e
                LEFT JOIN `Usuario` u ON u.Estudiantes_id = e.id
                WHERE e.matricula = :mat";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':mat' => trim($matricula)]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function obtenerTodos(): array {
        $sql = "SELECT e.id, e.matricula, e.grado, e.grupo,
                       u.id as usuario_id, u.nombre, u.Apellidos, u.Correo,
                       (SELECT COUNT(*) FROM `inscripciones` i WHERE i.Estudiantes_id = e.id) as total_cursos
                FROM `Estudiantes` e
                LEFT JOIN `Usuario` u ON u.Estudiantes_id = e.id
                ORDER BY e.id DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function crear(string $matricula, int $grado, string $grupo): int {
        $stmt = $this->db->prepare("INSERT INTO `Estudiantes` (`matricula`, `grado`, `grupo`) VALUES (:mat, :gra, :gru)");
        $stmt->execute([
            ':mat' => trim($matricula),
            ':gra' => $grado,
            ':gru' => trim($grupo)
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function actualizar(int $id, string $matricula, int $grado, string $grupo): bool {
        $stmt = $this->db->prepare("UPDATE `Estudiantes` SET `matricula` = :mat, `grado` = :gra, `grupo` = :gru WHERE `id` = :id");
        return $stmt->execute([
            ':mat' => trim($matricula),
            ':gra' => $grado,
            ':gru' => trim($grupo),
            ':id'  => $id
        ]);
    }

    public function eliminar(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM `Estudiantes` WHERE `id` = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function contar(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM `Estudiantes`")->fetchColumn();
    }
}
