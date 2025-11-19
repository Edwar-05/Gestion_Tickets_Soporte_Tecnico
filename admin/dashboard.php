<?php
// admin/dashboard.php
$titulo_pagina = "Dashboard";
require_once 'includes/header.php';

$usuario_id = $_SESSION['user_id'] ?? null;


$stats = [
    'total_usuarios'     => (int)$pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(),
    'total_tickets'      => (int)$pdo->query("SELECT COUNT(*) FROM tickets")->fetchColumn(),
    'tickets_abiertos'   => (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE estado = 'abierto'")->fetchColumn(),
    'tickets_en_proceso' => (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE estado = 'en_proceso'")->fetchColumn(),
    'tickets_en_espera'  => (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE estado = 'en_espera'")->fetchColumn(),
    'tickets_cerrados'   => (int)$pdo->query("SELECT COUNT(*) FROM tickets WHERE estado = 'cerrado'")->fetchColumn(),
];

$sqlCat = "
    SELECT c.nombre_categoria, COUNT(t.id_ticket) AS total 
    FROM categorias c 
    LEFT JOIN tickets t ON c.id_categoria = t.id_categoria 
    GROUP BY c.id_categoria, c.nombre_categoria
    ORDER BY total DESC
    LIMIT 5
";
$categorias_tickets = $pdo->query($sqlCat)->fetchAll(PDO::FETCH_ASSOC);


$sqlUlt = "
    SELECT 
        t.id_ticket as id,
        t.titulo,
        t.estado,
        t.fecha_creacion,
        u.nombre as usuario,
        c.nombre_categoria as categoria
    FROM tickets t
    JOIN usuarios u ON t.id_usuario = u.id_usuario
    JOIN categorias c ON t.id_categoria = c.id_categoria
    ORDER BY t.fecha_creacion DESC
    LIMIT 5
";
$ultimos_tickets = $pdo->query($sqlUlt)->fetchAll(PDO::FETCH_ASSOC);

// =====================================
//  TICKETS ASIGNADOS AL ADMIN ACTUAL (TOP 5)
// =====================================
$tickets_asignados = [];
if ($usuario_id) {
    $sqlAsig = "
        SELECT 
            t.id_ticket as id,
            t.titulo,
            t.estado,
            t.prioridad,
            t.fecha_creacion,
            u.nombre as usuario,
            c.nombre_categoria as categoria
        FROM tickets t
        JOIN usuarios u ON t.id_usuario = u.id_usuario
        JOIN categorias c ON t.id_categoria = c.id_categoria
        WHERE t.asignado_a = :uid
          AND t.estado NOT IN ('Resuelto', 'Cerrado')
        ORDER BY 
            CASE 
                WHEN t.prioridad = 'Alta' THEN 1
                WHEN t.prioridad = 'Media' THEN 2
                WHEN t.prioridad = 'Baja' THEN 3
                ELSE 4
            END,
            t.fecha_creacion ASC
        LIMIT 5
    ";
    $stmtAsig = $pdo->prepare($sqlAsig);
    $stmtAsig->execute([':uid' => (int)$usuario_id]);
    $tickets_asignados = $stmtAsig->fetchAll(PDO::FETCH_ASSOC);
}

// Helper para badge de estado
function badgeEstado(string $estado): string {
    // estados válidos: abierto, en_proceso, en_espera, cerrado
    switch ($estado) {
        case 'abierto':     return 'warning';
        case 'en_proceso':  return 'info';
        case 'en_espera':   return 'secondary';
        case 'cerrado':     return 'success';
        default:            return 'light';
    }
}

// Helper para badge de prioridad (baja, media, alta)
function badgePrioridad(?string $prioridad): string {
    if ($prioridad === null) return 'light';
    switch ($prioridad) {
        case 'alta':  return 'danger';
        case 'media': return 'primary';
        case 'baja':  return 'secondary';
        default:      return 'light';
    }
}
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Panel de Control</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="tickets.php" class="btn btn-sm btn-outline-secondary">Ver tickets</a>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">Imprimir</button>
        </div>
        
    </div>
</div>

<div class="container-fluid px-4">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <style>
        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            overflow: hidden;
            position: relative;
            color: white;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        .stat-card .card-body {
            position: relative;
            z-index: 1;
            padding: 1.5rem;
        }
        .stat-card .card-title {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            opacity: 0.9;
        }
        .stat-card .card-value {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        .stat-card .card-text {
            font-size: 0.85rem;
            opacity: 0.9;
            margin-bottom: 0;
        }
        .stat-card i {
            font-size: 2.5rem;
            opacity: 0.2;
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            transition: all 0.3s ease;
        }
        .stat-card:hover i {
            transform: translateY(-50%) scale(1.1);
            opacity: 0.3;
        }
        .bg-users { background: linear-gradient(135deg, #4e73df 0%, #224abe 100%); }
        .bg-total { background: linear-gradient(135deg, #36b9cc 0%, #258391 100%); }
        .bg-open { background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%); }
        .bg-process { background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%); }
        .bg-waiting { background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%); }
        .bg-closed { background: linear-gradient(135deg, #e74a3b 0%, #be2617 100%); }
    </style>

    <div class="row g-4 mb-4">
        <!-- Usuarios -->
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="stat-card bg-users">
                <div class="card-body">
                    <h6 class="card-title">Usuarios</h6>
                    <h2 class="card-value"><?php echo $stats['total_usuarios']; ?></h2>
                    <p class="card-text">Registrados en el sistema</p>
                    <i class="bi bi-people"></i>
                </div>
            </div>
        </div>

        <!-- Total Tickets -->
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="stat-card bg-total">
                <div class="card-body">
                    <h6 class="card-title">Total Tickets</h6>
                    <h2 class="card-value"><?php echo $stats['total_tickets']; ?></h2>
                    <p class="card-text">Solicitudes totales</p>
                    <i class="bi bi-ticket-detailed"></i>
                </div>
            </div>
        </div>

        <!-- Tickets Abiertos -->
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="stat-card bg-open">
                <div class="card-body">
                    <h6 class="card-title">Abiertos</h6>
                    <h2 class="card-value"><?php echo $stats['tickets_abiertos']; ?></h2>
                    <p class="card-text">Pendientes de revisión</p>
                    <i class="bi bi-exclamation-circle"></i>
                </div>
            </div>
        </div>

        <!-- En Proceso -->
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="stat-card bg-process">
                <div class="card-body">
                    <h6 class="card-title">En proceso</h6>
                    <h2 class="card-value"><?php echo $stats['tickets_en_proceso']; ?></h2>
                    <p class="card-text">En atención</p>
                    <i class="bi bi-arrow-repeat"></i>
                </div>
            </div>
        </div>

        <!-- En Espera -->
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="stat-card bg-waiting">
                <div class="card-body">
                    <h6 class="card-title">En espera</h6>
                    <h2 class="card-value"><?php echo $stats['tickets_en_espera']; ?></h2>
                    <p class="card-text">A la espera de respuesta</p>
                    <i class="bi bi-hourglass-split"></i>
                </div>
            </div>
        </div>

        <!-- Cerrados -->
        <div class="col-12 col-sm-6 col-xl-2">
            <div class="stat-card bg-closed">
                <div class="card-body">
                    <h6 class="card-title">Cerrados</h6>
                    <h2 class="card-value"><?php echo $stats['tickets_cerrados']; ?></h2>
                    <p class="card-text">Solicitudes finalizadas</p>
                    <i class="bi bi-check-circle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimos tickets -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Últimos Tickets</h5>
            <a href="tickets.php" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-list-ul me-1"></i> Ver todos
            </a>
        </div>
        <div class="card-body">
            <?php if (count($ultimos_tickets) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Título</th>
                                <th>Fecha</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ultimos_tickets as $ticket): ?>
                                <tr>
                                    <td>#<?php echo $ticket['id']; ?></td>
                                    <td><?php echo htmlspecialchars($ticket['titulo']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($ticket['fecha_creacion'])); ?></td>
                                    <td>
                                        <a href="ver_ticket.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            Ver
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info mb-0">No hay tickets registrados.</div>
            <?php endif; ?>
        </div>
    </div>
</div>

</div>

<?php require_once 'includes/footer.php'; ?>
