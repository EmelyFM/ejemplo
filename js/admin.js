/**
 * Lógica asíncrona de administración mediante Fetch API y SweetAlert2
 */

let listaUsuariosGlobal = [];
let listaCursosGlobal = [];
let listaProfesoresGlobal = [];
let listaEstudiantesGlobal = [];

// ==========================================
// FUNCIONES DE VALIDACIÓN (LETRAS Y CORREO)
// ==========================================

function esSoloLetras(texto) {
    return /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/.test((texto || '').trim());
}

function esCorreoValidoCom(correo) {
    const val = (correo || '').trim();
    return val.includes('@') && val.toLowerCase().includes('.com') && /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.com$/i.test(val);
}

function limpiarAlertasValidacion(idsAlertas, idsInputs = []) {
    idsAlertas.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.add('d-none');
    });
    idsInputs.forEach(id => {
        const inp = document.getElementById(id);
        if (inp) inp.classList.remove('is-invalid', 'is-valid');
    });
}

function configurarValidacionSoloLetras(inputId, alertaId) {
    const input = document.getElementById(inputId);
    const alerta = document.getElementById(alertaId);
    if (!input) return;

    input.addEventListener('input', function () {
        const valorOriginal = this.value;
        const valorLimpio = valorOriginal.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/g, '');
        if (valorOriginal !== valorLimpio) {
            this.value = valorLimpio;
            if (alerta) alerta.classList.remove('d-none');
        } else {
            if (alerta) alerta.classList.add('d-none');
        }
    });

    input.addEventListener('blur', function () {
        if (alerta) alerta.classList.add('d-none');
    });
}

function configurarValidacionCorreo(inputId, alertaId, textoId) {
    const input = document.getElementById(inputId);
    const alerta = document.getElementById(alertaId);
    const texto = document.getElementById(textoId);
    if (!input || !alerta) return;

    const evaluarCorreo = () => {
        const val = input.value.trim();
        if (val === '') {
            alerta.classList.add('d-none');
            input.classList.remove('is-invalid', 'is-valid');
            return;
        }

        const tieneArroba = val.includes('@');
        const tieneCom = val.toLowerCase().includes('.com');
        const validoCompleto = esCorreoValidoCom(val);

        if (validoCompleto) {
            // Desaparece la alerta cuando cumple con lo requerido
            alerta.classList.add('d-none');
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
        } else {
            let msg = "El correo electrónico debe incluir '@' y '.com'.";
            if (!tieneArroba && !tieneCom) {
                msg = "El correo electrónico debe incluir '@' y '.com'.";
            } else if (!tieneArroba) {
                msg = "Al correo electrónico le falta incluir el símbolo '@'.";
            } else if (!tieneCom) {
                msg = "Al correo electrónico le falta incluir la terminación '.com'.";
            } else {
                msg = "Ingrese un correo electrónico válido con '@' y '.com' (ej. usuario@dominio.com).";
            }
            if (texto) texto.innerText = msg;
            alerta.classList.remove('d-none');
            input.classList.remove('is-valid');
            input.classList.add('is-invalid');
        }
    };

    input.addEventListener('input', evaluarCorreo);
    input.addEventListener('blur', evaluarCorreo);
}

