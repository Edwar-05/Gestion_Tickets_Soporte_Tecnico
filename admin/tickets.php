<?php
$titulo_pagina = "Gestión de Tickets";
require_once 'includes/header.php';

// Obtener parámetros de búsqueda y filtrado
$busqueda = $_GET['busqueda'] ?? '';
$estado = $_GET['estado'] ?? '';
$prioridad = $_GET['prioridad'] ?? '';
$categoria = $_GET['categoria'] ?? '';
$asignado = $_GET['asignado'] ?? '';
$orden = $_GET['orden'] ?? 'fecha_desc';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 15;

// Construir la consulta base
$where = "WHERE 1=1";
$params = [];

// Aplicar filtros
if (!empty($busqueda)) {
    $where .= " AND (t.titulo LIKE ? OR t.descripcion LIKE ? OR u.nombre LIKE ? OR u.email LIKE ?)";
    $busqueda_param = "%$busqueda%";
    $params = array_merge($params, [$busqueda_param, $busqueda_param, $busqueda_param, $busqueda_param]);
}

if (!empty($estado) && $estado !== 'todos') {
    $where .= " AND t.estado = ?";
    $params[] = $estado;
}

if (!empty($prioridad) && $prioridad !== 'todas') {
    $where .= " AND t.prioridad = ?";
    $params[] = $prioridad;
}

if (!empty($categoria) && is_numeric($categoria)) {
    $where .= " AND t.id_categoria = ?";
    $params[] = $categoria;
}

if (!empty($asignado) && $asignado !== 'sin_asignar') {
    if ($asignado === 'yo') {
        $where .= " AND t.asignado_a = ?";
        $params[] = $usuario_id;
    } else if (is_numeric($asignado)) {
        $where .= " AND t.asignado_a = ?";
        $params[] = $asignado;
    }
} else if ($asignado === 'sin_asignar') {
    $where .= " AND (t.asignado_a IS NULL OR t.asignado_a = 0)";
}

// Ordenar
switch ($orden) {
    case 'fecha_asc':
        $orden_sql = "t.fecha_creacion ASC";
        break;
    case 'prioridad':
        $orden_sql = "FIELD(t.prioridad, 'Alta', 'Media', 'Baja'), t.fecha_creacion DESC";
        break;
    case 'antiguedad':
        $orden_sql = "t.fecha_creacion ASC";
        break;
    default: // fecha_desc
        $orden_sql = "t.fecha_creacion DESC";
        break;
}

// Obtener el total de registros para la paginación
$count_query = "SELECT COUNT(*) as total 
                FROM tickets t 
                JOIN usuarios u ON t.id_usuario = u.id_usuario 
                JOIN categorias c ON t.id_categoria = c.id_categoria 
                $where";

$stmt = $pdo->prepare($count_query);
$stmt->execute($params);
$total_registros = $stmt->fetch()['total'];
$total_paginas = ceil($total_registros / $por_pagina);
$offset = ($pagina - 1) * $por_pagina;

// Consulta principal con paginación
$query = "SELECT t.*, u.nombre as usuario, u.email, c.nombre_categoria as categoria,
          DATEDIFF(NOW(), t.fecha_creacion) as dias_abierto
          FROM tickets t 
          JOIN usuarios u ON t.id_usuario = u.id_usuario 
          JOIN categorias c ON t.id_categoria = c.id_categoria
          $where 
          ORDER BY $orden_sql 
          LIMIT $por_pagina OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

// Obtener categorías para el filtro
$categorias = $pdo->query("SELECT * FROM categorias WHERE activa = 1 ORDER BY nombre_categoria")->fetchAll();

