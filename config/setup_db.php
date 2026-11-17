<?php
$host = '127.0.0.1';
$port = 3306;
$passwords = ['12345', '', 'root'];
$user = 'root';
$pdo = null;
$connectedPass = '';

foreach ($passwords as $pwd) {
    try {
        $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pwd, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $connectedPass = $pwd;
        break;
    } catch (PDOException $e) {
        // try next
     }
}
if (!$pdo) {
    die("Error crítico: No se pudo conectar al servidor MySQL en {$host}:{$port} con ninguna credencial conocida.\n");
}
echo "[OK] Conexión establecida con MySQL (Usuario: {$user}, Puerto: {$port})\n";
// 1. Crear base de datos si no existe
$pdo->exec("CREATE DATABASE IF NOT EXISTS `gestion_examenes` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
$pdo->exec("USE `gestion_examenes`;");
echo "[OK] Base de datos 'gestion_examenes' lista.\n";
// 2. Ejecutar esquema original si las tablas no existen
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('Roles', $tables)) {
    echo "[INFO] Importando tablas base desde gestion.sql...\n";
    $sqlOriginal = file_get_contents(__DIR__ . '/../gestion.sql'); 
    // Remover CREATE DATABASE / USE si existen para evitar conflictos
    $sqlOriginal = preg_replace('/CREATE\s+DATABASE[^\n;]+;/i', '', $sqlOriginal);
    $sqlOriginal = preg_replace('/USE\s+[^\n;]+;/i', '', $sqlOriginal);

    $statements = array_filter(array_map('trim', explode(';', $sqlOriginal)));
    foreach ($statements as $stmt) {
        if (!empty($stmt)) {
            $pdo->exec($stmt);
        }
    }
    echo "[OK] Tablas base creadas exitosamente.\n";
} else {
    echo "[INFO] Las tablas base ya existen.\n";}
