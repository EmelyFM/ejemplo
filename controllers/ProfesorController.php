<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/CursoModel.php';
require_once __DIR__ . '/../models/ExamenModel.php';
require_once __DIR__ . '/../models/PreguntaModel.php';
require_once __DIR__ . '/../models/RespuestaModel.php';
require_once __DIR__ . '/../models/CalificacionModel.php';

class ProfesorController extends BaseController {

    private CursoModel $cursoModel;
    private ExamenModel $examenModel;
    private PreguntaModel $preguntaModel;
    private RespuestaModel $respuestaModel;
    private CalificacionModel $calificacionModel;

    public function __construct() {
        $this->cursoModel = new CursoModel();
        $this->examenModel = new ExamenModel();
        $this->preguntaModel = new PreguntaModel();
        $this->respuestaModel = new RespuestaModel();
        $this->calificacionModel = new CalificacionModel();
    }

    private function getProfesorId(): int {
        $this->requireRole(ROL_PROFESOR);
        $profId = $_SESSION['profesor_id'] ?? null;
        if (!$profId) {
            $this->json(false, "El usuario actual no tiene un perfil de profesor asociado.", [], 403);
        }
        return (int)$profId;
    }

    private function verificarPropiedadExamen(int $examenId): array {
        $profId = $this->getProfesorId();
        $examen = $this->examenModel->obtenerPorId($examenId);
        if (!$examen) {
            $this->json(false, "El examen solicitado no existe.", [], 404);
        }
        if ((int)$examen['Profesor_id'] !== $profId) {
            $this->json(false, "Acceso no autorizado: este examen pertenece a otro profesor.", [], 403);
        }
        return $examen;
    }

    public function dashboard(): void {
        $profId = $this->getProfesorId();
        $cursos = $this->cursoModel->obtenerPorProfesor($profId);
        $examenes = $this->examenModel->obtenerPorProfesor($profId);

        $this->renderView('profesores/dashboard.php', [
            'titulo'   => 'Panel del Profesor',
            'cursos'   => $cursos,
            'examenes' => $examenes
        ]);
    }

    public function cursos(): void {
        $profId = $this->getProfesorId();
        $cursos = $this->cursoModel->obtenerPorProfesor($profId);
        $this->renderView('profesores/cursos.php', [
            'titulo' => 'Mis Cursos Asignados',
            'cursos' => $cursos
        ]);
    }

    public function examenes(): void {
        $profId = $this->getProfesorId();
        $cursos = $this->cursoModel->obtenerPorProfesor($profId);
        $this->renderView('profesores/examenes.php', [
            'titulo' => 'Gestión de Exámenes',
            'cursos' => $cursos
        ]);
    }

    public function editorExamen(): void {
        $profId = $this->getProfesorId();
        $examenId = (int)($_GET['id'] ?? 0);
        $cursos = $this->cursoModel->obtenerPorProfesor($profId);

        $examen = null;
        $preguntas = [];
        if ($examenId > 0) {
            $examen = $this->verificarPropiedadExamen($examenId);
            $preguntas = $this->preguntaModel->obtenerPorExamen($examenId, true);
        }

        $this->renderView('profesores/editor_examen.php', [
            'titulo'    => $examenId > 0 ? 'Editar Examen' : 'Crear Nuevo Examen',
            'cursos'    => $cursos,
            'examen'    => $examen,
            'preguntas' => $preguntas
        ]);
    }

    public function calificar(): void {
        $profId = $this->getProfesorId();
        $examenId = (int)($_GET['examen_id'] ?? 0);
        if ($examenId <= 0) {
            header("Location: " . BASE_URL . "/index.php?controller=profesor&action=examenes");
            exit;
        }

        $examen = $this->verificarPropiedadExamen($examenId);
        $entregas = $this->calificacionModel->obtenerPorExamen($examenId);

        $this->renderView('profesores/calificar.php', [
            'titulo'   => 'Calificar Examen: ' . $examen['nom_exam'],
            'examen'   => $examen,
            'entregas' => $entregas
        ]);
    }