// No se necesita la lista de administradores ya que no hay asignación de tickets
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestión de Tickets</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-funnel"></i> Filtros
    </div>
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-3">
                <label for="busqueda" class="form-label">Buscar</label>
                <input type="text" class="form-control" id="busqueda" name="busqueda" 
                       value="<?php echo htmlspecialchars($busqueda); ?>" placeholder="Buscar en tickets...">
            </div>
            <div class="col-md-2">
                <label for="estado" class="form-label">Estado</label>
                <select class="form-select" id="estado" name="estado">
                    <option value="">Todos los estados</option>
                    <option value="Abierto" <?php echo $estado === 'Abierto' ? 'selected' : ''; ?>>Abierto</option>
                    <option value="En Proceso" <?php echo $estado === 'En Proceso' ? 'selected' : ''; ?>>En Proceso</option>
                    <option value="En Espera" <?php echo $estado === 'En Espera' ? 'selected' : ''; ?>>En Espera</option>
                    <option value="Resuelto" <?php echo $estado === 'Resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                    <option value="Cerrado" <?php echo $estado === 'Cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="prioridad" class="form-label">Prioridad</label>
                <select class="form-select" id="prioridad" name="prioridad">
                    <option value="">Todas</option>
                    <option value="Alta" <?php echo $prioridad === 'Alta' ? 'selected' : ''; ?>>Alta</option>
                    <option value="Media" <?php echo $prioridad === 'Media' ? 'selected' : ''; ?>>Media</option>
                    <option value="Baja" <?php echo $prioridad === 'Baja' ? 'selected' : ''; ?>>Baja</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="categoria" class="form-label">Categoría</label>
                <select class="form-select" id="categoria" name="categoria">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id_categoria']; ?>" 
                                <?php echo $categoria == $cat['id_categoria'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['nombre_categoria']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="asignado" class="form-label">Asignado a</label>
                <select class="form-select" id="asignado" name="asignado">
                    <option value="">Todos los tickets</option>
                    <option value="sin_asignar" <?php echo $asignado === 'sin_asignar' ? 'selected' : ''; ?>>Sin asignar</option>
                    <option value="yo" <?php echo $asignado === 'yo' ? 'selected' : ''; ?>>Asignados a mí</option>
                    <?php if (!empty($administradores)): ?>
                        <optgroup label="Administradores">
                            <?php foreach ($administradores as $admin): ?>
                                <option value="<?php echo $admin['id_usuario']; ?>" 
                                        <?php echo $asignado == $admin['id_usuario'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($admin['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="orden" class="form-label">Ordenar por</label>
                <select class="form-select" id="orden" name="orden">
                    <option value="fecha_desc" <?php echo $orden === 'fecha_desc' ? 'selected' : ''; ?>>Más recientes</option>
                    <option value="fecha_asc" <?php echo $orden === 'fecha_asc' ? 'selected' : ''; ?>>Más antiguos</option>
                    <option value="prioridad" <?php echo $orden === 'prioridad' ? 'selected' : ''; ?>>Prioridad</option>
                    <option value="antiguedad" <?php echo $orden === 'antiguedad' ? 'selected' : ''; ?>>Antigüedad</option>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-funnel"></i> Aplicar Filtros
                </button>
                <a href="tickets.php" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle"></i> Limpiar Filtros
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Resumen de tickets -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <h5 class="card-title">Abiertos</h5>
                <h2 class="mb-0"><?php echo $pdo->query("SELECT COUNT(*) FROM tickets WHERE estado = 'Abierto'")->fetchColumn(); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <h5 class="card-title">En Proceso</h5>
                <h2 class="mb-0"><?php echo $pdo->query("SELECT COUNT(*) FROM tickets WHERE estado = 'En Proceso'")->fetchColumn(); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Resueltos</h5>
                <h2 class="mb-0"><?php echo $pdo->query("SELECT COUNT(*) FROM tickets WHERE estado = 'Resuelto'")->fetchColumn(); ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-secondary text-white">
            <div class="card-body">
                <h5 class="card-title">Cerrados</h5>
                <h2 class="mb-0"><?php echo $pdo->query("SELECT COUNT(*) FROM tickets WHERE estado = 'Cerrado'")->fetchColumn(); ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Lista de tickets -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Tickets (<?php echo $total_registros; ?>)</h5>
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-download"></i> Exportar
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="exportar_tickets.php?<?php echo http_build_query(array_merge($_GET, ['tipo' => 'pdf'])); ?>"><i class="bi bi-file-earmark-pdf me-2"></i>Exportar a PDF</a></li>
                <li><a class="dropdown-item" href="exportar_tickets.php?<?php echo http_build_query(array_merge($_GET, ['tipo' => 'excel'])); ?>"><i class="bi bi-file-earmark-excel me-2"></i>Exportar a Excel</a></li>
            </ul>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (count($tickets) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Solicitante</th>
                            <th>Categoría</th>
                            <th>Estado</th>
                            <th>Prioridad</th>
                            <th>Días</th>
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
                                default:
                                    $clase_estado = 'bg-light text-dark';
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
                            
                            // Determinar clase para días abierto
                            $clase_dias = '';
                            if ($ticket['dias_abierto'] > 7) {
                                $clase_dias = 'text-danger fw-bold';
                            } elseif ($ticket['dias_abierto'] > 3) {
                                $clase_dias = 'text-warning';
                            }
                            ?>
                            <tr>
                                <td>#<?php echo $ticket['id_ticket']; ?></td>
                                <td>
                                    <a href="ver_ticket.php?id=<?php echo $ticket['id_ticket']; ?>" class="text-decoration-none">
                                        <?php echo htmlspecialchars($ticket['titulo']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($ticket['usuario']); ?></td>
                                <td><?php echo htmlspecialchars($ticket['categoria']); ?></td>
                                <td>
                                    <span class="badge rounded-pill <?php echo $clase_estado; ?>">
                                        <?php echo $ticket['estado']; ?>
                                    </span>
                                </td>
                                <td class="<?php echo $clase_prioridad; ?>">
                                    <i class="bi bi-flag-fill"></i> <?php echo $ticket['prioridad']; ?>
                                </td>
                                <td class="<?php echo $clase_dias; ?>">
                                    <?php echo $ticket['dias_abierto']; ?>d
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Acciones del ticket">
                                        <!-- Botón Ver -->
                                        <a href="ver_ticket.php?id=<?php echo $ticket['id_ticket']; ?>" 
                                           class="btn btn-outline-primary btn-sm" 
                                           data-bs-toggle="tooltip" 
                                           data-bs-placement="top" 
                                           title="Ver detalles">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        
                                        <?php if ($ticket['estado'] !== 'Cerrado' && $ticket['estado'] !== 'Resuelto'): ?>
                                            <!-- Botón Marcar como Resuelto -->
                                            <button type="button" 
                                                    class="btn btn-outline-success btn-sm ms-1" 
                                                    onclick="if(confirm('¿Está seguro de marcar este ticket como resuelto?')) { cambiarEstado(<?php echo $ticket['id_ticket']; ?>, 'Resuelto'); }" 
                                                    data-bs-toggle="tooltip" 
                                                    data-bs-placement="top" 
                                                    title="Marcar como resuelto">
                                                <i class="bi bi-check-circle"></i> Resolver
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($ticket['estado'] === 'Resuelto'): ?>
                                            <!-- Botón Marcar como Cerrado -->
                                            <button type="button" 
                                                    class="btn btn-outline-dark" 
                                                    onclick="cambiarEstado(<?php echo $ticket['id_ticket']; ?>, 'Cerrado')" 
                                                    data-bs-toggle="tooltip" 
                                                    data-bs-placement="top" 
                                                    title="Cerrar ticket">
                                                <i class="bi bi-lock"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
                <nav class="px-3 py-2">
                    <ul class="pagination pagination-sm justify-content-end mb-0">
                        <?php if ($pagina > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])); ?>">
                                    &laquo; Anterior
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
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])); ?>">
                                    Siguiente &raquo;
                                </a>
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
                <a href="tickets.php" class="btn btn-primary mt-2">
                    <i class="bi bi-arrow-counterclockwise"></i> Restablecer filtros
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Inicializar tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});

function cambiarEstado(ticketId, estado) {
    if (confirm('¿Está seguro de que desea marcar este ticket como ' + estado.toLowerCase() + '?')) {
        // Crear un formulario para enviar la solicitud
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'actualizar_estado.php';
        
        // Añadir el ID del ticket
        const ticketIdInput = document.createElement('input');
        ticketIdInput.type = 'hidden';
        ticketIdInput.name = 'id_ticket';
        ticketIdInput.value = ticketId;
        form.appendChild(ticketIdInput);
        
        // Añadir el nuevo estado
        const estadoInput = document.createElement('input');
        estadoInput.type = 'hidden';
        estadoInput.name = 'nuevo_estado';
        estadoInput.value = estado;
        form.appendChild(estadoInput);
        inputEstado.name = 'estado';
        inputEstado.value = estado;
        
        form.appendChild(inputId);
        form.appendChild(inputEstado);
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
