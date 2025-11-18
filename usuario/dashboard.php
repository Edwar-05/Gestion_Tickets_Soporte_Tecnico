<?php
require_once '../includes/config.php';

// Verificar si el usuario está logueado
if (!estaLogueado()) {
    header('Location: ../index.php');
    exit();
}

$pdo = conectarDB();
$usuario_id = $_SESSION['usuario_id'];

// Obtener estadísticas del usuario
$stats = [
    'total_tickets' => $pdo->query("SELECT COUNT(*) FROM tickets WHERE id_usuario = $usuario_id")->fetchColumn(),
    'tickets_abiertos' => $pdo->query("SELECT COUNT(*) FROM tickets WHERE id_usuario = $usuario_id AND estado = 'Abierto'")->fetchColumn(),
    'tickets_en_proceso' => $pdo->query("SELECT COUNT(*) FROM tickets WHERE id_usuario = $usuario_id AND estado = 'En Proceso'")->fetchColumn(),
    'tickets_cerrados' => $pdo->query("SELECT COUNT(*) FROM tickets WHERE id_usuario = $usuario_id AND estado = 'Cerrado'")->fetchColumn(),
];

// Obtener últimos tickets
$stmt = $pdo->prepare("SELECT t.*, c.nombre_categoria as categoria 
                      FROM tickets t 
                      JOIN categorias c ON t.id_categoria = c.id_categoria 
                      WHERE t.id_usuario = ? 
                      ORDER BY t.fecha_creacion DESC LIMIT 5");
$stmt->execute([$usuario_id]);
$ultimos_tickets = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Panel - <?php echo SITE_NAME; ?></title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- CSS Personalizado (ruta correcta) -->
    <link href="<?php echo rtrim(SITE_URL, '/'); ?>/usuario/assets/css/style.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">

    <!-- Overrides -->
    <style>
        :root {
            --mi-primary: #6F0303;
            --mi-primary-dark: #580202;
            --mi-primary-rgb: 111, 3, 3;
        }

        /* Navbar del usuario */
        .navbar.bg-primary,
        .bg-primary {
            background-color: var(--mi-primary) !important;
            border-color: var(--mi-primary) !important;
        }

        .navbar .nav-link,
        .navbar .navbar-brand {
            color: white !important;
        }

        .nav-link.active {
            background-color: rgba(255,255,255,0.2) !important;
            border-radius: 4px;
        }

        /* Reemplazar azul por tu color */
        .text-primary {
            color: var(--mi-primary) !important;
        }

        .btn-primary {
            background-color: var(--mi-primary) !important;
            border-color: var(--mi-primary) !important;
        }

        .btn-primary:hover {
            background-color: var(--mi-primary-dark) !important;
        }

        .form-control:focus {
            border-color: var(--mi-primary) !important;
            box-shadow: 0 0 0 0.2rem rgba(var(--mi-primary-rgb), .25) !important;
        }
    </style>
</head>

<body>
    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php"><?php echo SITE_NAME; ?></a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">

                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="dashboard.php"><i class="bi bi-speedometer2"></i> Mi Panel</a></li>
                    <li class="nav-item"><a class="nav-link" href="tickets.php"><i class="bi bi-ticket"></i> Mis Tickets</a></li>
                    <li class="nav-item"><a class="nav-link" href="nuevo_ticket.php"><i class="bi bi-plus-circle"></i> Nuevo Ticket</a></li>
                </ul>

                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?php echo $_SESSION['nombre']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="perfil.php"><i class="bi bi-person"></i> Mi Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Cerrar Sesión</a></li>
                        </ul>
                    </li>
                </ul>

            </div>

        </div>
    </nav>

    <div class="container">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <h2 class="mb-4">Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?></h2>

        <!-- ESTADÍSTICAS -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card dashboard-card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Total de Tickets</h5>
                        <h2 class="text-primary"><?php echo $stats['total_tickets']; ?></h2>
                        <p>Tus tickets en total</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card dashboard-card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Abiertos</h5>
                        <h2 class="text-primary"><?php echo $stats['tickets_abiertos']; ?></h2>
                        <p>Tickets pendientes</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card dashboard-card h-100">
                    <div class="card-body">
                        <h5 class="card-title">En Proceso</h5>
                        <h2 class="text-primary"><?php echo $stats['tickets_en_proceso']; ?></h2>
                        <p>Tickets en revisión</p>
                    </div>
                </div>
            </div>

            <div class="col-md-3 mb-3">
                <div class="card dashboard-card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Cerrados</h5>
                        <h2 class="text-primary"><?php echo $stats['tickets_cerrados']; ?></h2>
                        <p>Tickets resueltos</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ÚLTIMOS TICKETS -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between">
                <h5 class="mb-0">Mis Últimos Tickets</h5>
                <a href="tickets.php" class="btn btn-sm btn-primary">Ver todos</a>
            </div>

            <div class="card-body">
                <?php if ($ultimos_tickets): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Título</th>
                                    <th>Categoría</th>
                                    <th>Estado</th>
                                    <th>Prioridad</th>
                                    <th>Fecha</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ultimos_tickets as $ticket): ?>
                                    <tr>
                                        <td>#<?php echo $ticket['id_ticket']; ?></td>
                                        <td><?php echo htmlspecialchars($ticket['titulo']); ?></td>
                                        <td><?php echo htmlspecialchars($ticket['categoria']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $ticket['estado'] === 'Abierto' ? 'warning' :
                                                     ($ticket['estado'] === 'En Proceso' ? 'info' :
                                                      ($ticket['estado'] === 'Resuelto' ? 'success' : 'secondary'));
                                            ?>">
                                                <?php echo $ticket['estado']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $ticket['prioridad'] === 'Alta' ? 'danger' :
                                                     ($ticket['prioridad'] === 'Media' ? 'warning' : 'success');
                                            ?>">
                                                <?php echo $ticket['prioridad']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($ticket['fecha_creacion'])); ?></td>
                                        <td><a href="ver_ticket.php?id=<?php echo $ticket['id_ticket']; ?>" class="btn btn-sm btn-primary"><i class="bi bi-eye"></i> Ver</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No tienes tickets aún.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <footer class="footer bg-light mt-4 py-3">
        <div class="container text-center">
            <?php echo SITE_NAME; ?> &copy; <?php echo date('Y'); ?>
        </div>
    </footer>

    <!-- JS Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
