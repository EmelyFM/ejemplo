<?php
$currentController = $_GET['controller'] ?? '';
$currentAction = $_GET['action'] ?? '';
$rol = getAuthRoleId();
?>
<aside class="app-sidebar">
    <div class="sidebar-header">
        <a href="<?= BASE_URL ?>" class="sidebar-brand">
            <i class="bi bi-mortarboard-fill"></i>
            <span>EXAM SYSTEM</span>
        </a>
    </div>

    <ul class="sidebar-nav">
        <?php if ($rol === ROL_ADMIN): ?>
            <li class="nav-section-title">ADMINISTRACIÓN</li>
            <li>
                <a href="<?= BASE_URL ?>/index.php?controller=admin&action=dashboard" class="sidebar-link <?= ($currentController === 'admin' && $currentAction === 'dashboard') ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/index.php?controller=admin&action=usuarios" class="sidebar-link <?= ($currentController === 'admin' && $currentAction === 'usuarios') ? 'active' : '' ?>">
                    <i class="bi bi-people-fill"></i>
                    <span>Usuarios</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/index.php?controller=admin&action=profesores" class="sidebar-link <?= ($currentController === 'admin' && $currentAction === 'profesores') ? 'active' : '' ?>">
                    <i class="bi bi-person-video3"></i>
                    <span>Profesores</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/index.php?controller=admin&action=estudiantes" class="sidebar-link <?= ($currentController === 'admin' && $currentAction === 'estudiantes') ? 'active' : '' ?>">
                    <i class="bi bi-backpack4-fill"></i>
                    <span>Estudiantes</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/index.php?controller=admin&action=cursos" class="sidebar-link <?= ($currentController === 'admin' && $currentAction === 'cursos') ? 'active' : '' ?>">
                    <i class="bi bi-journal-bookmark-fill"></i>
                    <span>Cursos</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/index.php?controller=admin&action=inscripciones" class="sidebar-link <?= ($currentController === 'admin' && $currentAction === 'inscripciones') ? 'active' : '' ?>">
                    <i class="bi bi-card-checklist"></i>
                    <span>Inscripciones</span>
                </a>
            </li>

        <?php elseif ($rol === ROL_PROFESOR): ?>
            <li class="nav-section-title">MÓDULO DOCENTE</li>
            <li>
                <a href="<?= BASE_URL ?>/index.php?controller=profesor&action=dashboard" class="sidebar-link <?= ($currentController === 'profesor' && $currentAction === 'dashboard') ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/index.php?controller=profesor&action=cursos" class="sidebar-link <?= ($currentController === 'profesor' && $currentAction === 'cursos') ? 'active' : '' ?>">
                    <i class="bi bi-journals"></i>
                    <span>Mis Cursos</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/index.php?controller=profesor&action=examenes" class="sidebar-link <?= ($currentController === 'profesor' && ($currentAction === 'examenes' || $currentAction === 'editorExamen' || $currentAction === 'calificar')) ? 'active' : '' ?>">
                    <i class="bi bi-file-earmark-check-fill"></i>
                    <span>Gestión de Exámenes</span>
                </a>
            </li>

        <?php elseif ($rol === ROL_ESTUDIANTE): ?>
            <li class="nav-section-title">MÓDULO ALUMNO</li>
            <li>
                <a href="<?= BASE_URL ?>/index.php?controller=estudiante&action=dashboard" class="sidebar-link <?= ($currentController === 'estudiante' && $currentAction === 'dashboard') ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Mi Panel</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/index.php?controller=estudiante&action=misCursos" class="sidebar-link <?= ($currentController === 'estudiante' && $currentAction === 'misCursos') ? 'active' : '' ?>">
                    <i class="bi bi-book-half"></i>
                    <span>Mis Cursos</span>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/index.php?controller=estudiante&action=misResultados" class="sidebar-link <?= ($currentController === 'estudiante' && $currentAction === 'misResultados') ? 'active' : '' ?>">
                    <i class="bi bi-award-fill"></i>
                    <span>Mis Calificaciones</span>
                </a>
            </li>
        <?php endif; ?>

        <li class="nav-section-title mt-4">SESIÓN</li>
        <li>
            <a href="javascript:void(0)" onclick="cerrarSesion()" class="sidebar-link text-danger">
                <i class="bi bi-box-arrow-right"></i>
                <span>Cerrar Sesión</span>
            </a>
        </li>
    </ul>
</aside>
<div class="app-main">
