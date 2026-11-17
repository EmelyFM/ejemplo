<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/CursoModel.php';
require_once __DIR__ . '/../models/ExamenModel.php';
require_once __DIR__ . '/../models/PreguntaModel.php';
require_once __DIR__ . '/../models/RespuestaModel.php';
require_once __DIR__ . '/../models/CalificacionModel.php';
require_once __DIR__ . '/../models/InscripcionModel.php';

class EstudianteController extends BaseController {

    private CursoModel $cursoModel;
    private ExamenModel $examenModel;
    private PreguntaModel $preguntaModel;
    private RespuestaModel $respuestaModel;
    private CalificacionModel $calificacionModel;
    private InscripcionModel $inscripcionModel;

    public function __construct() {
        $this->cursoModel = new CursoModel();
        $this->examenModel = new ExamenModel();
        $this->preguntaModel = new PreguntaModel();
        $this->respuestaModel = new RespuestaModel();
        $this->calificacionModel = new CalificacionModel();
        $this->inscripcionModel = new InscripcionModel();
    }

    private function getEstudianteId(): int {
        $this->requireRole(ROL_ESTUDIANTE);
        $estId = $_SESSION['estudiante_id'] ?? null;
        if (!$estId) {
            $this->json(false, "El usuario no cuenta con un registro de estudiante activo.", [], 403);
        }
        return (int)$estId;
    }

    public function dashboard(): void {
        $estId = $this->getEstudianteId();
        $cursos = $this->cursoModel->obtenerPorEstudiante($estId);
        $examenes = $this->examenModel->obtenerParaEstudiante($estId);
        $historial = $this->calificacionModel->obtenerHistorialEstudiante($estId, true);

        // Agrupar exámenes por estado
        $disponibles = [];
        $pendientes = [];
        $realizados = [];
        $ahora = time();

        foreach ($examenes as $ex) {
            $fIni = !empty($ex['fecha_inicio']) ? strtotime($ex['fecha_inicio']) : 0;
            $fFin = !empty($ex['fecha_fin']) ? strtotime($ex['fecha_fin']) : PHP_INT_MAX;
            $yaRespondio = (int)$ex['respuestas_enviadas'] > 0;

            if ($yaRespondio) {
                $realizados[] = $ex;
            } else if ($ahora >= $fIni && $ahora <= $fFin && $ex['estado'] === 'activo') {
                $disponibles[] = $ex;
            } else {
                $pendientes[] = $ex;
            }
        }

        $this->renderView('estudiantes/dashboard.php', [
            'titulo'      => 'Panel del Estudiante',
            'cursos'      => $cursos,
            'disponibles' => $disponibles,
            'pendientes'  => $pendientes,
            'realizados'  => $realizados,
            'historial'   => $historial
        ]);
    }

    public function misCursos(): void {
        $estId = $this->getEstudianteId();
        $cursos = $this->cursoModel->obtenerPorEstudiante($estId);
        $this->renderView('estudiantes/mis_cursos.php', [
            'titulo' => 'Mis Cursos Inscritos',
            'cursos' => $cursos
        ]);
    }

    public function responderExamen(): void {
        $estId = $this->getEstudianteId();
        $examenId = (int)($_GET['id'] ?? 0);

        if ($examenId <= 0) {
            header("Location: " . BASE_URL . "/index.php?controller=estudiante&action=dashboard");
            exit;
        }

        $examen = $this->examenModel->obtenerPorId($examenId);
        if (!$examen) {
            die("Examen no encontrado.");
        }

        // 1. Verificar inscripción del estudiante en el curso
        if (!$this->inscripcionModel->estaInscrito((int)$examen['Cursos_id'], $estId)) {
            die("No estás inscrito en el curso correspondiente a este examen.");
        }

        // 2. Verificar si ya respondió el examen
        if ($this->respuestaModel->haRespondidoExamen($examenId, $estId)) {
            header("Location: " . BASE_URL . "/index.php?controller=estudiante&action=misResultados&examen_id=" . $examenId);
            exit;
        }

        // 3. Verificar fechas de disponibilidad
        $ahora = time();
        $fIni = !empty($examen['fecha_inicio']) ? strtotime($examen['fecha_inicio']) : 0;
        $fFin = !empty($examen['fecha_fin']) ? strtotime($examen['fecha_fin']) : PHP_INT_MAX;

        if ($ahora < $fIni) {
            die("Este examen aún no está habilitado para su resolución.");
        }
        if ($ahora > $fFin) {
            die("El plazo para resolver este examen ha vencido.");
        }

        // 4. Obtener preguntas SIN respuestas correctas para proteger la evaluación
        $preguntas = $this->preguntaModel->obtenerPorExamen($examenId, false);

        $this->renderView('estudiantes/responder_examen.php', [
            'titulo'    => 'Resolución: ' . $examen['nom_exam'],
            'examen'    => $examen,
            'preguntas' => $preguntas
        ]);
    }

