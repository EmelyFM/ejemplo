<?php
require_once __DIR__ . '/Model.php';

class ExamenModel extends Model {

    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT e.id, e.nom_exam, e.Fecha, e.fecha_inicio, e.fecha_fin, e.duracion_minutos,
                       e.resultados_publicados, e.estado, e.Cursos_id,
                       c.nombre as curso_nombre, c.Profesor_id,
                       u.nombre as profesor_nombre, u.Apellidos as profesor_apellidos,
                       (SELECT COUNT(*) FROM `Preguntas` p WHERE p.Examenes_id = e.id) as total_preguntas,
                       (SELECT COALESCE(SUM(puntaje), 0) FROM `Preguntas` p WHERE p.Examenes_id = e.id) as puntaje_total,
                       (SELECT COUNT(DISTINCT r.Estudiantes_id) FROM `Respuestas` r 
                        INNER JOIN `Preguntas` p ON r.Preguntas_id = p.id 
                        WHERE p.Examenes_id = e.id) as total_entregas
                FROM `Examenes` e
                INNER JOIN `Cursos` c ON e.Cursos_id = c.id
                INNER JOIN `Profesor` prof ON c.Profesor_id = prof.id
                LEFT JOIN `Usuario` u ON u.Profesor_id = prof.id
                WHERE e.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function obtenerTodos(): array {
        $sql = "SELECT e.id, e.nom_exam, e.Fecha, e.fecha_inicio, e.fecha_fin, e.duracion_minutos,
                       e.resultados_publicados, e.estado, e.Cursos_id,
                       c.nombre as curso_nombre,
                       u.nombre as profesor_nombre, u.Apellidos as profesor_apellidos,
                       (SELECT COUNT(*) FROM `Preguntas` p WHERE p.Examenes_id = e.id) as total_preguntas,
                       (SELECT COUNT(DISTINCT r.Estudiantes_id) FROM `Respuestas` r 
                        INNER JOIN `Preguntas` p ON r.Preguntas_id = p.id 
                        WHERE p.Examenes_id = e.id) as total_entregas
                FROM `Examenes` e
                INNER JOIN `Cursos` c ON e.Cursos_id = c.id
                INNER JOIN `Profesor` prof ON c.Profesor_id = prof.id
                LEFT JOIN `Usuario` u ON u.Profesor_id = prof.id
                ORDER BY e.id DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function obtenerPorProfesor(int $profesorId): array {
        $sql = "SELECT e.id, e.nom_exam, e.Fecha, e.fecha_inicio, e.fecha_fin, e.duracion_minutos,
                       e.resultados_publicados, e.estado, e.Cursos_id,
                       c.nombre as curso_nombre,
                       (SELECT COUNT(*) FROM `Preguntas` p WHERE p.Examenes_id = e.id) as total_preguntas,
                       (SELECT COALESCE(SUM(puntaje), 0) FROM `Preguntas` p WHERE p.Examenes_id = e.id) as puntaje_total,
                       (SELECT COUNT(DISTINCT r.Estudiantes_id) FROM `Respuestas` r 
                        INNER JOIN `Preguntas` p ON r.Preguntas_id = p.id 
                        WHERE p.Examenes_id = e.id) as total_entregas
                FROM `Examenes` e
                INNER JOIN `Cursos` c ON e.Cursos_id = c.id
                WHERE c.Profesor_id = :pid
                ORDER BY e.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pid' => $profesorId]);
        return $stmt->fetchAll();
    }

    public function obtenerPorCurso(int $cursoId): array {
        $sql = "SELECT e.id, e.nom_exam, e.Fecha, e.fecha_inicio, e.fecha_fin, e.duracion_minutos,
                       e.resultados_publicados, e.estado, e.Cursos_id,
                       (SELECT COUNT(*) FROM `Preguntas` p WHERE p.Examenes_id = e.id) as total_preguntas
                FROM `Examenes` e
                WHERE e.Cursos_id = :cid
                ORDER BY e.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cid' => $cursoId]);
        return $stmt->fetchAll();
    }

    public function obtenerParaEstudiante(int $estudianteId): array {
        require_once __DIR__ . '/CalificacionModel.php';
        $califModel = new CalificacionModel();
        $califModel->recalcularEstudiante($estudianteId);

        $sql = "SELECT e.id, e.nom_exam, e.Fecha, e.fecha_inicio, e.fecha_fin, e.duracion_minutos,
                       e.resultados_publicados, e.estado, e.Cursos_id,
                       c.nombre as curso_nombre,
                       u.nombre as profesor_nombre, u.Apellidos as profesor_apellidos,
                       (SELECT COUNT(*) FROM `Preguntas` p WHERE p.Examenes_id = e.id) as total_preguntas,
                       (SELECT COALESCE(SUM(p.puntaje), 0) FROM `Preguntas` p WHERE p.Examenes_id = e.id) as puntaje_total,
                       (SELECT cal.calificaciones FROM `Calificaciones` cal 
                        WHERE cal.Examenes_id = e.id AND cal.Estudiantes_id = :eid1 LIMIT 1) as calificacion,
                       (SELECT cal.estado FROM `Calificaciones` cal 
                        WHERE cal.Examenes_id = e.id AND cal.Estudiantes_id = :eid2 LIMIT 1) as estado_calificacion,
                       (SELECT COUNT(*) FROM `Respuestas` r 
                        INNER JOIN `Preguntas` p ON r.Preguntas_id = p.id 
                        WHERE p.Examenes_id = e.id AND r.Estudiantes_id = :eid3) as respuestas_enviadas
                FROM `Examenes` e
                INNER JOIN `Cursos` c ON e.Cursos_id = c.id
                INNER JOIN `inscripciones` i ON i.Cursos_id = c.id
                INNER JOIN `Profesor` prof ON c.Profesor_id = prof.id
                LEFT JOIN `Usuario` u ON u.Profesor_id = prof.id
                WHERE i.Estudiantes_id = :eid4
                ORDER BY e.fecha_inicio DESC, e.id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':eid1' => $estudianteId,
            ':eid2' => $estudianteId,
            ':eid3' => $estudianteId,
            ':eid4' => $estudianteId
        ]);
        return $stmt->fetchAll();
    }

    public function crear(string $nomExam, string $fecha, int $cursoId, ?string $fechaInicio, ?string $fechaFin, int $duracionMin = 60, string $estado = 'activo'): int {
        $sql = "INSERT INTO `Examenes` (`nom_exam`, `Fecha`, `Cursos_id`, `fecha_inicio`, `fecha_fin`, `duracion_minutos`, `estado`, `resultados_publicados`)
                VALUES (:nom, :fec, :cid, :ini, :fin, :dur, :est, 0)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nom' => trim($nomExam),
            ':fec' => $fecha,
            ':cid' => $cursoId,
            ':ini' => $fechaInicio,
            ':fin' => $fechaFin,
            ':dur' => $duracionMin,
            ':est' => $estado
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function actualizar(int $id, string $nomExam, string $fecha, int $cursoId, ?string $fechaInicio, ?string $fechaFin, int $duracionMin, string $estado): bool {
        $sql = "UPDATE `Examenes` 
                SET `nom_exam` = :nom, `Fecha` = :fec, `Cursos_id` = :cid, `fecha_inicio` = :ini, `fecha_fin` = :fin, `duracion_minutos` = :dur, `estado` = :est
                WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nom' => trim($nomExam),
            ':fec' => $fecha,
            ':cid' => $cursoId,
            ':ini' => $fechaInicio,
            ':fin' => $fechaFin,
            ':dur' => $duracionMin,
            ':est' => $estado,
            ':id'  => $id
        ]);
    }

    public function publicarResultados(int $id, bool $publicar = true): bool {
        $stmt = $this->db->prepare("UPDATE `Examenes` SET `resultados_publicados` = :pub WHERE `id` = :id");
        return $stmt->execute([
            ':pub' => $publicar ? 1 : 0,
            ':id'  => $id
        ]);
    }

    public function tieneRespuestas(int $examenId): bool {
        $sql = "SELECT COUNT(*) FROM `Respuestas` r
                INNER JOIN `Preguntas` p ON r.Preguntas_id = p.id
                WHERE p.Examenes_id = :eid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':eid' => $examenId]);
        return ((int)$stmt->fetchColumn()) > 0;
    }

    public function eliminar(int $id): bool {
        if ($this->tieneRespuestas($id)) {
            throw new Exception("No es posible eliminar el examen porque ya cuenta con respuestas enviadas por estudiantes.");
        }
        $this->beginTransaction();
        try {
            // Eliminar preguntas asociadas
            $this->db->prepare("DELETE FROM `Preguntas` WHERE `Examenes_id` = :id")->execute([':id' => $id]);
            // Eliminar calificaciones asociadas si hubiese
            $this->db->prepare("DELETE FROM `Calificaciones` WHERE `Examenes_id` = :id")->execute([':id' => $id]);
            // Eliminar examen
            $stmt = $this->db->prepare("DELETE FROM `Examenes` WHERE `id` = :id");
            $res = $stmt->execute([':id' => $id]);
            $this->commit();
            return $res;
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function contar(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM `Examenes`")->fetchColumn();
    }

    public function contarActivos(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM `Examenes` WHERE `estado` = 'activo' AND (fecha_fin IS NULL OR fecha_fin >= NOW())")->fetchColumn();
    }

    public function contarFinalizados(): int {
        return (int)$this->db->query("SELECT COUNT(*) FROM `Examenes` WHERE `estado` = 'finalizado' OR (fecha_fin IS NOT NULL AND fecha_fin < NOW())")->fetchColumn();
    }
}
