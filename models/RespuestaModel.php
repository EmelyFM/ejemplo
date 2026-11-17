<?php
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/CalificacionModel.php';

class RespuestaModel extends Model {

    public function haRespondidoExamen(int $examenId, int $estudianteId): bool {
        $sql = "SELECT COUNT(*) FROM `Respuestas` r
                INNER JOIN `Preguntas` p ON r.Preguntas_id = p.id
                WHERE p.Examenes_id = :eid AND r.Estudiantes_id = :sid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':eid' => $examenId, ':sid' => $estudianteId]);
        return ((int)$stmt->fetchColumn()) > 0;
    }

    public function guardarRespuestasEstudiante(int $examenId, int $estudianteId, array $respuestas): array {
        if ($this->haRespondidoExamen($examenId, $estudianteId)) {
            throw new Exception("Ya has enviado tus respuestas para este examen. Solo se permite un intento.");
        }

        $this->beginTransaction();
        try {
            // Obtener todas las preguntas del examen con sus respuestas correctas
            $sqlP = "SELECT id, tipo, respuesta, puntaje FROM `Preguntas` WHERE Examenes_id = :eid";
            $stmtP = $this->db->prepare($sqlP);
            $stmtP->execute([':eid' => $examenId]);
            $preguntas = $stmtP->fetchAll();

            if (empty($preguntas)) {
                throw new Exception("El examen no tiene preguntas registradas.");
            }

            $ahora = date('Y-m-d H:i:s');
            $insertSql = "INSERT INTO `Respuestas` (`respuesta`, `fecha_envio`, `Preguntas_id`, `Estudiantes_id`, `puntaje_obtenido`, `retroalimentacion`)
                          VALUES (:res, :fec, :pid, :sid, :pts, NULL)";
            $stmtIns = $this->db->prepare($insertSql);

            $hayPreguntasAbiertas = false;

            foreach ($preguntas as $p) {
                $pId = (int)$p['id'];
                $respuestaUsuario = isset($respuestas[$pId]) ? trim((string)$respuestas[$pId]) : '';
                $puntajeObtenido = null;

                if ($p['tipo'] === 'opcion_multiple' || $p['tipo'] === 'verdadero_falso') {
                    // Calificación automática objetiva
                    if (mb_strtolower($respuestaUsuario, 'UTF-8') === mb_strtolower(trim($p['respuesta']), 'UTF-8')) {
                        $puntajeObtenido = (float)$p['puntaje'];
                    } else {
                        $puntajeObtenido = 0.0;
                    }
                } else if ($p['tipo'] === 'abierta') {
                    // Pregunta abierta: requiere calificación manual por el profesor
                    $hayPreguntasAbiertas = true;
                    $puntajeObtenido = null;
                }

                $stmtIns->execute([
                    ':res' => $respuestaUsuario,
                    ':fec' => $ahora,
                    ':pid' => $pId,
                    ':sid' => $estudianteId,
                    ':pts' => $puntajeObtenido
                ]);
            }

            // Calcular y registrar la calificación
            $califModel = new CalificacionModel();
            $calificacionGenerada = $califModel->calcularYGuardar($examenId, $estudianteId);

            $this->commit();

            return [
                'calificacion' => $calificacionGenerada['calificacion'],
                'estado'       => $calificacionGenerada['estado'],
                'tiene_abiertas' => $hayPreguntasAbiertas
            ];
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }

    public function obtenerRespuestasPorExamenEstudiante(int $examenId, int $estudianteId): array {
        $sql = "SELECT r.id as respuesta_id, r.respuesta as respuesta_estudiante, r.fecha_envio,
                       r.puntaje_obtenido, r.retroalimentacion,
                       p.id as pregunta_id, p.pregunta, p.imagen, p.tipo, p.respuesta as respuesta_correcta,
                       p.puntaje as puntaje_maximo, p.opciones
                FROM `Respuestas` r
                INNER JOIN `Preguntas` p ON r.Preguntas_id = p.id
                WHERE p.Examenes_id = :eid AND r.Estudiantes_id = :sid
                ORDER BY p.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':eid' => $examenId, ':sid' => $estudianteId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            $r['opciones_array'] = !empty($r['opciones']) ? (json_decode($r['opciones'], true) ?: []) : [];
        }
        return $rows;
    }

    public function calificarRespuestaManual(int $respuestaId, float $puntajeObtenido, ?string $retroalimentacion = null): bool {
        $sql = "UPDATE `Respuestas` 
                SET `puntaje_obtenido` = :pts, `retroalimentacion` = :retro
                WHERE `id` = :id";
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute([
            ':pts'   => $puntajeObtenido,
            ':retro' => $retroalimentacion ? trim($retroalimentacion) : null,
            ':id'    => $respuestaId
        ]);

        if ($ok) {
            // Re-calcular nota total del examen y estudiante
            $info = $this->db->prepare("SELECT p.Examenes_id, r.Estudiantes_id 
                                        FROM `Respuestas` r
                                        INNER JOIN `Preguntas` p ON r.Preguntas_id = p.id
                                        WHERE r.id = :id");
            $info->execute([':id' => $respuestaId]);
            $row = $info->fetch();
            if ($row) {
                $califModel = new CalificacionModel();
                $califModel->calcularYGuardar((int)$row['Examenes_id'], (int)$row['Estudiantes_id']);
            }
        }

        return $ok;
    }
}