document.addEventListener('DOMContentLoaded', () => {
    // Módulo Usuarios
    configurarValidacionSoloLetras('usuarioNombre', 'alertaUsuarioNombre');
    configurarValidacionSoloLetras('usuarioApellidos', 'alertaUsuarioApellidos');
    configurarValidacionCorreo('usuarioCorreo', 'alertaUsuarioCorreo', 'textoAlertaUsuarioCorreo');

    // Módulo Profesores
    configurarValidacionSoloLetras('profNombre', 'alertaProfNombre');
    configurarValidacionSoloLetras('profApellidos', 'alertaProfApellidos');
    configurarValidacionCorreo('profCorreo', 'alertaProfCorreo', 'textoAlertaProfCorreo');

    // Módulo Estudiantes
    configurarValidacionSoloLetras('estNombre', 'alertaEstNombre');
    configurarValidacionSoloLetras('estApellidos', 'alertaEstApellidos');
    configurarValidacionCorreo('estCorreo', 'alertaEstCorreo', 'textoAlertaEstCorreo');

    // Módulo Cursos: validar nombre del curso (no más de 5 dígitos, no solo números, no más de 5 letras iguales seguidas)
    const inputCursoNombre = document.getElementById('cursoNombre');
    if (inputCursoNombre) {
        inputCursoNombre.addEventListener('input', function () {
            if (/([a-zA-ZáéíóúÁÉÍÓÚñÑüÜ])\1{5,}/i.test(this.value)) {
                this.value = this.value.replace(/([a-zA-ZáéíóúÁÉÍÓÚñÑüÜ])(\1{4})\1+/gi, '$1$2');
                Swal.fire({
                    icon: 'warning',
                    title: 'Nombre de Curso Inválido',
                    text: 'No debe repetir más de 5 veces seguidas la misma letra en el nombre del curso.'
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
                    title: 'Nombre de Curso Inválido',
                    text: 'No puede ingresar más de 5 dígitos o solo números en el nombre del curso.'
                });
            }
        });

        inputCursoNombre.addEventListener('blur', function () {
            const val = this.value.trim();
            if (val.length > 0 && /^\d+$/.test(val.replace(/\s+/g, ''))) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Nombre de Curso Inválido',
                    text: 'No puede ingresar más de 5 dígitos o solo números en el nombre del curso.'
                });
            }
        });
    }
});

// ==========================================
// MÓDULO DE USUARIOS
// ==========================================

async function cargarUsuarios() {
    try {
        const res = await apiFetch(`${BASE_URL}/index.php?controller=admin&action=listarUsuarios`);
        if (res.success) {
            listaUsuariosGlobal = res.data;
            renderizarTablaUsuarios(listaUsuariosGlobal);
        }
    } catch (e) {
        document.getElementById('tbodyUsuarios').innerHTML = `
            <tr><td colspan="6" class="text-center text-danger py-4">Error al cargar usuarios.</td></tr>
        `;
    }
}

