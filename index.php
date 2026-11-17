<?php
require_once __DIR__ . '/config/config.php';
// Obtener controlador y acción
$controllerParam = $_GET['controller'] ?? '';
$actionParam = $_GET['action'] ?? '';
// Si no se especifica controlador, determinar según autenticación
if (empty($controllerParam)) {
    if (!isAuthenticated()) {
        $controllerParam = 'auth';
        $actionParam = 'login';
    } else {
        $rol = getAuthRoleId();
        if ($rol === ROL_ADMIN) {
            $controllerParam = 'admin';
            $actionParam = 'dashboard';
        } else if ($rol === ROL_PROFESOR) {
            $controllerParam = 'profesor';
            $actionParam = 'dashboard';
        } else {
            $controllerParam = 'estudiante';
            $actionParam = 'dashboard';
        }
    }
}
// Mapeo seguro de controladores
$controllersMap = [
    'auth'        => 'AuthController',
    'admin'       => 'AdminController',
    'profesor'    => 'ProfesorController',
    'estudiante'  => 'EstudianteController',
    'estadistica' => 'EstadisticaController',
    'reporte'     => 'ReporteController'];

if (!array_key_exists($controllerParam, $controllersMap)) {
    http_response_code(404);
    die("Controlador no válido o no encontrado: " . e($controllerParam));
}

$className = $controllersMap[$controllerParam];
$controllerFile = __DIR__ . '/controllers/' . $className . '.php';

if (!file_exists($controllerFile)) {
    http_response_code(404);
    die("Archivo de controlador no encontrado: " . e($className));
}

require_once $controllerFile;

if (!class_exists($className)) {
    http_response_code(500);
    die("Clase controladora no definida: " . e($className));
}

$controllerInstance = new $className();

// Acción por defecto según el controlador
if (empty($actionParam)) {
    $actionParam = ($controllerParam === 'auth') ? 'login' : 'dashboard';
}

if (!method_exists($controllerInstance, $actionParam)) {
    http_response_code(404);
    die("Acción no válida en el controlador: " . e($actionParam));
}

// Ejecutar la acción
$controllerInstance->$actionParam();
