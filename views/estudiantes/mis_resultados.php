<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 fw-bold text-dark m-0">Mis Calificaciones y Resultados</h1>
            <p class="text-muted m-0 small">Historial de evaluaciones publicadas y descarga de boletas oficiales</p>
        </div>
    </div>

    <!-- Si se está consultando un examen específico con resultados publicados -->
    <?php if ($detalleExamen && !empty($detalleExamen['resultados_publicados'])): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4 p-4 border-start border-primary border-4">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                <div>
                    <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 mb-1">EVALUACIÓN PUBLICADA</span>
                    <h4 class="fw-bold m-0 text-dark"><?= e($detalleExamen['nom_exam']) ?></h4>
                    <p class="text-muted small m-0">Curso: <?= e($detalleExamen['curso_nombre']) ?> &bull; Evaluado el: <?= $detalleExamen['Fecha'] ?></p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <?php
                        $ptsTotalDet = (float)($detalleExamen['puntaje_total'] ?? 100);
                        $notaDet = (float)$detalleExamen['calificaciones'];
                        $aprobadoDet = $ptsTotalDet > 0 ? ($notaDet >= ($ptsTotalDet / 2)) : false;
                    ?>
                    <div class="text-end">
                        <small class="text-muted d-block">Nota Final Obtenida</small>
                        <span class="fs-3 fw-bold <?= $aprobadoDet ? 'text-success' : 'text-danger' ?>">
                            <?= number_format($notaDet, 2) ?> / <?= $ptsTotalDet ?> pts
                        </span>
                    </div>
                    <a href="<?= BASE_URL ?>/index.php?controller=reporte&action=pdfIndividual&examen_id=<?= $detalleExamen['Examenes_id'] ?>" target="_blank" class="btn btn-danger rounded-pill px-3 shadow-sm">
                        <i class="bi bi-file-pdf me-1"></i> Boleta PDF
                    </a>
                </div>
            </div>

            <!-- Desglose de Preguntas y Respuestas -->
            <h6 class="fw-bold text-dark border-top pt-3 mt-2 mb-3">Retroalimentación y Respuestas por Pregunta:</h6>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($respuestasExamen as $idx => $r): ?>
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold small text-secondary">Reactivo #<?= $idx + 1 ?> (<?= e($r['tipo']) ?>)</span>
                            <span class="badge <?= ($r['puntaje_obtenido'] !== null && (float)$r['puntaje_obtenido'] > 0) ? 'bg-success' : 'bg-danger' ?> rounded-pill">
                                <?= $r['puntaje_obtenido'] !== null ? number_format((float)$r['puntaje_obtenido'], 2) : 'Pendiente' ?> / <?= $r['puntaje_maximo'] ?> pts
                            </span>
                        </div>
                        <h6 class="fw-bold text-dark mb-2"><?= e($r['pregunta']) ?></h6>

                        <?php if (!empty($r['imagen'])): ?>
                            <?php $srcImg = (strpos($r['imagen'], 'data:image') === 0 || strpos($r['imagen'], 'http') === 0) ? $r['imagen'] : (BASE_URL . '/' . ltrim($r['imagen'], '/')); ?>
                            <div class="mb-2 text-center bg-white p-2 rounded-3 border">
                                <img src="<?= e($srcImg) ?>" 
                                     alt="Imagen de la pregunta" 
                                     class="img-fluid rounded-2" 
                                     style="max-height: 220px; object-fit: contain; cursor: zoom-in;"
                                     onclick="Swal.fire({ title: 'Reactivo #<?= $idx + 1 ?>', imageUrl: this.src, imageAlt: 'Imagen de la pregunta', width: 'auto', showCloseButton: true, confirmButtonText: 'Cerrar' })">
                                <div class="small text-muted mt-1"><i class="bi bi-zoom-in me-1"></i> Clic en la imagen para ampliar</div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="small mb-1">
                            <span class="text-muted">Tu respuesta:</span> 
                            <strong class="text-dark"><?= e($r['respuesta_estudiante'] ?: '(Sin respuesta)') ?></strong>
                        </div>

                        <?php if ($r['tipo'] !== 'abierta'): ?>
                            <div class="small text-success">
                                <span>Respuesta correcta:</span> 
                                <strong><?= e($r['respuesta_correcta']) ?></strong>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($r['retroalimentacion'])): ?>
                            <div class="small alert alert-info py-1 px-2 mt-2 mb-0 rounded-2">
                                <i class="bi bi-info-circle me-1"></i> <strong>Comentario del profesor:</strong> <?= e($r['retroalimentacion']) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Tabla Histórica de Calificaciones Publicadas -->
    <div class="card-table">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Curso</th>
                        <th>Examen</th>
                        <th>Profesor</th>
                        <th>Fecha Evaluación</th>
                        <th>Calificación (Puntos)</th>
                        <th>Estado</th>
                        <th class="text-end">Comprobante</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($calificacionesPublicadas)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-award fs-2 text-secondary d-block mb-2"></i>
                                Aún no tienes calificaciones publicadas. Cuando tus profesores publiquen los resultados, aparecerán aquí.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($calificacionesPublicadas as $c): ?>
                            <?php
                                $ptsMax = (float)($c['puntaje_total'] ?? 100);
                                $notaCal = (float)$c['calificaciones'];
                                $esAprob = $ptsMax > 0 ? ($notaCal >= ($ptsMax / 2)) : false;
                            ?>
                            <tr>
                                <td class="fw-bold text-dark"><?= e($c['curso_nombre']) ?></td>
                                <td><?= e($c['nom_exam']) ?></td>
                                <td><?= e($c['profesor_nombre'] . ' ' . $c['profesor_apellidos']) ?></td>
                                <td><?= $c['fecha_evaluacion'] ?></td>
                                <td>
                                    <span class="fw-bold fs-6 <?= $esAprob ? 'text-success' : 'text-danger' ?>">
                                        <?= number_format($notaCal, 2) ?> / <?= $ptsMax ?> pts
                                    </span>
                                </td>
                                <td>
                                    <?php if ($esAprob): ?>
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                                            <i class="bi bi-check-circle me-1"></i> Aprobado
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill">
                                            <i class="bi bi-x-circle me-1"></i> Reprobado
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= BASE_URL ?>/index.php?controller=estudiante&action=misResultados&examen_id=<?= $c['examen_id'] ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 me-1">
                                        <i class="bi bi-eye"></i> Detalle
                                    </a>
                                    <a href="<?= BASE_URL ?>/index.php?controller=reporte&action=pdfIndividual&examen_id=<?= $c['examen_id'] ?>" target="_blank" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                                        <i class="bi bi-file-pdf"></i> Boleta PDF
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
