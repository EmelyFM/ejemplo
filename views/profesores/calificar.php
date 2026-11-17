<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <a href="<?= BASE_URL ?>/index.php?controller=profesor&action=examenes" class="btn btn-outline-secondary btn-sm rounded-pill px-3 mb-2">
                <i class="bi bi-arrow-left me-1"></i> Volver a Exámenes
            </a>
            <h1 class="h3 fw-bold text-dark m-0">Calificaciones: <?= e($examen['nom_exam']) ?></h1>
            <p class="text-muted m-0 small">Curso: <?= e($examen['curso_nombre']) ?> &bull; Revisión de respuestas y asignación de notas</p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <button class="btn <?= $examen['resultados_publicados'] ? 'btn-success' : 'btn-outline-secondary' ?> rounded-pill px-4" onclick="togglePublicar(<?= $examen['id'] ?>, <?= $examen['resultados_publicados'] ? 'false' : 'true' ?>)">
                <i class="bi <?= $examen['resultados_publicados'] ? 'bi-eye-fill' : 'bi-eye-slash-fill' ?> me-1"></i>
                <?= $examen['resultados_publicados'] ? 'Resultados Públicos (Haga clic para ocultar)' : 'Resultados Ocultos (Haga clic para publicar)' ?>
            </button>
            <a href="<?= BASE_URL ?>/index.php?controller=reporte&action=excelCurso&curso_id=<?= $examen['Cursos_id'] ?>" class="btn btn-outline-success rounded-pill px-3">
                <i class="bi bi-file-earmark-excel me-1"></i> Excel
            </a>
        </div>
    </div>

    <!-- Tabla de Entregas de Alumnos -->
    <div class="card-table">
        <div class="table-responsive">
            <table class="table mb-0" id="tablaEntregas">
                <thead>
                    <tr>
                        <th>Matrícula</th>
                        <th>Estudiante</th>
                        <th>Grado y Grupo</th>
                        <th>Fecha de Entrega</th>
                        <th>Preguntas Abiertas</th>
                        <th>Calificación (Total: <?= (float)($examen['puntaje_total'] ?? 0) ?> pts)</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entregas)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 text-secondary d-block mb-2"></i>
                                Ningún estudiante ha enviado respuestas para esta evaluación aún.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($entregas as $ent): ?>
                            <tr>
                                <td class="fw-bold"><?= e($ent['matricula']) ?></td>
                                <td><?= e($ent['Apellidos'] . ' ' . $ent['nombre']) ?></td>
                                <td><?= e($ent['grado'] . '° "' . $ent['grupo'] . '"') ?></td>
                                <td><?= e($ent['Fecha']) ?></td>
                                <td>
                                    <?php if ($ent['abiertas_sin_calificar'] > 0): ?>
                                        <span class="badge bg-warning text-dark rounded-pill">
                                            <?= $ent['abiertas_sin_calificar'] ?> por calificar
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success rounded-pill">
                                            <i class="bi bi-check2"></i> Completas
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                        $ptsTotalExam = (float)($ent['puntaje_total'] ?? $examen['puntaje_total'] ?? 0);
                                        $notaAlumno = (float)$ent['calificaciones'];
                                        $esAprobado = $ptsTotalExam > 0 ? ($notaAlumno >= ($ptsTotalExam / 2)) : false;
                                    ?>
                                    <span class="fw-bold fs-6 <?= $esAprobado ? 'text-success' : 'text-danger' ?>">
                                        <?= number_format($notaAlumno, 2) ?> / <?= $ptsTotalExam ?> pts
                                    </span>
                                    <span class="badge <?= $esAprobado ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle' ?> rounded-pill ms-1">
                                        <?= $esAprobado ? 'Aprobado' : 'Reprobado' ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($ent['estado'] === 'pendiente'): ?>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill">Pendiente</span>
                                    <?php else: ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">Calificado</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1">
                                        <button class="btn btn-primary btn-sm rounded-pill px-3" onclick="revisarRespuestasAlumno(<?= $examen['id'] ?>, <?= $ent['estudiante_id'] ?>, '<?= e(addslashes($ent['nombre'] . ' ' . $ent['Apellidos'])) ?>')">
                                            <i class="bi bi-pencil-square me-1"></i> Revisar Respuestas
                                        </button>
                                        <a href="<?= BASE_URL ?>/index.php?controller=reporte&action=pdfIndividual&examen_id=<?= $examen['id'] ?>&estudiante_id=<?= $ent['estudiante_id'] ?>" target="_blank" class="btn btn-outline-danger btn-sm rounded-pill px-2" title="Boleta PDF de este Examen">
                                            <i class="bi bi-file-pdf"></i> Boleta
                                        </a>
                                        <a href="<?= BASE_URL ?>/index.php?controller=reporte&action=pdfEstudiante&estudiante_id=<?= $ent['estudiante_id'] ?>&curso_id=<?= $examen['Cursos_id'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-2" title="Reporte PDF de Calificaciones del Estudiante">
                                            <i class="bi bi-person-vcard"></i> PDF
                                        </a>
                                        <a href="<?= BASE_URL ?>/index.php?controller=reporte&action=excelEstudiante&estudiante_id=<?= $ent['estudiante_id'] ?>&curso_id=<?= $examen['Cursos_id'] ?>" class="btn btn-outline-success btn-sm rounded-pill px-2" title="Exportar Excel de Calificaciones del Estudiante">
                                            <i class="bi bi-file-earmark-excel"></i> Excel
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Revisión y Calificación Manual -->
<div class="modal fade" id="modalRevisar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <div>
                    <h5 class="modal-title fw-bold" id="modalRevisarTitulo">Revisión de Evaluación</h5>
                    <small class="text-muted" id="modalRevisarSubtitulo"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="contenedorRespuestasAlumno">
                    <div class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Cargando respuestas...</div>
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
