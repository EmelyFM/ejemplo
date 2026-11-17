<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold text-dark m-0">Gestión de Usuarios</h1>
            <p class="text-muted m-0 small">Administración de credenciales, roles y accesos al sistema</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4" onclick="abrirModalUsuario()">
            <i class="bi bi-person-plus-fill me-1"></i> Nuevo Usuario
        </button>
    </div>

    <!-- Buscador y Filtros -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-3">
            <div class="row g-2 align-items-center">
                <div class="col-12 col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" id="buscarUsuario" class="form-control border-start-0" placeholder="Buscar por nombre, apellidos, correo o matrícula..." onkeyup="filtrarTablaUsuarios()">
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <select id="filtroRol" class="form-select" onchange="filtrarTablaUsuarios()">
                        <option value="">Todos los roles</option>
                        <option value="Administrador">Administrador</option>
                        <option value="Profesor">Profesor</option>
                        <option value="Estudiante">Estudiante</option>
                    </select>
                </div>
                <div class="col-12 col-md-3 text-md-end">
                    <span class="text-muted small" id="totalUsuariosBadge">Cargando...</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Usuarios -->
    <div class="card-table">
        <div class="table-responsive">
            <table class="table mb-0" id="tablaUsuarios">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre Completo</th>
                        <th>Correo Electrónico</th>
                        <th>Rol</th>
                        <th>Detalles / Matrícula</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbodyUsuarios">
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            <span class="spinner-border spinner-border-sm me-2"></span>Cargando usuarios...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Crear / Editar Usuario -->
<div class="modal fade" id="modalUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="modalUsuarioTitulo">Nuevo Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formUsuario" onsubmit="guardarUsuario(event)">
                <div class="modal-body p-4">
                    <input type="hidden" id="usuarioId" name="id">
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Nombre</label>
                            <input type="text" id="usuarioNombre" name="nombre" class="form-control" required>
                            <div id="alertaUsuarioNombre" class="alert alert-warning py-1 px-2 mt-1 mb-0 small d-none">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Solo se permiten letras.
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold text-secondary">Apellidos</label>
                            <input type="text" id="usuarioApellidos" name="apellidos" class="form-control" required>
                            <div id="alertaUsuarioApellidos" class="alert alert-warning py-1 px-2 mt-1 mb-0 small d-none">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Solo se permiten letras.
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Correo Electrónico</label>
                            <input type="email" id="usuarioCorreo" name="correo" class="form-control" required>
                            <div id="alertaUsuarioCorreo" class="alert alert-danger py-1 px-2 mt-1 mb-0 small d-none">
                                <i class="bi bi-exclamation-circle-fill me-1"></i> <span id="textoAlertaUsuarioCorreo">El correo electrónico debe contener '@' y '.com'.</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">
                                Contraseña <small class="text-muted fw-normal" id="helpPassword">(Obligatoria para nuevos)</small>
                            </label>
                            <input type="password" id="usuarioPassword" name="password" class="form-control" placeholder="••••••••">
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Rol en el Sistema</label>
                            <select id="usuarioRol" name="rol_id" class="form-select" required onchange="cambiarCamposPorRol(this.value)">
                                <option value="">Seleccione un rol...</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>"><?= e($r['Rol']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Campos extra para Estudiante -->
                        <div id="camposEstudiante" class="col-12 border-top pt-3 d-none">
                            <h6 class="fw-bold small text-primary mb-2">Información del Estudiante</h6>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small text-muted">Matrícula</label>
                                    <input type="text" id="estMatricula" name="matricula" class="form-control form-control-sm" placeholder="EST-2026-XXX">
                                </div>
                                <div class="col-3">
                                    <label class="form-label small text-muted">Grado</label>
                                    <input type="number" id="estGrado" name="grado" class="form-control form-control-sm" value="1" min="1" max="12">
                                </div>
                                <div class="col-3">
                                    <label class="form-label small text-muted">Grupo</label>
                                    <input type="text" id="estGrupo" name="grupo" class="form-control form-control-sm" value="A" maxlength="2">
                                </div>
                            </div>
                        </div>

                        <!-- Campos extra para Profesor -->
                        <div id="camposProfesor" class="col-12 border-top pt-3 d-none">
                            <h6 class="fw-bold small text-success mb-2">Información del Profesor</h6>
                            <label class="form-label small text-muted">Especialidad Académica</label>
                            <input type="text" id="profEspecialidad" name="especialidad" class="form-control form-control-sm" placeholder="Ej. Desarrollo Web y Bases de Datos">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnGuardarUsuario" class="btn btn-primary rounded-pill px-4">Guardar Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<script src="<?= BASE_URL ?>/js/admin.js?v=<?= time() ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    cargarUsuarios();
});
</script>
