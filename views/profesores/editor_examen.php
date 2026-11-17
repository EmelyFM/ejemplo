<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <a href="<?= BASE_URL ?>/index.php?controller=profesor&action=examenes" class="btn btn-outline-secondary btn-sm rounded-pill px-3 mb-2">
                <i class="bi bi-arrow-left me-1"></i> Volver a Exámenes
            </a>
            <h1 class="h3 fw-bold text-dark m-0"><?= $examen ? 'Editar Examen: ' . e($examen['nom_exam']) : 'Diseñador de Exámenes' ?></h1>
            <p class="text-muted m-0 small">Configure los parámetros de tiempo, cursos asignados y reactivos de evaluación</p>
        </div>
        <?php if ($examen): ?>
            <div>
                <a href="<?= BASE_URL ?>/index.php?controller=profesor&action=calificar&examen_id=<?= $examen['id'] ?>" class="btn btn-outline-primary rounded-pill px-3">
                    <i class="bi bi-check2-circle me-1"></i> Ver Respuestas de Alumnos
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- SECCIÓN 1: DATOS GENERALES DEL EXAMEN -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
            <h5 class="fw-bold text-dark m-0">1. Parámetros Generales de la Evaluación</h5>
            <small class="text-muted">Defina el nombre, asignatura, fechas límite y duración</small>
        </div>
        <div class="card-body p-4">
            <form id="formDatosExamen" onsubmit="guardarExamen(event)">
                <input type="hidden" id="examenId" name="id" value="<?= $examen['id'] ?? '' ?>">
                <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Nombre de la Evaluación / Examen</label>
                        <input type="text" id="nomExam" name="nom_exam" class="form-control" value="<?= e($examen['nom_exam'] ?? '') ?>" placeholder="Ej. Examen Parcial: Arquitectura MVC" required>
                    </div>

                    <div class="col-12 col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Curso Asignado</label>
                        <select id="examenCursoId" name="curso_id" class="form-select" required>
                            <option value="">Seleccione el curso...</option>
                            <?php foreach ($cursos as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= (isset($examen['Cursos_id']) && $examen['Cursos_id'] == $c['id']) ? 'selected' : '' ?>>
                                    <?= e($c['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Fecha y Hora de Apertura</label>
                        <?php 
                        $valIni = !empty($examen['fecha_inicio']) ? date('Y-m-d\TH:i', strtotime($examen['fecha_inicio'])) : date('Y-m-d\TH:i');
                        ?>
                        <input type="datetime-local" id="fechaInicio" name="fecha_inicio" class="form-control" value="<?= $valIni ?>" required>
                    </div>

                    <div class="col-12 col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Fecha y Hora de Cierre (Límite)</label>
                        <?php 
                        $valFin = !empty($examen['fecha_fin']) ? date('Y-m-d\TH:i', strtotime($examen['fecha_fin'])) : date('Y-m-d\TH:i', strtotime('+7 days'));
                        ?>
                        <input type="datetime-local" id="fechaFin" name="fecha_fin" class="form-control" value="<?= $valFin ?>" required>
                    </div>

                    <div class="col-12 col-md-2">
                        <label class="form-label small fw-semibold text-secondary">Duración (Minutos)</label>
                        <input type="number" id="duracionMinutos" name="duracion_minutos" class="form-control" value="<?= $examen['duracion_minutos'] ?? 60 ?>" min="5" max="300" required>
                    </div>

                    <div class="col-12 col-md-2">
                        <label class="form-label small fw-semibold text-secondary">Estado</label>
                        <select id="examenEstado" name="estado" class="form-select">
                            <option value="activo" <?= (isset($examen['estado']) && $examen['estado'] === 'activo') ? 'selected' : '' ?>>Activo</option>
                            <option value="borrador" <?= (isset($examen['estado']) && $examen['estado'] === 'borrador') ? 'selected' : '' ?>>Borrador</option>
                            <option value="finalizado" <?= (isset($examen['estado']) && $examen['estado'] === 'finalizado') ? 'selected' : '' ?>>Finalizado</option>
                        </select>
                    </div>
                </div>

                <div class="mt-4 text-end">
                    <button type="submit" id="btnGuardarDatosExamen" class="btn btn-primary rounded-pill px-4">
                        <i class="bi bi-save me-1"></i> Guardar Parámetros del Examen
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SECCIÓN 2: BANCO DE PREGUNTAS DEL EXAMEN -->
    <?php if ($examen): ?>
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-header bg-white border-0 pt-4 pb-0 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold text-dark m-0">2. Banco de Preguntas y Reactivos</h5>
                    <small class="text-muted">Total acumulado: <span id="totalPuntosSpan" class="fw-bold text-primary"><?= $examen['puntaje_total'] ?></span> pts</small>
                </div>
                <button class="btn btn-success rounded-pill px-4" onclick="abrirModalPregunta()">
                    <i class="bi bi-patch-question-fill me-1"></i> Agregar Pregunta
                </button>
            </div>
            <div class="card-body p-4">
                <div id="contenedorPreguntas">
                    <?php if (empty($preguntas)): ?>
                        <div class="text-center py-5 text-muted" id="sinPreguntasMsg">
                            <i class="bi bi-question-circle fs-1"></i>
                            <h6 class="mt-2 text-secondary">Aún no se han agregado preguntas</h6>
                            <p class="small text-muted">Haga clic en "Agregar Pregunta" para crear reactivos de opción múltiple, verdadero/falso o abiertos.</p>
                        </div>
                    <?php else: ?>
                        <div class="row g-3" id="listaPreguntas">
                            <?php foreach ($preguntas as $idx => $p): ?>
                                <div class="col-12" id="cardPregunta-<?= $p['id'] ?>">
                                    <div class="card border border-light-subtle rounded-3 shadow-none p-3">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge bg-primary rounded-pill">#<?= $idx + 1 ?></span>
                                                <span class="badge bg-secondary-subtle text-secondary rounded-pill text-uppercase">
                                                    <?= str_replace('_', ' ', $p['tipo']) ?>
                                                </span>
                                                <span class="badge bg-success-subtle text-success rounded-pill">
                                                    <?= $p['puntaje'] ?> pts
                                                </span>
                                            </div>
                                            <div class="d-flex gap-1">
                                                <button type="button" class="btn btn-outline-primary btn-sm" title="Editar Pregunta / Adjuntar Imagen" onclick='editarPregunta(<?= json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)'>
                                                    <i class="bi bi-pencil-fill"></i>
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-sm" title="Eliminar Pregunta" onclick="eliminarPregunta(<?= $p['id'] ?>)">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>

                                        <h6 class="fw-bold text-dark mb-2"><?= e($p['pregunta']) ?></h6>

                                        <?php if (!empty($p['imagen'])): ?>
                                            <?php $srcImg = (strpos($p['imagen'], 'data:image') === 0 || strpos($p['imagen'], 'http') === 0) ? $p['imagen'] : (BASE_URL . '/' . ltrim($p['imagen'], '/')); ?>
                                            <div class="mb-3">
                                                <img src="<?= e($srcImg) ?>" alt="Imagen de la pregunta" class="img-fluid rounded-3 border shadow-sm" style="max-height: 220px; object-fit: contain;">
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($p['tipo'] === 'opcion_multiple' && !empty($p['opciones_array'])): ?>
                                            <div class="bg-light p-2 rounded-3 small mb-2">
                                                <span class="fw-semibold text-secondary d-block mb-1">Opciones:</span>
                                                <ul class="mb-0 ps-3">
                                                    <?php foreach ($p['opciones_array'] as $opc): ?>
                                                        <li class="<?= (mb_strtolower(trim($opc), 'UTF-8') === mb_strtolower(trim($p['respuesta']), 'UTF-8')) ? 'text-success fw-bold' : 'text-muted' ?>">
                                                            <?= e($opc) ?>
                                                            <?php if (mb_strtolower(trim($opc), 'UTF-8') === mb_strtolower(trim($p['respuesta']), 'UTF-8')): ?>
                                                                <i class="bi bi-check-circle-fill text-success ms-1"></i> (Correcta)
                                                            <?php endif; ?>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php elseif ($p['tipo'] === 'verdadero_falso'): ?>
                                            <div class="small text-muted mb-1">
                                                Respuesta Correcta: <strong class="text-success"><?= e($p['respuesta']) ?></strong>
                                            </div>
                                        <?php elseif ($p['tipo'] === 'abierta'): ?>
                                            <div class="small text-muted mb-1">
                                                Criterio de Evaluación sugerido: <em><?= e($p['respuesta']) ?></em> (Calificación manual por el docente)
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Crear / Editar Pregunta -->
<?php if ($examen): ?>
<div class="modal fade" id="modalPregunta" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold">Agregar Pregunta al Examen</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formPregunta" onsubmit="guardarPregunta(event)">
                <div class="modal-body p-4">
                    <input type="hidden" name="examen_id" value="<?= $examen['id'] ?>">
                    <input type="hidden" id="preguntaId" name="id" value="">
                    <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <label class="form-label small fw-semibold text-secondary">Tipo de Pregunta</label>
                            <select id="pregTipo" name="tipo" class="form-select" onchange="cambiarTipoPregunta(this.value)" required>
                                <option value="opcion_multiple">Opción Múltiple (Calificación automática)</option>
                                <option value="verdadero_falso">Verdadero o Falso (Calificación automática)</option>
                                <option value="abierta">Pregunta Abierta / Redacción (Revisión manual docente)</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-semibold text-secondary">Puntaje</label>
                            <input type="number" id="pregPuntaje" name="puntaje" class="form-control" value="20" min="1" max="100" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">Texto de la Pregunta / Enunciado</label>
                            <textarea id="pregTexto" name="pregunta" rows="3" class="form-control" placeholder="Escriba aquí la pregunta claramente..." required></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary">
                                <i class="bi bi-image me-1"></i> Imagen de Apoyo para la Pregunta <small class="text-muted fw-normal">(Opcional - JPG, PNG, GIF, WEBP)</small>
                            </label>
                            <input type="file" id="pregImagen" name="imagen" class="form-control form-control-sm" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previsualizarImagenPregunta(this)">
                            <div id="previewImagenPreguntaContainer" class="mt-2 p-2 bg-light border rounded-3 d-none text-center position-relative">
                                <img id="previewImagenPregunta" src="" alt="Vista previa" class="img-fluid rounded-2" style="max-height: 180px; object-fit: contain;">
                                <div class="mt-2">
                                    <button type="button" class="btn btn-xs btn-outline-danger btn-sm rounded-pill px-3" onclick="limpiarImagenPregunta()">
                                        <i class="bi bi-trash me-1"></i> Quitar Imagen
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Sección Dinámica: Opción Múltiple -->
                        <div id="seccionOpcionMultiple" class="col-12">
                            <label class="form-label small fw-semibold text-secondary d-flex justify-content-between align-items-center">
                                <span>Opciones Disponibles <small class="text-success fw-normal">(Seleccione con el círculo cuál es la opción correcta)</small></span>
                                <button type="button" class="btn btn-xs btn-outline-primary btn-sm py-0" onclick="agregarCampoOpcion()">
                                    <i class="bi bi-plus"></i> Añadir Opción
                                </button>
                            </label>
                            <div id="contenedorOpcionesInputs" class="d-flex flex-column gap-2 mb-2">
                                <div class="input-group opcion-item">
                                    <div class="input-group-text bg-light">
                                        <input class="form-check-input mt-0 radio-opcion-correcta" type="radio" name="radio_opcion_correcta" value="0" checked onchange="sincronizarOpcionCorrecta()" title="Marcar como respuesta correcta">
                                    </div>
                                    <span class="input-group-text bg-light text-muted fw-semibold letra-opcion">A</span>
                                    <input type="text" name="opciones[]" class="form-control input-opcion" placeholder="Opción 1" oninput="sincronizarOpcionCorrecta()">
                                </div>
                                <div class="input-group opcion-item">
                                    <div class="input-group-text bg-light">
                                        <input class="form-check-input mt-0 radio-opcion-correcta" type="radio" name="radio_opcion_correcta" value="1" onchange="sincronizarOpcionCorrecta()" title="Marcar como respuesta correcta">
                                    </div>
                                    <span class="input-group-text bg-light text-muted fw-semibold letra-opcion">B</span>
                                    <input type="text" name="opciones[]" class="form-control input-opcion" placeholder="Opción 2" oninput="sincronizarOpcionCorrecta()">
                                </div>
                                <div class="input-group opcion-item">
                                    <div class="input-group-text bg-light">
                                        <input class="form-check-input mt-0 radio-opcion-correcta" type="radio" name="radio_opcion_correcta" value="2" onchange="sincronizarOpcionCorrecta()" title="Marcar como respuesta correcta">
                                    </div>
                                    <span class="input-group-text bg-light text-muted fw-semibold letra-opcion">C</span>
                                    <input type="text" name="opciones[]" class="form-control input-opcion" placeholder="Opción 3" oninput="sincronizarOpcionCorrecta()">
                                </div>
                            </div>
                            <label class="form-label small fw-semibold text-success mt-2">
                                <i class="bi bi-check-circle-fill me-1"></i> Seleccione la Opción Correcta
                            </label>
                            <select id="selectOpcionCorrecta" class="form-select border-success" onchange="seleccionarRadioDesdeSelect(this.value)">
                                <option value="0">Opción A</option>
                                <option value="1">Opción B</option>
                                <option value="2">Opción C</option>
                            </select>
                            <input type="hidden" id="pregRespuestaCorrecta" name="respuesta" value="">
                            <small class="text-muted">Puede marcar el círculo junto a la opción o elegirla en la lista desplegable.</small>
                        </div>

                        <!-- Sección Dinámica: Verdadero / Falso -->
                        <div id="seccionVerdaderoFalso" class="col-12 d-none">
                            <label class="form-label small fw-semibold text-success d-block mb-2">
                                <i class="bi bi-check-circle-fill me-1"></i> Seleccione la Opción Correcta (Verdadero o Falso)
                            </label>
                            <div class="d-flex gap-3 mb-2">
                                <label class="border rounded-3 p-3 flex-fill d-flex align-items-center gap-2 bg-light" style="cursor: pointer;">
                                    <input class="form-check-input mt-0" type="radio" name="radio_vf_correcta" value="Verdadero" checked onchange="document.getElementById('pregRespuestaVF').value = this.value">
                                    <span class="fw-bold text-success"><i class="bi bi-check-lg me-1"></i> Verdadero</span>
                                </label>
                                <label class="border rounded-3 p-3 flex-fill d-flex align-items-center gap-2 bg-light" style="cursor: pointer;">
                                    <input class="form-check-input mt-0" type="radio" name="radio_vf_correcta" value="Falso" onchange="document.getElementById('pregRespuestaVF').value = this.value">
                                    <span class="fw-bold text-danger"><i class="bi bi-x-lg me-1"></i> Falso</span>
                                </label>
                            </div>
                            <select id="pregRespuestaVF" class="form-select border-success" onchange="document.querySelectorAll('input[name=radio_vf_correcta]').forEach(r => r.checked = (r.value === this.value))">
                                <option value="Verdadero">Verdadero</option>
                                <option value="Falso">Falso</option>
                            </select>
                        </div>

                        <!-- Sección Dinámica: Pregunta Abierta -->
                        <div id="seccionAbierta" class="col-12 d-none">
                            <label class="form-label small fw-semibold text-secondary">Criterio / Pauta de Calificación Sugerida</label>
                            <textarea id="pregRespuestaAbierta" rows="2" class="form-control" placeholder="Escriba la respuesta esperada o los puntos clave que evaluará en la redacción del alumno..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnGuardarPregunta" class="btn btn-success rounded-pill px-4">Guardar Pregunta</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<script src="<?= BASE_URL ?>/js/profesor.js?v=<?= time() ?>"></script>
