/**
 * Lógica del módulo de Profesores: Exámenes, Preguntas y Calificación Manual
 */

// ==========================================
// EXÁMENES DEL PROFESOR
// ==========================================

async function cargarExamenesProfesor() {
    try {
        const res = await apiFetch(`${BASE_URL}/index.php?controller=profesor&action=listarExamenes`);
        if (res.success) {
            const tbody = document.getElementById('tbodyExamenesProf');
            if (!tbody) return;

            if (res.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="9" class="text-center text-muted py-5">Aún no has creado ningún examen. Haz clic en "Diseñar Examen" para comenzar.</td></tr>`;
                return;
            }

            tbody.innerHTML = res.data.map(ex => {
                const publicado = parseInt(ex.resultados_publicados) === 1;
                const pubBadge = publicado 
                    ? `<button class="btn btn-sm btn-success rounded-pill px-3" onclick="togglePublicar(${ex.id}, false)"><i class="bi bi-eye-fill me-1"></i> Publicado</button>`
                    : `<button class="btn btn-sm btn-outline-secondary rounded-pill px-3" onclick="togglePublicar(${ex.id}, true)"><i class="bi bi-eye-slash-fill me-1"></i> Privado</button>`;

                return `
                    <tr>
                        <td class="fw-bold">${ex.id}</td>
                        <td>
                            <div class="fw-bold text-dark">${ex.nom_exam}</div>
                            <small class="badge bg-light text-secondary border">${ex.estado}</small>
                        </td>
                        <td class="text-primary fw-semibold">${ex.curso_nombre}</td>
                        <td>
                            <small class="d-block text-muted"><strong>Abre:</strong> ${ex.fecha_inicio || 'Inmediato'}</small>
                            <small class="d-block text-muted"><strong>Cierra:</strong> ${ex.fecha_fin || 'Sin límite'}</small>
                        </td>
                        <td><span class="badge bg-light text-dark border">${ex.duracion_minutos} min</span></td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary rounded-pill px-2">${ex.total_preguntas} preg</span>
                            <span class="badge bg-success-subtle text-success rounded-pill px-2">${ex.puntaje_total} pts</span>
                        </td>
                        <td>
                            <span class="badge bg-info-subtle text-dark rounded-pill px-3">${ex.total_entregas} entregas</span>
                        </td>
                        <td>${pubBadge}</td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="${BASE_URL}/index.php?controller=profesor&action=editorExamen&id=${ex.id}" class="btn btn-sm btn-outline-primary" title="Editar Examen y Preguntas">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>
                                <a href="${BASE_URL}/index.php?controller=profesor&action=calificar&examen_id=${ex.id}" class="btn btn-sm btn-outline-success" title="Revisar Respuestas">
                                    <i class="bi bi-check2-circle"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarExamen(${ex.id})" title="Eliminar Examen">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }
    } catch (e) {}
}

async function togglePublicar(id, publicar) {
    const accionTxt = publicar ? 'publicar' : 'ocultar';
    const confirm = await Swal.fire({
        title: `¿Desea ${accionTxt} los resultados?`,
        text: publicar ? 'Los estudiantes podrán ver inmediatamente sus calificaciones y su boleta.' : 'Las notas permanecerán privadas para los estudiantes.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, cambiar estado',
        cancelButtonText: 'Cancelar'
    });

    if (confirm.isConfirmed) {
        try {
            const res = await apiFetch(`${BASE_URL}/index.php?controller=profesor&action=togglePublicarResultados`, {
                method: 'POST',
                body: { id: id, publicar: publicar ? 1 : 0, csrf_token: CSRF_TOKEN }
            });

            if (res.success) {
                Toast.fire({ icon: 'success', title: res.message });
                setTimeout(() => location.reload(), 600);
            } else {
                Swal.fire({ icon: 'error', title: 'Error', text: res.message });
            }
        } catch (e) {}
    }
}

function eliminarExamen(id) {
    Swal.fire({
        title: '¿Eliminar este examen?',
        text: 'Solo se puede eliminar si ningún estudiante ha enviado respuestas.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const res = await apiFetch(`${BASE_URL}/index.php?controller=profesor&action=eliminarExamen`, {
                    method: 'POST',
                    body: { id: id, csrf_token: CSRF_TOKEN }
                });
                if (res.success) {
                    Toast.fire({ icon: 'success', title: res.message });
                    cargarExamenesProfesor();
                } else {
                    Swal.fire({ icon: 'error', title: 'No se pudo eliminar', text: res.message });
                }
            } catch (e) {}
        }
    });
}