    // ==========================================
    // API JSON PARA PROFESORES
    // ==========================================

    public function listarExamenes(): void {
        $profId = $this->getProfesorId();
        $examenes = $this->examenModel->obtenerPorProfesor($profId);
        $this->json(true, "Exámenes obtenidos", $examenes);
    }

    public function obtenerEstudiantesCurso(): void {
        $this->getProfesorId();
        $cursoId = (int)($_GET['curso_id'] ?? 0);
        $estudiantes = $this->cursoModel->obtenerEstudiantesInscritos($cursoId);
        $this->json(true, "Estudiantes inscritos", $estudiantes);
    }

    public function guardarExamen(): void {
        $profId = $this->getProfesorId();
        $this->validateCsrfHeaderOrPost();
        $data = $this->getRequestData();

        $id = !empty($data['id']) ? (int)$data['id'] : null;
        $nomExam = trim($data['nom_exam'] ?? '');
        $cursoId = (int)($data['curso_id'] ?? 0);
        $fechaInicio = !empty($data['fecha_inicio']) ? $data['fecha_inicio'] : null;
        $fechaFin = !empty($data['fecha_fin']) ? $data['fecha_fin'] : null;
        $duracion = (int)($data['duracion_minutos'] ?? 60);
        $estado = in_array($data['estado'] ?? '', ['borrador', 'activo', 'finalizado']) ? $data['estado'] : 'activo';

        if (empty($nomExam) || $cursoId <= 0) {
            $this->json(false, "El nombre del examen y el curso son requeridos.", [], 400);
        }

        // Validar que el nombre del examen no tenga más de 5 dígitos ni sea solo números
        $cantDigitos = preg_match_all('/\d/', $nomExam);
        $soloNumeros = (bool)preg_match('/^\d+$/', preg_replace('/\s+/', '', $nomExam));
        if ($cantDigitos > 5 || $soloNumeros) {
            $this->json(false, "No puede ingresar más de 5 dígitos o solo números en el nombre de la evaluación o examen.", [], 400);
        }

        // Validar que no se repita más de 5 veces seguidas la misma letra en el nombre del examen
        if (preg_match('/(\p{L})\1{5,}/iu', $nomExam)) {
            $this->json(false, "No debe repetir más de 5 veces seguidas la misma letra en el nombre del examen.", [], 400);
        }

        // Validar que el curso pertenezca al profesor
        $curso = $this->cursoModel->obtenerPorId($cursoId);
        if (!$curso || (int)$curso['Profesor_id'] !== $profId) {
            $this->json(false, "El curso seleccionado no le pertenece.", [], 403);
        }

        // Validar fechas
        if ($fechaInicio && $fechaFin) {
            if (strtotime($fechaFin) <= strtotime($fechaInicio)) {
                $this->json(false, "La fecha de cierre debe ser estrictamente posterior a la fecha de apertura.", [], 400);
            }
        }

        $fechaBase = $fechaInicio ? date('Y-m-d', strtotime($fechaInicio)) : date('Y-m-d');

        try {
            if ($id) {
                $this->verificarPropiedadExamen($id);
                // Si ya tiene respuestas, advertir o restringir cambios estructurales
                $this->examenModel->actualizar($id, $nomExam, $fechaBase, $cursoId, $fechaInicio, $fechaFin, $duracion, $estado);
                $this->json(true, "Examen actualizado correctamente.", ['id' => $id]);
            } else {
                $nuevoId = $this->examenModel->crear($nomExam, $fechaBase, $cursoId, $fechaInicio, $fechaFin, $duracion, $estado);
                $this->json(true, "Examen creado exitosamente. Ahora puede agregar las preguntas.", ['id' => $nuevoId]);
            }
        } catch (Exception $e) {
            $this->json(false, "Error al guardar examen: " . $e->getMessage(), [], 500);
        }
    }

