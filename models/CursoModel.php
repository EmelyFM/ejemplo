<?php
require_once __DIR__ . '/Model.php';

class CursoModel extends Model {

    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT c.id, c.nombre, c.fecha_ini, c.fecha_fin, c.Profesor_id,
                       p.especialidad,
                       u.id as usuario_profesor_id, u.nombre as profesor_nombre, u.Apellidos as profesor_apellidos, u.Correo as profesor_correo
                FROM `Cursos` c
                INNER JOIN `Profesor` p ON c.Profesor_id = p.id
                LEFT JOIN `Usuario` u ON u.Profesor_id = p.id
                WHERE c.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function obtenerTodos(): array {
        $sql = "SELECT c.id, c.nombre, c.fecha_ini, c.fecha_fin, c.Profesor_id,
                       p.especialidad,
                       u.nombre as profesor_nombre, u.Apellidos as profesor_apellidos,
                       (SELECT COUNT(*) FROM `inscripciones` i WHERE i.Cursos_id = c.id) as total_estudiantes,
                       (SELECT COUNT(*) FROM `Examenes` e WHERE e.Cursos_id = c.id) as total_examenes
                FROM `Cursos` c
                INNER JOIN `Profesor` p ON c.Profesor_id = p.id
                LEFT JOIN `Usuario` u ON u.Profesor_id = p.id
                ORDER BY c.id DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function obtenerPorProfesor(int $profesorId): array {
        $sql = "SELECT c.id, c.nombre, c.fecha_ini, c.fecha_fin, c.Profesor_id,
                       (SELECT COUNT(*) FROM `inscripciones` i WHERE i.Cursos_id = c.id) as total_estudiantes,
                       (SELECT COUNT(*) FROM `Examenes` e WHERE e.Cursos_id = c.id) as total_examenes
                FROM `Cursos` c
                WHERE c.Profesor_id = :pid
                ORDER BY c.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pid' => $profesorId]);
        return $stmt->fetchAll();
    }

    public function obtenerPorEstudiante(int $estudianteId): array {
        $sql = "SELECT c.id, c.nombre, c.fecha_ini, c.fecha_fin, c.Profesor_id,
                       p.especialidad,
                       u.nombre as profesor_nombre, u.Apellidos as profesor_apellidos,
                       i.fecha as fecha_inscripcion,
                       (SELECT COUNT(*) FROM `Examenes` e WHERE e.Cursos_id = c.id) as total_examenes
                FROM `inscripciones` i
                INNER JOIN `Cursos` c ON i.Cursos_id = c.id
                INNER JOIN `Profesor` p ON c.Profesor_id = p.id
                LEFT JOIN `Usuario` u ON u.Profesor_id = p.id
                WHERE i.Estudiantes_id = :eid
                ORDER BY c.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':eid' => $estudianteId]);
        return $stmt->fetchAll();
    }

    public function crear(string $nombre, string $fechaIni, string $fechaFin, int $profesorId): int {
        $sql = "INSERT INTO `Cursos` (`nombre`, `fecha_ini`, `fecha_fin`, `Profesor_id`)
                VALUES (:nom, :ini, :fin, :pid)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nom' => trim($nombre),
            ':ini' => $fechaIni,
            ':fin' => $fechaFin,
            ':pid' => $profesorId
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function actualizar(int $id, string $nombre, string $fechaIni, string $fechaFin, int $profesorId): bool {
        $sql = "UPDATE `Cursos` SET `nombre` = :nom, `fecha_ini` = :ini, `fecha_fin` = :fin, `Profesor_id` = :pid WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nom' => trim($nombre),
            ':ini' => $fechaIni,
            ':fin' => $fechaFin,
            ':pid' => $profesorId,
            ':id'  => $id
        ]);
    }

    public function eliminar(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM `Cursos` WHERE `id` = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function obtenerEstudiantesInscritos(int $cursoId): array {
        $sql = "SELECT i.id as inscripcion_id, i.fecha as fecha_inscripcion,
                       e.id as estudiante_id, e.matricula, e.grado, e.grupo,
                       u.id as usuario_id, u.nombre, u.Apellidos, u.Correo
                FROM `inscripciones` i
                INNER JOIN `Estudiantes` e ON i.Estudiantes_id = e.id
                LEFT JOIN `Usuario` u ON u.Estudiantes_id = e.id
                WHERE i.Cursos_id = :cid
                ORDER BY u.Apellidos ASC, u.nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cid' => $cursoId]);
        return $stmt->fetchAll();
    }

    public function contar(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM `Cursos`")->fetchColumn();
    }
}