// ==========================================
// DISEÑADOR / EDITOR DE EXAMEN
// ==========================================

async function guardarExamen(e) {
    e.preventDefault();
    const btn = document.getElementById('btnGuardarDatosExamen');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

    const form = e.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    // Validación del nombre del examen: no más de 5 dígitos ni solo números
    const nomExam = (data.nom_exam || '').trim();
    const cantDigitos = (nomExam.match(/\d/g) || []).length;
    const soloNumeros = nomExam.length > 0 && /^\d+$/.test(nomExam.replace(/\s+/g, ''));
    if (cantDigitos > 5 || soloNumeros) {
        Swal.fire({
            icon: 'warning',
            title: 'Nombre de Evaluación Inválido',
            text: 'No puede ingresar más de 5 dígitos o solo números en el nombre de la evaluación o examen.'
        });
        btn.disabled = false;
        btn.innerHTML = originalText;
        return;
    }

    // Validación: no repetir más de 5 veces seguidas la misma letra en el nombre del examen
    if (/([a-zA-ZáéíóúÁÉÍÓÚñÑüÜ])\1{5,}/i.test(nomExam)) {
        Swal.fire({
            icon: 'warning',
            title: 'Nombre de Evaluación Inválido',
            text: 'No debe repetir más de 5 veces seguidas la misma letra en el nombre del examen.'
        });
        btn.disabled = false;
        btn.innerHTML = originalText;
        return;
    }

    // Validación de fechas en cliente
    const fIni = new Date(data.fecha_inicio);
    const fFin = new Date(data.fecha_fin);
    if (fFin <= fIni) {
        Swal.fire({
            icon: 'error',
            title: 'Fechas Inválidas',
            text: 'La fecha y hora de cierre debe ser posterior a la fecha y hora de inicio.'
        });
        btn.disabled = false;
        btn.innerHTML = originalText;
        return;
    }

    try {
        const res = await apiFetch(`${BASE_URL}/index.php?controller=profesor&action=guardarExamen`, {
            method: 'POST',
            body: data
        });

        if (res.success) {
            Swal.fire({
                icon: 'success',
                title: 'Examen Guardado',
                text: res.message,
                timer: 1500,
                showConfirmButton: false
            }).then(() => {
                if (!data.id && res.data.id) {
                    window.location.href = `${BASE_URL}/index.php?controller=profesor&action=editorExamen&id=${res.data.id}`;
                } else {
                    location.reload();
                }
            });
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message });
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    } catch (e) {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

// ==========================================
// PREGUNTAS DEL EXAMEN
// ==========================================

function abrirModalPregunta() {
    const modalEl = document.getElementById('modalPregunta');
    if (!modalEl) return;
    const form = document.getElementById('formPregunta');
    if (form) form.reset();
    const pregId = document.getElementById('preguntaId');
    if (pregId) pregId.value = '';
    limpiarImagenPregunta();
    const modalTitle = modalEl.querySelector('.modal-title');
    if (modalTitle) modalTitle.innerText = 'Agregar Pregunta al Examen';
    const btn = document.getElementById('btnGuardarPregunta');
    if (btn) { btn.disabled = false; btn.innerText = 'Guardar Pregunta'; }

    renderizarOpcionesMultiple(['', '', ''], '');
    const vfSelect = document.getElementById('pregRespuestaVF');
    if (vfSelect) vfSelect.value = 'Verdadero';
    document.querySelectorAll('input[name="radio_vf_correcta"]').forEach(r => r.checked = (r.value === 'Verdadero'));

    cambiarTipoPregunta('opcion_multiple');
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function renderizarOpcionesMultiple(opciones, respuestaCorrecta = '') {
    const cont = document.getElementById('contenedorOpcionesInputs');
    if (!cont) return;
    const lista = Array.isArray(opciones) && opciones.length >= 2 ? opciones : ['', '', ''];
    const letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

    let idxSeleccionado = lista.findIndex(o => (o || '').trim().toLowerCase() === (respuestaCorrecta || '').trim().toLowerCase() && (respuestaCorrecta || '').trim() !== '');
    if (idxSeleccionado < 0) idxSeleccionado = 0;

    cont.innerHTML = lista.map((opc, idx) => `
        <div class="input-group opcion-item">
            <div class="input-group-text bg-light">
                <input class="form-check-input mt-0 radio-opcion-correcta" type="radio" name="radio_opcion_correcta" value="${idx}" ${idx === idxSeleccionado ? 'checked' : ''} onchange="sincronizarOpcionCorrecta()" title="Marcar como respuesta correcta">
            </div>
            <span class="input-group-text bg-light text-muted fw-semibold letra-opcion">${letras[idx] || (idx + 1)}</span>
            <input type="text" name="opciones[]" class="form-control input-opcion" value="${(opc || '').replace(/"/g, '&quot;')}" placeholder="Opción ${idx + 1}" oninput="sincronizarOpcionCorrecta()">
            ${idx >= 2 ? `<button type="button" class="btn btn-outline-danger" onclick="eliminarCampoOpcion(this)"><i class="bi bi-trash"></i></button>` : ''}
        </div>
    `).join('');

    sincronizarOpcionCorrecta();
}

function eliminarCampoOpcion(btnEl) {
    const item = btnEl.closest('.opcion-item');
    if (item) item.remove();
    reindexarOpcionesMultiple();
    sincronizarOpcionCorrecta();
}

function reindexarOpcionesMultiple() {
    const items = document.querySelectorAll('#contenedorOpcionesInputs .opcion-item');
    const letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
    let algunoMarcado = false;

    items.forEach((item, idx) => {
        const radio = item.querySelector('.radio-opcion-correcta');
        const spanLetra = item.querySelector('.letra-opcion');
        const inputTxt = item.querySelector('.input-opcion');

        if (radio) {
            radio.value = idx;
            if (radio.checked) algunoMarcado = true;
        }
        if (spanLetra) spanLetra.innerText = letras[idx] || (idx + 1);
        if (inputTxt) inputTxt.placeholder = `Opción ${idx + 1}`;
    });

    if (!algunoMarcado && items.length > 0) {
        const primerRadio = items[0].querySelector('.radio-opcion-correcta');
        if (primerRadio) primerRadio.checked = true;
    }
}

function sincronizarOpcionCorrecta() {
    const items = document.querySelectorAll('#contenedorOpcionesInputs .opcion-item');
    const select = document.getElementById('selectOpcionCorrecta');
    const hiddenResp = document.getElementById('pregRespuestaCorrecta');
    const letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

    let idxChecked = 0;
    let valorCorrecto = '';
    const opcionesSelectHtml = [];

    items.forEach((item, idx) => {
        const radio = item.querySelector('.radio-opcion-correcta');
        const inputTxt = item.querySelector('.input-opcion');
        const texto = (inputTxt?.value || '').trim();
        const letra = letras[idx] || (idx + 1);

        if (radio && radio.checked) {
            idxChecked = idx;
            valorCorrecto = texto;
            item.classList.add('border', 'border-success', 'rounded-2');
        } else {
            item.classList.remove('border', 'border-success', 'rounded-2');
        }

        const etiqueta = texto ? `Opción ${letra}: ${texto}` : `Opción ${letra} (sin texto aún)`;
        opcionesSelectHtml.push(`<option value="${idx}">${etiqueta.replace(/</g, '&lt;')}</option>`);
    });

    if (select) {
        select.innerHTML = opcionesSelectHtml.join('');
        select.value = String(idxChecked);
    }
    if (hiddenResp) {
        hiddenResp.value = valorCorrecto;
    }
}

function seleccionarRadioDesdeSelect(idxStr) {
    const idx = parseInt(idxStr, 10);
    const radios = document.querySelectorAll('#contenedorOpcionesInputs .radio-opcion-correcta');
    radios.forEach((r, i) => {
        r.checked = (i === idx);
    });
    sincronizarOpcionCorrecta();
}

function editarPregunta(p) {
    const modalEl = document.getElementById('modalPregunta');
    if (!modalEl || !p) return;

    const form = document.getElementById('formPregunta');
    if (form) form.reset();
    limpiarImagenPregunta();

    document.getElementById('preguntaId').value = p.id;
    document.getElementById('pregTipo').value = p.tipo || 'opcion_multiple';
    document.getElementById('pregPuntaje').value = p.puntaje || 10;
    document.getElementById('pregTexto').value = p.pregunta || '';

    cambiarTipoPregunta(p.tipo || 'opcion_multiple');

    if (p.tipo === 'opcion_multiple') {
        const opciones = Array.isArray(p.opciones_array) && p.opciones_array.length > 0 ? p.opciones_array : ['', '', ''];
        renderizarOpcionesMultiple(opciones, p.respuesta || '');
    } else if (p.tipo === 'verdadero_falso') {
        const valVF = (p.respuesta && p.respuesta.toLowerCase() === 'falso') ? 'Falso' : 'Verdadero';
        const vfSelect = document.getElementById('pregRespuestaVF');
        if (vfSelect) vfSelect.value = valVF;
        document.querySelectorAll('input[name="radio_vf_correcta"]').forEach(r => r.checked = (r.value === valVF));
    } else if (p.tipo === 'abierta') {
        const abText = document.getElementById('pregRespuestaAbierta');
        if (abText) abText.value = p.respuesta || '';
    }

    if (p.imagen) {
        const contImg = document.getElementById('previewImagenPreguntaContainer');
        const imgEl = document.getElementById('previewImagenPregunta');
        if (contImg && imgEl) {
            imgEl.src = p.imagen.startsWith('data:image') ? p.imagen : `${BASE_URL}/${p.imagen}`;
            contImg.classList.remove('d-none');
        }
    }

    const modalTitle = modalEl.querySelector('.modal-title');
    if (modalTitle) modalTitle.innerText = 'Editar Pregunta / Imagen';
    const btn = document.getElementById('btnGuardarPregunta');
    if (btn) { btn.disabled = false; btn.innerText = 'Actualizar Pregunta'; }

    const modal = new bootstrap.Modal(modalEl);
    modal.show();
}

function previsualizarImagenPregunta(input) {
    const cont = document.getElementById('previewImagenPreguntaContainer');
    const img = document.getElementById('previewImagenPregunta');
    if (!cont || !img) return;

    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (!file.type.startsWith('image/')) {
            Swal.fire({ icon: 'warning', title: 'Archivo no válido', text: 'Seleccione un archivo de imagen válido (JPG, PNG, GIF o WEBP).' });
            input.value = '';
            cont.classList.add('d-none');
            return;
        }
        const reader = new FileReader();
        reader.onload = function (e) {
            img.src = e.target.result;
            cont.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    } else {
        img.src = '';
        cont.classList.add('d-none');
    }
}

function limpiarImagenPregunta() {
    const input = document.getElementById('pregImagen');
    const cont = document.getElementById('previewImagenPreguntaContainer');
    const img = document.getElementById('previewImagenPregunta');
    if (input) input.value = '';
    if (img) img.src = '';
    if (cont) cont.classList.add('d-none');
}

function cambiarTipoPregunta(tipo) {
    const secOpc = document.getElementById('seccionOpcionMultiple');
    const secVF = document.getElementById('seccionVerdaderoFalso');
    const secAb = document.getElementById('seccionAbierta');

    if (!secOpc || !secVF || !secAb) return;

    secOpc.classList.add('d-none');
    secVF.classList.add('d-none');
    secAb.classList.add('d-none');

    // Deshabilitar los campos de secciones no activas para evitar bloqueos de validación HTML5
    secOpc.querySelectorAll('input, select').forEach(inp => inp.disabled = (tipo !== 'opcion_multiple'));
    secVF.querySelectorAll('input, select').forEach(inp => inp.disabled = (tipo !== 'verdadero_falso'));
    secAb.querySelectorAll('textarea').forEach(inp => inp.disabled = (tipo !== 'abierta'));

    if (tipo === 'opcion_multiple') {
        secOpc.classList.remove('d-none');
        sincronizarOpcionCorrecta();
    } else if (tipo === 'verdadero_falso') {
        secVF.classList.remove('d-none');
    } else if (tipo === 'abierta') {
        secAb.classList.remove('d-none');
    }
}

function agregarCampoOpcion() {
    const cont = document.getElementById('contenedorOpcionesInputs');
    const count = cont.children.length;
    const letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
    const letra = letras[count] || `${count + 1}`;

    const div = document.createElement('div');
    div.className = 'input-group opcion-item';
    div.innerHTML = `
        <div class="input-group-text bg-light">
            <input class="form-check-input mt-0 radio-opcion-correcta" type="radio" name="radio_opcion_correcta" value="${count}" onchange="sincronizarOpcionCorrecta()" title="Marcar como respuesta correcta">
        </div>
        <span class="input-group-text bg-light text-muted fw-semibold letra-opcion">${letra}</span>
        <input type="text" name="opciones[]" class="form-control input-opcion" placeholder="Opción ${count + 1}" oninput="sincronizarOpcionCorrecta()">
        <button type="button" class="btn btn-outline-danger" onclick="eliminarCampoOpcion(this)"><i class="bi bi-trash"></i></button>
    `;
    cont.appendChild(div);
    sincronizarOpcionCorrecta();
}

function leerArchivoBase64(file) {
    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onload = (ev) => resolve(ev.target.result || '');
        reader.onerror = () => resolve('');
        reader.readAsDataURL(file);
    });
}

