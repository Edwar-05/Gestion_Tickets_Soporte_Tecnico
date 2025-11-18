<?php
$titulo_pagina = "Mis Tickets";
require_once 'includes/header.php';

// Obtener parámetros de búsqueda y filtrado
$busqueda = $_GET['busqueda'] ?? '';
$estado = $_GET['estado'] ?? '';
$categoria = $_GET['categoria'] ?? '';
$orden = $_GET['orden'] ?? 'fecha_desc';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 10;

// Construir la consulta base
$where = "WHERE t.id_usuario = ?";
$params = [$usuario_id];

// Inicializar la consulta base sin el WHERE
$base_query = "SELECT t.*, c.nombre_categoria as categoria 
              FROM tickets t 
              JOIN categorias c ON t.id_categoria = c.id_categoria ";

// Aplicar filtros
if (!empty($busqueda)) {
    $where .= " AND (t.titulo LIKE ? OR t.descripcion LIKE ?)";
    $params[] = "%$busqueda%";
    $params[] = "%$busqueda%";
}

if (!empty($estado) && $estado !== 'todos') {
    $where .= " AND t.estado = ?";
    $params[] = $estado;
}

if (!empty($categoria) && is_numeric($categoria)) {
    $where .= " AND t.id_categoria = ?";
    $params[] = $categoria;
}

// Construir la consulta final con la cláusula WHERE
$query = $base_query . " " . $where;

// Ordenar
switch ($orden) {
    case 'fecha_asc':
        $query .= " ORDER BY t.fecha_creacion ASC";
        break;
    case 'prioridad':
        $query .= " ORDER BY FIELD(t.prioridad, 'Alta', 'Media', 'Baja')";
        break;
    default: // fecha_desc
        $query .= " ORDER BY t.fecha_creacion DESC";
}

// Obtener el total de registros para la paginación
$count_query = "SELECT COUNT(*) as total FROM tickets t " . $where;
$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_registros = $stmt->fetch()['total'];
$total_paginas = ceil($total_registros / $por_pagina);
$offset = ($pagina - 1) * $por_pagina;

// Aplicar paginación
$query .= " LIMIT " . (int)$por_pagina . " OFFSET " . (int)$offset;

// Ejecutar consulta final
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Obtener categorías para el filtro
$categorias = $pdo->query("SELECT * FROM categorias WHERE activa = 1 ORDER BY nombre_categoria")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Mis Tickets</h2>
    <a href="nuevo_ticket.php" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Nuevo Ticket
    </a>
</div>

<!-- Filtros y búsqueda -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-3">
                <label for="busqueda" class="form-label">Buscar</label>
                <input type="text" class="form-control" id="busqueda" name="busqueda" value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Buscar en tickets...">
            </div>
            <div class="col-md-2">
                <label for="estado" class="form-label">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="">Todos</option>
                    <option value="Abierto" <?php echo $estado === 'Abierto' ? 'selected' : ''; ?>>Abierto</option>
                    <option value="En Proceso" <?php echo $estado === 'En Proceso' ? 'selected' : ''; ?>>En Proceso</option>
                    <option value="En Espera" <?php echo $estado === 'En Espera' ? 'selected' : ''; ?>>En Espera</option>
                    <option value="Resuelto" <?php echo $estado === 'Resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                    <option value="Cancelado" <?php echo $estado === 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                    <option value="Cerrado" <?php echo $estado === 'Cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="categoria" class="form-label">Categoría</label>
                <select class="form-select" id="categoria" name="categoria">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id_categoria']; ?>" <?php echo $categoria == $cat['id_categoria'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nombre_categoria']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="orden" class="form-label">Ordenar por</label>
                <select class="form-select" id="orden" name="orden" onchange="this.form.submit()">
                    <option value="fecha_desc" <?php echo $orden === 'fecha_desc' ? 'selected' : ''; ?>>Más recientes</option>
                    <option value="fecha_asc" <?php echo $orden === 'fecha_asc' ? 'selected' : ''; ?>>Más antiguos</option>
                    <option value="prioridad" <?php echo $orden === 'prioridad' ? 'selected' : ''; ?>>Prioridad</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Lista de tickets -->
<div class="card">
    <div class="card-body p-0">
        <?php if (count($tickets) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
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
                        <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td>#<?php echo $ticket['id_ticket']; ?></td>
                                <td>
                                    <a href="ver_ticket.php?id=<?php echo $ticket['id_ticket']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($ticket['titulo']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($ticket['categoria']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $ticket['estado'] === 'Abierto' ? 'warning' : 
                                            ($ticket['estado'] === 'En Proceso' ? 'info' : 
                                            ($ticket['estado'] === 'Resuelto' ? 'success' : 
                                            ($ticket['estado'] === 'Cancelado' ? 'dark' : 'secondary'))); 
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
                                <td>
                                    <a href="ver_ticket.php?id=<?php echo $ticket['id_ticket']; ?>" class="btn btn-sm btn-outline-primary" title="Ver detalles">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if ($ticket['estado'] === 'Abierto' || $ticket['estado'] === 'En Espera'): ?>
                                        <a href="editar_ticket.php?id=<?php echo $ticket['id_ticket']; ?>" class="btn btn-sm btn-outline-secondary" title="Editar">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($ticket['estado'] === 'Cancelado'): ?>
                                        <form method="POST" action="acciones_ticket.php" class="d-inline" onsubmit="return confirm('¿Estás seguro de que deseas eliminar este ticket? Esta acción no se puede deshacer.');">
                                            <input type="hidden" name="id_ticket" value="<?php echo $ticket['id_ticket']; ?>">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <nav class="p-3">
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($pagina > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>">Anterior</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                            <li class="page-item <?php echo $i === $pagina ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i])); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagina < $total_paginas): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>">Siguiente</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3">No se encontraron tickets</h5>
                <p class="text-muted">No hay tickets que coincidan con los filtros seleccionados.</p>
                <a href="nuevo_ticket.php" class="btn btn-primary mt-2">
                    <i class="bi bi-plus-circle"></i> Crear nuevo ticket
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
