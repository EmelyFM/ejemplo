<?php
require_once __DIR__ . '/Model.php';
class CalificacionModel extends Model {

    public function calcularYGuardar(int $examenId, int $estudianteId): array {
        // 1. Obtener puntaje máximo total del examen (suma de puntos de cada pregunta)
        $sqlMax = "SELECT COALESCE(SUM(puntaje), 0) FROM `Preguntas` WHERE Examenes_id = :eid";
        $stmtMax = $this->db->prepare($sqlMax);
        $stmtMax->execute([':eid' => $examenId]);
        $puntajeMaximo = (float)$stmtMax->fetchColumn();

        // 2. Obtener respuestas, sincronizar puntos de preguntas cerradas con el puntaje actual de la pregunta y verificar pendientes
        $sqlResp = "SELECT r.id, r.respuesta as respuesta_estudiante, r.puntaje_obtenido,
                           p.tipo, p.respuesta as respuesta_correcta, p.puntaje
                    FROM `Respuestas` r
                    INNER JOIN `Preguntas` p ON r.Preguntas_id = p.id
                    WHERE p.Examenes_id = :eid AND r.Estudiantes_id = :sid";
        $stmtResp = $this->db->prepare($sqlResp);
        $stmtResp->execute([':eid' => $examenId, ':sid' => $estudianteId]);
        $respuestas = $stmtResp->fetchAll();

        $sumaPuntos = 0.0;
        $hayPendientes = false;
        $stmtUpdateResp = $this->db->prepare("UPDATE `Respuestas` SET `puntaje_obtenido` = :pts WHERE `id` = :id");

        foreach ($respuestas as $r) {
            if ($r['tipo'] === 'opcion_multiple' || $r['tipo'] === 'verdadero_falso') {
                $respEst = mb_strtolower(trim((string)($r['respuesta_estudiante'] ?? '')), 'UTF-8');
                $respCorr = mb_strtolower(trim((string)($r['respuesta_correcta'] ?? '')), 'UTF-8');
                $esperado = ($respEst !== '' && $respEst === $respCorr) ? (float)$r['puntaje'] : 0.0;

                if ($r['puntaje_obtenido'] === null || abs((float)$r['puntaje_obtenido'] - $esperado) > 0.001) {
                    $stmtUpdateResp->execute([':pts' => $esperado, ':id' => $r['id']]);
                }
                $sumaPuntos += $esperado;
            } else {
                if ($r['puntaje_obtenido'] === null) {
                    $hayPendientes = true;
                } else {
                    $ptsAbierta = (float)$r['puntaje_obtenido'];
                    if ($ptsAbierta > (float)$r['puntaje']) {
                        $ptsAbierta = (float)$r['puntaje'];
                        $stmtUpdateResp->execute([':pts' => $ptsAbierta, ':id' => $r['id']]);
                    }
                    $sumaPuntos += $ptsAbierta;
                }
            }
        }

        // La calificación final es el total de puntos obtenidos sobre el puntaje total de las preguntas del examen
        $notaFinal = round($sumaPuntos, 2);
        if ($notaFinal > $puntajeMaximo) $notaFinal = $puntajeMaximo;
        if ($notaFinal < 0) $notaFinal = 0.0;

        $estado = $hayPendientes ? 'pendiente' : 'calificado';

        // 3. Insertar o actualizar en Calificaciones
        $sqlUpsert = "INSERT INTO `Calificaciones` (`calificaciones`, `Fecha`, `Examenes_id`, `Estudiantes_id`, `estado`)
                      VALUES (:nota, CURDATE(), :eid, :sid, :est)
                      ON DUPLICATE KEY UPDATE `calificaciones` = VALUES(`calificaciones`), `Fecha` = CURDATE(), `estado` = VALUES(`estado`)";
        $stmtUpsert = $this->db->prepare($sqlUpsert);
        $stmtUpsert->execute([
            ':nota' => $notaFinal,
            ':eid'  => $examenId,
            ':sid'  => $estudianteId,
            ':est'  => $estado
        ]);

        return [
            'calificacion'     => $notaFinal,
            'estado'           => $estado,
            'puntos_obtenidos' => $sumaPuntos,
            'puntos_maximos'   => $puntajeMaximo,
            'aprobado'         => $puntajeMaximo > 0 ? ($notaFinal >= ($puntajeMaximo / 2)) : false
        ];
    }