async function guardarPregunta(e) {
    e.preventDefault();
    const btn = document.getElementById('btnGuardarPregunta');
    const originalText = btn ? btn.innerHTML : 'Guardar Pregunta';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';
    }

    const form = e.target;
    const formData = new FormData(form);

    const tipo = document.getElementById('pregTipo').value;
    const preguntaTexto = (document.getElementById('pregTexto').value || '').trim();
    const puntaje = parseInt(document.getElementById('pregPuntaje').value || '0');

    if (!preguntaTexto || puntaje <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos Incompletos',
            text: 'Debe ingresar el texto de la pregunta y un puntaje válido.'
        });
        if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
        return;
    }

    if (/\d{16,}/.test(preguntaTexto)) {
        Swal.fire({
            icon: 'warning',
            title: 'Límite de Números Excedido',
            text: 'No se permite ingresar más de 15 números seguidos en el cuadro de texto de la pregunta.'
        });
        if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
        return;
    }

    if (/([a-zA-ZáéíóúÁÉÍÓÚñÑüÜ])\1{5,}/i.test(preguntaTexto)) {
        Swal.fire({
            icon: 'warning',
            title: 'Texto de Pregunta Inválido',
            text: 'No debe repetir más de 5 veces seguidas la misma letra en la pregunta.'
        });
        if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
        return;
    }

    let respuestaFinal = '';
    let opcionesFinales = [];

    if (tipo === 'opcion_multiple') {
        sincronizarOpcionCorrecta();
        const inputsOpc = document.querySelectorAll('#contenedorOpcionesInputs input[name="opciones[]"]');
        opcionesFinales = Array.from(inputsOpc).map(i => i.value.trim()).filter(v => v.length > 0);

        if (opcionesFinales.length < 2) {
            Swal.fire({
                icon: 'warning',
                title: 'Opciones Insuficientes',
                text: 'Debe ingresar al menos dos opciones para la pregunta de opción múltiple.'
            });
            if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
            return;
        }

        respuestaFinal = (document.getElementById('pregRespuestaCorrecta')?.value || '').trim();
        if (!respuestaFinal) {
            Swal.fire({
                icon: 'warning',
                title: 'Seleccione una Opción Correcta Válida',
                text: 'La opción marcada como correcta no tiene texto. Escriba el texto de la opción o seleccione otra opción con texto como respuesta correcta.'
            });
            if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
            return;
        }

        const coincide = opcionesFinales.some(o => o.toLowerCase() === respuestaFinal.toLowerCase());
        if (!coincide) {
            Swal.fire({
                icon: 'warning',
                title: 'Respuesta no coincide',
                text: 'Seleccione cuál de las opciones ingresadas es la correcta.'
            });
            if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
            return;
        }

    } else if (tipo === 'verdadero_falso') {
        const radioVF = document.querySelector('input[name="radio_vf_correcta"]:checked');
        respuestaFinal = radioVF ? radioVF.value : (document.getElementById('pregRespuestaVF')?.value || 'Verdadero');
        opcionesFinales = ['Verdadero', 'Falso'];

    } else if (tipo === 'abierta') {
        respuestaFinal = (document.getElementById('pregRespuestaAbierta')?.value || '').trim() || 'Criterio pendiente de revisión por el docente';
        opcionesFinales = [];
    }

    const payload = new FormData();
    payload.append('examen_id', formData.get('examen_id'));
    if (document.getElementById('preguntaId')?.value) {
        payload.append('id', document.getElementById('preguntaId').value);
    }
    payload.append('pregunta', preguntaTexto);
    payload.append('tipo', tipo);
    payload.append('puntaje', puntaje);
    payload.append('respuesta', respuestaFinal);
    payload.append('opciones', JSON.stringify(opcionesFinales));
    payload.append('csrf_token', CSRF_TOKEN);

    const fileInput = document.getElementById('pregImagen');
    if (fileInput && fileInput.files && fileInput.files[0]) {
        payload.append('imagen', fileInput.files[0]);
        const b64 = await leerArchivoBase64(fileInput.files[0]);
        if (b64) {
            payload.append('imagen_base64', b64);
        }
    }

    try {
        const res = await apiFetch(`${BASE_URL}/index.php?controller=profesor&action=guardarPregunta`, {
            method: 'POST',
            body: payload
        });

        if (res.success) {
            const modalEl = document.getElementById('modalPregunta');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            Toast.fire({ icon: 'success', title: res.message });
            setTimeout(() => location.reload(), 500);
        } else {
            Swal.fire({ icon: 'error', title: 'Error al Guardar', text: res.message });
            if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
        }
    } catch (e) {
        if (btn) { btn.disabled = false; btn.innerHTML = originalText; }
    }
}

