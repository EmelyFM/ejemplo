/**
 * Utilidades globales y manejo centralizado de Fetch API y SweetAlert2
 */

// Helper para peticiones asíncronas con Fetch API
async function apiFetch(url, options = {}) {
    const defaultHeaders = {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    };

    if (typeof CSRF_TOKEN !== 'undefined' && CSRF_TOKEN) {
        defaultHeaders['X-CSRF-Token'] = CSRF_TOKEN;
    }

    // Si el body es un objeto plano y no es FormData, convertir a JSON
    if (options.body && !(options.body instanceof FormData) && typeof options.body === 'object') {
        defaultHeaders['Content-Type'] = 'application/json';
        options.body = JSON.stringify(options.body);
    }

    options.headers = {
        ...defaultHeaders,
        ...(options.headers || {})
    };

    try {
        const response = await fetch(url, options);

        // Si la sesión expiró (401)
        if (response.status === 401) {
            Swal.fire({
                icon: 'warning',
                title: 'Sesión Expirada',
                text: 'Su sesión ha caducado. Será redirigido a la pantalla de inicio de sesión.',
                confirmButtonText: 'Entendido'
            }).then(() => {
                window.location.href = `${BASE_URL}/index.php?controller=auth&action=login`;
            });
            throw new Error('Sesión expirada');
        }

        // Si acceso denegado (403)
        if (response.status === 403) {
            const errData = await response.json().catch(() => ({}));
            Swal.fire({
                icon: 'error',
                title: 'Acceso Denegado',
                text: errData.message || 'No tiene permisos para ejecutar esta acción.'
            });
            throw new Error('Acceso denegado');
        }

        const data = await response.json();
        return data;
    } catch (error) {
        if (error.message !== 'Sesión expirada' && error.message !== 'Acceso denegado') {
            console.error('Error de red o procesamiento:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error de Comunicación',
                text: 'Ocurrió un problema de comunicación con el servidor. Verifique que XAMPP y MySQL estén activos.'
            });
        }
        throw error;
    }
}

// Alerta flotante tipo Toast
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer);
        toast.addEventListener('mouseleave', Swal.resumeTimer);
    }
});

// Función de cierre de sesión seguro
function cerrarSesion() {
    Swal.fire({
        title: '¿Desea cerrar su sesión?',
        text: 'Tendrá que ingresar sus credenciales nuevamente para acceder.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Sí, salir',
        cancelButtonText: 'Cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const res = await apiFetch(`${BASE_URL}/index.php?controller=auth&action=logout`, { method: 'POST' });
                if (res.success) {
                    window.location.href = res.data.redirect || `${BASE_URL}/index.php?controller=auth&action=login`;
                }
            } catch (e) {
                window.location.href = `${BASE_URL}/index.php?controller=auth&action=login`;
            }
        }
    });
}
