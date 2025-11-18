<?php
$titulo_pagina = "Historial de Tickets";
require_once 'includes/header.php';

// Configuración de paginación
$por_pagina = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina > 1) ? ($pagina * $por_pagina - $por_pagina) : 0;

// Filtros
$estado = isset($_GET['estado']) ? $_GET['estado'] : '';
$orden = isset($_GET['orden']) ? $_GET['orden'] : 'fecha_desc';
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

// Construir la consulta base
$where = "WHERE t.id_usuario = ?";
$params = [$usuario_id];

// Aplicar filtro de estado
if (!empty($estado) && $estado !== 'todos') {
    $where .= " AND t.estado = ?";
    $params[] = $estado;
}

// Aplicar búsqueda
if (!empty($busqueda)) {
    $where .= " AND (t.titulo LIKE ? OR t.descripcion LIKE ? OR c.nombre_categoria LIKE ?)";
    $busqueda_param = "%$busqueda%";
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
    $params[] = $busqueda_param;
}

// Ordenar resultados
$orden_sql = "ORDER BY ";
switch ($orden) {
    case 'fecha_asc':
        $orden_sql .= "t.fecha_creacion ASC";
        break;
    case 'titulo_asc':
        $orden_sql .= "t.titulo ASC";
        break;
    case 'titulo_desc':
        $orden_sql .= "t.titulo DESC";
        break;
    case 'fecha_desc':
    default:
        $orden_sql .= "t.fecha_creacion DESC";
        break;
}

// Obtener el total de tickets para la paginación
$sql_total = "SELECT COUNT(*) as total FROM tickets t 
              LEFT JOIN categorias c ON t.id_categoria = c.id_categoria 
              $where";
$stmt = $pdo->prepare($sql_total);
$stmt->execute($params);
$total_tickets = $stmt->fetch()['total'];
$total_paginas = ceil($total_tickets / $por_pagina);

// Obtener los tickets con paginación
$sql = "SELECT t.*, c.nombre_categoria, 
        (SELECT COUNT(*) FROM respuestas r WHERE r.id_ticket = t.id_ticket) as total_respuestas
        FROM tickets t 
        LEFT JOIN categorias c ON t.id_categoria = c.id_categoria 
        $where 
        $orden_sql 
        LIMIT $inicio, $por_pagina";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tickets = $stmt->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-clock-history me-2"></i>Historial de Tickets</h2>
    <a href="nuevo_ticket.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Nuevo Ticket
    </a>
</div>

<!-- Filtros y búsqueda -->
<div class="card mb-4">
    <div class="card-body">
        <form action="" method="get" class="row g-3">
            <div class="col-md-4">
                <label for="estado" class="form-label">Filtrar por estado:</label>
                <select name="estado" id="estado" class="form-select">
                    <option value="todos" <?php echo $estado === 'todos' ? 'selected' : ''; ?>>Todos los estados</option>
                    <option value="Abierto" <?php echo $estado === 'Abierto' ? 'selected' : ''; ?>>Abierto</option>
                    <option value="En Proceso" <?php echo $estado === 'En Proceso' ? 'selected' : ''; ?>>En Proceso</option>
                    <option value="Resuelto" <?php echo $estado === 'Resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                    <option value="Cerrado" <?php echo $estado === 'Cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="orden" class="form-label">Ordenar por:</label>
                <select name="orden" id="orden" class="form-select">
                    <option value="fecha_desc" <?php echo $orden === 'fecha_desc' ? 'selected' : ''; ?>>Más recientes primero</option>
                    <option value="fecha_asc" <?php echo $orden === 'fecha_asc' ? 'selected' : ''; ?>>Más antiguos primero</option>
                    <option value="titulo_asc" <?php echo $orden === 'titulo_asc' ? 'selected' : ''; ?>>Título (A-Z)</option>
                    <option value="titulo_desc" <?php echo $orden === 'titulo_desc' ? 'selected' : ''; ?>>Título (Z-A)</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="busqueda" class="form-label">Buscar:</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="busqueda" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Buscar en tickets...">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Lista de tickets -->
<?php if (count($tickets) > 0): ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Categoría</th>
                    <th>Estado</th>
                    <th>Prioridad</th>
                    <th>Fecha</th>
                    <th>Respuestas</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tickets as $ticket): 
                    // Determinar clase de estado
                    $clase_estado = '';
                    switch ($ticket['estado']) {
                        case 'Abierto':
                            $clase_estado = 'bg-warning text-dark';
                            break;
                        case 'En Proceso':
                            $clase_estado = 'bg-info text-white';
                            break;
                        case 'Resuelto':
                            $clase_estado = 'bg-success text-white';
                            break;
                        case 'Cerrado':
                            $clase_estado = 'bg-secondary text-white';
                            break;
                    }
                    
                    // Determinar clase de prioridad
                    $clase_prioridad = '';
                    switch ($ticket['prioridad']) {
                        case 'Alta':
                            $clase_prioridad = 'text-danger fw-bold';
                            break;
                        case 'Media':
                            $clase_prioridad = 'text-warning';
                            break;
                        case 'Baja':
                            $clase_prioridad = 'text-success';
                            break;
                    }
                    ?>
                    <tr>
                        <td>#<?php echo $ticket['id_ticket']; ?></td>
                        <td>
                            <a href="ver_ticket.php?id=<?php echo $ticket['id_ticket']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($ticket['titulo']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($ticket['nombre_categoria']); ?></td>
                        <td>
                            <span class="badge rounded-pill <?php echo $clase_estado; ?>">
                                <?php echo $ticket['estado']; ?>
                            </span>
                        </td>
                        <td class="<?php echo $clase_prioridad; ?>">
                            <i class="bi bi-flag-fill"></i> <?php echo $ticket['prioridad']; ?>
                        </td>
                        <td>
                            <span class="d-none d-md-inline">
                                <?php echo date('d/m/Y', strtotime($ticket['fecha_creacion'])); ?>
                            </span>
                            <span class="d-inline d-md-none">
                                <?php echo date('d/m/y', strtotime($ticket['fecha_creacion'])); ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-primary rounded-pill">
                                <?php echo $ticket['total_respuestas']; ?>
                            </span>
                        </td>
                        <td>
                            <a href="ver_ticket.php?id=<?php echo $ticket['id_ticket']; ?>" 
                               class="btn btn-sm btn-outline-primary" 
                               title="Ver detalles">
                                <i class="bi bi-eye"></i>
                                <span class="d-none d-md-inline">Ver</span>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <?php if ($total_paginas > 1): ?>
        <nav aria-label="Navegación de tickets">
            <ul class="pagination justify-content-center">
                <?php if ($pagina > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>" aria-label="Anterior">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                    <li class="page-item <?php echo $i === $pagina ? 'active' : ''; ?>">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($pagina < $total_paginas): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>" aria-label="Siguiente">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    <?php endif; ?>

<?php else: ?>
    <div class="text-center py-5">
        <div class="mb-4">
            <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
        </div>
        <h4>No se encontraron tickets</h4>
        <p class="text-muted">
            <?php if (!empty($estado) && $estado !== 'todos'): ?>
                No hay tickets con el estado "<?php echo htmlspecialchars($estado); ?>".
            <?php elseif (!empty($busqueda)): ?>
                No se encontraron resultados para "<?php echo htmlspecialchars($busqueda); ?>".
            <?php else: ?>
                Aún no has creado ningún ticket.
            <?php endif; ?>
        </p>
        <a href="nuevo_ticket.php" class="btn btn-primary mt-3">
            <i class="bi bi-plus-circle"></i> Crear mi primer ticket
        </a>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
