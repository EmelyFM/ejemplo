<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/CalificacionModel.php';
require_once __DIR__ . '/../models/CursoModel.php';
require_once __DIR__ . '/../models/ExamenModel.php';

class EstadisticaController extends BaseController {
    private CalificacionModel $calificacionModel;
    private CursoModel $cursoModel;
    private ExamenModel $examenModel;
    public function __construct() {
        $this->calificacionModel = new CalificacionModel();
        $this->cursoModel = new CursoModel();
        $this->examenModel = new ExamenModel();
    }
    public function getDatosDashboard(): void {
        $this->requireAuth();
        $stats = $this->calificacionModel->obtenerEstadisticasGenerales();
        $this->json(true, "Datos estadísticos obtenidos", $stats);
    }
    public function getEstadisticasCurso(): void {
        $this->requireAuth();
        $cursoId = (int)($_GET['curso_id'] ?? 0);
        if ($cursoId <= 0) {
            $this->json(false, "ID de curso no válido.", [], 400);
        }
        $reporte = $this->calificacionModel->obtenerReporteCurso($cursoId);
    
        $calificaciones = [];
        $aprobados = 0;
        $reprobados = 0;
        foreach ($reporte as $r) {
            if ($r['calificaciones'] !== null) {
                $val = (float)$r['calificaciones'];
                $ptsMax = (float)($r['puntaje_total'] ?? 100);
                $calificaciones[] = $val;
                if ($ptsMax > 0 && $val >= ($ptsMax / 2)) {
                    $aprobados++;
                } else {
                    $reprobados++;
                }
            }
        }

        $promedio = count($calificaciones) > 0 ? round(array_sum($calificaciones) / count($calificaciones), 2) : 0;

        $this->json(true, "Estadísticas del curso", [
            'promedio'   => $promedio,
            'aprobados'  => $aprobados,
            'reprobados' => $reprobados,
            'total'      => count($calificaciones),
            'detalles'   => $reporte
        ]);
    }

    public function getDatosProfesor(): void {
        $this->requireAuth();
        $profId = (int)($_SESSION['profesor_id'] ?? $_GET['profesor_id'] ?? 0);
        if ($profId <= 0) {
            $this->json(false, "Perfil de profesor no identificado.", [], 400);
        }

        $stats = $this->calificacionModel->obtenerEstadisticasProfesor($profId);
        $this->json(true, "Estadísticas de rendimiento del profesor obtenidas", $stats);
    }
}