function eliminarPregunta(id) {
    Swal.fire({
        title: '¿Eliminar pregunta?',
        text: 'Se eliminará de la evaluación y se reajustará el puntaje acumulado.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            const examenId = document.getElementById('examenId')?.value;
            try {
                const res = await apiFetch(`${BASE_URL}/index.php?controller=profesor&action=eliminarPregunta`, {
                    method: 'POST',
                    body: { id: id, examen_id: examenId, csrf_token: CSRF_TOKEN }
                });
                if (res.success) {
                    Toast.fire({ icon: 'success', title: res.message });
                    const card = document.getElementById(`cardPregunta-${id}`);
                    if (card) card.remove();
                    setTimeout(() => location.reload(), 500);
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                }
            } catch (e) {}
        }
    });
}

// ==========================================
// REVISIÓN Y CALIFICACIÓN DE ALUMNOS
// ==========================================

async function verEstudiantesInscritos(cursoId, cursoNombre) {
    const modalEl = document.getElementById('modalAlumnosCurso');
    if (!modalEl) return;
    document.getElementById('modalAlumnosTitulo').innerText = `Estudiantes Inscritos: ${cursoNombre}`;
    const tbody = document.getElementById('tbodyAlumnosCurso');
    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Cargando lista...</td></tr>`;

    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    try {
        const res = await apiFetch(`${BASE_URL}/index.php?controller=profesor&action=obtenerEstudiantesCurso&curso_id=${cursoId}`);
        if (res.success) {
            if (res.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">No hay estudiantes inscritos en este curso aún.</td></tr>`;
                return;
            }
            tbody.innerHTML = res.data.map(e => `
                <tr>
                    <td class="fw-bold">${e.matricula}</td>
                    <td>${e.Apellidos} ${e.nombre}</td>
                    <td><small class="text-muted">${e.Correo}</small></td>
                    <td><span class="badge bg-secondary-subtle text-secondary rounded-pill">${e.grado}° "${e.grupo}"</span></td>
                    <td><small class="text-muted">${e.fecha_inscripcion}</small></td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-1">
                            <a href="${BASE_URL}/index.php?controller=reporte&action=pdfEstudiante&estudiante_id=${e.estudiante_id}&curso_id=${cursoId}" target="_blank" class="btn btn-sm btn-outline-danger rounded-pill px-2" title="Reporte PDF de Calificaciones del Estudiante">
                                <i class="bi bi-file-pdf me-1"></i> PDF
                            </a>
                            <a href="${BASE_URL}/index.php?controller=reporte&action=excelEstudiante&estudiante_id=${e.estudiante_id}&curso_id=${cursoId}" class="btn btn-sm btn-outline-success rounded-pill px-2" title="Exportar Excel de Calificaciones del Estudiante">
                                <i class="bi bi-file-earmark-excel me-1"></i> Excel
                            </a>
                        </div>
                    </td>
                </tr>
            `).join('');
        }
    } catch (e) {}
}

