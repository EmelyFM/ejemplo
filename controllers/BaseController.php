<?php
require_once __DIR__ . '/../config/config.php';
abstract class BaseController {

    protected function requireAuth(): void {
        if (!isAuthenticated()) {
            if ($this->isAjaxOrJson()) {
                $this->json(false, "Sesión no válida o expirada. Por favor inicie sesión nuevamente.", [], 401);
            } else {
                header("Location: " . BASE_URL . "/index.php?controller=auth&action=login");
                exit;
            }
        }
    }

    protected function requireRole(int ...$rolesPermitidos): void {
        $this->requireAuth();
        $rolActual = getAuthRoleId();
        if (!in_array($rolActual, $rolesPermitidos)) {
            if ($this->isAjaxOrJson()) {
                $this->json(false, "Acceso denegado: no cuenta con permisos suficientes para esta acción.", [], 403);
            } else {
                http_response_code(403);
                $this->renderView('errors/403.php', ['mensaje' => 'Acceso denegado']);
                exit;
            }
        }
    }

    protected function json(bool $success, string $message, $data = [], int $httpCode = 200): void {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data'    => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function getRequestData(): array {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);
            return is_array($data) ? $data : [];
        }
        return $_POST;
    }

    protected function validateCsrfHeaderOrPost(): void {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
        if (empty($token)) {
            $data = $this->getRequestData();
            $token = $data['csrf_token'] ?? '';
        }
        if (!validateCsrfToken($token)) {
            $this->json(false, "Token de seguridad CSRF inválido o expirado. Recargue la página.", [], 403);
        }
    }

    protected function isAjaxOrJson(): bool {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
            || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
    }

    protected function renderView(string $viewPath, array $data = []): void {
        extract($data);
        $fullPath = __DIR__ . '/../views/' . ltrim($viewPath, '/');
        if (!file_exists($fullPath)) {
            http_response_code(404);
            die("Vista no encontrada: " . e($viewPath));
        }
        require $fullPath;
        exit;
    }
}
