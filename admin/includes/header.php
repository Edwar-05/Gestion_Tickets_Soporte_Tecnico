<?php
// admin/includes/header.php
// Asegúrate de tener SITE_URL y conectarDB() en includes/config.php

// Definir rutas y cargar configuración
require_once dirname(__DIR__, 2) . '/includes/config.php';

// Verificar si el usuario está logueado y es administrador
if (!estaLogueado() || ($_SESSION['rol'] ?? '') !== 'Administrador') {
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

// Conexión y datos
$pdo = conectarDB();
$usuario_id = $_SESSION['usuario_id'] ?? null;
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title><?php echo isset($titulo_pagina) ? htmlspecialchars($titulo_pagina) . ' - ' : ''; ?><?php echo SITE_NAME; ?> - Admin</title>

    <!-- 1) Bootstrap CSS (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- 2) Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">

    <!-- 3) Tu CSS personalizado — DEBE cargarse DESPUÉS de Bootstrap -->
    <link href="<?php echo rtrim(SITE_URL, '/'); ?>/admin/assets/css/style.css" rel="stylesheet">

    <!-- 4) Pequeñas reglas críticas (si prefieres, muévelas a style.css) -->
    <style>
        :root {
            --mi-primary: #6F0303;
            --mi-primary-dark: #580202;
        }

        /* Forzar color principal en navbar si bootstrap lo aplica distinto */
        .navbar.bg-primary, .bg-primary {
            background-color: var(--mi-primary) !important;
            border-color: var(--mi-primary) !important;
        }
        .navbar .navbar-brand, .navbar .nav-link {
            color: #fff !important;
        }

        /* Sidebar layout base (mejor mover a style.css) */
        .sidebar {
            min-height: calc(100vh - 56px);
            background: #f8f9fa;
            padding: 20px 0;
            box-shadow: 2px 0 5px rgba(0,0,0,.05);
        }
        .sidebar .list-group-item {
            border: none;
            color: #333;
        }
        .sidebar .list-group-item.active {
            background-color: rgba(111,3,3,0.10);
            color: var(--mi-primary);
        }
        .sidebar .list-group-item:hover {
            background-color: #efefef;
            color: var(--mi-primary);
        }

        /* Asegurar que la columna principal no se superponga */
        .main-content {
            padding: 20px;
        }

        /* Responsivo: mostrar sidebar por defecto en md+ */
        @media (min-width: 768px) {
            .sidebar { display: block !important; }
        }
    </style>
</head>
<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo SITE_URL . '/admin/dashboard.php'; ?>">
                <i class="bi bi-shield-lock-fill me-2"></i><?php echo htmlspecialchars(SITE_NAME); ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item me-2 d-none d-lg-block">
                        <!-- ejemplo botón si lo necesitas -->
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle me-1"></i>
                            <?php echo htmlspecialchars($_SESSION['nombre'] ?? 'Administrador'); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/admin/perfil.php"><i class="bi bi-person me-2"></i>Mi Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Salir</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- CONTENEDOR PRINCIPAL -->
    <div class="container-fluid">
        <div class="row">
            <!-- SIDEBAR -->
            <aside class="col-md-2 px-0 sidebar d-none d-md-block">
                <div class="list-group list-group-flush">
                    <a href="<?php echo SITE_URL; ?>/admin/dashboard.php" class="list-group-item list-group-item-action <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                        <i class="bi bi-speedometer2 me-2"></i> Dashboard
                    </a>

                    <a href="<?php echo SITE_URL; ?>/admin/tickets.php" class="list-group-item list-group-item-action <?php echo $current_page == 'tickets.php' ? 'active' : ''; ?>">
                        <i class="bi bi-ticket-detailed me-2"></i> Tickets
                        <span class="badge rounded-pill float-end bg-danger text-white">
                            <?php 
                                try {
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tickets WHERE estado = 'Abierto'");
                                    $stmt->execute();
                                    $row = $stmt->fetch();
                                    echo (int)($row['total'] ?? 0);
                                } catch (Exception $e) {
                                    echo 0;
                                }
                            ?>
                        </span>
                    </a>

                    <a href="<?php echo SITE_URL; ?>/admin/usuarios.php" class="list-group-item list-group-item-action <?php echo $current_page == 'usuarios.php' ? 'active' : ''; ?>">
                        <i class="bi bi-people me-2"></i> Usuarios
                    </a>

                    <a href="<?php echo SITE_URL; ?>/admin/solicitudes_software.php" class="list-group-item list-group-item-action <?php echo $current_page == 'solicitudes_software.php' ? 'active' : ''; ?>">
                        <i class="bi bi-download me-2"></i> Solicitudes Software
                        <span class="badge rounded-pill float-end bg-warning text-white">
                            <?php 
                                try {
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM solicitudes_software WHERE estado = 'pendiente'");
                                    $stmt->execute();
                                    $row = $stmt->fetch();
                                    echo (int)($row['total'] ?? 0);
                                } catch (Exception $e) {
                                    echo 0;
                                }
                            ?>
                        </span>
                    </a>

                    <a href="<?php echo SITE_URL; ?>/admin/configuracion.php" class="list-group-item list-group-item-action <?php echo $current_page == 'configuracion.php' ? 'active' : ''; ?>">
                        <i class="bi bi-gear me-2"></i> Configuración
                    </a>
                </div>
            </aside>

            <!-- MAIN CONTENT START -->
            <main class="col-md-10 ms-sm-auto px-md-4 main-content">
                <!-- Aquí comienza el contenido de cada página -->
