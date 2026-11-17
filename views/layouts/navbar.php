<header class="app-navbar">
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-sm btn-outline-secondary d-md-none" id="btnToggleSidebar">
            <i class="bi bi-list fs-5"></i>
        </button>
        <span class="fw-semibold text-secondary d-none d-sm-inline">
            <i class="bi bi-calendar3 me-1"></i> <?= date('d/m/Y') ?>
        </span>
    </div>

    <div class="d-flex align-items-center gap-3">
        <div class="user-badge">
            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['nombre'] ?? 'U', 0, 1)) ?>
            </div>
            <div class="d-none d-md-block text-end">
                <div class="fw-bold lh-1 text-dark"><?= e($_SESSION['nombre_completo'] ?? 'Usuario') ?></div>
                <small class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill">
                    <?= e($_SESSION['rol_nombre'] ?? 'Rol') ?>
                </small>
            </div>
        </div>

        <button onclick="cerrarSesion()" class="btn btn-outline-danger btn-sm rounded-pill px-3 ms-2" title="Cerrar Sesión">
            <i class="bi bi-box-arrow-right me-1"></i> Salir
        </button>
    </div>
</header>
<main class="p-4 flex-grow-1">
