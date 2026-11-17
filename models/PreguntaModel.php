<?php
require_once __DIR__ . '/Model.php';

class PreguntaModel extends Model {

    public function __construct() {
        parent::__construct();
        $this->asegurarColumnaImagen();
    }

    private function asegurarColumnaImagen(): void {
        try {
            $chk = $this->db->query("SHOW COLUMNS FROM `Preguntas` LIKE 'imagen'")->fetch();
            if (!$chk) {
                $this->db->exec("ALTER TABLE `Preguntas` ADD COLUMN `imagen` LONGTEXT NULL DEFAULT NULL AFTER `pregunta`");
            } else if (isset($chk['Type']) && stripos($chk['Type'], 'longtext') === false) {
                $this->db->exec("ALTER TABLE `Preguntas` MODIFY COLUMN `imagen` LONGTEXT NULL DEFAULT NULL");
            }
        } catch (\Throwable $t) {}
    }

    public function obtenerPorId(int $id): ?array {
        $sql = "SELECT p.id, p.pregunta, p.imagen, p.tipo, p.respuesta, p.opciones, p.puntaje, p.Examenes_id
                FROM `Preguntas` p
                WHERE p.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        $res = $stmt->fetch();
        if ($res && !empty($res['opciones'])) {
            $res['opciones_array'] = json_decode($res['opciones'], true) ?: [];
        }
        return $res ?: null;
    }

    public function obtenerPorExamen(int $examenId, bool $incluirRespuestasCorrectas = true): array {
        if ($incluirRespuestasCorrectas) {
            $sql = "SELECT p.id, p.pregunta, p.imagen, p.tipo, p.respuesta, p.opciones, p.puntaje, p.Examenes_id
                    FROM `Preguntas` p
                    WHERE p.Examenes_id = :eid
                    ORDER BY p.id ASC";
        } else {
            // Protección de respuestas para estudiantes
            $sql = "SELECT p.id, p.pregunta, p.imagen, p.tipo, '' as respuesta, p.opciones, p.puntaje, p.Examenes_id
                    FROM `Preguntas` p
                    WHERE p.Examenes_id = :eid
                    ORDER BY p.id ASC";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':eid' => $examenId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            $r['opciones_array'] = !empty($r['opciones']) ? (json_decode($r['opciones'], true) ?: []) : [];
        }
        return $rows;
    }