    public function enviarRespuestas(): void {
        $estId = $this->getEstudianteId();
        $this->validateCsrfHeaderOrPost();
        $data = $this->getRequestData();

        $examenId = (int)($data['examen_id'] ?? 0);
        $respuestas = $data['respuestas'] ?? [];

        if ($examenId <= 0 || !is_array($respuestas)) {
            $this->json(false, "Datos del examen no válidos.", [], 400);
        }

        $examen = $this->examenModel->obtenerPorId($examenId);
        if (!$examen) {
            $this->json(false, "El examen no existe.", [], 404);
        }

        // Validación en servidor de inscripción
        if (!$this->inscripcionModel->estaInscrito((int)$examen['Cursos_id'], $estId)) {
            $this->json(false, "No está inscrito en este curso.", [], 403);
        }

        // Validación en servidor de fechas de entrega
        $ahora = time();
        $fIni = !empty($examen['fecha_inicio']) ? strtotime($examen['fecha_inicio']) : 0;
        $fFin = !empty($examen['fecha_fin']) ? strtotime($examen['fecha_fin']) : PHP_INT_MAX;

        if ($ahora > $fFin) {
            $this->json(false, "El tiempo límite del examen ha concluido. No se aceptan envíos fuera de plazo.", [], 403);
        }

        try {
            $resultado = $this->respuestaModel->guardarRespuestasEstudiante($examenId, $estId, $respuestas);
            
            $msg = "Tus respuestas han sido enviadas correctamente.";
            if ($resultado['tiene_abiertas']) {
                $msg .= " La evaluación incluye preguntas abiertas que serán revisadas por tu profesor.";
            }

            $this->json(true, $msg, [
                'calificacion' => $resultado['calificacion'],
                'estado'       => $resultado['estado'],
                'redirect'     => BASE_URL . '/index.php?controller=estudiante&action=misResultados&examen_id=' . $examenId
            ]);
        } catch (Exception $e) {
            $this->json(false, $e->getMessage(), [], 400);
        }
    }

    public function misResultados(): void {
        $estId = $this->getEstudianteId();
        $examenId = (int)($_GET['examen_id'] ?? 0);

        $calificacionesPublicadas = $this->calificacionModel->obtenerHistorialEstudiante($estId, true);
        
        $detalleExamen = null;
        $respuestasExamen = [];

        if ($examenId > 0) {
            $calif = $this->calificacionModel->obtenerPorExamenEstudiante($examenId, $estId);
            if ($calif) {
                $detalleExamen = $calif;
                // Si están publicados, mostrar el desglose de preguntas
                if (!empty($calif['resultados_publicados'])) {
                    $respuestasExamen = $this->respuestaModel->obtenerRespuestasPorExamenEstudiante($examenId, $estId);
                }
            }
        }

        $this->renderView('estudiantes/mis_resultados.php', [
            'titulo'                   => 'Mis Calificaciones y Resultados',
            'calificacionesPublicadas' => $calificacionesPublicadas,
            'detalleExamen'            => $detalleExamen,
            'respuestasExamen'         => $respuestasExamen
        ]);
    }
}
