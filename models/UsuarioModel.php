<?php
require_once __DIR__ . '/Model.php';

class UsuarioModel extends Model {

    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT u.id, u.nombre, u.Apellidos, u.Correo, u.Roles_id, u.Estudiantes_id, u.Profesor_id,
                       r.Rol as rol_nombre,
                       e.matricula, e.grado, e.grupo,
                       p.especialidad
                FROM `Usuario` u
                INNER JOIN `Roles` r ON u.Roles_id = r.id
                LEFT JOIN `Estudiantes` e ON u.Estudiantes_id = e.id
                LEFT JOIN `Profesor` p ON u.Profesor_id = p.id
                WHERE u.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function obtenerPorCorreo(string $correo): ?array {
        $sql = "SELECT u.id, u.nombre, u.Apellidos, u.Correo, u.`Contraseña`, u.Roles_id, u.Estudiantes_id, u.Profesor_id,
                       r.Rol as rol_nombre,
                       e.matricula, e.grado, e.grupo,
                       p.especialidad
                FROM `Usuario` u
                INNER JOIN `Roles` r ON u.Roles_id = r.id
                LEFT JOIN `Estudiantes` e ON u.Estudiantes_id = e.id
                LEFT JOIN `Profesor` p ON u.Profesor_id = p.id
                WHERE u.Correo = :correo";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':correo' => trim($correo)]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function obtenerTodos(?int $rolId = null): array {
        $sql = "SELECT u.id, u.nombre, u.Apellidos, u.Correo, u.Roles_id, u.Estudiantes_id, u.Profesor_id,
                       r.Rol as rol_nombre,
                       e.matricula, e.grado, e.grupo,
                       p.especialidad
                FROM `Usuario` u
                INNER JOIN `Roles` r ON u.Roles_id = r.id
                LEFT JOIN `Estudiantes` e ON u.Estudiantes_id = e.id
                LEFT JOIN `Profesor` p ON u.Profesor_id = p.id";
        
        $params = [];
        if ($rolId !== null) {
            $sql .= " WHERE u.Roles_id = :rolId";
            $params[':rolId'] = $rolId;
        }
        $sql .= " ORDER BY u.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function crear(string $nombre, string $apellidos, string $correo, string $passwordPlana, int $rolId, ?int $estudianteId = null, ?int $profesorId = null): int {
        $hash = password_hash($passwordPlana, PASSWORD_BCRYPT);
        $sql = "INSERT INTO `Usuario` (`nombre`, `Apellidos`, `Correo`, `Contraseña`, `Roles_id`, `Estudiantes_id`, `Profesor_id`)
                VALUES (:nombre, :apellidos, :correo, :pass, :rolId, :estId, :profId)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombre'    => trim($nombre),
            ':apellidos' => trim($apellidos),
            ':correo'    => trim($correo),
            ':pass'      => $hash,
            ':rolId'     => $rolId,
            ':estId'     => $estudianteId,
            ':profId'    => $profesorId
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function actualizar(int $id, string $nombre, string $apellidos, string $correo, ?string $passwordPlana = null, ?int $rolId = null): bool {
        if (!empty($passwordPlana)) {
            $hash = password_hash($passwordPlana, PASSWORD_BCRYPT);
            $sql = "UPDATE `Usuario` SET `nombre` = :nombre, `Apellidos` = :apellidos, `Correo` = :correo, `Contraseña` = :pass";
            $params = [
                ':nombre'    => trim($nombre),
                ':apellidos' => trim($apellidos),
                ':correo'    => trim($correo),
                ':pass'      => $hash,
                ':id'        => $id
            ];
        } else {
            $sql = "UPDATE `Usuario` SET `nombre` = :nombre, `Apellidos` = :apellidos, `Correo` = :correo";
            $params = [
                ':nombre'    => trim($nombre),
                ':apellidos' => trim($apellidos),
                ':correo'    => trim($correo),
                ':id'        => $id
            ];
        }

        if ($rolId !== null) {
            $sql .= ", `Roles_id` = :rolId";
            $params[':rolId'] = $rolId;
        }
        $sql .= " WHERE `id` = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function eliminar(int $id): bool {
        $user = $this->obtenerPorId($id);
        if (!$user) return false;

        $this->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM `Usuario` WHERE id = :id");
            $stmt->execute([':id' => $id]);

            if ($user['Estudiantes_id']) {
                $this->db->prepare("DELETE FROM `Estudiantes` WHERE id = ?")->execute([$user['Estudiantes_id']]);
            }
            if ($user['Profesor_id']) {
                $this->db->prepare("DELETE FROM `Profesor` WHERE id = ?")->execute([$user['Profesor_id']]);
            }
            $this->commit();
            return true;
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function contarPorRol(int $rolId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `Usuario` WHERE Roles_id = :rolId");
        $stmt->execute([':rolId' => $rolId]);
        return (int)$stmt->fetchColumn();
    }
}