    public function crear(string $pregunta, string $tipo, string $respuesta, $opciones, int $puntaje, int $examenId, ?string $imagen = null): int {
        $opcionesJson = is_array($opciones) ? json_encode(array_values(array_filter(array_map('trim', $opciones)))) : $opciones;
        
        $sql = "INSERT INTO `Preguntas` (`pregunta`, `imagen`, `tipo`, `respuesta`, `opciones`, `puntaje`, `Examenes_id`)
                VALUES (:pre, :img, :tip, :res, :opc, :pun, :eid)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':pre' => trim($pregunta),
            ':img' => $imagen,
            ':tip' => trim($tipo),
            ':res' => trim($respuesta),
            ':opc' => $opcionesJson,
            ':pun' => $puntaje,
            ':eid' => $examenId
        ]);
        $nuevoId = (int)$this->db->lastInsertId();

        // Si existe la tabla complementaria opciones_pregunta, poblarla también para compatibilidad
        try {
            $chkTable = $this->db->query("SHOW TABLES LIKE 'opciones_pregunta'")->fetch();
            if ($chkTable && is_array($opciones) && count($opciones) > 0) {
                $stmtOpt = $this->db->prepare("INSERT INTO `opciones_pregunta` (`Preguntas_id`, `numero_opcion`, `texto_opcion`, `es_correcta`) VALUES (:pid, :num, :txt, :corr)");
                foreach ($opciones as $i => $opt) {
                    $optTxt = trim((string)$opt);
                    if ($optTxt === '') continue;
                    $esCorr = (mb_strtolower($optTxt, 'UTF-8') === mb_strtolower(trim($respuesta), 'UTF-8')) ? 1 : 0;
                    $stmtOpt->execute([
                        ':pid' => $nuevoId,
                        ':num' => $i + 1,
                        ':txt' => $optTxt,
                        ':corr' => $esCorr
                    ]);
                }
            }
        } catch (\Throwable $t) {}

        return $nuevoId;
    }

    public function actualizar(int $id, string $pregunta, string $tipo, string $respuesta, $opciones, int $puntaje, ?string $imagen = null): bool {
        $opcionesJson = is_array($opciones) ? json_encode(array_values(array_filter(array_map('trim', $opciones)))) : $opciones;
        
        if ($imagen !== null) {
            $sql = "UPDATE `Preguntas`
                    SET `pregunta` = :pre, `imagen` = :img, `tipo` = :tip, `respuesta` = :res, `opciones` = :opc, `puntaje` = :pun
                    WHERE `id` = :id";
            $params = [
                ':pre' => trim($pregunta),
                ':img' => $imagen,
                ':tip' => trim($tipo),
                ':res' => trim($respuesta),
                ':opc' => $opcionesJson,
                ':pun' => $puntaje,
                ':id'  => $id
            ];
        } else {
            $sql = "UPDATE `Preguntas`
                    SET `pregunta` = :pre, `tipo` = :tip, `respuesta` = :res, `opciones` = :opc, `puntaje` = :pun
                    WHERE `id` = :id";
            $params = [
                ':pre' => trim($pregunta),
                ':tip' => trim($tipo),
                ':res' => trim($respuesta),
                ':opc' => $opcionesJson,
                ':pun' => $puntaje,
                ':id'  => $id
            ];
        }
        $stmt = $this->db->prepare($sql);
        $ok = $stmt->execute($params);

        if ($ok && is_array($opciones)) {
            try {
                $chkTable = $this->db->query("SHOW TABLES LIKE 'opciones_pregunta'")->fetch();
                if ($chkTable) {
                    $this->db->prepare("DELETE FROM `opciones_pregunta` WHERE `Preguntas_id` = ?")->execute([$id]);
                    $stmtOpt = $this->db->prepare("INSERT INTO `opciones_pregunta` (`Preguntas_id`, `numero_opcion`, `texto_opcion`, `es_correcta`) VALUES (:pid, :num, :txt, :corr)");
                    foreach ($opciones as $i => $opt) {
                        $optTxt = trim((string)$opt);
                        if ($optTxt === '') continue;
                        $esCorr = (mb_strtolower($optTxt, 'UTF-8') === mb_strtolower(trim($respuesta), 'UTF-8')) ? 1 : 0;
                        $stmtOpt->execute([
                            ':pid' => $id,
                            ':num' => $i + 1,
                            ':txt' => $optTxt,
                            ':corr' => $esCorr
                        ]);
                    }
                }
            } catch (\Throwable $t) {}
        }

        return $ok;
    }

    public function tieneRespuestas(int $id): bool {
        $chk = $this->db->prepare("SELECT COUNT(*) FROM `Respuestas` WHERE Preguntas_id = :pid");
        $chk->execute([':pid' => $id]);
        return ((int)$chk->fetchColumn()) > 0;
    }

    public function eliminar(int $id): bool {
        if ($this->tieneRespuestas($id)) {
            throw new Exception("No se puede eliminar la pregunta porque ya existen respuestas de alumnos asociadas.");
        }

        try {
            $chkTable = $this->db->query("SHOW TABLES LIKE 'opciones_pregunta'")->fetch();
            if ($chkTable) {
                $this->db->prepare("DELETE FROM `opciones_pregunta` WHERE `Preguntas_id` = ?")->execute([$id]);
            }
        } catch (\Throwable $t) {}

        $stmt = $this->db->prepare("DELETE FROM `Preguntas` WHERE `id` = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function contarPorExamen(int $examenId): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM `Preguntas` WHERE Examenes_id = :eid");
        $stmt->execute([':eid' => $examenId]);
        return (int)$stmt->fetchColumn();
    }
}
