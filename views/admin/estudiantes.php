<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold text-dark m-0">Gestión de Estudiantes</h1>
            <p class="text-muted m-0 small">Padrón de alumnos, matrículas, grados y grupos académicos</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4" onclick="abrirModalEstudiante()">
            <i class="bi bi-person-plus-fill me-1"></i> Registrar Estudiante
        </button>
    </div>

    <!-- Tabla de Estudiantes -->
    <div class="card-table">
        <div class="table-responsive">
            <table class="table mb-0" id="tablaEstudiantes">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Matrícula</th>
                        <th>Estudiante</th>
                        <th>Correo</th>
                        <th>Grado y Grupo</th>
                        <th>Cursos Inscritos</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbodyEstudiantes">
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <span class="spinner-border spinner-border-sm me-2"></span>Cargando estudiantes...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Estudiante -->
<div class="modal fade" id="modalEstudiante" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="modalEstudianteTitulo">Registrar Estudiante</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEstudiante" onsubmit="guardarEstudiante(event)">
                <div class="modal-body p-4">
                    <input type="hidden" id="estudianteId" name="id">
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                    <div id="camposUsuarioEstudiante">
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary">Nombre</label>
                                <input type="text" id="estNombre" name="nombre" class="form-control" required>
                                <div id="alertaEstNombre" class="alert alert-warning py-1 px-2 mt-1 mb-0 small d-none">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Solo se permiten letras.
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary">Apellidos</label>
                                <input type="text" id="estApellidos" name="apellidos" class="form-control" required>
                                <div id="alertaEstApellidos" class="alert alert-warning py-1 px-2 mt-1 mb-0 small d-none">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Solo se permiten letras.
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Correo</label>
                            <input type="email" id="estCorreo" name="correo" class="form-control" required>
                            <div id="alertaEstCorreo" class="alert alert-danger py-1 px-2 mt-1 mb-0 small d-none">
                                <i class="bi bi-exclamation-circle-fill me-1"></i> <span id="textoAlertaEstCorreo">El correo electrónico debe contener '@' y '.com'.</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Contraseña Temporal</label>
                            <input type="password" id="estPassword" name="password" class="form-control" placeholder="••••••••">
                        </div>
                    </div>

                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Matrícula</label>
                            <input type="text" id="estMatriculaModal" name="matricula" class="form-control" placeholder="EST-2026-001" required>
                        </div>
                        <div class="col-3">
                            <label class="form-label small fw-semibold text-secondary">Grado</label>
                            <input type="number" id="estGradoModal" name="grado" class="form-control" value="7" min="1" max="12" required>
                        </div>
                        <div class="col-3">
                            <label class="form-label small fw-semibold text-secondary">Grupo</label>
                            <input type="text" id="estGrupoModal" name="grupo" class="form-control" value="A" maxlength="2" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<script src="<?= BASE_URL ?>/js/admin.js?v=<?= time() ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    cargarEstudiantes();
});
</script>
