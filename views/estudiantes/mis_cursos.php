<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold text-dark m-0">Mis Cursos Inscritos</h1>
            <p class="text-muted m-0 small">Asignaturas académicas registradas en su plan de estudios</p>
        </div>
    </div>

    <div class="row g-4">
        <?php if (empty($cursos)): ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-book text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-secondary">No tienes cursos asignados</h5>
                <p class="text-muted small">Comunícate con la coordinación académica para registrar tus asignaturas.</p>
            </div>
        <?php else: ?>
            <?php foreach ($cursos as $c): ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                        <div class="card-body">
                            <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-1 mb-2">Curso Activo</span>
                            <h5 class="fw-bold text-dark mb-1"><?= e($c['nombre']) ?></h5>
                            <p class="text-secondary small mb-3">
                                <i class="bi bi-person-badge me-1"></i> Prof. <?= e($c['profesor_nombre'] . ' ' . $c['profesor_apellidos']) ?> (<?= e($c['especialidad']) ?>)
                            </p>
                            <div class="p-3 bg-light rounded-3 small mb-3 text-muted">
                                <div><i class="bi bi-calendar-event me-1"></i> Periodo: <?= $c['fecha_ini'] ?> al <?= $c['fecha_fin'] ?></div>
                                <div><i class="bi bi-check2-circle me-1"></i> Fecha de Inscripción: <?= $c['fecha_inscripcion'] ?></div>
                            </div>
                            <a href="<?= BASE_URL ?>/index.php?controller=estudiante&action=dashboard" class="btn btn-outline-primary btn-sm rounded-pill w-100">
                                <i class="bi bi-eye me-1"></i> Ver Evaluaciones del Curso
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