function renderizarTablaUsuarios(usuarios) {
    const tbody = document.getElementById('tbodyUsuarios');
    const badge = document.getElementById('totalUsuariosBadge');
    if (badge) badge.innerText = `${usuarios.length} usuarios registrados`;

    if (usuarios.length === 0) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">No se encontraron usuarios.</td></tr>`;
        return;
    }

    tbody.innerHTML = usuarios.map(u => {
        let roleBadge = 'bg-secondary';
        if (u.Roles_id == 1) roleBadge = 'bg-primary';
        if (u.Roles_id == 2) roleBadge = 'bg-success';
        if (u.Roles_id == 3) roleBadge = 'bg-info text-dark';

        let detalles = '-';
        if (u.Roles_id == 3 && u.matricula) {
            detalles = `<span class="badge bg-light text-dark border">${u.matricula} (${u.grado}° "${u.grupo}")</span>`;
        } else if (u.Roles_id == 2 && u.especialidad) {
            detalles = `<span class="text-muted small">${u.especialidad}</span>`;
        }

        return `
            <tr>
                <td class="fw-bold">${u.id}</td>
                <td>
                    <div class="fw-bold">${u.Apellidos} ${u.nombre}</div>
                </td>
                <td><span class="text-muted">${u.Correo}</span></td>
                <td><span class="badge ${roleBadge} rounded-pill px-3">${u.rol_nombre}</span></td>
                <td>${detalles}</td>
                <td class="text-end">
                    <div class="d-flex justify-content-end gap-1">
                        <button class="btn btn-sm btn-outline-primary" onclick="editarUsuario(${u.id})" title="Editar Usuario">
                            <i class="bi bi-pencil-fill"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarUsuario(${u.id}, '${u.nombre} ${u.Apellidos}')" title="Eliminar Usuario">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function filtrarTablaUsuarios() {
    const texto = (document.getElementById('buscarUsuario')?.value || '').toLowerCase().trim();
    const rol = document.getElementById('filtroRol')?.value || '';

    const filtrados = listaUsuariosGlobal.filter(u => {
        const coincideTexto = !texto 
            || (u.nombre && u.nombre.toLowerCase().includes(texto))
            || (u.Apellidos && u.Apellidos.toLowerCase().includes(texto))
            || (u.Correo && u.Correo.toLowerCase().includes(texto))
            || (u.matricula && u.matricula.toLowerCase().includes(texto));

        const coincideRol = !rol || (u.rol_nombre === rol);

        return coincideTexto && coincideRol;
    });

    renderizarTablaUsuarios(filtrados);
}

function abrirModalUsuario() {
    document.getElementById('formUsuario').reset();
    document.getElementById('usuarioId').value = '';
    document.getElementById('modalUsuarioTitulo').innerText = 'Nuevo Usuario';
    document.getElementById('helpPassword').innerText = '(Obligatoria para nuevos)';
    document.getElementById('usuarioPassword').required = true;
    limpiarAlertasValidacion(
        ['alertaUsuarioNombre', 'alertaUsuarioApellidos', 'alertaUsuarioCorreo'],
        ['usuarioNombre', 'usuarioApellidos', 'usuarioCorreo']
    );
    cambiarCamposPorRol('');
    const modal = new bootstrap.Modal(document.getElementById('modalUsuario'));
    modal.show();
}

function editarUsuario(id) {
    const u = listaUsuariosGlobal.find(item => item.id == id);
    if (!u) return;

    document.getElementById('formUsuario').reset();
    limpiarAlertasValidacion(
        ['alertaUsuarioNombre', 'alertaUsuarioApellidos', 'alertaUsuarioCorreo'],
        ['usuarioNombre', 'usuarioApellidos', 'usuarioCorreo']
    );

    document.getElementById('usuarioId').value = u.id;
    document.getElementById('usuarioNombre').value = u.nombre || '';
    document.getElementById('usuarioApellidos').value = u.Apellidos || '';
    document.getElementById('usuarioCorreo').value = u.Correo || '';
    document.getElementById('usuarioPassword').value = '';
    document.getElementById('usuarioPassword').required = false;
    document.getElementById('helpPassword').innerText = '(Opcional: dejar en blanco para mantener actual)';
    document.getElementById('usuarioRol').value = u.Roles_id || '';
    document.getElementById('modalUsuarioTitulo').innerText = 'Editar Usuario';

    cambiarCamposPorRol(u.Roles_id);
    if (u.Roles_id == 3) {
        const matEl = document.getElementById('estMatricula');
        const graEl = document.getElementById('estGrado');
        const gruEl = document.getElementById('estGrupo');
        if (matEl) matEl.value = u.matricula || '';
        if (graEl) graEl.value = u.grado || 1;
        if (gruEl) gruEl.value = u.grupo || 'A';
    } else if (u.Roles_id == 2) {
        const espEl = document.getElementById('profEspecialidad');
        if (espEl) espEl.value = u.especialidad || '';
    }

    const modal = new bootstrap.Modal(document.getElementById('modalUsuario'));
    modal.show();
}

function cambiarCamposPorRol(rolId) {
    const divEst = document.getElementById('camposEstudiante');
    const divProf = document.getElementById('camposProfesor');
    if (!divEst || !divProf) return;

    divEst.classList.add('d-none');
    divProf.classList.add('d-none');

    if (rolId == 3) { // Estudiante
        divEst.classList.remove('d-none');
    } else if (rolId == 2) { // Profesor
        divProf.classList.remove('d-none');
    }
}

async function guardarUsuario(e) {
    e.preventDefault();
    const btn = document.getElementById('btnGuardarUsuario');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Guardando...';

    const form = e.target;
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    if (!esSoloLetras(data.nombre) || !esSoloLetras(data.apellidos)) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos Inválidos',
            text: 'Los campos de Nombre y Apellidos solo deben contener letras.'
        });
        btn.disabled = false;
        btn.innerHTML = originalText;
        return;
    }

    if (!esCorreoValidoCom(data.correo)) {
        const alertaCorreo = document.getElementById('alertaUsuarioCorreo');
        if (alertaCorreo) alertaCorreo.classList.remove('d-none');
        Swal.fire({
            icon: 'warning',
            title: 'Correo Electrónico Inválido',
            text: "El correo electrónico debe incluir '@' y '.com'."
        });
        btn.disabled = false;
        btn.innerHTML = originalText;
        return;
    }

    try {
        const res = await apiFetch(`${BASE_URL}/index.php?controller=admin&action=guardarUsuario`, {
            method: 'POST',
            body: data
        });

        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalUsuario')).hide();
            Toast.fire({ icon: 'success', title: res.message });
            cargarUsuarios();
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message });
        }
    } catch (err) {
        // error manejado en apiFetch
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}

function eliminarUsuario(id, nombre) {
    Swal.fire({
        title: `¿Eliminar a ${nombre}?`,
        text: 'Esta acción borrará de manera permanente sus accesos y registros dependientes.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const res = await apiFetch(`${BASE_URL}/index.php?controller=admin&action=eliminarUsuario`, {
                    method: 'POST',
                    body: { id: id, csrf_token: CSRF_TOKEN }
                });
                if (res.success) {
                    Toast.fire({ icon: 'success', title: res.message });
                    cargarUsuarios();
                } else {
                    Swal.fire({ icon: 'error', title: 'No se pudo eliminar', text: res.message });
                }
            } catch (e) {}
        }
    });
}

// ==========================================
// MÓDULO DE PROFESORES
// ==========================================

async function cargarProfesores() {
    try {
        const res = await apiFetch(`${BASE_URL}/index.php?controller=admin&action=listarProfesores`);
        if (res.success) {
            listaProfesoresGlobal = res.data;
            const tbody = document.getElementById('tbodyProfesores');
            if (res.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-4">No hay profesores registrados.</td></tr>`;
                return;
            }
            tbody.innerHTML = res.data.map(p => `
                <tr>
                    <td class="fw-bold">${p.id}</td>
                    <td class="fw-bold">${p.Apellidos || ''} ${p.nombre || ''}</td>
                    <td><span class="text-muted">${p.Correo || '-'}</span></td>
                    <td><span class="badge bg-light text-dark border">${p.especialidad}</span></td>
                    <td><span class="badge bg-primary-subtle text-primary rounded-pill px-3">${p.total_cursos} curso(s)</span></td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-1">
                            <button class="btn btn-sm btn-outline-primary" onclick="editarProfesor(${p.id})" title="Editar Profesor">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarUsuario(${p.usuario_id}, '${p.nombre} ${p.Apellidos}')" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }
    } catch (e) {}
}

function abrirModalProfesor() {
    document.getElementById('formProfesor').reset();
    document.getElementById('profesorId').value = '';
    const titulo = document.getElementById('modalProfesorTitulo');
    if (titulo) titulo.innerText = 'Registrar Profesor';
    limpiarAlertasValidacion(
        ['alertaProfNombre', 'alertaProfApellidos', 'alertaProfCorreo'],
        ['profNombre', 'profApellidos', 'profCorreo']
    );
    const modal = new bootstrap.Modal(document.getElementById('modalProfesor'));
    modal.show();
}

function editarProfesor(id) {
    const p = listaProfesoresGlobal.find(item => item.id == id);
    if (!p) return;

    document.getElementById('formProfesor').reset();
    limpiarAlertasValidacion(
        ['alertaProfNombre', 'alertaProfApellidos', 'alertaProfCorreo'],
        ['profNombre', 'profApellidos', 'profCorreo']
    );

    document.getElementById('profesorId').value = p.id;
    document.getElementById('profNombre').value = p.nombre || '';
    document.getElementById('profApellidos').value = p.Apellidos || '';
    document.getElementById('profCorreo').value = p.Correo || '';
    document.getElementById('profPassword').value = '';
    document.getElementById('profEspecialidadModal').value = p.especialidad || '';

    const titulo = document.getElementById('modalProfesorTitulo');
    if (titulo) titulo.innerText = 'Editar Profesor';

    const modal = new bootstrap.Modal(document.getElementById('modalProfesor'));
    modal.show();
}

async function guardarProfesor(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    if (!esSoloLetras(data.nombre) || !esSoloLetras(data.apellidos)) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos Inválidos',
            text: 'Los campos de Nombre y Apellidos solo deben contener letras.'
        });
        return;
    }

    if (!esCorreoValidoCom(data.correo)) {
        const alertaCorreo = document.getElementById('alertaProfCorreo');
        if (alertaCorreo) alertaCorreo.classList.remove('d-none');
        Swal.fire({
            icon: 'warning',
            title: 'Correo Electrónico Inválido',
            text: "El correo electrónico debe incluir '@' y '.com'."
        });
        return;
    }

    try {
        const res = await apiFetch(`${BASE_URL}/index.php?controller=admin&action=guardarProfesor`, {
            method: 'POST',
            body: data
        });
        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalProfesor')).hide();
            Toast.fire({ icon: 'success', title: res.message });
            cargarProfesores();
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message });
        }
    } catch (e) {}
}

// ==========================================
// MÓDULO DE ESTUDIANTES
// ==========================================

async function cargarEstudiantes() {
    try {
        const res = await apiFetch(`${BASE_URL}/index.php?controller=admin&action=listarEstudiantes`);
        if (res.success) {
            listaEstudiantesGlobal = res.data;
            const tbody = document.getElementById('tbodyEstudiantes');
            if (res.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">No hay estudiantes registrados.</td></tr>`;
                return;
            }
            tbody.innerHTML = res.data.map(e => `
                <tr>
                    <td class="fw-bold">${e.id}</td>
                    <td><span class="badge bg-light text-primary border">${e.matricula}</span></td>
                    <td class="fw-bold">${e.Apellidos || ''} ${e.nombre || ''}</td>
                    <td><span class="text-muted">${e.Correo || '-'}</span></td>
                    <td><span class="badge bg-secondary-subtle text-secondary rounded-pill">${e.grado}° "${e.grupo}"</span></td>
                    <td><span class="badge bg-info-subtle text-info rounded-pill px-3">${e.total_cursos} curso(s)</span></td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-1">
                            <button class="btn btn-sm btn-outline-primary" onclick="editarEstudiante(${e.id})" title="Editar Estudiante">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarUsuario(${e.usuario_id}, '${e.nombre} ${e.Apellidos}')" title="Eliminar">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }
    } catch (e) {}
}

function abrirModalEstudiante() {
    document.getElementById('formEstudiante').reset();
    document.getElementById('estudianteId').value = '';
    const titulo = document.getElementById('modalEstudianteTitulo');
    if (titulo) titulo.innerText = 'Registrar Estudiante';
    limpiarAlertasValidacion(
        ['alertaEstNombre', 'alertaEstApellidos', 'alertaEstCorreo'],
        ['estNombre', 'estApellidos', 'estCorreo']
    );
    const modal = new bootstrap.Modal(document.getElementById('modalEstudiante'));
    modal.show();
}

function editarEstudiante(id) {
    const e = listaEstudiantesGlobal.find(item => item.id == id);
    if (!e) return;

    document.getElementById('formEstudiante').reset();
    limpiarAlertasValidacion(
        ['alertaEstNombre', 'alertaEstApellidos', 'alertaEstCorreo'],
        ['estNombre', 'estApellidos', 'estCorreo']
    );

    document.getElementById('estudianteId').value = e.id;
    document.getElementById('estNombre').value = e.nombre || '';
    document.getElementById('estApellidos').value = e.Apellidos || '';
    document.getElementById('estCorreo').value = e.Correo || '';
    document.getElementById('estPassword').value = '';
    document.getElementById('estMatriculaModal').value = e.matricula || '';
    document.getElementById('estGradoModal').value = e.grado || 1;
    document.getElementById('estGrupoModal').value = e.grupo || 'A';

    const titulo = document.getElementById('modalEstudianteTitulo');
    if (titulo) titulo.innerText = 'Editar Estudiante';

    const modal = new bootstrap.Modal(document.getElementById('modalEstudiante'));
    modal.show();
}

async function guardarEstudiante(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    if (!esSoloLetras(data.nombre) || !esSoloLetras(data.apellidos)) {
        Swal.fire({
            icon: 'warning',
            title: 'Campos Inválidos',
            text: 'Los campos de Nombre y Apellidos solo deben contener letras.'
        });
        return;
    }

    if (!esCorreoValidoCom(data.correo)) {
        const alertaCorreo = document.getElementById('alertaEstCorreo');
        if (alertaCorreo) alertaCorreo.classList.remove('d-none');
        Swal.fire({
            icon: 'warning',
            title: 'Correo Electrónico Inválido',
            text: "El correo electrónico debe incluir '@' y '.com'."
        });
        return;
    }

    try {
        const res = await apiFetch(`${BASE_URL}/index.php?controller=admin&action=guardarEstudiante`, {
            method: 'POST',
            body: data
        });
        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalEstudiante')).hide();
            Toast.fire({ icon: 'success', title: res.message });
            cargarEstudiantes();
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message });
        }
    } catch (e) {}
}

// ==========================================
// MÓDULO DE CURSOS
// ==========================================

async function cargarCursos() {
    try {
        const res = await apiFetch(`${BASE_URL}/index.php?controller=admin&action=listarCursos`);
        if (res.success) {
            listaCursosGlobal = res.data;
            const tbody = document.getElementById('tbodyCursos');
            if (res.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-4">No hay cursos creados.</td></tr>`;
                return;
            }
            tbody.innerHTML = res.data.map(c => `
                <tr>
                    <td class="fw-bold">${c.id}</td>
                    <td class="fw-bold text-primary">${c.nombre}</td>
                    <td>${c.profesor_nombre || ''} ${c.profesor_apellidos || ''}</td>
                    <td><small class="text-muted">${c.fecha_ini}</small></td>
                    <td><small class="text-muted">${c.fecha_fin}</small></td>
                    <td><span class="badge bg-light text-dark border">${c.total_estudiantes} alumnos</span></td>
                    <td><span class="badge bg-purple-subtle text-purple border">${c.total_examenes} exámenes</span></td>
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-1">
                            <button class="btn btn-sm btn-outline-primary" onclick="editarCurso(${c.id})" title="Editar Curso">
                                <i class="bi bi-pencil-fill"></i>
                            </button>
                            <a href="${BASE_URL}/index.php?controller=reporte&action=pdfCurso&curso_id=${c.id}" target="_blank" class="btn btn-sm btn-outline-danger" title="Reporte PDF">
                                <i class="bi bi-file-pdf"></i>
                            </a>
                            <a href="${BASE_URL}/index.php?controller=reporte&action=excelCurso&curso_id=${c.id}" class="btn btn-sm btn-outline-success" title="Exportar Excel">
                                <i class="bi bi-file-earmark-excel"></i>
                            </a>
                            <button class="btn btn-sm btn-outline-danger" onclick="eliminarCurso(${c.id}, '${c.nombre}')" title="Eliminar Curso">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }
    } catch (e) {}
}

function abrirModalCurso() {
    document.getElementById('formCurso').reset();
    document.getElementById('cursoId').value = '';
    const titulo = document.getElementById('modalCursoTitulo');
    if (titulo) titulo.innerText = 'Nuevo Curso';
    const modal = new bootstrap.Modal(document.getElementById('modalCurso'));
    modal.show();
}

function editarCurso(id) {
    const c = listaCursosGlobal.find(item => item.id == id);
    if (!c) return;

    document.getElementById('formCurso').reset();
    document.getElementById('cursoId').value = c.id;
    document.getElementById('cursoNombre').value = c.nombre || '';
    document.getElementById('cursoProfesorId').value = c.Profesor_id || '';
    document.getElementById('cursoFechaIni').value = c.fecha_ini || '';
    document.getElementById('cursoFechaFin').value = c.fecha_fin || '';

    const titulo = document.getElementById('modalCursoTitulo');
    if (titulo) titulo.innerText = 'Editar Curso';

    const modal = new bootstrap.Modal(document.getElementById('modalCurso'));
    modal.show();
}

async function guardarCurso(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    const nomCurso = (data.nombre || '').trim();
    const cantDigitos = (nomCurso.match(/\d/g) || []).length;
    const soloNumeros = nomCurso.length > 0 && /^\d+$/.test(nomCurso.replace(/\s+/g, ''));
    if (cantDigitos > 5 || soloNumeros) {
        Swal.fire({
            icon: 'warning',
            title: 'Nombre de Curso Inválido',
            text: 'No puede ingresar más de 5 dígitos o solo números en el nombre del curso.'
        });
        return;
    }

    if (/([a-zA-ZáéíóúÁÉÍÓÚñÑüÜ])\1{5,}/i.test(nomCurso)) {
        Swal.fire({
            icon: 'warning',
            title: 'Nombre de Curso Inválido',
            text: 'No debe repetir más de 5 veces seguidas la misma letra en el nombre del curso.'
        });
        return;
    }

    try {
        const res = await apiFetch(`${BASE_URL}/index.php?controller=admin&action=guardarCurso`, {
            method: 'POST',
            body: data
        });
        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalCurso')).hide();
            Toast.fire({ icon: 'success', title: res.message });
            cargarCursos();
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message });
        }
    } catch (e) {}
}

function eliminarCurso(id, nombre) {
    Swal.fire({
        title: `¿Eliminar curso "${nombre}"?`,
        text: 'Se eliminarán sus exámenes y registros relacionados.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const res = await apiFetch(`${BASE_URL}/index.php?controller=admin&action=eliminarCurso`, {
                    method: 'POST',
                    body: { id: id, csrf_token: CSRF_TOKEN }
                });
                if (res.success) {
                    Toast.fire({ icon: 'success', title: res.message });
                    cargarCursos();
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: res.message });
                }
            } catch (e) {}
        }
    });
}

// ==========================================
// MÓDULO DE INSCRIPCIONES
// ==========================================

async function cargarInscripciones() {
    try {
        const res = await apiFetch(`${BASE_URL}/index.php?controller=admin&action=listarInscripciones`);
        if (res.success) {
            const tbody = document.getElementById('tbodyInscripciones');
            if (res.data.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">No hay inscripciones registradas.</td></tr>`;
                return;
            }
            tbody.innerHTML = res.data.map(i => `
                <tr>
                    <td class="fw-bold">${i.id}</td>
                    <td><small class="text-muted">${i.fecha}</small></td>
                    <td class="fw-bold text-primary">${i.curso_nombre}</td>
                    <td><span class="badge bg-light text-dark border">${i.matricula}</span></td>
                    <td>${i.estudiante_apellidos} ${i.estudiante_nombre}</td>
                    <td><span class="badge bg-secondary-subtle text-secondary rounded-pill">${i.grado}° "${i.grupo}"</span></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-danger" onclick="eliminarInscripcion(${i.id})" title="Cancelar Inscripción">
                            <i class="bi bi-x-circle me-1"></i> Desinscribir
                        </button>
                    </td>
                </tr>
            `).join('');
        }
    } catch (e) {}
}

