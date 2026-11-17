<?php
require_once __DIR__ . '/Model.php';
class InscripcionModel extends Model {
    public function inscribir(int $cursoId, int $estudianteId): bool {
        $sql = "INSERT IGNORE INTO `inscripciones` (`fecha`, `Cursos_id`, `Estudiantes_id`)
                VALUES (CURDATE(), :cid, :eid)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':cid' => $cursoId, ':eid' => $estudianteId]);
    }
    public function desinscribir(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM `inscripciones` WHERE `id` = :id");
        return $stmt->execute([':id' => $id]);
    }
    public function eliminarPorCursoYEstudiante(int $cursoId, int $estudianteId): bool {
        $stmt = $this->db->prepare("DELETE FROM `inscripciones` WHERE `Cursos_id` = :cid AND `Estudiantes_id` = :eid");
        return $stmt->execute([':cid' => $cursoId, ':eid' => $estudianteId]);
    }

    public function estaInscrito(int $cursoId, int $estudianteId): bool {
        $sql = "SELECT COUNT(*) FROM `inscripciones` WHERE `Cursos_id` = :cid AND `Estudiantes_id` = :eid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cid' => $cursoId, ':eid' => $estudianteId]);
        return ((int)$stmt->fetchColumn()) > 0;
    }

    public function obtenerTodas(): array {
        $sql = "SELECT i.id, i.fecha, i.Cursos_id, i.Estudiantes_id,
                       c.nombre as curso_nombre,
                       e.matricula, e.grado, e.grupo,
                       u.nombre as estudiante_nombre, u.Apellidos as estudiante_apellidos
                FROM `inscripciones` i
                INNER JOIN `Cursos` c ON i.Cursos_id = c.id
                INNER JOIN `Estudiantes` e ON i.Estudiantes_id = e.id
                LEFT JOIN `Usuario` u ON u.Estudiantes_id = e.id
                ORDER BY i.id DESC";
        return $this->db->query($sql)->fetchAll();
    }
    public function contar(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM `inscripciones`")->fetchColumn();
    }
}
