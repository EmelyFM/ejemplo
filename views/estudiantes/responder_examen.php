<?php require_once __DIR__ . '/../layouts/header.php'; ?>
<?php require_once __DIR__ . '/../layouts/sidebar.php'; ?>
<?php require_once __DIR__ . '/../layouts/navbar.php'; ?>

<div class="container-fluid" style="max-width: 900px;">
    <!-- Barra superior con Temporizador e Información del Examen -->
    <div class="exam-timer mb-4">
        <div>
            <span class="badge bg-primary-subtle text-primary border rounded-pill px-3 py-1 mb-1 small">EVALUACIÓN EN CURSO</span>
            <h4 class="fw-bold m-0 text-white"><?= e($examen['nom_exam']) ?></h4>
            <small class="text-light opacity-75">Curso: <?= e($examen['curso_nombre']) ?> &bull; <?= count($preguntas) ?> preguntas</small>
        </div>
        <div class="text-end">
            <small class="text-secondary d-block" style="font-size: 0.75rem; text-transform: uppercase;">Tiempo Restante</small>
            <div id="cronometro" class="timer-digits">00:00:00</div>
        </div>
    </div>

    <!-- Advertencia e Instrucciones -->
    <div class="alert alert-warning border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center gap-3">
        <i class="bi bi-exclamation-triangle-fill fs-3 text-warning"></i>
        <div class="small">
            <strong>Importante:</strong> Solo cuentas con <strong>un único intento</strong> para resolver y enviar tus respuestas. Al confirmar la entrega, tus resultados serán procesados por el sistema y el profesor. No cierres esta ventana mientras respondes.
        </div>
    </div>

    <!-- Formulario de Resolución de Examen -->
    <form id="formExamen" onsubmit="confirmarEnvioExamen(event)">
        <input type="hidden" name="examen_id" value="<?= $examen['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

        <?php foreach ($preguntas as $index => $p): ?>
            <div class="card border-0 shadow-sm rounded-4 mb-4 p-4 pregunta-card" id="pregunta-card-<?= $p['id'] ?>">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="badge bg-primary rounded-pill px-3 py-2">
                        Pregunta <?= $index + 1 ?> de <?= count($preguntas) ?>
                    </span>
                    <span class="badge bg-light text-secondary border rounded-pill px-3 py-2">
                        Valor: <?= $p['puntaje'] ?> pts
                    </span>
                </div>

                <h5 class="fw-bold text-dark mb-3"><?= e($p['pregunta']) ?></h5>

                <?php if (!empty($p['imagen'])): ?>
                    <?php $srcImg = (strpos($p['imagen'], 'data:image') === 0 || strpos($p['imagen'], 'http') === 0) ? $p['imagen'] : (BASE_URL . '/' . ltrim($p['imagen'], '/')); ?>
                    <div class="mb-3 text-center bg-light p-3 rounded-3 border">
                        <img src="<?= e($srcImg) ?>" 
                             alt="Ilustración de la pregunta" 
                             class="img-fluid rounded-2 shadow-sm" 
                             style="max-height: 320px; object-fit: contain; cursor: zoom-in;"
                             onclick="ampliarImagenExamen(this.src, 'Pregunta <?= $index + 1 ?>')">
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1" onclick="ampliarImagenExamen(this.closest('.mb-3').querySelector('img').src, 'Pregunta <?= $index + 1 ?>')">
                                <i class="bi bi-zoom-in me-1"></i> Ampliar imagen de la pregunta
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- 1. Opción Múltiple -->
                <?php if ($p['tipo'] === 'opcion_multiple'): ?>
                    <div class="d-flex flex-column gap-2">
                        <?php foreach ($p['opciones_array'] as $opcIndex => $opcion): ?>
                            <label class="form-check p-3 border rounded-3 bg-light bg-opacity-50 d-flex align-items-center gap-3 cursor-pointer">
                                <input class="form-check-input mt-0" type="radio" name="respuestas[<?= $p['id'] ?>]" value="<?= e($opcion) ?>">
                                <span class="form-check-label text-dark"><?= e($opcion) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>

                <!-- 2. Verdadero o Falso -->
                <?php elseif ($p['tipo'] === 'verdadero_falso'): ?>
                    <div class="d-flex gap-3">
                        <label class="form-check p-3 border rounded-3 bg-light bg-opacity-50 flex-fill d-flex align-items-center gap-3 cursor-pointer">
                            <input class="form-check-input mt-0" type="radio" name="respuestas[<?= $p['id'] ?>]" value="Verdadero">
                            <span class="form-check-label fw-semibold text-dark"><i class="bi bi-check-circle-fill text-success me-1"></i> Verdadero</span>
                        </label>
                        <label class="form-check p-3 border rounded-3 bg-light bg-opacity-50 flex-fill d-flex align-items-center gap-3 cursor-pointer">
                            <input class="form-check-input mt-0" type="radio" name="respuestas[<?= $p['id'] ?>]" value="Falso">
                            <span class="form-check-label fw-semibold text-dark"><i class="bi bi-x-circle-fill text-danger me-1"></i> Falso</span>
                        </label>
                    </div>

                <!-- 3. Pregunta Abierta -->
                <?php elseif ($p['tipo'] === 'abierta'): ?>
                    <div>
                        <textarea class="form-control" name="respuestas[<?= $p['id'] ?>]" rows="4" placeholder="Redacte su respuesta detallada aquí..."></textarea>
                        <small class="text-muted mt-1 d-block">Esta respuesta será evaluada manualmente por su docente.</small>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- Botón de Envío -->
        <div class="card border-0 shadow-sm rounded-4 p-4 text-center mb-5">
            <h5 class="fw-bold mb-2">¿Has completado todas tus respuestas?</h5>
            <p class="text-muted small mb-3">Revisa tus elecciones cuidadosamente antes de realizar el envío definitivo.</p>
            <div class="d-flex justify-content-center gap-3">
                <button type="submit" id="btnEnviarExamen" class="btn btn-primary rounded-pill px-5 py-2 fw-semibold fs-5 shadow">
                    <i class="bi bi-send-check-fill me-2"></i> Finalizar y Enviar Examen
                </button>
            </div>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
<script src="<?= BASE_URL ?>/js/estudiante.js?v=<?= time() ?>"></script>
<script>
// Inicializar temporizador con los minutos configurados en el examen
const duracionMinutos = <?= (int)($examen['duracion_minutos'] ?? 60) ?>;
iniciarTemporizadorExamen(duracionMinutos);
</script>