    public function eliminarExamen(): void {
        $this->getProfesorId();
        $this->validateCsrfHeaderOrPost();
        $data = $this->getRequestData();
        $id = (int)($data['id'] ?? 0);

        $this->verificarPropiedadExamen($id);

        try {
            $this->examenModel->eliminar($id);
            $this->json(true, "Examen eliminado correctamente.");
        } catch (Exception $e) {
            $this->json(false, $e->getMessage(), [], 400);
        }
    }

    public function togglePublicarResultados(): void {
        $this->getProfesorId();
        $this->validateCsrfHeaderOrPost();
        $data = $this->getRequestData();
        $id = (int)($data['id'] ?? 0);
        $publicar = !empty($data['publicar']);

        $this->verificarPropiedadExamen($id);

        $ok = $this->examenModel->publicarResultados($id, $publicar);
        if ($ok) {
            $msg = $publicar ? "Resultados publicados. Los estudiantes ya pueden consultar sus calificaciones." : "Resultados ocultados.";
            $this->json(true, $msg);
        } else {
            $this->json(false, "No se pudo actualizar el estado de publicación.", [], 500);
        }
    }

    // --- PREGUNTAS ---

    public function listarPreguntas(): void {
        $this->getProfesorId();
        $examenId = (int)($_GET['examen_id'] ?? 0);
        $this->verificarPropiedadExamen($examenId);

        $preguntas = $this->preguntaModel->obtenerPorExamen($examenId, true);
        $this->json(true, "Preguntas obtenidas", $preguntas);
    }

    public function guardarPregunta(): void {
        $this->getProfesorId();
        $this->validateCsrfHeaderOrPost();
        $data = $this->getRequestData();

        $id = !empty($data['id']) ? (int)$data['id'] : null;
        $examenId = (int)($data['examen_id'] ?? 0);
        $pregunta = trim($data['pregunta'] ?? '');
        $tipo = trim($data['tipo'] ?? 'opcion_multiple');
        $respuesta = trim($data['respuesta'] ?? '');
        $puntaje = (int)($data['puntaje'] ?? 10);
        $opciones = $data['opciones'] ?? [];
        if (is_string($opciones)) {
            $decoded = json_decode($opciones, true);
            $opciones = is_array($decoded) ? $decoded : [];
        }

        $this->verificarPropiedadExamen($examenId);

        if (empty($pregunta) || $puntaje <= 0) {
            $this->json(false, "El texto de la pregunta y el puntaje son requeridos.", [], 400);
        }

        if (preg_match('/\d{16,}/', $pregunta)) {
            $this->json(false, "No se permite ingresar más de 15 números seguidos en el cuadro de texto de la pregunta.", [], 400);
        }

        if (preg_match('/(\p{L})\1{5,}/iu', $pregunta)) {
            $this->json(false, "No debe repetir más de 5 veces seguidas la misma letra en la pregunta.", [], 400);
        }

        if ($tipo === 'opcion_multiple' && empty($opciones)) {
            $this->json(false, "Debe ingresar al menos dos opciones para preguntas de opción múltiple.", [], 400);
        }

        // Procesar imagen opcional de la pregunta (por archivo subido o base64)
        $rutaImagen = null;
        if (!empty($_FILES['imagen']['name']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
            $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($ext, $permitidas)) {
                $this->json(false, "Formato de imagen no permitido. Use JPG, PNG, GIF o WEBP.", [], 400);
            }
            if ($_FILES['imagen']['size'] > 5 * 1024 * 1024) {
                $this->json(false, "La imagen no debe superar los 5 MB.", [], 400);
            }

            $dirUploads = __DIR__ . '/../uploads/preguntas';
            if (!is_dir($dirUploads)) {
                @mkdir($dirUploads, 0777, true);
            }

            $nombreArchivo = 'preg_' . $examenId . '_' . uniqid() . '.' . $ext;
            $destino = $dirUploads . '/' . $nombreArchivo;
            if (@move_uploaded_file($_FILES['imagen']['tmp_name'], $destino)) {
                $rutaImagen = 'uploads/preguntas/' . $nombreArchivo;
            }
        }

        if ($rutaImagen === null && !empty($data['imagen_base64']) && strpos($data['imagen_base64'], 'data:image/') === 0) {
            if (preg_match('/^data:image\/(\w+);base64,/', $data['imagen_base64'], $matches)) {
                $ext = strtolower($matches[1]);
                if ($ext === 'jpeg') $ext = 'jpg';
                $rawB64 = substr($data['imagen_base64'], strpos($data['imagen_base64'], ',') + 1);
                $binData = base64_decode($rawB64);

                $dirUploads = __DIR__ . '/../uploads/preguntas';
                if (!is_dir($dirUploads)) {
                    @mkdir($dirUploads, 0777, true);
                }

                $nombreArchivo = 'preg_' . $examenId . '_' . uniqid() . '.' . $ext;
                $destino = $dirUploads . '/' . $nombreArchivo;
                if ($binData !== false && @file_put_contents($destino, $binData) !== false) {
                    $rutaImagen = 'uploads/preguntas/' . $nombreArchivo;
                } else {
                    $rutaImagen = $data['imagen_base64'];
                }
            }
        }

        try {
            if ($id) {
                $this->preguntaModel->actualizar($id, $pregunta, $tipo, $respuesta, $opciones, $puntaje, $rutaImagen);
                $this->calificacionModel->recalcularExamen($examenId);
                $this->json(true, "Pregunta actualizada correctamente.");
            } else {
                $this->preguntaModel->crear($pregunta, $tipo, $respuesta, $opciones, $puntaje, $examenId, $rutaImagen);
                $this->calificacionModel->recalcularExamen($examenId);
                $this->json(true, "Pregunta agregada exitosamente.");
            }
        } catch (Exception $e) {
            $this->json(false, "Error: " . $e->getMessage(), [], 500);
        }
    }