// 3. Ejecutar migraciones indispensables (idempotente)
echo "[INFO] Verificando y aplicando migraciones necesarias...\n";
// Verificar y agregar columnas a Examenes
$colsExamenes = $pdo->query("SHOW COLUMNS FROM `Examenes`")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('fecha_inicio', $colsExamenes)) {
    $pdo->exec("ALTER TABLE `Examenes` ADD COLUMN `fecha_inicio` DATETIME NULL AFTER `Fecha`;");
    $pdo->exec("ALTER TABLE `Examenes` ADD COLUMN `fecha_fin` DATETIME NULL AFTER `fecha_inicio`;");
    $pdo->exec("ALTER TABLE `Examenes` ADD COLUMN `duracion_minutos` INT NOT NULL DEFAULT 60 AFTER `fecha_fin`;");
    $pdo->exec("ALTER TABLE `Examenes` ADD COLUMN `resultados_publicados` TINYINT(1) NOT NULL DEFAULT 0 AFTER `duracion_minutos`;");
    $pdo->exec("ALTER TABLE `Examenes` ADD COLUMN `estado` ENUM('borrador', 'activo', 'finalizado') 
    NOT NULL DEFAULT 'activo' AFTER `resultados_publicados`;");
    echo "[OK] Columnas agregadas a 'Examenes'.\n";
}
// Verificar y agregar columna opciones e imagen a Preguntas
$colsPreguntas = $pdo->query("SHOW COLUMNS FROM `Preguntas`")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('opciones', $colsPreguntas)) {
    $pdo->exec("ALTER TABLE `Preguntas` ADD COLUMN `opciones` TEXT NULL AFTER `respuesta`;");
    echo "[OK] Columna 'opciones' agregada a 'Preguntas'.\n";
}
if (!in_array('imagen', $colsPreguntas)) {
    $pdo->exec("ALTER TABLE `Preguntas` ADD COLUMN `imagen` VARCHAR(255) NULL DEFAULT NULL AFTER `pregunta`;");
    echo "[OK] Columna 'imagen' agregada a 'Preguntas'.\n";
}
// Verificar y agregar columnas a Respuestas
$colsRespuestas = $pdo->query("SHOW COLUMNS FROM `Respuestas`")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('Estudiantes_id', $colsRespuestas)) {
    $pdo->exec("ALTER TABLE `Respuestas` ADD COLUMN `Estudiantes_id` INT NOT NULL AFTER `Preguntas_id`;");
    $pdo->exec("ALTER TABLE `Respuestas` ADD COLUMN `puntaje_obtenido` DECIMAL(5,2) NULL DEFAULT NULL AFTER `Estudiantes_id`;");
    $pdo->exec("ALTER TABLE `Respuestas` ADD COLUMN `retroalimentacion` VARCHAR(255) NULL AFTER `puntaje_obtenido`;");
    $pdo->exec("ALTER TABLE `Respuestas` ADD CONSTRAINT `fk_respuestas_estudiantes` FOREIGN KEY (`Estudiantes_id`) 
    REFERENCES `Estudiantes`(`id`) ON DELETE CASCADE;");
    echo "[OK] Columna 'Estudiantes_id' y puntuación agregada a 'Respuestas'.\n";
}
// Verificar y agregar columnas a Calificaciones
$colsCalif = $pdo->query("SHOW COLUMNS FROM `Calificaciones`")->fetchAll(PDO::FETCH_COLUMN);
if (!in_array('Estudiantes_id', $colsCalif)) {
    $pdo->exec("ALTER TABLE `Calificaciones` ADD COLUMN `Estudiantes_id` INT NOT NULL AFTER `Examenes_id`;");
    $pdo->exec("ALTER TABLE `Calificaciones` ADD COLUMN `estado` ENUM('pendiente', 'calificado') NOT NULL 
    DEFAULT 'calificado' AFTER `Estudiantes_id`;");
    $pdo->exec("ALTER TABLE `Calificaciones` ADD CONSTRAINT `fk_calificaciones_estudiantes` FOREIGN KEY (`Estudiantes_id`)
     REFERENCES `Estudiantes`(`id`) ON DELETE CASCADE;");
    $pdo->exec("ALTER TABLE `Calificaciones` ADD UNIQUE KEY `uq_examen_estudiante` (`Examenes_id`, `Estudiantes_id`);");
    echo "[OK] Columna 'Estudiantes_id' agregada a 'Calificaciones'.\n";
}
// Tabla configuracion
$pdo->exec("CREATE TABLE IF NOT EXISTS `configuracion` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `clave` VARCHAR(50) NOT NULL UNIQUE,
    `valor` VARCHAR(255) NOT NULL,
    `descripcion` VARCHAR(255) NULL
);");
$pdo->exec("INSERT INTO `configuracion` (`clave`, `valor`, `descripcion`) VALUES
    ('escala_maxima', '100', 'Escala máxima de calificación'),
    ('minimo_aprobatorio', '70', 'Calificación mínima para aprobar un examen'),
    ('nombre_institucion', 'Instituto Tecnológico Académico', 'Nombre oficial para reportes')
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`);");
// 4. Seeding inicial de Roles
$pdo->exec("INSERT IGNORE INTO `Roles` (`id`, `Rol`) VALUES 
    (1, 'Administrador'),
    (2, 'Profesor'),
    (3, 'Estudiante');");
// 5. Seeding de Usuarios de prueba
$passHashAdmin = password_hash('admin123', PASSWORD_BCRYPT);
$passHashProf = password_hash('profesor123', PASSWORD_BCRYPT);
$passHashEst = password_hash('estudiante123', PASSWORD_BCRYPT);
// Admin
$stmt = $pdo->prepare("SELECT id FROM `Usuario` WHERE `Correo` = ?");
$stmt->execute(['admin@sistema.com']);
if (!$stmt->fetch()) {
    $ins = $pdo->prepare("INSERT INTO `Usuario` (`nombre`, `Apellidos`, `Correo`, `Contraseña`, `Roles_id`, `Estudiantes_id`, `Profesor_id`) VALUES (?, ?, ?, ?, 1, NULL, NULL)");
    $ins->execute(['Admin', 'Principal', 'admin@sistema.com', $passHashAdmin]);
    echo "[OK] Usuario Administrador creado: admin@sistema.com / admin123\n";
}
// Profesor
$stmt->execute(['profesor@sistema.com']);
if (!$stmt->fetch()) {
    $pdo->exec("INSERT INTO `Profesor` (`especialidad`) VALUES ('Ingeniería de Software y Web');");
    $profId = $pdo->lastInsertId();
    $ins = $pdo->prepare("INSERT INTO `Usuario` (`nombre`, `Apellidos`, `Correo`, `Contraseña`, `Roles_id`, `Estudiantes_id`, `Profesor_id`) VALUES (?, ?, ?, ?, 2, NULL, ?)");
    $ins->execute(['Carlos', 'Mendoza Ramos', 'profesor@sistema.com', $passHashProf, $profId]);
    echo "[OK] Usuario Profesor creado: profesor@sistema.com / profesor123 (Profesor ID: {$profId})\n";}
// Obtener Profesor ID existente para crear cursos de prueba
$profId = $pdo->query("SELECT id FROM `Profesor` LIMIT 1")->fetchColumn();
// Estudiante 1
$stmt->execute(['estudiante@sistema.com']);
if (!$stmt->fetch()) {
    $pdo->exec("INSERT INTO `Estudiantes` (`matricula`, `grado`, `grupo`) VALUES ('EST-2026-001', 7, 'A');");
    $estId1 = $pdo->lastInsertId();
    $ins = $pdo->prepare("INSERT INTO `Usuario` (`nombre`, `Apellidos`, `Correo`, `Contraseña`, `Roles_id`, `Estudiantes_id`, `Profesor_id`) VALUES (?, ?, ?, ?, 3, ?, NULL)");
    $ins->execute(['Emely', 'Flores Martínez', 'estudiante@sistema.com', $passHashEst, $estId1]);
    echo "[OK] Usuario Estudiante 1 creado: estudiante@sistema.com / estudiante123 (Estudiante ID: {$estId1})\n";
}
// Estudiante 2
$stmt->execute(['maria@sistema.com']);
if (!$stmt->fetch()) {
    $pdo->exec("INSERT INTO `Estudiantes` (`matricula`, `grado`, `grupo`) VALUES ('EST-2026-002', 7, 'A');");
    $estId2 = $pdo->lastInsertId();
    $ins = $pdo->prepare("INSERT INTO `Usuario` (`nombre`, `Apellidos`, `Correo`, `Contraseña`, `Roles_id`, `Estudiantes_id`, `Profesor_id`) VALUES (?, ?, ?, ?, 3, ?, NULL)");
    $ins->execute(['María', 'López García', 'maria@sistema.com', $passHashEst, $estId2]);
    echo "[OK] Usuario Estudiante 2 creado: maria@sistema.com / estudiante123 (Estudiante ID: {$estId2})\n";
}
// Cursos de prueba
if ($profId) {
    $curCount = $pdo->query("SELECT COUNT(*) FROM `Cursos`")->fetchColumn();
    if ($curCount == 0) {
        $pdo->exec("INSERT INTO `Cursos` (`nombre`, `fecha_ini`, `fecha_fin`, `Profesor_id`) VALUES
            ('Programación Web Avanzada', '2026-08-15', '2026-12-20', {$profId}),
            ('Arquitectura MVC en PHP', '2026-08-15', '2026-12-20', {$profId});");
        echo "[OK] Cursos de prueba creados.\n";
    }
    $cursoId = $pdo->query("SELECT id FROM `Cursos` LIMIT 1")->fetchColumn();
    $estudiantes = $pdo->query("SELECT id FROM `Estudiantes`")->fetchAll(PDO::FETCH_COLUMN);

    // Inscribir estudiantes
    foreach ($estudiantes as $eId) {
        $pdo->exec("INSERT IGNORE INTO `inscripciones` (`fecha`, `Cursos_id`, `Estudiantes_id`) VALUES (CURDATE(), {$cursoId}, {$eId});");
    }
    echo "[OK] Inscripciones de prueba creadas.\n";

    // Examen de prueba
    $examCount = $pdo->query("SELECT COUNT(*) FROM `Examenes`")->fetchColumn();
    if ($examCount == 0) {
        $pdo->exec("INSERT INTO `Examenes` (`nom_exam`, `Fecha`, `fecha_inicio`, `fecha_fin`, `duracion_minutos`, `resultados_publicados`, `estado`, `Cursos_id`) VALUES
            ('Evaluación Parcial: Patrón MVC y PHP', CURDATE(), NOW() - INTERVAL 1 DAY, NOW() + INTERVAL 10 DAY, 60, 1, 'activo', {$cursoId});");
        $examId = $pdo->lastInsertId();

        // Preguntas
        // 1. Opción múltiple
        $opciones1 = json_encode(['Modelo', 'Vista', 'Controlador', 'Enrutador']);
        $pdo->exec("INSERT INTO `Preguntas` (`pregunta`, `tipo`, `respuesta`, `opciones`, `puntaje`, `Examenes_id`) VALUES
            ('¿Qué componente del patrón MVC contiene la lógica de negocio y el acceso a la base de datos?', 'opcion_multiple', 'Modelo', '{$opciones1}', 30, {$examId});");

        // 2. Verdadero o Falso
        $opciones2 = json_encode(['Verdadero', 'Falso']);
        $pdo->exec("INSERT INTO `Preguntas` (`pregunta`, `tipo`, `respuesta`, `opciones`, `puntaje`, `Examenes_id`) VALUES
            ('En PDO, las consultas preparadas previenen de manera efectiva los ataques de Inyección SQL.', 'verdadero_falso', 'Verdadero', '{$opciones2}', 30, {$examId});");

        // 3. Abierta
        $pdo->exec("INSERT INTO `Preguntas` (`pregunta`, `tipo`, `respuesta`, `opciones`, `puntaje`, `Examenes_id`) VALUES
            ('Explique brevemente cómo interactúa Fetch API con un controlador PHP en una arquitectura desacoplada.', 'abierta', 'Fetch envía peticiones HTTP asíncronas y el controlador procesa los datos devolviendo JSON.', NULL, 40, {$examId});");

        echo "[OK] Examen y preguntas de prueba creados.\n";
    }
}

echo "=== MIGRACIÓN Y SEMILLAS COMPLETADAS CON ÉXITO ===\n";
