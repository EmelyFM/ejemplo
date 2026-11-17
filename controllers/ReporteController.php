<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/CalificacionModel.php';
require_once __DIR__ . '/../models/EstudianteModel.php';
require_once __DIR__ . '/../models/CursoModel.php';
require_once __DIR__ . '/../models/ExamenModel.php';

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

if (!class_exists('FPDF') && file_exists(__DIR__ . '/../libs/fpdf/fpdf.php')) {
    require_once __DIR__ . '/../libs/fpdf/fpdf.php';
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReporteController extends BaseController {

    private CalificacionModel $calificacionModel;
    private EstudianteModel $estudianteModel;
    private CursoModel $cursoModel;
    private ExamenModel $examenModel;

    public function __construct() {
        $this->calificacionModel = new CalificacionModel();
        $this->estudianteModel = new EstudianteModel();
        $this->cursoModel = new CursoModel();
        $this->examenModel = new ExamenModel();
    }

    // ==========================================
    // REPORTES PDF CON FPDF
    // ==========================================

    /**
     * Reporte individual en PDF para un estudiante
     */
    public function pdfIndividual(): void {
        $this->requireAuth();
        $rol = getAuthRoleId();
        $examenId = (int)($_GET['examen_id'] ?? 0);
        $estudianteId = (int)($_GET['estudiante_id'] ?? 0);

        // Si es estudiante, forzar su propio ID
        if ($rol === ROL_ESTUDIANTE) {
            $estudianteId = (int)$_SESSION['estudiante_id'];
        }

        if ($examenId <= 0 || $estudianteId <= 0) {
            die("Parámetros insuficientes para generar el reporte individual.");
        }

        $calif = $this->calificacionModel->obtenerPorExamenEstudiante($examenId, $estudianteId);
        if (!$calif) {
            die("No se encontró el registro de calificación solicitado.");
        }

        // Si es estudiante y los resultados no están publicados, denegar
        if ($rol === ROL_ESTUDIANTE && empty($calif['resultados_publicados'])) {
            die("Los resultados de este examen aún no han sido publicados por el profesor.");
        }

        $estudiante = $this->estudianteModel->obtenerPorId($estudianteId);
        $examen = $this->examenModel->obtenerPorId($examenId);

        // Instanciar FPDF
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 20);

        // Encabezado institucional
        $pdf->SetFillColor(30, 41, 59); // Slate oscuro
        $pdf->Rect(0, 0, 210, 35, 'F');

        $pdf->SetFont('Arial', 'B', 18);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(15, 10);
        $pdf->Cell(180, 8, utf8_decode('SISTEMA ACADÉMICO DE EVALUACIONES'), 0, 1, 'L');
        
        $pdf->SetFont('Arial', '', 11);
        $pdf->SetTextColor(203, 213, 225);
        $pdf->SetX(15);
        $pdf->Cell(180, 6, utf8_decode('Boleta Oficial de Resultado de Examen Individual'), 0, 1, 'L');

        // Metadatos
        $pdf->SetY(45);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(180, 7, utf8_decode('DATOS DEL ESTUDIANTE'), 'B', 1, 'L');

        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->Ln(2);

        $nombreCompleto = $estudiante['nombre'] . ' ' . $estudiante['Apellidos'];
        $pdf->Cell(45, 6, utf8_decode('Nombre Completo:'), 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(135, 6, utf8_decode($nombreCompleto), 0, 1, 'L');

        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(45, 6, utf8_decode('Matrícula:'), 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(45, 6, utf8_decode($estudiante['matricula']), 0, 0, 'L');

        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(45, 6, utf8_decode('Grado y Grupo:'), 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(45, 6, utf8_decode($estudiante['grado'] . '° "' . $estudiante['grupo'] . '"'), 0, 1, 'L');

        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(180, 7, utf8_decode('INFORMACIÓN DE LA EVALUACIÓN'), 'B', 1, 'L');

        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->Ln(2);

        $pdf->Cell(45, 6, utf8_decode('Curso:'), 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(135, 6, utf8_decode($calif['curso_nombre']), 0, 1, 'L');

        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(45, 6, utf8_decode('Examen:'), 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(135, 6, utf8_decode($examen['nom_exam']), 0, 1, 'L');

        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(45, 6, utf8_decode('Fecha de Envío:'), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(45, 6, utf8_decode($calif['Fecha']), 0, 0, 'L');

        $pdf->Cell(45, 6, utf8_decode('Profesor Responsable:'), 0, 0, 'L');
        $pdf->Cell(45, 6, utf8_decode(($examen['profesor_nombre'] ?? '') . ' ' . ($examen['profesor_apellidos'] ?? '')), 0, 1, 'L');

        // Cuadro de Calificación
        $pdf->Ln(10);
        $nota = (float)$calif['calificaciones'];
        $puntajeTotal = (float)($calif['puntaje_total'] ?? $examen['puntaje_total'] ?? 100);
        $aprobado = $puntajeTotal > 0 ? ($nota >= ($puntajeTotal / 2)) : false;

        $pdf->SetFillColor(241, 245, 249);
        $pdf->Rect(15, $pdf->GetY(), 180, 32, 'DF');

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetXY(20, $pdf->GetY() + 4);
        $pdf->Cell(80, 6, utf8_decode("CALIFICACIÓN OBTENIDA (Total: {$puntajeTotal} pts)"), 0, 0, 'L');
        $pdf->Cell(90, 6, utf8_decode('ESTADO DEL RESULTADO'), 0, 1, 'R');

        $pdf->SetFont('Arial', 'B', 22);
        if ($aprobado) {
            $pdf->SetTextColor(22, 101, 52); // Verde
        } else {
            $pdf->SetTextColor(153, 27, 27); // Rojo
        }
        $pdf->SetX(20);
        $pdf->Cell(80, 12, number_format($nota, 2) . ' / ' . $puntajeTotal . ' pts', 0, 0, 'L');

        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(90, 12, $aprobado ? utf8_decode('APROBADO') : utf8_decode('REPROBADO'), 0, 1, 'R');

        // Pie y Firma
        $pdf->SetY(240);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->SetTextColor(148, 163, 184);
        $pdf->Cell(180, 5, utf8_decode('Documento generado digitalmente por el Sistema de Gestión de Exámenes - ' . date('d/m/Y H:i:s')), 0, 1, 'C');
        $pdf->Cell(180, 5, utf8_decode('Copia de control académico con validez institucional interna.'), 0, 1, 'C');

        header('Content-Type: application/pdf');
        $pdf->Output('I', 'Reporte_Individual_' . $estudiante['matricula'] . '.pdf');
        exit;
    }

    /**
     * Reporte general por curso en PDF
     */
    public function pdfCurso(): void {
        $this->requireAuth();
        $rol = getAuthRoleId();
        $cursoId = (int)($_GET['curso_id'] ?? 0);

        if ($cursoId <= 0) {
            die("ID de curso inválido.");
        }

        $curso = $this->cursoModel->obtenerPorId($cursoId);
        if (!$curso) {
            die("Curso no encontrado.");
        }

        // Restringir profesor a sus cursos
        if ($rol === ROL_PROFESOR && (int)$curso['Profesor_id'] !== (int)$_SESSION['profesor_id']) {
            die("Acceso denegado: este curso no le pertenece.");
        }

        $reporte = $this->calificacionModel->obtenerReporteCurso($cursoId);

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 15);

        // Encabezado
        $pdf->SetFillColor(30, 41, 59);
        $pdf->Rect(0, 0, 210, 35, 'F');
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(15, 10);
        $pdf->Cell(180, 8, utf8_decode('REPORTE GENERAL DE CALIFICACIONES POR CURSO'), 0, 1, 'L');
        
        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(203, 213, 225);
        $pdf->SetX(15);
        $pdf->Cell(180, 6, utf8_decode('Curso: ' . $curso['nombre'] . ' | Titular: ' . $curso['profesor_nombre'] . ' ' . $curso['profesor_apellidos']), 0, 1, 'L');

        // Tabla de calificaciones
        $pdf->SetY(45);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(226, 232, 240);
        $pdf->SetTextColor(15, 23, 42);

        $pdf->Cell(28, 8, utf8_decode('Matrícula'), 1, 0, 'C', true);
        $pdf->Cell(55, 8, utf8_decode('Estudiante'), 1, 0, 'L', true);
        $pdf->Cell(50, 8, utf8_decode('Examen'), 1, 0, 'L', true);
        $pdf->Cell(22, 8, utf8_decode('Calificación'), 1, 0, 'C', true);
        $pdf->Cell(25, 8, utf8_decode('Resultado'), 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(51, 65, 85);

        $suma = 0;
        $totalCalif = 0;
        $aprobados = 0;

        foreach ($reporte as $r) {
            $notaTxt = '-';
            $estadoTxt = 'Sin entrega';
            
            if ($r['calificaciones'] !== null) {
                $val = (float)$r['calificaciones'];
                $ptsMax = (float)($r['puntaje_total'] ?? 100);
                $notaTxt = number_format($val, 2) . '/' . $ptsMax;
                $suma += $val;
                $totalCalif++;
                if ($ptsMax > 0 && $val >= ($ptsMax / 2)) {
                    $estadoTxt = 'Aprobado';
                    $aprobados++;
                } else {
                    $estadoTxt = 'Reprobado';
                }
            }

            $pdf->Cell(28, 7, utf8_decode($r['matricula']), 1, 0, 'C');
            $pdf->Cell(55, 7, utf8_decode(mb_strimwidth($r['estudiante_apellidos'] . ' ' . $r['estudiante_nombre'], 0, 26, '...')), 1, 0, 'L');
            $pdf->Cell(50, 7, utf8_decode(mb_strimwidth($r['nom_exam'] ?? 'N/A', 0, 24, '...')), 1, 0, 'L');
            $pdf->Cell(22, 7, $notaTxt, 1, 0, 'C');
            $pdf->Cell(25, 7, utf8_decode($estadoTxt), 1, 1, 'C');
        }

        // Resumen
        $promedio = $totalCalif > 0 ? round($suma / $totalCalif, 2) : 0;
        $porcAprobados = $totalCalif > 0 ? round(($aprobados / $totalCalif) * 100, 1) : 0;

        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(180, 7, utf8_decode("Promedio General del Curso: {$promedio} pts  |  Porcentaje de Aprobados: {$porcAprobados}% ({$aprobados}/{$totalCalif})"), 0, 1, 'R');

        header('Content-Type: application/pdf');
        $pdf->Output('I', 'Reporte_Curso_' . $cursoId . '.pdf');
        exit;
    }

    // ==========================================
    // EXPORTACIÓN EXCEL CON PHPSPREADSHEET
    // ==========================================

    public function excelCurso(): void {
        $this->requireAuth();
        $rol = getAuthRoleId();
        $cursoId = (int)($_GET['curso_id'] ?? 0);

        if ($cursoId <= 0) {
            die("ID de curso inválido.");
        }

        $curso = $this->cursoModel->obtenerPorId($cursoId);
        if (!$curso) {
            die("Curso no encontrado.");
        }

        if ($rol === ROL_PROFESOR && (int)$curso['Profesor_id'] !== (int)$_SESSION['profesor_id']) {
            die("Acceso denegado: este curso no le pertenece.");
        }

        $reporte = $this->calificacionModel->obtenerReporteCurso($cursoId);

        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            die("La librería PhpSpreadsheet no está disponible en este entorno.");
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Calificaciones');

        // Título general
        $sheet->setCellValue('A1', 'SISTEMA DE GESTIÓN DE EXÁMENES - REPORTE DE CALIFICACIONES');
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Subtítulo con datos del curso
        $sheet->setCellValue('A2', 'Curso: ' . $curso['nombre'] . ' | Titular: ' . $curso['profesor_nombre'] . ' ' . $curso['profesor_apellidos'] . ' | Fecha de Emisión: ' . date('d/m/Y'));
        $sheet->mergeCells('A2:G2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Encabezados de tabla
        $headers = [
            'A4' => 'Matrícula',
            'B4' => 'Nombre Completo',
            'C4' => 'Grado y Grupo',
            'D4' => 'Examen',
            'E4' => 'Calificación (Obtenido / Total)',
            'F4' => 'Fecha de Envío',
            'G4' => 'Estado de Aprobación'
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B'] // Slate 800
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ];
        $sheet->getStyle('A4:G4')->applyFromArray($headerStyle);
        $sheet->getRowDimension(4)->setRowHeight(25);

        // Llenar datos
        $rowNum = 5;
        foreach ($reporte as $r) {
            $notaVal = $r['calificaciones'] !== null ? (float)$r['calificaciones'] : null;
            $ptsMax = (float)($r['puntaje_total'] ?? 100);
            $estadoAprobacion = 'Sin entrega';
            if ($notaVal !== null) {
                $estadoAprobacion = ($ptsMax > 0 && $notaVal >= ($ptsMax / 2)) ? 'Aprobado' : 'Reprobado';
            }

            $sheet->setCellValue("A{$rowNum}", $r['matricula']);
            $sheet->setCellValue("B{$rowNum}", $r['estudiante_apellidos'] . ' ' . $r['estudiante_nombre']);
            $sheet->setCellValue("C{$rowNum}", $r['grado'] . '° "' . $r['grupo'] . '"');
            $sheet->setCellValue("D{$rowNum}", $r['nom_exam'] ?? 'N/A');
            $sheet->setCellValue("E{$rowNum}", $notaVal !== null ? (number_format($notaVal, 2) . ' / ' . $ptsMax . ' pts') : '-');
            $sheet->setCellValue("F{$rowNum}", $r['fecha_calificacion'] ?? '-');
            $sheet->setCellValue("G{$rowNum}", $estadoAprobacion);

            // Centrar columnas específicas
            $sheet->getStyle("A{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("C{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("F{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("G{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Color para Aprobado/Reprobado
            if ($estadoAprobacion === 'Aprobado') {
                $sheet->getStyle("G{$rowNum}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('166534'))->setBold(true);
            } else if ($estadoAprobacion === 'Reprobado') {
                $sheet->getStyle("G{$rowNum}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('991B1B'))->setBold(true);
            }

            $rowNum++;
        }

        // Autoajuste de columnas
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Habilitar filtros automáticos en la fila de encabezados
        $sheet->setAutoFilter("A4:G" . ($rowNum - 1));

        // Bordes finos a toda la tabla
        $tableRange = "A4:G" . ($rowNum - 1);
        $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('CBD5E1'));

        // Salida a descarga HTTP
        $nombreArchivo = 'Calificaciones_Curso_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $curso['nombre']) . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    /**
     * Reporte de calificaciones por estudiante en PDF (para el Profesor o Administrador)
     */
    public function pdfEstudiante(): void {
        $this->requireAuth();
        $rol = getAuthRoleId();
        $estudianteId = (int)($_GET['estudiante_id'] ?? 0);
        $cursoId = !empty($_GET['curso_id']) ? (int)$_GET['curso_id'] : null;

        if ($rol === ROL_ESTUDIANTE) {
            $estudianteId = (int)$_SESSION['estudiante_id'];
        }

        if ($estudianteId <= 0) {
            die("ID de estudiante inválido.");
        }

        $estudiante = $this->estudianteModel->obtenerPorId($estudianteId);
        if (!$estudiante) {
            die("Estudiante no encontrado.");
        }

        $profesorId = ($rol === ROL_PROFESOR) ? (int)($_SESSION['profesor_id'] ?? 0) : null;
        if ($cursoId) {
            $curso = $this->cursoModel->obtenerPorId($cursoId);
            if ($rol === ROL_PROFESOR && $curso && (int)$curso['Profesor_id'] !== $profesorId) {
                die("Acceso denegado: este curso no le pertenece.");
            }
        }

        $registros = $this->calificacionModel->obtenerReporteEstudiante($estudianteId, $profesorId, $cursoId);

        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetAutoPageBreak(true, 15);

        // Encabezado institucional
        $pdf->SetFillColor(30, 41, 59);
        $pdf->Rect(0, 0, 210, 35, 'F');
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetXY(15, 10);
        $pdf->Cell(180, 8, utf8_decode('REPORTE DE CALIFICACIONES POR ESTUDIANTE'), 0, 1, 'L');

        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(203, 213, 225);
        $pdf->SetX(15);
        $pdf->Cell(180, 6, utf8_decode('Historial Académico de Evaluaciones y Rendimiento'), 0, 1, 'L');

        // Datos del estudiante
        $pdf->SetY(42);
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->Cell(180, 7, utf8_decode('DATOS DEL ESTUDIANTE'), 'B', 1, 'L');

        $pdf->SetFont('Arial', '', 10);
        $pdf->SetTextColor(51, 65, 85);
        $pdf->Ln(2);

        $nombreCompleto = ($estudiante['nombre'] ?? '') . ' ' . ($estudiante['Apellidos'] ?? '');
        $pdf->Cell(38, 6, utf8_decode('Estudiante:'), 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(82, 6, utf8_decode($nombreCompleto), 0, 0, 'L');

        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(25, 6, utf8_decode('Matrícula:'), 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(35, 6, utf8_decode($estudiante['matricula']), 0, 1, 'L');

        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(38, 6, utf8_decode('Correo:'), 0, 0, 'L');
        $pdf->Cell(82, 6, utf8_decode($estudiante['Correo'] ?? '-'), 0, 0, 'L');

        $pdf->Cell(25, 6, utf8_decode('Grado/Grupo:'), 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(35, 6, utf8_decode($estudiante['grado'] . '° "' . $estudiante['grupo'] . '"'), 0, 1, 'L');

        // Tabla de evaluaciones
        $pdf->Ln(6);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(226, 232, 240);
        $pdf->SetTextColor(15, 23, 42);

        $pdf->Cell(52, 8, utf8_decode('Curso'), 1, 0, 'L', true);
        $pdf->Cell(56, 8, utf8_decode('Examen'), 1, 0, 'L', true);
        $pdf->Cell(25, 8, utf8_decode('Fecha'), 1, 0, 'C', true);
        $pdf->Cell(22, 8, utf8_decode('Calificación'), 1, 0, 'C', true);
        $pdf->Cell(25, 8, utf8_decode('Resultado'), 1, 1, 'C', true);

        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(51, 65, 85);

        $suma = 0;
        $totalCalif = 0;
        $aprobados = 0;

        if (empty($registros)) {
            $pdf->Cell(180, 8, utf8_decode('No se encontraron registros de evaluaciones para este estudiante.'), 1, 1, 'C');
        } else {
            foreach ($registros as $r) {
                $notaTxt = '-';
                $estadoTxt = 'Sin entrega';
                $fechaTxt = $r['fecha_calificacion'] ?? ($r['fecha_examen'] ?? '-');

                if ($r['calificaciones'] !== null) {
                    $val = (float)$r['calificaciones'];
                    $ptsMax = (float)($r['puntaje_total'] ?? 100);
                    $notaTxt = number_format($val, 2) . '/' . $ptsMax;
                    $suma += $val;
                    $totalCalif++;
                    if ($ptsMax > 0 && $val >= ($ptsMax / 2)) {
                        $estadoTxt = 'Aprobado';
                        $aprobados++;
                    } else {
                        $estadoTxt = 'Reprobado';
                    }
                }

                $pdf->Cell(52, 7, utf8_decode(mb_strimwidth($r['curso_nombre'] ?? '-', 0, 26, '...')), 1, 0, 'L');
                $pdf->Cell(56, 7, utf8_decode(mb_strimwidth($r['nom_exam'] ?? 'Sin exámenes asignados', 0, 28, '...')), 1, 0, 'L');
                $pdf->Cell(25, 7, utf8_decode($fechaTxt), 1, 0, 'C');
                $pdf->Cell(22, 7, $notaTxt, 1, 0, 'C');
                $pdf->Cell(25, 7, utf8_decode($estadoTxt), 1, 1, 'C');
            }
        }

        $promedio = $totalCalif > 0 ? round($suma / $totalCalif, 2) : 0;
        $porcAprobados = $totalCalif > 0 ? round(($aprobados / $totalCalif) * 100, 1) : 0;

        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(180, 7, utf8_decode("Promedio del Estudiante: {$promedio} pts  |  Exámenes Aprobados: {$porcAprobados}% ({$aprobados}/{$totalCalif})"), 0, 1, 'R');

        $pdf->SetY(260);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->SetTextColor(148, 163, 184);
        $pdf->Cell(180, 5, utf8_decode('Documento generado digitalmente por el Sistema de Gestión de Exámenes - ' . date('d/m/Y H:i:s')), 0, 1, 'C');

        header('Content-Type: application/pdf');
        $pdf->Output('I', 'Reporte_Estudiante_' . $estudiante['matricula'] . '.pdf');
        exit;
    }

    /**
     * Reporte de calificaciones por estudiante en Excel (.xlsx)
     */
    public function excelEstudiante(): void {
        $this->requireAuth();
        $rol = getAuthRoleId();
        $estudianteId = (int)($_GET['estudiante_id'] ?? 0);
        $cursoId = !empty($_GET['curso_id']) ? (int)$_GET['curso_id'] : null;

        if ($rol === ROL_ESTUDIANTE) {
            $estudianteId = (int)$_SESSION['estudiante_id'];
        }

        if ($estudianteId <= 0) {
            die("ID de estudiante inválido.");
        }

        $estudiante = $this->estudianteModel->obtenerPorId($estudianteId);
        if (!$estudiante) {
            die("Estudiante no encontrado.");
        }

        $profesorId = ($rol === ROL_PROFESOR) ? (int)($_SESSION['profesor_id'] ?? 0) : null;
        if ($cursoId) {
            $curso = $this->cursoModel->obtenerPorId($cursoId);
            if ($rol === ROL_PROFESOR && $curso && (int)$curso['Profesor_id'] !== $profesorId) {
                die("Acceso denegado: este curso no le pertenece.");
            }
        }

        $registros = $this->calificacionModel->obtenerReporteEstudiante($estudianteId, $profesorId, $cursoId);

        if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
            die("La librería PhpSpreadsheet no está disponible en este entorno.");
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Calificaciones Alumno');

        $nombreCompleto = ($estudiante['Apellidos'] ?? '') . ' ' . ($estudiante['nombre'] ?? '');

        $sheet->setCellValue('A1', 'REPORTE DE CALIFICACIONES POR ESTUDIANTE');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', "Estudiante: {$nombreCompleto} | Matrícula: {$estudiante['matricula']} | Grado y Grupo: {$estudiante['grado']}° \"{$estudiante['grupo']}\" | Fecha: " . date('d/m/Y'));
        $sheet->mergeCells('A2:F2');
        $sheet->getStyle('A2')->getFont()->setItalic(true)->setSize(10);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = [
            'A4' => 'Curso',
            'B4' => 'Profesor',
            'C4' => 'Examen',
            'D4' => 'Fecha de Evaluación',
            'E4' => 'Calificación (Obtenido / Total)',
            'F4' => 'Estado de Aprobación'
        ];

        foreach ($headers as $cell => $text) {
            $sheet->setCellValue($cell, $text);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E293B']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ];
        $sheet->getStyle('A4:F4')->applyFromArray($headerStyle);
        $sheet->getRowDimension(4)->setRowHeight(25);

        $rowNum = 5;
        foreach ($registros as $r) {
            $notaVal = $r['calificaciones'] !== null ? (float)$r['calificaciones'] : null;
            $ptsMax = (float)($r['puntaje_total'] ?? 100);
            $estadoAprobacion = 'Sin entrega';
            if ($notaVal !== null) {
                $estadoAprobacion = ($ptsMax > 0 && $notaVal >= ($ptsMax / 2)) ? 'Aprobado' : 'Reprobado';
            }

            $sheet->setCellValue("A{$rowNum}", $r['curso_nombre'] ?? '-');
            $sheet->setCellValue("B{$rowNum}", trim(($r['profesor_nombre'] ?? '') . ' ' . ($r['profesor_apellidos'] ?? '')));
            $sheet->setCellValue("C{$rowNum}", $r['nom_exam'] ?? 'N/A');
            $sheet->setCellValue("D{$rowNum}", $r['fecha_calificacion'] ?? '-');
            $sheet->setCellValue("E{$rowNum}", $notaVal !== null ? (number_format($notaVal, 2) . ' / ' . $ptsMax . ' pts') : '-');
            $sheet->setCellValue("F{$rowNum}", $estadoAprobacion);

            $sheet->getStyle("D{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("E{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("F{$rowNum}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($estadoAprobacion === 'Aprobado') {
                $sheet->getStyle("F{$rowNum}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('166534'))->setBold(true);
            } else if ($estadoAprobacion === 'Reprobado') {
                $sheet->getStyle("F{$rowNum}")->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('991B1B'))->setBold(true);
            }

            $rowNum++;
        }

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        if ($rowNum > 5) {
            $sheet->setAutoFilter("A4:F" . ($rowNum - 1));
            $tableRange = "A4:F" . ($rowNum - 1);
            $sheet->getStyle($tableRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('CBD5E1'));
        }

        $nombreArchivo = 'Calificaciones_Estudiante_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $estudiante['matricula']) . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
