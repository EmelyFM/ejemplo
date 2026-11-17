<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold text-dark m-0">Panel del Docente</h1>
            <p class="text-muted m-0 small">Bienvenido(a), Prof. <?= e($_SESSION['nombre_completo']) ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/index.php?controller=profesor&action=editorExamen" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                <i class="bi bi-plus-circle-fill me-1"></i> Crear Nuevo Examen
            </a>
            <a href="<?= BASE_URL ?>/index.php?controller=profesor&action=examenes" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                <i class="bi bi-file-earmark-check me-1"></i> Gestionar Exámenes
            </a>
        </div>
    </div>

    <!-- Resumen Docente -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="stat-card">
                <div>
                    <div class="stat-value text-indigo" style="color: #4f46e5;"><?= count($cursos) ?></div>
                    <div class="stat-label">Cursos Asignados</div>
                </div>
                <div class="stat-icon" style="background: #eef2ff; color: #4f46e5;">
                    <i class="bi bi-journals"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="stat-card">
                <div>
                    <div class="stat-value text-emerald" style="color: #059669;"><?= count($examenes) ?></div>
                    <div class="stat-label">Exámenes Creados</div>
                </div>
                <div class="stat-icon" style="background: #ecfdf5; color: #059669;">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-4">
            <div class="stat-card">
                <div>
                    <?php 
                    $totalEntregas = array_sum(array_column($examenes, 'total_entregas'));
                    ?>
                    <div class="stat-value text-sky" style="color: #0284c7;"><?= $totalEntregas ?></div>
                    <div class="stat-label">Evaluaciones Recibidas</div>
                </div>
                <div class="stat-icon" style="background: #f0f9ff; color: #0284c7;">
                    <i class="bi bi-check2-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de Estadísticas de Rendimiento Académico con Chart.js -->
    <div class="row g-4 mb-4">
        <!-- Gráfica 1: Porcentaje de Aprobados vs Reprobados -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="fw-bold text-dark m-0">Porcentaje de Aprobados</h5>
                        <small class="text-muted">Relación de aprobados vs reprobados en sus cursos</small>
                    </div>
                    <span id="badgePorcentajeAprobadosProf" class="badge bg-success-subtle text-success rounded-pill px-3 py-2">0% Aprobados</span>
                </div>
                <div class="card-body p-4 d-flex align-items-center justify-content-center">
                    <div style="max-height: 260px; width: 100%;">
                        <canvas id="chartAprobacionProf"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfica 2: Promedio de Calificaciones por Curso -->
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <h5 class="fw-bold text-dark m-0">Promedio de Calificaciones por Curso</h5>
                    <small class="text-muted">Rendimiento promedio en cada asignatura asignada (escala 0 - 100)</small>
                </div>
                <div class="card-body p-4">
                    <div style="max-height: 260px; width: 100%;">
                        <canvas id="chartCursosProf"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfica 3: Resultados por Examen -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <h5 class="fw-bold text-dark m-0">Resultados por Examen</h5>
                    <small class="text-muted">Comparativa de calificación promedio, nota máxima y nota mínima por evaluación</small>
                </div>
                <div class="card-body p-4">
                    <div style="height: 260px; width: 100%;">
                        <canvas id="chartExamenesProf"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cursos y Exámenes Activos -->
    <div class="row g-4">
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark m-0">Mis Cursos</h5>
                    <a href="<?= BASE_URL ?>/index.php?controller=profesor&action=cursos" class="small text-primary text-decoration-none">Ver todos &rarr;</a>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($cursos)): ?>
                        <div class="text-center py-4 text-muted">No tienes cursos asignados actualmente.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($cursos as $c): ?>
                                <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold mb-1"><?= e($c['nombre']) ?></h6>
                                        <small class="text-muted">
                                            <i class="bi bi-people me-1"></i> <?= $c['total_estudiantes'] ?> estudiantes matriculados &bull; 
                                            <i class="bi bi-calendar3 me-1"></i> Hasta <?= $c['fecha_fin'] ?>
                                        </small>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <a href="<?= BASE_URL ?>/index.php?controller=reporte&action=pdfCurso&curso_id=<?= $c['id'] ?>" target="_blank" class="btn btn-outline-danger btn-sm" title="Reporte PDF">
                                            <i class="bi bi-file-pdf"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/index.php?controller=reporte&action=excelCurso&curso_id=<?= $c['id'] ?>" class="btn btn-outline-success btn-sm" title="Exportar Excel">
                                            <i class="bi bi-file-earmark-excel"></i>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold text-dark m-0">Exámenes Recientes</h5>
                    <a href="<?= BASE_URL ?>/index.php?controller=profesor&action=examenes" class="small text-primary text-decoration-none">Administrar &rarr;</a>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($examenes)): ?>
                        <div class="text-center py-4 text-muted">Aún no has creado exámenes.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($examenes, 0, 5) as $ex): ?>
                                <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="d-flex align-items-center gap-2 mb-1">
                                            <h6 class="fw-bold mb-0"><?= e($ex['nom_exam']) ?></h6>
                                            <?php if ($ex['resultados_publicados']): ?>
                                                <span class="badge bg-success-subtle text-success small rounded-pill">Resultados Publicados</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary small rounded-pill">Privado</span>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted">
                                            Curso: <?= e($ex['curso_nombre']) ?> &bull; <?= $ex['total_preguntas'] ?> preguntas (<?= $ex['puntaje_total'] ?> pts) &bull; <?= $ex['total_entregas'] ?> entregas
                                        </small>
                                    </div>
                                    <a href="<?= BASE_URL ?>/index.php?controller=profesor&action=calificar&examen_id=<?= $ex['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        Revisar
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<script src="<?= BASE_URL ?>/js/estadisticas.js?v=<?= time() ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    cargarEstadisticasProfesor();
});
</script>
