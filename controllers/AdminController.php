<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/ProfesorModel.php';
require_once __DIR__ . '/../models/EstudianteModel.php';
require_once __DIR__ . '/../models/CursoModel.php';
require_once __DIR__ . '/../models/InscripcionModel.php';
require_once __DIR__ . '/../models/ExamenModel.php';
require_once __DIR__ . '/../models/RolModel.php';

class AdminController extends BaseController {

    private UsuarioModel $usuarioModel;
    private ProfesorModel $profesorModel;
    private EstudianteModel $estudianteModel;
    private CursoModel $cursoModel;
    private InscripcionModel $inscripcionModel;
    private ExamenModel $examenModel;
    private RolModel $rolModel;

    public function __construct() {
        $this->usuarioModel = new UsuarioModel();
        $this->profesorModel = new ProfesorModel();
        $this->estudianteModel = new EstudianteModel();
        $this->cursoModel = new CursoModel();
        $this->inscripcionModel = new InscripcionModel();
        $this->examenModel = new ExamenModel();
        $this->rolModel = new RolModel();
    }

    public function dashboard(): void {
        $this->requireRole(ROL_ADMIN);

        $totales = [
            'estudiantes'       => $this->estudianteModel->contar(),
            'profesores'        => $this->profesorModel->contar(),
            'cursos'            => $this->cursoModel->contar(),
            'examenes'          => $this->examenModel->contar(),
            'examenes_activos'  => $this->examenModel->contarActivos(),
            'examenes_fin'      => $this->examenModel->contarFinalizados(),
            'inscripciones'     => $this->inscripcionModel->contar()
        ];

        $this->renderView('admin/dashboard.php', [
            'titulo'  => 'Panel del Administrador',
            'totales' => $totales
        ]);
    }

    // ==========================================
    // VISTAS DEL ADMINISTRADOR
    // ==========================================

    public function usuarios(): void {
        $this->requireRole(ROL_ADMIN);
        $roles = $this->rolModel->obtenerTodos();
        $this->renderView('admin/usuarios.php', ['titulo' => 'Gestión de Usuarios', 'roles' => $roles]);
    }

    public function profesores(): void {
        $this->requireRole(ROL_ADMIN);
        $this->renderView('admin/profesores.php', ['titulo' => 'Gestión de Profesores']);
    }

    public function estudiantes(): void {
        $this->requireRole(ROL_ADMIN);
        $this->renderView('admin/estudiantes.php', ['titulo' => 'Gestión de Estudiantes']);
    }

    public function cursos(): void {
        $this->requireRole(ROL_ADMIN);
        $profesores = $this->profesorModel->obtenerTodos();
        $this->renderView('admin/cursos.php', ['titulo' => 'Gestión de Cursos', 'profesores' => $profesores]);
    }

    public function inscripciones(): void {
        $this->requireRole(ROL_ADMIN);
        $cursos = $this->cursoModel->obtenerTodos();
        $estudiantes = $this->estudianteModel->obtenerTodos();
        $this->renderView('admin/inscripciones.php', [
            'titulo'      => 'Inscripciones a Cursos',
            'cursos'      => $cursos,
            'estudiantes' => $estudiantes
        ]);
    }

    // ==========================================
    // API JSON ENDPOINTS PARA ADMINISTRACIÓN
    // ==========================================

    public function getMetricas(): void {
        $this->requireRole(ROL_ADMIN);
        $this->json(true, "Métricas obtenidas", [
            'estudiantes'       => $this->estudianteModel->contar(),
            'profesores'        => $this->profesorModel->contar(),
            'cursos'            => $this->cursoModel->contar(),
            'examenes'          => $this->examenModel->contar(),
            'examenes_activos'  => $this->examenModel->contarActivos(),
            'examenes_fin'      => $this->examenModel->contarFinalizados()
        ]);
    }

    // --- CRUD USUARIOS ---

