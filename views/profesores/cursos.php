<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold text-dark m-0">Mis Cursos Asignados</h1>
            <p class="text-muted m-0 small">Asignaturas a su cargo, alumnos inscritos y reportes ejecutivos</p>
        </div>
    </div>

    <div class="row g-4">
        <?php if (empty($cursos)): ?>
            <div class="col-12 text-center py-5">
                <i class="bi bi-journal-x text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-secondary">No tiene cursos asignados</h5>
                <p class="text-muted small">Póngase en contacto con el administrador del sistema para la asignación de asignaturas.</p>
            </div>
        <?php else: ?>
            <?php foreach ($cursos as $c): ?>
                <div class="col-12 col-md-6 col-xl-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">
                                    <i class="bi bi-mortarboard me-1"></i> Curso Activo
                                </span>
                                <span class="text-muted small">ID #<?= $c['id'] ?></span>
                            </div>
                            <h5 class="fw-bold text-dark mb-2"><?= e($c['nombre']) ?></h5>
                            <p class="text-muted small mb-3">
                                <i class="bi bi-calendar3 me-1"></i> Vigencia: <?= $c['fecha_ini'] ?> al <?= $c['fecha_fin'] ?>
                            </p>

                            <div class="d-flex gap-3 mb-4 text-center">
                                <div class="p-2 bg-light rounded-3 flex-fill">
                                    <div class="fw-bold text-primary fs-5"><?= $c['total_estudiantes'] ?></div>
                                    <small class="text-muted" style="font-size: 0.75rem;">Estudiantes</small>
                                </div>
                                <div class="p-2 bg-light rounded-3 flex-fill">
                                    <div class="fw-bold text-success fs-5"><?= $c['total_examenes'] ?></div>
                                    <small class="text-muted" style="font-size: 0.75rem;">Exámenes</small>
                                </div>
                            </div>

                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill flex-fill" onclick="verEstudiantesInscritos(<?= $c['id'] ?>, '<?= e(addslashes($c['nombre'])) ?>')">
                                    <i class="bi bi-people me-1"></i> Alumnos (<?= $c['total_estudiantes'] ?>)
                                </button>
                                <a href="<?= BASE_URL ?>/index.php?controller=reporte&action=pdfCurso&curso_id=<?= $c['id'] ?>" target="_blank" class="btn btn-outline-danger btn-sm rounded-pill" title="Descargar Reporte PDF">
                                    <i class="bi bi-file-pdf"></i> PDF
                                </a>
                                <a href="<?= BASE_URL ?>/index.php?controller=reporte&action=excelCurso&curso_id=<?= $c['id'] ?>" class="btn btn-outline-success btn-sm rounded-pill" title="Exportar Calificaciones Excel">
                                    <i class="bi bi-file-earmark-excel"></i> Excel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Ver Estudiantes Inscritos -->
<div class="modal fade" id="modalAlumnosCurso" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="modalAlumnosTitulo">Estudiantes Inscritos</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th>Matrícula</th>
                                <th>Nombre y Apellidos</th>
                                <th>Correo</th>
                                <th>Grado y Grupo</th>
                                <th>Fecha Inscripción</th>
                                <th class="text-end">Reporte Individual</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyAlumnosCurso">
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">Cargando...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 pb-4 px-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<script src="<?= BASE_URL ?>/js/profesor.js?v=<?= time() ?>"></script>