async function revisarRespuestasAlumno(examenId, estudianteId, alumnoNombre) {
    const modalEl = document.getElementById('modalRevisar');
    if (!modalEl) return;
    document.getElementById('modalRevisarTitulo').innerText = `Revisión: ${alumnoNombre}`;
    document.getElementById('modalRevisarSubtitulo').innerText = `Calificación de preguntas abiertas y retroalimentación`;
    const cont = document.getElementById('contenedorRespuestasAlumno');
    cont.innerHTML = `<div class="text-center py-4"><span class="spinner-border spinner-border-sm me-2"></span>Cargando respuestas del alumno...</div>`;

    const modal = new bootstrap.Modal(modalEl);
    modal.show();

    try {
        const res = await apiFetch(`${BASE_URL}/index.php?controller=profesor&action=obtenerRespuestasEstudiante&examen_id=${examenId}&estudiante_id=${estudianteId}`);
        if (res.success) {
            const respuestas = res.data.respuestas;
            const calif = res.data.calificacion;

            if (respuestas.length === 0) {
                cont.innerHTML = `<div class="text-center py-4 text-muted">El estudiante no tiene respuestas registradas.</div>`;
                return;
            }

            const puntajeTotal = parseFloat(calif.puntaje_total ?? 100);
            const notaObtenida = parseFloat(calif.calificaciones ?? 0);
            const esAprobado = puntajeTotal > 0 ? (notaObtenida >= (puntajeTotal / 2)) : false;

            let html = `
                <div class="alert alert-light border p-3 rounded-3 mb-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <strong>Calificación Global Calculada:</strong>
                        <span class="fs-4 fw-bold ms-2 ${esAprobado ? 'text-success' : 'text-danger'}">
                            ${notaObtenida.toFixed(2)} / ${puntajeTotal} pts
                        </span>
                        <span class="badge ${esAprobado ? 'bg-success-subtle text-success border border-success-subtle' : 'bg-danger-subtle text-danger border border-danger-subtle'} rounded-pill ms-2 px-3 py-1">
                            ${esAprobado ? 'APROBATORIO' : 'REPROBATORIO'}
                        </span>
                    </div>
                    <div>
                        <span class="badge ${calif.estado === 'calificado' ? 'bg-success' : 'bg-warning text-dark'} rounded-pill px-3 py-2">
                            Estado: ${calif.estado.toUpperCase()}
                        </span>
                    </div>
                </div>
            `;

            html += respuestas.map((r, i) => {
                const esAbierta = r.tipo === 'abierta';
                return `
                    <div class="card border rounded-3 p-3 mb-3 shadow-none bg-light bg-opacity-25">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold small text-secondary">Pregunta ${i + 1} (${r.tipo.toUpperCase()})</span>
                            <span class="badge ${r.puntaje_obtenido !== null ? 'bg-primary' : 'bg-warning text-dark'} rounded-pill">
                                Puntos: ${r.puntaje_obtenido !== null ? parseFloat(r.puntaje_obtenido).toFixed(2) : 'Por evaluar'} / ${r.puntaje_maximo} pts
                            </span>
                        </div>
                        <h6 class="fw-bold text-dark mb-2">${r.pregunta}</h6>
                        ${r.imagen ? `
                            <div class="mb-2">
                                <img src="${r.imagen.startsWith('data:image') ? r.imagen : `${BASE_URL}/${r.imagen}`}" alt="Imagen de la pregunta" class="img-fluid rounded-2 border" style="max-height: 180px; object-fit: contain;">
                            </div>
                        ` : ''}
                        <div class="p-2 bg-white border rounded-2 small mb-2">
                            <span class="text-muted d-block">Respuesta del Alumno:</span>
                            <strong class="text-dark">${r.respuesta_estudiante || '(Sin respuesta)'}</strong>
                        </div>

                        ${!esAbierta ? `
                            <div class="small text-success mb-2">
                                <i class="bi bi-check-circle me-1"></i> Respuesta Correcta: <strong>${r.respuesta_correcta}</strong>
                            </div>
                        ` : `
                            <div class="border-top pt-2 mt-2">
                                <label class="form-label small fw-bold text-primary mb-1">Calificar Pregunta Abierta (Max: ${r.puntaje_maximo} pts):</label>
                                <div class="row g-2 align-items-center">
                                    <div class="col-4 col-md-3">
                                        <input type="number" id="ptsInput-${r.respuesta_id}" class="form-control form-control-sm" value="${r.puntaje_obtenido !== null ? r.puntaje_obtenido : 0}" min="0" max="${r.puntaje_maximo}" step="0.5">
                                    </div>
                                    <div class="col-8 col-md-6">
                                        <input type="text" id="retroInput-${r.respuesta_id}" class="form-control form-control-sm" placeholder="Retroalimentación para el alumno..." value="${r.retroalimentacion || ''}">
                                    </div>
                                    <div class="col-12 col-md-3">
                                        <button class="btn btn-sm btn-primary w-100 rounded-pill" onclick="guardarCalificacionAbierta(${r.respuesta_id}, ${examenId}, ${estudianteId}, '${alumnoNombre}')">
                                            <i class="bi bi-save me-1"></i> Asignar Nota
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `}
                    </div>
                `;
            }).join('');

            cont.innerHTML = html;
        }
    } catch (e) {}
}

