<?php
require_once __DIR__ . '/Model.php';

class ConfiguracionModel extends Model {

    public function obtener(string $clave, $default = null) {
        $stmt = $this->db->prepare("SELECT `valor` FROM `configuracion` WHERE `clave` = :k");
        $stmt->execute([':k' => $clave]);
        $val = $stmt->fetchColumn();
        return ($val !== false) ? $val : $default;
    }

    public function guardar(string $clave, string $valor, ?string $descripcion = null): bool {
        $stmt = $this->db->prepare("INSERT INTO `configuracion` (`clave`, `valor`, `descripcion`) 
                                    VALUES (:k, :v, :d) 
                                    ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`), `descripcion` = COALESCE(
                                    VALUES(`descripcion`), `descripcion`)");
        return $stmt->execute([
            ':k' => $clave,
            ':v' => $valor,
            ':d' => $descripcion
        ]);
    }

    public function obtenerTodas(): array {
        return $this->db->query("SELECT `clave`, `valor`, `descripcion` FROM `configuracion`")->fetchAll();
    }
}
