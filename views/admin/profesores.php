<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold text-dark m-0">Gestión de Profesores</h1>
            <p class="text-muted m-0 small">Plantilla docente registrada y especialidades académicas</p>
        </div>
        <button class="btn btn-success rounded-pill px-4" onclick="abrirModalProfesor()">
            <i class="bi bi-person-plus-fill me-1"></i> Registrar Profesor
        </button>
    </div>

    <!-- Tabla de Profesores -->
    <div class="card-table">
        <div class="table-responsive">
            <table class="table mb-0" id="tablaProfesores">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre y Apellidos</th>
                        <th>Correo</th>
                        <th>Especialidad</th>
                        <th>Cursos a Cargo</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbodyProfesores">
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <span class="spinner-border spinner-border-sm me-2"></span>Cargando profesores...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Profesor -->
<div class="modal fade" id="modalProfesor" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="modalProfesorTitulo">Registrar Profesor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formProfesor" onsubmit="guardarProfesor(event)">
                <div class="modal-body p-4">
                    <input type="hidden" id="profesorId" name="id">
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                    <div id="camposUsuarioProfesor">
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary">Nombre</label>
                                <input type="text" id="profNombre" name="nombre" class="form-control" required>
                                <div id="alertaProfNombre" class="alert alert-warning py-1 px-2 mt-1 mb-0 small d-none">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Solo se permiten letras.
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold text-secondary">Apellidos</label>
                                <input type="text" id="profApellidos" name="apellidos" class="form-control" required>
                                <div id="alertaProfApellidos" class="alert alert-warning py-1 px-2 mt-1 mb-0 small d-none">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i> Solo se permiten letras.
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Correo Institucional</label>
                            <input type="email" id="profCorreo" name="correo" class="form-control" required>
                            <div id="alertaProfCorreo" class="alert alert-danger py-1 px-2 mt-1 mb-0 small d-none">
                                <i class="bi bi-exclamation-circle-fill me-1"></i> <span id="textoAlertaProfCorreo">El correo electrónico debe contener '@' y '.com'.</span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary">Contraseña Temporal</label>
                            <input type="password" id="profPassword" name="password" class="form-control" placeholder="••••••••">
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small fw-semibold text-secondary">Especialidad Académica</label>
                        <input type="text" id="profEspecialidadModal" name="especialidad" class="form-control" placeholder="Ej. Sistemas Distribuidos" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<script src="<?= BASE_URL ?>/js/admin.js?v=<?= time() ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    cargarProfesores();
});
</script>
