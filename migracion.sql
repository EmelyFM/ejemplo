-- ==============================================================================
-- SISTEMA DE GESTIÓN DE EXÁMENES Y RESULTADOS ACADÉMICOS
-- ARCHIVO DE MIGRACIÓN Y ADECUACIÓN INDISPENSABLE
-- Conserva intacta la estructura original de 'gestion.sql'
-- ==============================================================================

USE `gestion_examenes`;

-- 1. Ampliación de 'Examenes': control de fechas/horas de apertura, cierre, duración y publicación
ALTER TABLE `Examenes`
    ADD COLUMN `fecha_inicio` DATETIME NULL AFTER `Fecha`,
    ADD COLUMN `fecha_fin` DATETIME NULL AFTER `fecha_inicio`,
    ADD COLUMN `duracion_minutos` INT NOT NULL DEFAULT 60 AFTER `fecha_fin`,
    ADD COLUMN `resultados_publicados` TINYINT(1) NOT NULL DEFAULT 0 AFTER `duracion_minutos`,
    ADD COLUMN `estado` ENUM('borrador', 'activo', 'finalizado') NOT NULL DEFAULT 'activo' AFTER `resultados_publicados`;

-- 2. Ampliación de 'Preguntas': almacenamiento de opciones para preguntas de opción múltiple e imagen opcional
ALTER TABLE `Preguntas`
    ADD COLUMN `imagen` VARCHAR(255) NULL DEFAULT NULL AFTER `pregunta`,
    ADD COLUMN `opciones` TEXT NULL AFTER `respuesta`;

-- 3. Corrección indispensable de 'Respuestas': vincular la respuesta con el Estudiante que la emitió
-- Originalmente 'Respuestas' solo tenía (id, respuesta, fecha_envio, Preguntas_id) sin referencia al alumno.
ALTER TABLE `Respuestas`
    ADD COLUMN `Estudiantes_id` INT NOT NULL AFTER `Preguntas_id`,
    ADD COLUMN `puntaje_obtenido` DECIMAL(5,2) NULL DEFAULT NULL AFTER `Estudiantes_id`,
    ADD COLUMN `retroalimentacion` VARCHAR(255) NULL AFTER `puntaje_obtenido`,
    ADD CONSTRAINT `fk_respuestas_estudiantes` FOREIGN KEY (`Estudiantes_id`) REFERENCES `Estudiantes`(`id`) ON DELETE CASCADE;

-- 4. Corrección indispensable de 'Calificaciones': vincular la calificación al Estudiante
-- Originalmente 'Calificaciones' solo tenía (id, calificaciones, Fecha, Examenes_id) sin referencia al alumno.
ALTER TABLE `Calificaciones`
    ADD COLUMN `Estudiantes_id` INT NOT NULL AFTER `Examenes_id`,
    ADD COLUMN `estado` ENUM('pendiente', 'calificado') NOT NULL DEFAULT 'calificado' AFTER `Estudiantes_id`,
    ADD CONSTRAINT `fk_calificaciones_estudiantes` FOREIGN KEY (`Estudiantes_id`) REFERENCES `Estudiantes`(`id`) ON DELETE CASCADE,
    ADD UNIQUE KEY `uq_examen_estudiante` (`Examenes_id`, `Estudiantes_id`);

-- 5. Tabla complementaria de configuración general (escala y mínimo aprobatorio configurable)
CREATE TABLE IF NOT EXISTS `configuracion` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `clave` VARCHAR(50) NOT NULL UNIQUE,
    `valor` VARCHAR(255) NOT NULL,
    `descripcion` VARCHAR(255) NULL
);

INSERT INTO `configuracion` (`clave`, `valor`, `descripcion`) VALUES
('escala_maxima', '100', 'Escala máxima de calificación'),
('minimo_aprobatorio', '70', 'Calificación mínima para aprobar un examen'),
('nombre_institucion', 'Instituto Tecnológico Académico', 'Nombre oficial para reportes')
ON DUPLICATE KEY UPDATE `valor` = VALUES(`valor`);