let opcionesOriginalesEstudiantesInscripcion = null;

function inicializarOpcionesEstudiantesInscripcion() {
    if (opcionesOriginalesEstudiantesInscripcion !== null) return;
    const select = document.getElementById('insEstudianteId');
    if (!select) return;

    opcionesOriginalesEstudiantesInscripcion = Array.from(select.querySelectorAll('option'))
        .filter(opt => opt.value !== '')
        .map(opt => ({
            value: opt.value,
            text: opt.textContent.trim(),
            nombre: (opt.getAttribute('data-nombre') || '').toLowerCase(),
            matricula: (opt.getAttribute('data-matricula') || '').toLowerCase(),
            grado: opt.getAttribute('data-grado') || '',
            grupo: opt.getAttribute('data-grupo') || ''
        }));
}

function filtrarSelectorEstudiantesInscripcion() {
    inicializarOpcionesEstudiantesInscripcion();
    const select = document.getElementById('insEstudianteId');
    if (!select || !opcionesOriginalesEstudiantesInscripcion) return;

    const texto = (document.getElementById('buscarEstInscripcion')?.value || '').toLowerCase().trim();
    const grado = document.getElementById('filtroGradoInscripcion')?.value || '';
    const grupo = document.getElementById('filtroGrupoInscripcion')?.value || '';
    const valorSeleccionadoPrevio = select.value;

    const filtrados = opcionesOriginalesEstudiantesInscripcion.filter(est => {
        const coincideTexto = !texto
            || est.nombre.includes(texto)
            || est.matricula.includes(texto)
            || est.text.toLowerCase().includes(texto);
        const coincideGrado = !grado || est.grado === grado;
        const coincideGrupo = !grupo || est.grupo.toLowerCase() === grupo.toLowerCase();
        return coincideTexto && coincideGrado && coincideGrupo;
    });

    const contador = document.getElementById('contadorEstudiantesInscripcion');
    if (contador) {
        contador.innerText = `${filtrados.length} disponible(s)`;
    }

    if (filtrados.length === 0) {
        select.innerHTML = `<option value="">No se encontraron alumnos con ese filtro...</option>`;
        return;
    }

    select.innerHTML = `<option value="">Seleccione el estudiante (${filtrados.length} encontrado${filtrados.length === 1 ? '' : 's'})...</option>` +
        filtrados.map(est => `
            <option value="${est.value}"
                    data-nombre="${est.nombre.replace(/"/g, '&quot;')}"
                    data-matricula="${est.matricula.replace(/"/g, '&quot;')}"
                    data-grado="${est.grado}"
                    data-grupo="${est.grupo}">
                ${est.text}
            </option>
        `).join('');

    if (filtrados.length === 1) {
        select.value = filtrados[0].value;
    } else if (valorSeleccionadoPrevio && filtrados.some(e => e.value === valorSeleccionadoPrevio)) {
        select.value = valorSeleccionadoPrevio;
    }
}