    public function eliminarPregunta(): void {
        $this->getProfesorId();
        $this->validateCsrfHeaderOrPost();
        $data = $this->getRequestData();
        $id = (int)($data['id'] ?? 0);
        $examenId = (int)($data['examen_id'] ?? 0);

        $this->verificarPropiedadExamen($examenId);

        try {
            $this->preguntaModel->eliminar($id);
            $this->calificacionModel->recalcularExamen($examenId);
            $this->json(true, "Pregunta eliminada correctamente.");
        } catch (Exception $e) {
            $this->json(false, $e->getMessage(), [], 400);
        }
    }

    // --- REVISIÓN Y CALIFICACIÓN ---

    public function obtenerRespuestasEstudiante(): void {
        $this->getProfesorId();
        $examenId = (int)($_GET['examen_id'] ?? 0);
        $estudianteId = (int)($_GET['estudiante_id'] ?? 0);

        $this->verificarPropiedadExamen($examenId);
        $calificacion = $this->calificacionModel->obtenerPorExamenEstudiante($examenId, $estudianteId);
        $respuestas = $this->respuestaModel->obtenerRespuestasPorExamenEstudiante($examenId, $estudianteId);

        $this->json(true, "Respuestas del estudiante", [
            'respuestas'   => $respuestas,
            'calificacion' => $calificacion
        ]);
    }

    public function calificarPreguntaAbierta(): void {
        $this->getProfesorId();
        $this->validateCsrfHeaderOrPost();
        $data = $this->getRequestData();

        $respuestaId = (int)($data['respuesta_id'] ?? 0);
        $puntaje = (float)($data['puntaje_obtenido'] ?? 0);
        $retro = trim($data['retroalimentacion'] ?? '');

        if ($respuestaId <= 0 || $puntaje < 0) {
            $this->json(false, "Datos de calificación inválidos.", [], 400);
        }

        try {
            $this->respuestaModel->calificarRespuestaManual($respuestaId, $puntaje, $retro);
            $this->json(true, "Pregunta calificada y nota actualizada correctamente.");
        } catch (Exception $e) {
            $this->json(false, "Error al calificar: " . $e->getMessage(), [], 500);
        }
    }
}
