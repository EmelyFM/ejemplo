<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();}

// Configuración de zona horaria y codificación
date_default_timezone_set('America/Mexico_City');
mb_internal_encoding('UTF-8');

// Constantes de Roles
define('ROL_ADMIN', 1);
define('ROL_PROFESOR', 2);
define('ROL_ESTUDIANTE', 3);

// Parámetros de calificación por defecto
define('ESCALA_MAXIMA_DEFAULT', 100.0);
define('MINIMO_APROBATORIO_DEFAULT', 70.0);

// Detección dinámica de Base URL para XAMPP
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$baseUrl = rtrim($protocol . $host . $scriptDir, '/');
// Ajustar para que siempre apunte a la raíz del proyecto Practica_MVC
if (strpos($baseUrl, 'Practica_MVC') !== false) {
    $baseUrl = preg_replace('#(/Practica_MVC).*$#', '$1', $baseUrl);
}
define('BASE_URL', $baseUrl);

function getCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];}

function validateCsrfToken(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Escapa cadenas para prevenir XSS en vistas HTML
 */
function e(?string $string): string {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

/**
 * Devuelve respuesta JSON consistente y termina la ejecución
 */
function jsonResponse(bool $success, string $message, $data = [], int $httpCode = 200): void {
    http_response_code($httpCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Verifica si el usuario actual tiene sesión activa
 */
function isAuthenticated(): bool {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Obtiene el rol del usuario autenticado
 */
function getAuthRoleId(): ?int {
    return $_SESSION['rol_id'] ?? null;
}
