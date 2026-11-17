<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

class AuthController extends BaseController {

    private UsuarioModel $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new UsuarioModel();
    }

    public function login(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $this->getRequestData();
            $correo = trim($data['correo'] ?? '');
            $password = $data['password'] ?? '';

            if (empty($correo) || empty($password)) {
                $this->json(false, "Debe ingresar su correo electrónico y su contraseña.", [], 400);
            }

            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $this->json(false, "El formato del correo electrónico es inválido.", [], 400);
            }

            $usuario = $this->usuarioModel->obtenerPorCorreo($correo);

            if (!$usuario || !password_verify($password, $usuario['Contraseña'])) {
                $this->json(false, "Credenciales incorrectas. Verifique su correo y contraseña.", [], 401);
            }

            // Regenerar sesión para prevenir fijación de sesión
            session_regenerate_id(true);

            $_SESSION['usuario_id']     = (int)$usuario['id'];
            $_SESSION['nombre']         = $usuario['nombre'];
            $_SESSION['apellidos']      = $usuario['Apellidos'];
            $_SESSION['nombre_completo']= $usuario['nombre'] . ' ' . $usuario['Apellidos'];
            $_SESSION['correo']         = $usuario['Correo'];
            $_SESSION['rol_id']         = (int)$usuario['Roles_id'];
            $_SESSION['rol_nombre']     = $usuario['rol_nombre'];
            $_SESSION['estudiante_id']  = $usuario['Estudiantes_id'] ? (int)$usuario['Estudiantes_id'] : null;
            $_SESSION['profesor_id']    = $usuario['Profesor_id'] ? (int)$usuario['Profesor_id'] : null;

            // Determinar ruta de redirección según el rol
            $redirectUrl = BASE_URL . '/index.php';
            switch ($_SESSION['rol_id']) {
                case ROL_ADMIN:
                    $redirectUrl .= '?controller=admin&action=dashboard';
                    break;
                case ROL_PROFESOR:
                    $redirectUrl .= '?controller=profesor&action=dashboard';
                    break;
                case ROL_ESTUDIANTE:
                    $redirectUrl .= '?controller=estudiante&action=dashboard';
                    break;
            }

            $this->json(true, "Bienvenido(a), " . $usuario['nombre'] . "!", [
                'redirect' => $redirectUrl,
                'rol'      => $usuario['rol_nombre']
            ]);
        }

        // Si ya está autenticado, redirigir a su panel
        if (isAuthenticated()) {
            $rolId = getAuthRoleId();
            if ($rolId === ROL_ADMIN) {
                header("Location: " . BASE_URL . "/index.php?controller=admin&action=dashboard");
            } else if ($rolId === ROL_PROFESOR) {
                header("Location: " . BASE_URL . "/index.php?controller=profesor&action=dashboard");
            } else {
                header("Location: " . BASE_URL . "/index.php?controller=estudiante&action=dashboard");
            }
            exit;
        }

        $this->renderView('auth/login.php');
    }

    public function logout(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();

        if ($this->isAjaxOrJson()) {
            $this->json(true, "Sesión finalizada correctamente.", [
                'redirect' => BASE_URL . '/index.php?controller=auth&action=login'
            ]);
        } else {
            header("Location: " . BASE_URL . "/index.php?controller=auth&action=login");
            exit;
        }
    }

    public function check(): void {
        if (isAuthenticated()) {
            $this->json(true, "Sesión activa", [
                'usuario_id' => $_SESSION['usuario_id'],
                'nombre'     => $_SESSION['nombre_completo'],
                'rol_id'     => $_SESSION['rol_id'],
                'rol'        => $_SESSION['rol_nombre'],
                'csrf_token' => getCsrfToken()
            ]);
        } else {
            $this->json(false, "No hay sesión activa", [], 401);
        }
    }
}
