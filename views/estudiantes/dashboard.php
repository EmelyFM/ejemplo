<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container-fluid">
    <!-- Bienvenida al Estudiante -->
    <div class="card border-0 shadow-sm rounded-4 mb-4 bg-primary text-white p-4" style="background: linear-gradient(135deg, #4f46e5 0%, #312e81 100%);">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <span class="badge bg-white text-primary rounded-pill px-3 py-1 mb-2 fw-semibold">PORTAL DEL ESTUDIANTE</span>
                <h1 class="h3 fw-bold m-0">¡Hola, <?= e($_SESSION['nombre']) ?>!</h1>
                <p class="m-0 text-light small opacity-75">
                    Bienvenido a tu panel académico. Consulta tus exámenes activos y calificaciones oficiales.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= BASE_URL ?>/index.php?controller=estudiante&action=misResultados" class="btn btn-light rounded-pill px-4 shadow-sm fw-semibold text-primary">
                    <i class="bi bi-award-fill me-1"></i> Ver Mis Calificaciones
                </a>
            </div>
        </div>
    </div>

    <!-- Sección de Exámenes Disponibles para Resolver -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
            <h5 class="fw-bold text-dark m-0">
                <i class="bi bi-play-circle-fill text-success me-2"></i>Exámenes Disponibles Ahora
            </h5>
            <small class="text-muted">Evaluaciones activas listas para responder dentro del plazo de entrega</small>
        </div>
        <div class="card-body p-4">
            <?php if (empty($disponibles)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="bi bi-check-circle-fill text-success fs-2 d-block mb-2"></i>
                    No tienes exámenes pendientes por realizar en este momento. ¡Estás al día!
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($disponibles as $d): ?>
                        <div class="col-12 col-md-6 col-xl-4">
                            <div class="card border border-primary-subtle rounded-4 h-100 p-3 shadow-none bg-primary-subtle bg-opacity-10">
                                <div class="card-body">
                                    <span class="badge bg-success rounded-pill px-3 py-1 mb-2">Habilitado</span>
                                    <h5 class="fw-bold text-dark mb-1"><?= e($d['nom_exam']) ?></h5>
                                    <p class="text-secondary small mb-2">
                                        <i class="bi bi-book me-1"></i> Curso: <?= e($d['curso_nombre']) ?>
                                    </p>
                                    <div class="small text-muted mb-3">
                                        <div><i class="bi bi-clock me-1"></i> Límite: <?= $d['fecha_fin'] ?? 'Sin límite' ?></div>
                                        <div><i class="bi bi-stopwatch me-1"></i> Duración: <?= $d['duracion_minutos'] ?> min</div>
                                    </div>
                                    <a href="<?= BASE_URL ?>/index.php?controller=estudiante&action=responderExamen&id=<?= $d['id'] ?>" class="btn btn-primary w-100 rounded-pill fw-semibold">
                                        <i class="bi bi-pencil-fill me-1"></i> Comenzar Examen
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4">
        <!-- Exámenes Realizados -->
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <h5 class="fw-bold text-dark m-0">Evaluaciones Presentadas</h5>
                    <small class="text-muted">Historial de exámenes que ya has contestado</small>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($realizados)): ?>
                        <div class="text-center py-4 text-muted">Aún no has presentado ninguna evaluación.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($realizados as $r): ?>
                                <div class="list-group-item px-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div>
                                        <h6 class="fw-bold mb-1"><?= e($r['nom_exam']) ?></h6>
                                        <small class="text-muted">
                                            Curso: <?= e($r['curso_nombre']) ?> &bull; 
                                            Profesor: <?= e($r['profesor_nombre'] . ' ' . $r['profesor_apellidos']) ?>
                                        </small>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if (!empty($r['resultados_publicados'])): ?>
                                            <?php
                                                $ptsTotDash = (float)($r['puntaje_total'] ?? 100);
                                                $notaDash = (float)$r['calificacion'];
                                                $aprobDash = $ptsTotDash > 0 ? ($notaDash >= ($ptsTotDash / 2)) : false;
                                            ?>
                                            <span class="badge <?= $aprobDash ? 'bg-success' : 'bg-danger' ?> rounded-pill px-3 py-2 fs-6">
                                                <?= number_format($notaDash, 2) ?> / <?= $ptsTotDash ?> pts
                                            </span>
                                            <a href="<?= BASE_URL ?>/index.php?controller=reporte&action=pdfIndividual&examen_id=<?= $r['id'] ?>" target="_blank" class="btn btn-outline-danger btn-sm rounded-pill" title="Descargar Boleta PDF">
                                                <i class="bi bi-file-pdf"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary rounded-pill px-3 py-2">
                                                <i class="bi bi-hourglass-split me-1"></i> En revisión
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Cursos Inscritos -->
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                    <h5 class="fw-bold text-dark m-0">Mis Asignaturas</h5>
                    <small class="text-muted">Cursos en los que te encuentras matriculado</small>
                </div>
                <div class="card-body p-4">
                    <?php if (empty($cursos)): ?>
                        <div class="text-center py-4 text-muted">No estás inscrito en ningún curso.</div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($cursos as $c): ?>
                                <div class="list-group-item px-0 py-3">
                                    <h6 class="fw-bold mb-1 text-primary"><?= e($c['nombre']) ?></h6>
                                    <div class="small text-muted mb-1">
                                        <i class="bi bi-person me-1"></i> Prof. <?= e($c['profesor_nombre'] . ' ' . $c['profesor_apellidos']) ?>
                                    </div>
                                    <small class="badge bg-light text-secondary border">
                                        Inscripción: <?= $c['fecha_inscripcion'] ?>
                                    </small>
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
