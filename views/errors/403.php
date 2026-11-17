<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado (403)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/custom.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center min-vh-100">
    <div class="text-center p-5 bg-white rounded-4 shadow-sm" style="max-width: 500px;">
        <i class="bi bi-shield-lock-fill text-danger" style="font-size: 4rem;"></i>
        <h1 class="h3 fw-bold mt-3 mb-2 text-dark">Acceso Restringido (403)</h1>
        <p class="text-muted mb-4"><?= e($mensaje ?? 'No tienes permisos suficientes para acceder a este módulo o recurso.') ?></p>
        <a href="<?= BASE_URL ?>" class="btn btn-primary rounded-pill px-4">
            <i class="bi bi-house-door-fill me-1"></i> Volver al Inicio
        </a>
    </div>
</body>
</html>
