<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold text-dark m-0">Gestión de Exámenes</h1>
            <p class="text-muted m-0 small">Diseño de evaluaciones, fechas de apertura y cierre, y publicación de notas</p>
        </div>
        <a href="<?= BASE_URL ?>/index.php?controller=profesor&action=editorExamen" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-plus-circle-fill me-1"></i> Diseñar Examen
        </a>
    </div>

    <!-- Tabla de Exámenes -->
    <div class="card-table">
        <div class="table-responsive">
            <table class="table mb-0" id="tablaExamenesProf">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Examen</th>
                        <th>Curso</th>
                        <th>Apertura / Cierre</th>
                        <th>Duración</th>
                        <th>Preguntas / Pts</th>
                        <th>Entregas</th>
                        <th>Publicación</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody id="tbodyExamenesProf">
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <span class="spinner-border spinner-border-sm me-2"></span>Cargando exámenes...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<script src="<?= BASE_URL ?>/js/profesor.js?v=<?= time() ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    cargarExamenesProfesor();
});
</script>
