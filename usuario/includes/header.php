<?php
// usuario/includes/header.php

// Iniciar buffer si no está iniciado (ayuda con redirecciones)
if (ob_get_level() == 0) {
    ob_start();
}

// Iniciar sesión si no existe
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar configuración (SITE_URL, conectarDB, funciones)
require_once dirname(__DIR__, 2) . '/includes/config.php';

// Si no está logueado, redirigir al index público
if (!estaLogueado()) {
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

// Conexión y datos del usuario
$pdo = conectarDB();
$usuario_id = $_SESSION['usuario_id'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo isset($titulo_pagina) ? htmlspecialchars($titulo_pagina) . ' - ' : ''; ?><?php echo SITE_NAME; ?></title>

    <!-- 1) Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- 2) Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">

    <!-- 3) TU CSS personalizado (DEBE cargarse DESPUÉS para sobrescribir Bootstrap) -->
    <link href="<?php echo rtrim(SITE_URL, '/'); ?>/usuario/assets/css/style.css" rel="stylesheet">

    <!-- 4) Reglas críticas específicas (opcional: mueve luego a style.css) -->
    <style>
        /* Color principal del sistema (usa #6F0303) */
        :root {
            --mi-primary: #6F0303;
            --mi-primary-dark: #580202;
            --mi-primary-rgb: 111, 3, 3;
        }

        /* Forzar barra superior con color principal */
        .navbar.bg-primary,
        .bg-primary {
            background-color: var(--mi-primary) !important;
            border-color: var(--mi-primary) !important;
        }
        .navbar .navbar-brand,
        .navbar .nav-link,
        .navbar .nav-item .nav-link {
            color: #fff !important;
        }

        /* Estilo del botón de inicio/links (ligero) */
        .nav-link.active {
            background-color: rgba(255,255,255,0.06);
            border-radius: 4px;
        }

        /* Focus en inputs (si no está en style.css) */
        .form-control:focus {
            border-color: var(--mi-primary) !important;
            box-shadow: 0 0 0 0.2rem rgba(var(--mi-primary-rgb), 0.12) !important;
        }

        /* Ajustes responsive para el navbar */
        @media (max-width: 991.98px) {
            .navbar .nav-link { padding: 0.5rem 1rem; }
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo SITE_URL; ?>/usuario/dashboard.php">
                <i class="bi bi-ticket-detailed-fill me-2"></i>
                <span><?php echo htmlspecialchars(SITE_NAME); ?></span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" 
                    aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 d-none d-md-flex">
                    <li class="nav-item d-flex align-items-center me-2">
                        <a class="btn btn-outline-light btn-sm" href="<?php echo SITE_URL; ?>" title="Volver al inicio">
                            <i class="bi bi-house-door"></i> Inicio
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/usuario/dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i> Panel
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'tickets.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/usuario/tickets.php">
                            <i class="bi bi-ticket-detailed me-1"></i> Mis Tickets
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'nuevo_ticket.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/usuario/nuevo_ticket.php">
                            <i class="bi bi-plus-circle me-1"></i> Nuevo Ticket
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'mis_solicitudes.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/usuario/mis_solicitudes.php">
                            <i class="bi bi-download me-1"></i> Mis Solicitudes
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'solicitar_software.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/usuario/solicitar_software.php">
                            <i class="bi bi-plus-circle me-1"></i> Solicitar Software
                        </a>
                    </li>
                </ul>

                <!-- Menú para mobile -->
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 d-md-none">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/usuario/dashboard.php">
                            <i class="bi bi-speedometer2 me-1"></i> Panel
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'tickets.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/usuario/tickets.php">
                            <i class="bi bi-ticket-detailed me-1"></i> Mis Tickets
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'nuevo_ticket.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/usuario/nuevo_ticket.php">
                            <i class="bi bi-plus-circle me-1"></i> Nuevo Ticket
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'mis_solicitudes.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/usuario/mis_solicitudes.php">
                            <i class="bi bi-download me-1"></i> Mis Solicitudes
                        </a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link <?php echo $current_page === 'solicitar_software.php' ? 'active' : ''; ?>" href="<?php echo SITE_URL; ?>/usuario/solicitar_software.php">
                            <i class="bi bi-plus-circle me-1"></i> Solicitar Software
                        </a>
                    </li>
                </ul>

                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" 
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i>
                            <span class="d-none d-md-inline"><?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Usuario'); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/usuario/perfil.php"><i class="bi bi-person me-2"></i> Mi Perfil</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/usuario/dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Panel de Control</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Contenedor principal usuario -->
    <div class="container py-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
