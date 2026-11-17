<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold text-dark m-0">Inscripciones a Cursos</h1>
            <p class="text-muted m-0 small">Asignación formal de estudiantes a grupos y asignaturas</p>
        </div>
        <button class="btn btn-primary rounded-pill px-4" onclick="abrirModalInscripcion()">
            <i class="bi bi-person-check-fill me-1"></i> Nueva Inscripción
        </button>
    </div>

    <!-- Tabla de Inscripciones -->
    <div class="card-table">
        <div class="table-responsive">
            <table class="table mb-0" id="tablaInscripciones">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fecha de Inscripción</th>
                        <th>Curso</th>
                        <th>Matrícula</th>
                        <th>Estudiante</th>
                        <th>Grado y Grupo</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbodyInscripciones">
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <span class="spinner-border spinner-border-sm me-2"></span>Cargando inscripciones...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Inscripción -->
<div class="modal fade" id="modalInscripcion" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">Inscribir Estudiante en Curso</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formInscripcion" onsubmit="guardarInscripcion(event)">
                <div class="modal-body p-4">
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Curso Destino</label>
                        <select id="insCursoId" name="curso_id" class="form-select" required>
                            <option value="">Seleccione el curso...</option>
                            <?php foreach ($cursos as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= e($c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Opciones de Filtrado y Búsqueda de Alumnos -->
                    <div class="p-3 bg-light rounded-3 border mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="small fw-semibold text-secondary">
                                <i class="bi bi-funnel-fill text-primary me-1"></i> Filtrar y Buscar Alumnos
                            </span>
                            <span class="badge bg-primary-subtle text-primary rounded-pill" id="contadorEstudiantesInscripcion">
                                <?= count($estudiantes) ?> disponible(s)
                            </span>
                        </div>
                        <div class="row g-2">
                            <div class="col-12">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                                    <input type="text" id="buscarEstInscripcion" class="form-control" placeholder="Buscar por nombre, apellidos o matrícula..." oninput="filtrarSelectorEstudiantesInscripcion()">
                                </div>
                            </div>
                            <div class="col-6">
                                <select id="filtroGradoInscripcion" class="form-select form-select-sm" onchange="filtrarSelectorEstudiantesInscripcion()">
                                    <option value="">Todos los grados</option>
                                    <?php
                                    $gradosUnicos = array_unique(array_filter(array_column($estudiantes, 'grado')));
                                    sort($gradosUnicos);
                                    foreach ($gradosUnicos as $g):
                                    ?>
                                        <option value="<?= e((string)$g) ?>"><?= e((string)$g) ?>° Grado</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-6">
                                <select id="filtroGrupoInscripcion" class="form-select form-select-sm" onchange="filtrarSelectorEstudiantesInscripcion()">
                                    <option value="">Todos los grupos</option>
                                    <?php
                                    $gruposUnicos = array_unique(array_filter(array_column($estudiantes, 'grupo')));
                                    sort($gruposUnicos);
                                    foreach ($gruposUnicos as $gr):
                                    ?>
                                        <option value="<?= e((string)$gr) ?>">Grupo "<?= e((string)$gr) ?>"</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Estudiante</label>
                        <select id="insEstudianteId" name="estudiante_id" class="form-select" required>
                            <option value="">Seleccione el estudiante...</option>
                            <?php foreach ($estudiantes as $e): ?>
                                <option value="<?= $e['id'] ?>"
                                        data-nombre="<?= e(mb_strtolower(($e['nombre'] ?? '') . ' ' . ($e['Apellidos'] ?? ''), 'UTF-8')) ?>"
                                        data-matricula="<?= e(mb_strtolower($e['matricula'] ?? '', 'UTF-8')) ?>"
                                        data-grado="<?= e((string)($e['grado'] ?? '')) ?>"
                                        data-grupo="<?= e((string)($e['grupo'] ?? '')) ?>">
                                    <?= e($e['matricula'] . ' - ' . $e['nombre'] . ' ' . $e['Apellidos'] . ' (' . $e['grado'] . '° "' . $e['grupo'] . '")') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Realizar Inscripción</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<script src="<?= BASE_URL ?>/js/admin.js?v=<?= time() ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    cargarInscripciones();
});
</script>