async function guardarCalificacionAbierta(respuestaId, examenId, estudianteId, alumnoNombre) {
    const pts = parseFloat(document.getElementById(`ptsInput-${respuestaId}`)?.value || 0);
    const retro = document.getElementById(`retroInput-${respuestaId}`)?.value || '';

    try {
        const res = await apiFetch(`${BASE_URL}/index.php?controller=profesor&action=calificarPreguntaAbierta`, {
            method: 'POST',
            body: {
                respuesta_id: respuestaId,
                puntaje_obtenido: pts,
                retroalimentacion: retro,
                csrf_token: CSRF_TOKEN
            }
        });

        if (res.success) {
            Toast.fire({ icon: 'success', title: res.message });
            // Recargar vista de revisión para actualizar nota calculada
            revisarRespuestasAlumno(examenId, estudianteId, alumnoNombre);
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message });
        }
    } catch (e) {}
}

// ==========================================
// VALIDACIONES EN TIEMPO REAL DEL DISEÑADOR DE EXÁMENES
// ==========================================

document.addEventListener('DOMContentLoaded', () => {
    const inputNomExam = document.getElementById('nomExam');
    if (inputNomExam) {
        inputNomExam.addEventListener('input', function () {
            if (/([a-zA-ZáéíóúÁÉÍÓÚñÑüÜ])\1{5,}/i.test(this.value)) {
                this.value = this.value.replace(/([a-zA-ZáéíóúÁÉÍÓÚñÑüÜ])(\1{4})\1+/gi, '$1$2');
                Swal.fire({
                    icon: 'warning',
                    title: 'Nombre de Evaluación Inválido',
                    text: 'No debe repetir más de 5 veces seguidas la misma letra en el nombre del examen.'
                });
            }

            const digitos = this.value.match(/\d/g) || [];
            if (digitos.length > 5) {
                let contador = 0;
                this.value = this.value.replace(/\d/g, (d) => {
                    contador++;
                    return contador <= 5 ? d : '';
                });
                Swal.fire({
                    icon: 'warning',
                    title: 'Nombre de Evaluación Inválido',
                    text: 'No puede ingresar más de 5 dígitos o solo números en el nombre de la evaluación o examen.'
                });
            }
        });

        inputNomExam.addEventListener('blur', function () {
            const val = this.value.trim();
            if (val.length > 0 && /^\d+$/.test(val.replace(/\s+/g, ''))) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Nombre de Evaluación Inválido',
                    text: 'No puede ingresar más de 5 dígitos o solo números en el nombre de la evaluación o examen.'
                });
            }
        });
    }

    const inputPregTexto = document.getElementById('pregTexto');
    if (inputPregTexto) {
        inputPregTexto.addEventListener('input', function () {
            if (/([a-zA-ZáéíóúÁÉÍÓÚñÑüÜ])\1{5,}/i.test(this.value)) {
                this.value = this.value.replace(/([a-zA-ZáéíóúÁÉÍÓÚñÑüÜ])(\1{4})\1+/gi, '$1$2');
                Swal.fire({
                    icon: 'warning',
                    title: 'Texto de Pregunta Inválido',
                    text: 'No debe repetir más de 5 veces seguidas la misma letra en la pregunta.'
                });
            }

            if (/\d{16,}/.test(this.value)) {
                this.value = this.value.replace(/(\d{15})\d+/g, '$1');
                Swal.fire({
                    icon: 'warning',
                    title: 'Límite de Números Excedido',
                    text: 'No se permite ingresar más de 15 números seguidos en el cuadro de texto de la pregunta.'
                });
            }
        });
    }
});
