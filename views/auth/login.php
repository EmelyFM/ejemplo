<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= getCsrfToken() ?>">
    <title>Iniciar Sesión - Sistema de Gestión de Exámenes</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.8/dist/sweetalert2.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/custom.css">

    <script>
        const BASE_URL = "<?= BASE_URL ?>";
        const CSRF_TOKEN = "<?= getCsrfToken() ?>";
    </script>
</head>
<body class="auth-wrapper">

<div class="auth-card">
    <div class="auth-brand">
        <i class="bi bi-mortarboard-fill"></i>
        <h2 class="h4 fw-bold m-0 text-dark">Portal Académico</h2>
    </div>
    
    <p class="text-center text-muted small mb-4">
        Ingrese sus credenciales registradas para acceder a sus evaluaciones y resultados
    </p>

    <form id="formLogin" onsubmit="loginUsuario(event)">
        <input type="hidden" name="csrf_token" value="<?= getCsrfToken() ?>">

        <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Correo Electrónico</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                <input type="email" id="correo" name="correo" class="form-control border-start-0" placeholder="nombre@correo.com" required autofocus>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label small fw-semibold text-secondary">Contraseña</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                <input type="password" id="password" name="password" class="form-control border-start-0" placeholder="••••••••" required>
            </div>
        </div>

        <button type="submit" id="btnLogin" class="btn btn-primary w-100 py-2 fw-semibold rounded-3 mb-3">
            <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
        </button>
    </form>

    <!-- Credenciales de prueba rápida para evaluación -->
    <div class="mt-4 pt-3 border-top text-center">
        <span class="badge bg-light text-secondary border mb-2">Accesos Rápidos de Prueba</span>
        <div class="d-flex justify-content-center gap-2 flex-wrap">
            <button type="button" class="btn btn-xs btn-outline-primary btn-sm" onclick="llenarCredenciales('admin@sistema.com', 'admin123')">
                <i class="bi bi-shield-check"></i> Admin
            </button>
            <button type="button" class="btn btn-xs btn-outline-success btn-sm" onclick="llenarCredenciales('profesor@sistema.com', 'profesor123')">
                <i class="bi bi-person-workspace"></i> Profesor
            </button>
            <button type="button" class="btn btn-xs btn-outline-info btn-sm" onclick="llenarCredenciales('estudiante@sistema.com', 'estudiante123')">
                <i class="bi bi-backpack"></i> Alumno
            </button>
        </div>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.8/dist/sweetalert2.all.min.js"></script>
<script src="<?= BASE_URL ?>/js/main.js"></script>
<script>
function llenarCredenciales(correo, pass) {
    document.getElementById('correo').value = correo;
    document.getElementById('password').value = pass;
}

async function loginUsuario(e) {
    e.preventDefault();
    const btn = document.getElementById('btnLogin');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verificando...';

    const correo = document.getElementById('correo').value.trim();
    const password = document.getElementById('password').value;

    try {
        const res = await fetch(`${BASE_URL}/index.php?controller=auth&action=login`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ correo, password })
        });

        const data = await res.json();

        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Acceso Correcto',
                text: data.message,
                showConfirmButton: false,
                timer: 1200
            }).then(() => {
                window.location.href = data.data.redirect;
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error de Autenticación',
                text: data.message || 'Credenciales inválidas'
            });
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
    } catch (err) {
        Swal.fire({
            icon: 'error',
            title: 'Error de Red',
            text: 'No se pudo comunicar con el servidor en XAMPP. Verifique su conexión.'
        });
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
}
</script>
</body>
</html>
