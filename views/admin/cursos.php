<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold text-dark m-0">Gestión de Cursos</h1>
            <p class="text-muted m-0 small">Asignaturas académicas, periodos de vigencia y profesores asignados</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4" onclick="abrirModalCurso()">
            <i class="bi bi-journal-plus me-1"></i> Nuevo Curso
        </button>
    </div>

    <!-- Tabla de Cursos -->
    <div class="card-table">
        <div class="table-responsive">
            <table class="table mb-0" id="tablaCursos">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Curso</th>
                        <th>Profesor Titular</th>
                        <th>Fecha Inicio</th>
                        <th>Fecha Fin</th>
                        <th>Alumnos</th>
                        <th>Exámenes</th>
                        <th class="text-end">Reportes y Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbodyCursos">
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <span class="spinner-border spinner-border-sm me-2"></span>Cargando cursos...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Curso -->
<div class="modal fade" id="modalCurso" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="modalCursoTitulo">Nuevo Curso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formCurso" onsubmit="guardarCurso(event)">
                <div class="modal-body p-4">
                    <input type="hidden" id="cursoId" name="id">
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nombre del Curso</label>
                        <input type="text" id="cursoNombre" name="nombre" class="form-control" placeholder="Ej. Arquitectura de Software" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Profesor Responsable</label>
                        <select id="cursoProfesorId" name="profesor_id" class="form-select" required>
                            <option value="">Seleccione un profesor...</option>
                            <?php foreach ($profesores as $p): ?>
                                <option value="<?= $p['id'] ?>">
                                    <?= e($p['nombre'] . ' ' . $p['Apellidos'] . ' (' . $p['especialidad'] . ')') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Fecha de Inicio</label>
                            <input type="date" id="cursoFechaIni" name="fecha_ini" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Fecha de Término</label>
                            <input type="date" id="cursoFechaFin" name="fecha_fin" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Guardar Curso</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<script src="<?= BASE_URL ?>/js/admin.js?v=<?= time() ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    cargarCursos();
});
</script>
