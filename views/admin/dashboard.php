<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container-fluid">
    <!-- Título y Bienvenida -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold text-dark m-0">Dashboard de Administración</h1>
            <p class="text-muted m-0 small">Supervisión general del sistema académico y estadísticas en tiempo real</p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/index.php?controller=admin&action=usuarios" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                <i class="bi bi-person-plus-fill me-1"></i> Gestionar Usuarios
            </a>
            <a href="<?= BASE_URL ?>/index.php?controller=admin&action=cursos" class="btn btn-outline-secondary btn-sm rounded-pill px-3 shadow-sm">
                <i class="bi bi-journal-plus me-1"></i> Ver Cursos
            </a>
        </div>
    </div>

    <!-- Tarjetas de Métricas -->
    <div class="row g-3 mb-4">
        <!-- Estudiantes -->
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="stat-card">
                <div>
                    <div class="stat-value text-indigo" style="color: #4f46e5;"><?= $totales['estudiantes'] ?></div>
                    <div class="stat-label">Estudiantes</div>
                </div>
                <div class="stat-icon" style="background: #eef2ff; color: #4f46e5;">
                    <i class="bi bi-backpack4-fill"></i>
                </div>
            </div>
        </div>

        <!-- Profesores -->
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="stat-card">
                <div>
                    <div class="stat-value text-emerald" style="color: #059669;"><?= $totales['profesores'] ?></div>
                    <div class="stat-label">Profesores</div>
                </div>
                <div class="stat-icon" style="background: #ecfdf5; color: #059669;">
                    <i class="bi bi-person-video3"></i>
                </div>
            </div>
        </div>

        <!-- Cursos -->
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="stat-card">
                <div>
                    <div class="stat-value text-sky" style="color: #0284c7;"><?= $totales['cursos'] ?></div>
                    <div class="stat-label">Cursos</div>
                </div>
                <div class="stat-icon" style="background: #f0f9ff; color: #0284c7;">
                    <i class="bi bi-journal-bookmark-fill"></i>
                </div>
            </div>
        </div>

        <!-- Total Exámenes -->
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="stat-card">
                <div>
                    <div class="stat-value text-purple" style="color: #9333ea;"><?= $totales['examenes'] ?></div>
                    <div class="stat-label">Exámenes</div>
                </div>
                <div class="stat-icon" style="background: #faf5ff; color: #9333ea;">
                    <i class="bi bi-file-earmark-text-fill"></i>
                </div>
            </div>
        </div>

        <!-- Exámenes Activos -->
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="stat-card">
                <div>
                    <div class="stat-value text-success"><?= $totales['examenes_activos'] ?></div>
                    <div class="stat-label">Activos</div>
                </div>
                <div class="stat-icon" style="background: #f0fdf4; color: #16a34a;">
                    <i class="bi bi-clock-history"></i>
                </div>
            </div>
        </div>

        <!-- Exámenes Finalizados -->
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="stat-card">
                <div>
                    <div class="stat-value text-secondary"><?= $totales['examenes_fin'] ?></div>
                    <div class="stat-label">Finalizados</div>
                </div>
                <div class="stat-icon" style="background: #f1f5f9; color: #64748b;">
                    <i class="bi bi-check2-all"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Sección de Gráficas Estadísticas con Chart.js -->
    <div class="row g-4 mb-4">
        <!-- Gráfica 1: Aprobados vs Reprobados -->
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <h5 class="fw-bold text-dark m-0">Tasa de Aprobación General</h5>
                    <small class="text-muted">Distribución de estudiantes aprobados vs reprobados</small>
                </div>
                <div class="card-body p-4 d-flex align-items-center justify-content-center">
                    <div style="max-height: 260px; width: 100%;">
                        <canvas id="chartAprobacion"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfica 2: Promedio de Calificaciones por Curso -->
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <h5 class="fw-bold text-dark m-0">Rendimiento Promedio por Curso</h5>
                    <small class="text-muted">Promedios de calificaciones acumuladas (escala 0 - 100)</small>
                </div>
                <div class="card-body p-4">
                    <div style="max-height: 260px; width: 100%;">
                        <canvas id="chartCursos"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfica 3: Distribución por Rangos de Calificación -->
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <h5 class="fw-bold text-dark m-0">Distribución de Calificaciones por Rangos</h5>
                    <small class="text-muted">Concentración de notas de evaluaciones presentadas</small>
                </div>
                <div class="card-body p-4">
                    <div style="height: 240px; width: 100%;">
                        <canvas id="chartDistribucion"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<script src="<?= BASE_URL ?>/js/estadisticas.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    cargarEstadisticasDashboard();
});
</script>