    public function recalcularExamen(int $examenId): void {
        $sql = "SELECT DISTINCT r.Estudiantes_id
                FROM `Respuestas` r
                INNER JOIN `Preguntas` p ON r.Preguntas_id = p.id
                WHERE p.Examenes_id = :eid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':eid' => $examenId]);
        $estudiantes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($estudiantes as $estId) {
            $this->calcularYGuardar($examenId, (int)$estId);
        }
    }

    public function recalcularEstudiante(int $estudianteId): void {
        $sql = "SELECT DISTINCT p.Examenes_id
                FROM `Respuestas` r
                INNER JOIN `Preguntas` p ON r.Preguntas_id = p.id
                WHERE r.Estudiantes_id = :sid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':sid' => $estudianteId]);
        $examenes = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($examenes as $exId) {
            $this->calcularYGuardar((int)$exId, $estudianteId);
        }
    }

    public function recalcularTodas(): void {
        $sql = "SELECT DISTINCT p.Examenes_id, r.Estudiantes_id
                FROM `Respuestas` r
                INNER JOIN `Preguntas` p ON r.Preguntas_id = p.id";
        $pares = $this->db->query($sql)->fetchAll();
        foreach ($pares as $par) {
            $this->calcularYGuardar((int)$par['Examenes_id'], (int)$par['Estudiantes_id']);
        }
    }

    public function obtenerPorExamenEstudiante(int $examenId, int $estudianteId): ?array {
        $this->calcularYGuardar($examenId, $estudianteId);
        $sql = "SELECT c.id, c.calificaciones, c.Fecha, c.Examenes_id, c.Estudiantes_id, c.estado,
                       e.nom_exam, e.resultados_publicados, e.fecha_inicio, e.fecha_fin,
                       cur.nombre as curso_nombre,
                       (SELECT COALESCE(SUM(p.puntaje), 0) FROM `Preguntas` p WHERE p.Examenes_id = e.id) as puntaje_total
                FROM `Calificaciones` c
                INNER JOIN `Examenes` e ON c.Examenes_id = e.id
                INNER JOIN `Cursos` cur ON e.Cursos_id = cur.id
                WHERE c.Examenes_id = :eid AND c.Estudiantes_id = :sid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':eid' => $examenId, ':sid' => $estudianteId]);
        $res = $stmt->fetch();
        return $res ?: null;
    }

    public function obtenerPorExamen(int $examenId): array {
        $this->recalcularExamen($examenId);
        $sql = "SELECT c.id as calificacion_id, c.calificaciones, c.Fecha, c.estado,
                       e.id as estudiante_id, e.matricula, e.grado, e.grupo,
                       u.id as usuario_id, u.nombre, u.Apellidos, u.Correo,
                       (SELECT COALESCE(SUM(p.puntaje), 0) FROM `Preguntas` p WHERE p.Examenes_id = :eid0) as puntaje_total,
                       (SELECT COUNT(*) FROM `Respuestas` r 
                        INNER JOIN `Preguntas` p ON r.Preguntas_id = p.id 
                        WHERE p.Examenes_id = :eid1 AND r.Estudiantes_id = e.id AND r.puntaje_obtenido IS NULL) as abiertas_sin_calificar
                FROM `Calificaciones` c
                INNER JOIN `Estudiantes` e ON c.Estudiantes_id = e.id
                LEFT JOIN `Usuario` u ON u.Estudiantes_id = e.id
                WHERE c.Examenes_id = :eid2
                ORDER BY u.Apellidos ASC, u.nombre ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':eid0' => $examenId, ':eid1' => $examenId, ':eid2' => $examenId]);
        return $stmt->fetchAll();
    }

    public function obtenerHistorialEstudiante(int $estudianteId, bool $soloPublicadas = true): array {
        $this->recalcularEstudiante($estudianteId);
        $sql = "SELECT c.id as calificacion_id, c.calificaciones, c.Fecha as fecha_evaluacion, c.estado as estado_revision,
                       e.id as examen_id, e.nom_exam, e.resultados_publicados,
                       (SELECT COALESCE(SUM(p.puntaje), 0) FROM `Preguntas` p WHERE p.Examenes_id = e.id) as puntaje_total,
                       cur.id as curso_id, cur.nombre as curso_nombre,
                       u_prof.nombre as profesor_nombre, u_prof.Apellidos as profesor_apellidos
                FROM `Calificaciones` c
                INNER JOIN `Examenes` e ON c.Examenes_id = e.id
                INNER JOIN `Cursos` cur ON e.Cursos_id = cur.id
                INNER JOIN `Profesor` prof ON cur.Profesor_id = prof.id
                LEFT JOIN `Usuario` u_prof ON u_prof.Profesor_id = prof.id
                WHERE c.Estudiantes_id = :sid";
        
        if ($soloPublicadas) {
            $sql .= " AND e.resultados_publicados = 1";
        }
        $sql .= " ORDER BY c.Fecha DESC, c.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':sid' => $estudianteId]);
        return $stmt->fetchAll();
    }

    public function obtenerReporteCurso(int $cursoId): array {
        $this->recalcularTodas();
        $sql = "SELECT cur.id as curso_id, cur.nombre as curso_nombre, cur.fecha_ini, cur.fecha_fin,
                       u_prof.nombre as profesor_nombre, u_prof.Apellidos as profesor_apellidos,
                       e.matricula, e.grado, e.grupo,
                       u_est.nombre as estudiante_nombre, u_est.Apellidos as estudiante_apellidos,
                       ex.id as examen_id, ex.nom_exam, ex.resultados_publicados,
                       (SELECT COALESCE(SUM(p.puntaje), 0) FROM `Preguntas` p WHERE p.Examenes_id = ex.id) as puntaje_total,
                       cal.calificaciones, cal.Fecha as fecha_calificacion, cal.estado as estado_calif
                FROM `inscripciones` i
                INNER JOIN `Cursos` cur ON i.Cursos_id = cur.id
                INNER JOIN `Estudiantes` e ON i.Estudiantes_id = e.id
                LEFT JOIN `Usuario` u_est ON u_est.Estudiantes_id = e.id
                INNER JOIN `Profesor` prof ON cur.Profesor_id = prof.id
                LEFT JOIN `Usuario` u_prof ON u_prof.Profesor_id = prof.id
                LEFT JOIN `Examenes` ex ON ex.Cursos_id = cur.id
                LEFT JOIN `Calificaciones` cal ON (cal.Examenes_id = ex.id AND cal.Estudiantes_id = e.id)
                WHERE cur.id = :cid
                ORDER BY u_est.Apellidos ASC, u_est.nombre ASC, ex.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':cid' => $cursoId]);
        return $stmt->fetchAll();
    }

    public function actualizarNotaDirecta(int $calificacionId, float $nota, string $estado = 'calificado'): bool {
        $stmt = $this->db->prepare("UPDATE `Calificaciones` SET `calificaciones` = :nota, `estado` = :est WHERE `id` = :id");
        return $stmt->execute([
            ':nota' => $nota,
            ':est'  => $estado,
            ':id'   => $calificacionId
        ]);
    }

    public function obtenerEstadisticasGenerales(): array {
        $this->recalcularTodas();

        // Calificación promedio general
        $promedioGeneral = (float)$this->db->query("SELECT COALESCE(AVG(calificaciones), 0) FROM `Calificaciones`")->fetchColumn();
        
        // Aprobados (>= mitad de puntos totales del examen) y Reprobados (< mitad de puntos totales del examen)
        $aprobados = (int)$this->db->query("
            SELECT COUNT(*) FROM `Calificaciones` cal
            WHERE cal.calificaciones >= ((SELECT COALESCE(SUM(p.puntaje), 0) FROM `Preguntas` p WHERE p.Examenes_id = cal.Examenes_id) / 2)
              AND (SELECT COALESCE(SUM(p.puntaje), 0) FROM `Preguntas` p WHERE p.Examenes_id = cal.Examenes_id) > 0
        ")->fetchColumn();
        $reprobados = (int)$this->db->query("
            SELECT COUNT(*) FROM `Calificaciones` cal
            WHERE cal.calificaciones < ((SELECT COALESCE(SUM(p.puntaje), 0) FROM `Preguntas` p WHERE p.Examenes_id = cal.Examenes_id) / 2)
               OR (SELECT COALESCE(SUM(p.puntaje), 0) FROM `Preguntas` p WHERE p.Examenes_id = cal.Examenes_id) = 0
        ")->fetchColumn();

        // Promedio por curso
        $promPorCurso = $this->db->query("SELECT cur.nombre, ROUND(AVG(cal.calificaciones), 2) as promedio, COUNT(cal.id) as total_evaluaciones
                                           FROM `Calificaciones` cal
                                           INNER JOIN `Examenes` ex ON cal.Examenes_id = ex.id
                                           INNER JOIN `Cursos` cur ON ex.Cursos_id = cur.id
                                           GROUP BY cur.id, cur.nombre")->fetchAll();

        // Distribución por rangos
        $distribucion = [
            '0-59'   => (int)$this->db->query("SELECT COUNT(*) FROM `Calificaciones` WHERE calificaciones < 60")->fetchColumn(),
            '60-69'  => (int)$this->db->query("SELECT COUNT(*) FROM `Calificaciones` WHERE calificaciones >= 60 AND calificaciones < 70")->fetchColumn(),
            '70-79'  => (int)$this->db->query("SELECT COUNT(*) FROM `Calificaciones` WHERE calificaciones >= 70 AND calificaciones < 80")->fetchColumn(),
            '80-89'  => (int)$this->db->query("SELECT COUNT(*) FROM `Calificaciones` WHERE calificaciones >= 80 AND calificaciones < 90")->fetchColumn(),
            '90-100' => (int)$this->db->query("SELECT COUNT(*) FROM `Calificaciones` WHERE calificaciones >= 90")->fetchColumn(),
        ];

        return [
            'promedio_general' => round($promedioGeneral, 2),
            'aprobados'        => $aprobados,
            'reprobados'       => $reprobados,
            'promedio_cursos'  => $promPorCurso,
            'distribucion'     => $distribucion
        ];
    }

    public function obtenerEstadisticasProfesor(int $profesorId): array {
        $this->recalcularTodas();

        // 1. Resumen de Aprobados (>= 50% del puntaje total del examen), Reprobados (< 50%) y Promedio General del Profesor
        $sqlResumen = "SELECT COALESCE(AVG(cal.calificaciones), 0) as promedio_general,
                              COALESCE(SUM(CASE WHEN cal.calificaciones >= ((SELECT COALESCE(SUM(p.puntaje), 0) FROM `Preguntas` p WHERE p.Examenes_id = ex.id) / 2) AND (SELECT COALESCE(SUM(p.puntaje), 0) FROM `Preguntas` p WHERE p.Examenes_id = ex.id) > 0 THEN 1 ELSE 0 END), 0) as aprobados,
                              COALESCE(SUM(CASE WHEN cal.calificaciones < ((SELECT COALESCE(SUM(p.puntaje), 0) FROM `Preguntas` p WHERE p.Examenes_id = ex.id) / 2) OR (SELECT COALESCE(SUM(p.puntaje), 0) FROM `Preguntas` p WHERE p.Examenes_id = ex.id) = 0 THEN 1 ELSE 0 END), 0) as reprobados
                       FROM `Calificaciones` cal
                       INNER JOIN `Examenes` ex ON cal.Examenes_id = ex.id
                       INNER JOIN `Cursos` cur ON ex.Cursos_id = cur.id
                       WHERE cur.Profesor_id = :pid";
        $stmtRes = $this->db->prepare($sqlResumen);
        $stmtRes->execute([':pid' => $profesorId]);
        $resumen = $stmtRes->fetch();

        $aprobados = (int)($resumen['aprobados'] ?? 0);
        $reprobados = (int)($resumen['reprobados'] ?? 0);
        $totalEval = $aprobados + $reprobados;
        $porcentajeAprobados = $totalEval > 0 ? round(($aprobados / $totalEval) * 100, 1) : 0;

        // 2. Promedio de calificaciones por curso del profesor
        $sqlCursos = "SELECT cur.id, cur.nombre,
                             COALESCE(ROUND(AVG(cal.calificaciones), 2), 0) as promedio,
                             COUNT(cal.id) as total_evaluaciones
                      FROM `Cursos` cur
                      LEFT JOIN `Examenes` ex ON ex.Cursos_id = cur.id
                      LEFT JOIN `Calificaciones` cal ON cal.Examenes_id = ex.id
                      WHERE cur.Profesor_id = :pid
                      GROUP BY cur.id, cur.nombre
                      ORDER BY cur.id ASC";
        $stmtCursos = $this->db->prepare($sqlCursos);
        $stmtCursos->execute([':pid' => $profesorId]);
        $promPorCurso = $stmtCursos->fetchAll();

        // 3. Resultados por examen del profesor
        $sqlExamenes = "SELECT ex.id, ex.nom_exam, cur.nombre as curso_nombre,
                               (SELECT COALESCE(SUM(p.puntaje), 0) FROM `Preguntas` p WHERE p.Examenes_id = ex.id) as puntaje_total,
                               COALESCE(ROUND(AVG(cal.calificaciones), 2), 0) as promedio,
                               COALESCE(ROUND(MAX(cal.calificaciones), 2), 0) as maxima,
                               COALESCE(ROUND(MIN(cal.calificaciones), 2), 0) as minima,
                               COALESCE(SUM(CASE WHEN cal.calificaciones >= ((SELECT COALESCE(SUM(p.puntaje), 0) FROM `Preguntas` p WHERE p.Examenes_id = ex.id) / 2) AND (SELECT COALESCE(SUM(p.puntaje), 0) FROM `Preguntas` p WHERE p.Examenes_id = ex.id) > 0 THEN 1 ELSE 0 END), 0) as aprobados,
                               COALESCE(SUM(CASE WHEN cal.calificaciones < ((SELECT COALESCE(SUM(p.puntaje), 0) FROM `Preguntas` p WHERE p.Examenes_id = ex.id) / 2) OR (SELECT COALESCE(SUM(p.puntaje), 0) FROM `Preguntas` p WHERE p.Examenes_id = ex.id) = 0 THEN 1 ELSE 0 END), 0) as reprobados,
                               COUNT(cal.id) as total_evaluaciones
                        FROM `Examenes` ex
                        INNER JOIN `Cursos` cur ON ex.Cursos_id = cur.id
                        LEFT JOIN `Calificaciones` cal ON cal.Examenes_id = ex.id
                        WHERE cur.Profesor_id = :pid
                        GROUP BY ex.id, ex.nom_exam, cur.nombre
                        ORDER BY ex.id ASC";
        $stmtExam = $this->db->prepare($sqlExamenes);
        $stmtExam->execute([':pid' => $profesorId]);
        $resultadosExamenes = $stmtExam->fetchAll();

        return [
            'promedio_general'     => round((float)($resumen['promedio_general'] ?? 0), 2),
            'aprobados'            => $aprobados,
            'reprobados'           => $reprobados,
            'porcentaje_aprobados' => $porcentajeAprobados,
            'promedio_cursos'      => $promPorCurso,
            'resultados_examenes'  => $resultadosExamenes
        ];
    }

    public function obtenerReporteEstudiante(int $estudianteId, ?int $profesorId = null, ?int $cursoId = null): array {
        $this->recalcularEstudiante($estudianteId);
        $sql = "SELECT cur.id as curso_id, cur.nombre as curso_nombre,
                       u_prof.nombre as profesor_nombre, u_prof.Apellidos as profesor_apellidos,
                       ex.id as examen_id, ex.nom_exam, ex.Fecha as fecha_examen, ex.resultados_publicados,
                       (SELECT COALESCE(SUM(p.puntaje), 0) FROM `Preguntas` p WHERE p.Examenes_id = ex.id) as puntaje_total,
                       cal.calificaciones, cal.Fecha as fecha_calificacion, cal.estado as estado_calif
                FROM `inscripciones` i
                INNER JOIN `Cursos` cur ON i.Cursos_id = cur.id
                INNER JOIN `Profesor` prof ON cur.Profesor_id = prof.id
                LEFT JOIN `Usuario` u_prof ON u_prof.Profesor_id = prof.id
                LEFT JOIN `Examenes` ex ON ex.Cursos_id = cur.id
                LEFT JOIN `Calificaciones` cal ON (cal.Examenes_id = ex.id AND cal.Estudiantes_id = i.Estudiantes_id)
                WHERE i.Estudiantes_id = :sid";

        $params = [':sid' => $estudianteId];

        if ($profesorId !== null && $profesorId > 0) {
            $sql .= " AND cur.Profesor_id = :pid";
            $params[':pid'] = $profesorId;
        }

        if ($cursoId !== null && $cursoId > 0) {
            $sql .= " AND cur.id = :cid";
            $params[':cid'] = $cursoId;
        }

        $sql .= " ORDER BY cur.nombre ASC, ex.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
