<?php
class Conexion {
    private $servidor = "localhost";
    private $bd = "gestion_examenes";
    private $usuario = "root";
    private $password = ""; // Contraseña directa
    protected $conexion = null;
    protected ?PDO $pdo = null;

    /**
     * Conexión MySQL mediante mysqli_connect en el puerto 3307
     */
    public function conexion() {
        if ($this->conexion === null) {
            // Asegurar que la contraseña es un string puro (sin objetos)
            $password = strval($this->password);

            // Conectar a MySQL en puerto 3307
            $conexion = @mysqli_connect($this->servidor, $this->usuario, $password, $this->bd, 3307);

            // Reintento de contingencia en puerto 3306 si 3307 no está activo en el entorno
            if (!$conexion) {
                $conexion = @mysqli_connect($this->servidor, $this->usuario, $password, $this->bd, 3306);
            }
            if (!$conexion) {
                $conexion = @mysqli_connect($this->servidor, $this->usuario, "12345", $this->bd, 3306);
            }
            if (!$conexion && $this->bd !== "asistencias_isc") {
                $conexion = @mysqli_connect($this->servidor, $this->usuario, "12345", "asistencias_isc", 3306);
            }

            if (!$conexion) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                die(json_encode([
                    'success' => false,
                    'message' => "Error al conectar a MySQL: " . mysqli_connect_error()
                ], JSON_UNESCAPED_UNICODE));
            }

            mysqli_set_charset($conexion, "utf8mb4");
            $this->conexion = $conexion;
        }

        return $this->conexion;
    }

    /**
     * Alias de conexion() para compatibilidad
     */
    public function conectar() {
        return $this->conexion();
    }

    /**
     * Conexión PDO requerida para los Modelos MVC (Consultas preparadas y transacciones)
     */
    public function conectarPDO(): PDO {
        if ($this->pdo === null) {
            $servidor = $this->servidor;
            $usuario = $this->usuario;
            $password = strval($this->password);
            $bd = $this->bd;

            // Intentos ordenados con puerto 3307 prioritario
            $intentos = [
                ['host' => $servidor, 'port' => 3307, 'pass' => $password, 'db' => $bd],
                ['host' => $servidor, 'port' => 3306, 'pass' => $password, 'db' => $bd],
                ['host' => $servidor, 'port' => 3306, 'pass' => '12345', 'db' => $bd],
                ['host' => $servidor, 'port' => 3307, 'pass' => $password, 'db' => 'asistencias_isc'],
            ];

            $lastException = null;
            foreach ($intentos as $cfg) {
                try {
                    $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['db']};charset=utf8mb4";
                    $this->pdo = new PDO($dsn, $usuario, $cfg['pass'], [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]);
                    $this->bd = $cfg['db'];
                    $this->asegurarEstructura($this->pdo);
                    break;
                } catch (PDOException $e) {
                    $lastException = $e;
                }
            }

            if ($this->pdo === null) {
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                die(json_encode([
                    'success' => false,
                    'message' => "Error crítico de conexión PDO a MySQL ({$bd}): " . ($lastException ? $lastException->getMessage() : 'Desconocido')
                ], JSON_UNESCAPED_UNICODE));
            }
        }

        return $this->pdo;
    }

    /**
     * Asegura de manera transparente que existan las columnas indispensables
     */
    private function asegurarEstructura(PDO $pdo): void {
        try {
            $colsEx = $pdo->query("SHOW COLUMNS FROM `examenes` LIKE 'resultados_publicados'")->fetchAll();
            if (empty($colsEx)) {
                $pdo->exec("ALTER TABLE `examenes` ADD COLUMN `resultados_publicados` TINYINT(1) NOT NULL DEFAULT 0;");
            }
            $colsPreg = $pdo->query("SHOW COLUMNS FROM `preguntas` LIKE 'opciones'")->fetchAll();
            if (empty($colsPreg)) {
                $pdo->exec("ALTER TABLE `preguntas` ADD COLUMN `opciones` TEXT NULL;");
            }
            $colsCal = $pdo->query("SHOW COLUMNS FROM `calificaciones` LIKE 'estado'")->fetchAll();
            if (empty($colsCal)) {
                $pdo->exec("ALTER TABLE `calificaciones` ADD COLUMN `estado` VARCHAR(20) DEFAULT 'calificado';");
                $pdo->exec("UPDATE `calificaciones` SET `estado` = `estado_revision` WHERE `estado_revision` IS NOT NULL;");
            }
        } catch (\Throwable $t) {
            // No interrumpir si la tabla aún se está creando
        }
    }

    /**
     * Instancia singleton estática para los modelos
     */
    public static function getConexion(): PDO {
        static $instancia = null;
        if ($instancia === null) {
            $instancia = new self();
        }
        return $instancia->conectarPDO();
    }

    /**
     * Cierra las conexiones activas
     */
    public function desconectar(): void {
        if ($this->conexion !== null) {
            mysqli_close($this->conexion);
            $this->conexion = null;
        }
        $this->pdo = null;
    }
}
?>