    public function listarUsuarios(): void {
        $this->requireRole(ROL_ADMIN);
        $usuarios = $this->usuarioModel->obtenerTodos();
        $this->json(true, "Lista de usuarios", $usuarios);
    }

    public function guardarUsuario(): void {
        $this->requireRole(ROL_ADMIN);
        $this->validateCsrfHeaderOrPost();
        $data = $this->getRequestData();

        $id = !empty($data['id']) ? (int)$data['id'] : null;
        $nombre = trim($data['nombre'] ?? '');
        $apellidos = trim($data['apellidos'] ?? '');
        $correo = trim($data['correo'] ?? '');
        $password = $data['password'] ?? '';
        $rolId = (int)($data['rol_id'] ?? 0);

        if (empty($nombre) || empty($apellidos) || empty($correo) || $rolId <= 0) {
            $this->json(false, "Todos los campos obligatorios deben ser completados.", [], 400);
        }

        if (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/u', $nombre) || !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/u', $apellidos)) {
            $this->json(false, "Los campos de Nombre y Apellidos solo deben contener letras.", [], 400);
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.com$/i', $correo)) {
            $this->json(false, "El correo electrónico debe ser válido e incluir '@' y '.com'.", [], 400);
        }

        try {
            if ($id) {
                // Actualización
                $usuarioActual = $this->usuarioModel->obtenerPorId($id);
                if (!$usuarioActual) {
                    $this->json(false, "Usuario no encontrado.", [], 404);
                }

                $existente = $this->usuarioModel->obtenerPorCorreo($correo);
                if ($existente && (int)$existente['id'] !== $id) {
                    $this->json(false, "El correo electrónico ya pertenece a otro usuario.", [], 400);
                }

                $this->usuarioModel->actualizar($id, $nombre, $apellidos, $correo, !empty($password) ? $password : null, $rolId);

                if ($rolId === ROL_ESTUDIANTE && !empty($usuarioActual['Estudiantes_id'])) {
                    $matricula = trim($data['matricula'] ?? $usuarioActual['matricula'] ?? '');
                    $grado = (int)($data['grado'] ?? $usuarioActual['grado'] ?? 1);
                    $grupo = trim($data['grupo'] ?? $usuarioActual['grupo'] ?? 'A');
                    if (!empty($matricula)) {
                        $this->estudianteModel->actualizar((int)$usuarioActual['Estudiantes_id'], $matricula, $grado, $grupo);
                    }
                } else if ($rolId === ROL_PROFESOR && !empty($usuarioActual['Profesor_id'])) {
                    $especialidad = trim($data['especialidad'] ?? $usuarioActual['especialidad'] ?? '');
                    if (!empty($especialidad)) {
                        $this->profesorModel->actualizar((int)$usuarioActual['Profesor_id'], $especialidad);
                    }
                }

                $this->json(true, "Usuario actualizado correctamente.");
            } else {
                // Creación
                if (empty($password)) {
                    $this->json(false, "La contraseña es requerida para un nuevo usuario.", [], 400);
                }

                $existente = $this->usuarioModel->obtenerPorCorreo($correo);
                if ($existente) {
                    $this->json(false, "El correo electrónico ya está registrado.", [], 400);
                }

                $estudianteId = null;
                $profesorId = null;

                // Crear automáticamente el registro complementario según el rol
                if ($rolId === ROL_ESTUDIANTE) {
                    $matricula = trim($data['matricula'] ?? 'EST-' . date('Y') . '-' . rand(100, 999));
                    $grado = (int)($data['grado'] ?? 1);
                    $grupo = trim($data['grupo'] ?? 'A');
                    $estudianteId = $this->estudianteModel->crear($matricula, $grado, $grupo);
                } else if ($rolId === ROL_PROFESOR) {
                    $especialidad = trim($data['especialidad'] ?? 'General');
                    $profesorId = $this->profesorModel->crear($especialidad);
                }

                $this->usuarioModel->crear($nombre, $apellidos, $correo, $password, $rolId, $estudianteId, $profesorId);
                $this->json(true, "Usuario registrado exitosamente.");
            }
        } catch (Exception $e) {
            $this->json(false, "Error al procesar el usuario: " . $e->getMessage(), [], 500);
        }
    }

    public function eliminarUsuario(): void {
        $this->requireRole(ROL_ADMIN);
        $this->validateCsrfHeaderOrPost();
        $data = $this->getRequestData();
        $id = (int)($data['id'] ?? 0);

        if ($id <= 0) {
            $this->json(false, "ID de usuario inválido.", [], 400);
        }

        if ($id === (int)$_SESSION['usuario_id']) {
            $this->json(false, "No puede eliminar su propio usuario activo.", [], 400);
        }

        try {
            $res = $this->usuarioModel->eliminar($id);
            if ($res) {
                $this->json(true, "Usuario eliminado correctamente.");
            } else {
                $this->json(false, "No se encontró el usuario.", [], 404);
            }
        } catch (Exception $e) {
            $this->json(false, "Error al eliminar usuario: " . $e->getMessage(), [], 500);
        }
    }

    // --- CRUD PROFESORES ---

    public function listarProfesores(): void {
        $this->requireRole(ROL_ADMIN);
        $profesores = $this->profesorModel->obtenerTodos();
        $this->json(true, "Lista de profesores", $profesores);
    }

    public function guardarProfesor(): void {
        $this->requireRole(ROL_ADMIN);
        $this->validateCsrfHeaderOrPost();
        $data = $this->getRequestData();

        $id = !empty($data['id']) ? (int)$data['id'] : null;
        $especialidad = trim($data['especialidad'] ?? '');

        if (empty($especialidad)) {
            $this->json(false, "La especialidad es obligatoria.", [], 400);
        }

        try {
            $nombre = trim($data['nombre'] ?? '');
            $apellidos = trim($data['apellidos'] ?? '');
            $correo = trim($data['correo'] ?? '');

            if (!empty($nombre) && (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/u', $nombre) || !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/u', $apellidos))) {
                $this->json(false, "Los campos de Nombre y Apellidos solo deben contener letras.", [], 400);
            }

            if (!empty($correo) && (!filter_var($correo, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.com$/i', $correo))) {
                $this->json(false, "El correo electrónico debe incluir '@' y '.com'.", [], 400);
            }

            if ($id) {
                $this->profesorModel->actualizar($id, $especialidad);
                $profActual = $this->profesorModel->obtenerPorId($id);
                if ($profActual && !empty($profActual['usuario_id']) && !empty($nombre) && !empty($apellidos) && !empty($correo)) {
                    $password = !empty($data['password']) ? $data['password'] : null;
                    $this->usuarioModel->actualizar((int)$profActual['usuario_id'], $nombre, $apellidos, $correo, $password, ROL_PROFESOR);
                }
                $this->json(true, "Profesor actualizado correctamente.");
            } else {
                $password = !empty($data['password']) ? $data['password'] : 'profesor123';

                if (empty($nombre) || empty($apellidos) || empty($correo)) {
                    $this->json(false, "Debe ingresar nombre, apellidos y correo para el profesor.", [], 400);
                }

                if ($this->usuarioModel->obtenerPorCorreo($correo)) {
                    $this->json(false, "El correo ya está en uso.", [], 400);
                }

                $profId = $this->profesorModel->crear($especialidad);
                $this->usuarioModel->crear($nombre, $apellidos, $correo, $password, ROL_PROFESOR, null, $profId);
                $this->json(true, "Profesor y usuario creados exitosamente.");
            }
        } catch (Exception $e) {
            $this->json(false, "Error: " . $e->getMessage(), [], 500);
        }
    }

    // --- CRUD ESTUDIANTES ---

    public function listarEstudiantes(): void {
        $this->requireRole(ROL_ADMIN);
        $estudiantes = $this->estudianteModel->obtenerTodos();
        $this->json(true, "Lista de estudiantes", $estudiantes);
    }

    public function guardarEstudiante(): void {
        $this->requireRole(ROL_ADMIN);
        $this->validateCsrfHeaderOrPost();
        $data = $this->getRequestData();

        $id = !empty($data['id']) ? (int)$data['id'] : null;
        $matricula = trim($data['matricula'] ?? '');
        $grado = (int)($data['grado'] ?? 0);
        $grupo = trim($data['grupo'] ?? '');

        if (empty($matricula) || $grado <= 0 || empty($grupo)) {
            $this->json(false, "Matrícula, grado y grupo son obligatorios.", [], 400);
        }

        try {
            $nombre = trim($data['nombre'] ?? '');
            $apellidos = trim($data['apellidos'] ?? '');
            $correo = trim($data['correo'] ?? '');

            if (!empty($nombre) && (!preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/u', $nombre) || !preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/u', $apellidos))) {
                $this->json(false, "Los campos de Nombre y Apellidos solo deben contener letras.", [], 400);
            }

            if (!empty($correo) && (!filter_var($correo, FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.com$/i', $correo))) {
                $this->json(false, "El correo electrónico debe incluir '@' y '.com'.", [], 400);
            }

            if ($id) {
                $this->estudianteModel->actualizar($id, $matricula, $grado, $grupo);
                $estActual = $this->estudianteModel->obtenerPorId($id);
                if ($estActual && !empty($estActual['usuario_id']) && !empty($nombre) && !empty($apellidos) && !empty($correo)) {
                    $password = !empty($data['password']) ? $data['password'] : null;
                    $this->usuarioModel->actualizar((int)$estActual['usuario_id'], $nombre, $apellidos, $correo, $password, ROL_ESTUDIANTE);
                }
                $this->json(true, "Estudiante actualizado correctamente.");
            } else {
                $password = !empty($data['password']) ? $data['password'] : 'estudiante123';

                if (empty($nombre) || empty($apellidos) || empty($correo)) {
                    $this->json(false, "Nombre, apellidos y correo son requeridos.", [], 400);
                }

                if ($this->usuarioModel->obtenerPorCorreo($correo)) {
                    $this->json(false, "El correo ya está en uso.", [], 400);
                }

                if ($this->estudianteModel->obtenerPorMatricula($matricula)) {
                    $this->json(false, "La matrícula ya se encuentra registrada.", [], 400);
                }

                $estId = $this->estudianteModel->crear($matricula, $grado, $grupo);
                $this->usuarioModel->crear($nombre, $apellidos, $correo, $password, ROL_ESTUDIANTE, $estId, null);
                $this->json(true, "Estudiante registrado exitosamente.");
            }
        } catch (Exception $e) {
            $this->json(false, "Error: " . $e->getMessage(), [], 500);
        }
    }

    // --- CRUD CURSOS ---

    public function listarCursos(): void {
        $this->requireRole(ROL_ADMIN);
        $cursos = $this->cursoModel->obtenerTodos();
        $this->json(true, "Lista de cursos", $cursos);
    }

    public function guardarCurso(): void {
        $this->requireRole(ROL_ADMIN);
        $this->validateCsrfHeaderOrPost();
        $data = $this->getRequestData();

        $id = !empty($data['id']) ? (int)$data['id'] : null;
        $nombre = trim($data['nombre'] ?? '');
        $fechaIni = trim($data['fecha_ini'] ?? '');
        $fechaFin = trim($data['fecha_fin'] ?? '');
        $profesorId = (int)($data['profesor_id'] ?? 0);

        if (empty($nombre) || empty($fechaIni) || empty($fechaFin) || $profesorId <= 0) {
            $this->json(false, "Todos los campos del curso son obligatorios.", [], 400);
        }

        // Validar que el nombre del curso no tenga más de 5 dígitos ni sea solo números
        $cantDigitos = preg_match_all('/\d/', $nombre);
        $soloNumeros = (bool)preg_match('/^\d+$/', preg_replace('/\s+/', '', $nombre));
        if ($cantDigitos > 5 || $soloNumeros) {
            $this->json(false, "No puede ingresar más de 5 dígitos o solo números en el nombre del curso.", [], 400);
        }

        // Validar que no se repita más de 5 veces seguidas la misma letra en el nombre del curso
        if (preg_match('/(\p{L})\1{5,}/iu', $nombre)) {
            $this->json(false, "No debe repetir más de 5 veces seguidas la misma letra en el nombre del curso.", [], 400);
        }

        if (strtotime($fechaFin) < strtotime($fechaIni)) {
            $this->json(false, "La fecha de fin no puede ser anterior a la fecha de inicio.", [], 400);
        }

        try {
            if ($id) {
                $this->cursoModel->actualizar($id, $nombre, $fechaIni, $fechaFin, $profesorId);
                $this->json(true, "Curso actualizado correctamente.");
            } else {
                $this->cursoModel->crear($nombre, $fechaIni, $fechaFin, $profesorId);
                $this->json(true, "Curso creado exitosamente.");
            }
        } catch (Exception $e) {
            $this->json(false, "Error al guardar curso: " . $e->getMessage(), [], 500);
        }
    }

    public function eliminarCurso(): void {
        $this->requireRole(ROL_ADMIN);
        $this->validateCsrfHeaderOrPost();
        $data = $this->getRequestData();
        $id = (int)($data['id'] ?? 0);

        if ($id <= 0) {
            $this->json(false, "ID de curso no válido.", [], 400);
        }

        try {
            $this->cursoModel->eliminar($id);
            $this->json(true, "Curso eliminado correctamente.");
        } catch (Exception $e) {
            $this->json(false, "Error al eliminar curso: " . $e->getMessage(), [], 500);
        }
    }

    // --- INSCRIPCIONES ---

    public function listarInscripciones(): void {
        $this->requireRole(ROL_ADMIN);
        $inscripciones = $this->inscripcionModel->obtenerTodas();
        $this->json(true, "Lista de inscripciones", $inscripciones);
    }

    public function inscribirEstudiante(): void {
        $this->requireRole(ROL_ADMIN);
        $this->validateCsrfHeaderOrPost();
        $data = $this->getRequestData();

        $cursoId = (int)($data['curso_id'] ?? 0);
        $estudianteId = (int)($data['estudiante_id'] ?? 0);

        if ($cursoId <= 0 || $estudianteId <= 0) {
            $this->json(false, "Debe seleccionar un curso y un estudiante válidos.", [], 400);
        }

        if ($this->inscripcionModel->estaInscrito($cursoId, $estudianteId)) {
            $this->json(false, "El estudiante ya está inscrito en este curso.", [], 400);
        }

        try {
            $this->inscripcionModel->inscribir($cursoId, $estudianteId);
            $this->json(true, "Estudiante inscrito exitosamente.");
        } catch (Exception $e) {
            $this->json(false, "Error al inscribir: " . $e->getMessage(), [], 500);
        }
    }

    public function eliminarInscripcion(): void {
        $this->requireRole(ROL_ADMIN);
        $this->validateCsrfHeaderOrPost();
        $data = $this->getRequestData();
        $id = (int)($data['id'] ?? 0);

        if ($id <= 0) {
            $this->json(false, "ID de inscripción no válido.", [], 400);
        }

        try {
            $this->inscripcionModel->desinscribir($id);
            $this->json(true, "Inscripción cancelada correctamente.");
        } catch (Exception $e) {
            $this->json(false, "Error: " . $e->getMessage(), [], 500);
        }
    }
}