function abrirModalInscripcion() {
    document.getElementById('formInscripcion').reset();
    const buscarInput = document.getElementById('buscarEstInscripcion');
    const filtroGrado = document.getElementById('filtroGradoInscripcion');
    const filtroGrupo = document.getElementById('filtroGrupoInscripcion');
    if (buscarInput) buscarInput.value = '';
    if (filtroGrado) filtroGrado.value = '';
    if (filtroGrupo) filtroGrupo.value = '';
    filtrarSelectorEstudiantesInscripcion();
    const select = document.getElementById('insEstudianteId');
    if (select) select.value = '';
    const modal = new bootstrap.Modal(document.getElementById('modalInscripcion'));
    modal.show();
}

async function guardarInscripcion(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());

    try {
        const res = await apiFetch(`${BASE_URL}/index.php?controller=admin&action=inscribirEstudiante`, {
            method: 'POST',
            body: data
        });
        if (res.success) {
            bootstrap.Modal.getInstance(document.getElementById('modalInscripcion')).hide();
            Toast.fire({ icon: 'success', title: res.message });
            cargarInscripciones();
        } else {
            Swal.fire({ icon: 'error', title: 'Error', text: res.message });
        }
    } catch (e) {}
}

function eliminarInscripcion(id) {
    Swal.fire({
        title: '¿Cancelar esta inscripción?',
        text: 'El alumno dejará de tener acceso a los exámenes de este curso.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, cancelar',
        cancelButtonText: 'No'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const res = await apiFetch(`${BASE_URL}/index.php?controller=admin&action=eliminarInscripcion`, {
                    method: 'POST',
                    body: { id: id, csrf_token: CSRF_TOKEN }
                });
                if (res.success) {
                    Toast.fire({ icon: 'success', title: res.message });
                    cargarInscripciones();
                }
            } catch (e) {}
        }
    });
}
