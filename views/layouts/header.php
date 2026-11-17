<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= getCsrfToken() ?>">
    <title><?= isset($titulo) ? e($titulo) . ' - ' : '' ?>Sistema de Gestión de Exámenes</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.10.8/dist/sweetalert2.min.css">
    <!-- Estilos personalizados -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/css/custom.css">

    <script>
        const BASE_URL = "<?= BASE_URL ?>";
        const CSRF_TOKEN = "<?= getCsrfToken() ?>";
    </script>
</head>
<body>
<div class="app-wrapper">
