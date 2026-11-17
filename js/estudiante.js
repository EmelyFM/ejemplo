let temporizadorInterval = null;
let segundosRestantes = 0;

function iniciarTemporizadorExamen(duracionMinutos) {
    segundosRestantes = duracionMinutos * 60;
    const cronoEl = document.getElementById('cronometro');
    if (!cronoEl) return;

    actualizarDisplayCrono();

    temporizadorInterval = setInterval(() => {
        segundosRestantes--;

        if (segundosRestantes <= 0) {
            clearInterval(temporizadorInterval);
            cronoEl.innerText = "00:00:00";
            cronoEl.classList.add('text-danger');

            Swal.fire({
                icon: 'warning',
                title: 'Tiempo Concluido',
                text: 'El tiempo límite para resolver esta evaluación ha finalizado. Sus respuestas actuales se enviarán automáticamente.',
                allowOutsideClick: false,
                confirmButtonText: 'Aceptar'
            }).then(() => {
                enviarExamenDirecto();
            });
            return;
        }

        actualizarDisplayCrono();
    }, 1000);
}

function actualizarDisplayCrono() {
    const cronoEl = document.getElementById('cronometro');
    if (!cronoEl) return;

    const h = Math.floor(segundosRestantes / 3600);
    const m = Math.floor((segundosRestantes % 3600) / 60);
    const s = segundosRestantes % 60;

    const hh = String(h).padStart(2, '0');
    const mm = String(m).padStart(2, '0');
    const ss = String(s).padStart(2, '0');

    cronoEl.innerText = `${hh}:${mm}:${ss}`;

    // Si quedan menos de 5 minutos, cambiar estilo de alerta
    if (segundosRestantes < 300) {
        cronoEl.style.color = '#ef4444';
    }
}

async function confirmarEnvioExamen(e) {
    if (e) e.preventDefault();

    // 1. Validar cuántas preguntas están sin responder
    const form = document.getElementById('formExamen');
    const formData = new FormData(form);
    const totalTarjetas = document.querySelectorAll('.pregunta-card').length;

    const respuestas = {};
    for (const [key, value] of formData.entries()) {
        const match = key.match(/respuestas\[(\d+)\]/);
        if (match && value.trim() !== '') {
            respuestas[match[1]] = value.trim();
        }
    }

    const respondidas = Object.keys(respuestas).length;
    const sinResponder = totalTarjetas - respondidas;

    let mensajeTexto = `Has contestado ${respondidas} de ${totalTarjetas} preguntas.`;
    if (sinResponder > 0) {
        mensajeTexto += ` Advertencia: Tienes ${sinResponder} pregunta(s) sin responder.`;
    }
    mensajeTexto += ' ¿Deseas realizar la entrega definitiva ahora?';

    const confirmacion = await Swal.fire({
        title: '¿Confirmar entrega del examen?',
        text: mensajeTexto,
        icon: sinResponder > 0 ? 'warning' : 'question',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, enviar examen',
        cancelButtonText: 'Seguir revisando'
    });

    if (confirmacion.isConfirmed) {
        await ejecutarEnvio(form);
    }
}

async function enviarExamenDirecto() {
    const form = document.getElementById('formExamen');
    await ejecutarEnvio(form);
}

async function ejecutarEnvio(form) {
    const btn = document.getElementById('btnEnviarExamen');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando respuestas...';
    }

    if (temporizadorInterval) {
        clearInterval(temporizadorInterval);
    }

    const formData = new FormData(form);
    const examenId = formData.get('examen_id');

    const respuestas = {};
    for (const [key, value] of formData.entries()) {
        const match = key.match(/respuestas\[(\d+)\]/);
        if (match) {
            respuestas[match[1]] = value;
        }
    }

    try {
        const res = await apiFetch(`${BASE_URL}/index.php?controller=estudiante&action=enviarRespuestas`, {
            method: 'POST',
            body: {
                examen_id: examenId,
                respuestas: respuestas,
                csrf_token: CSRF_TOKEN
            }
        });

        if (res.success) {
            await Swal.fire({
                icon: 'success',
                title: '¡Examen Enviado con Éxito!',
                text: res.message,
                confirmButtonText: 'Ver Mis Calificaciones'
            });

            window.location.href = res.data.redirect || `${BASE_URL}/index.php?controller=estudiante&action=misResultados`;
        } else {
            Swal.fire({
                icon: 'error',
                title: 'No se pudo enviar',
                text: res.message
            });
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-send-check-fill me-2"></i> Finalizar y Enviar Examen';
            }
        }
    } catch (err) {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-send-check-fill me-2"></i> Finalizar y Enviar Examen';
        }
    }
}

function ampliarImagenExamen(urlImagen, titulo = 'Imagen de la Pregunta') {
    Swal.fire({
        title: titulo,
        imageUrl: urlImagen,
        imageAlt: titulo,
        width: 'auto',
        padding: '1rem',
        showCloseButton: true,
        confirmButtonText: 'Cerrar',
        confirmButtonColor: '#4f46e5'
    });
}
