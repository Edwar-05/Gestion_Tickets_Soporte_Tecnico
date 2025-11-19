<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar que el usuario sea administrador
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'Administrador') {
    die('Acceso no autorizado');
}

// Incluir configuración
require_once __DIR__ . '/../includes/config.php';

// Conectar a la base de datos
$pdo = conectarDB();

try {
    // Obtener parámetros de filtrado de la URL
    $busqueda = $_GET['busqueda'] ?? '';
    $estado = $_GET['estado'] ?? '';
    $prioridad = $_GET['prioridad'] ?? '';
    $categoria = $_GET['categoria'] ?? '';
    $orden = $_GET['orden'] ?? 'fecha_desc';

    // Construir la consulta base
    $where = "WHERE 1=1";
    $params = [];

    // Aplicar filtros (mismos que en tickets.php)
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

    // Ordenar
    switch ($orden) {
        case 'fecha_asc': $orden_sql = "t.fecha_creacion ASC"; break;
        case 'prioridad': $orden_sql = "FIELD(t.prioridad, 'Alta', 'Media', 'Baja'), t.fecha_creacion DESC"; break;
        case 'antiguedad': $orden_sql = "t.fecha_creacion ASC"; break;
        default: $orden_sql = "t.fecha_creacion DESC";
    }

    // Consulta para obtener los tickets
    $query = "SELECT t.*, u.nombre as usuario, u.email, c.nombre_categoria as categoria,
              DATE_FORMAT(t.fecha_creacion, '%d/%m/%Y %H:%i') as fecha_formateada
              FROM tickets t 
              JOIN usuarios u ON t.id_usuario = u.id_usuario 
              JOIN categorias c ON t.id_categoria = c.id_categoria
              $where 
              ORDER BY $orden_sql";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();

    // Determinar el tipo de exportación (PDF o Excel)
    $tipo = isset($_GET['tipo']) && in_array(strtolower($_GET['tipo']), ['pdf', 'excel']) 
            ? strtolower($_GET['tipo']) 
            : 'pdf';

    if ($tipo === 'pdf') {
        // Usar una solución simple para PDF
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename=tickets_' . date('Y-m-d') . '.html');
        
        // Estilos CSS para el PDF
        echo '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Reporte de Tickets</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; }
                h1 { color: #333; text-align: center; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th { background-color: #f2f2f2; text-align: left; padding: 8px; border: 1px solid #ddd; }
                td { padding: 8px; border: 1px solid #ddd; }
                .text-center { text-align: center; }
                .badge { padding: 3px 6px; border-radius: 3px; color: white; font-size: 11px; }
                .bg-primary { background-color: #4e73df; }
                .bg-success { background-color: #1cc88a; }
                .bg-warning { background-color: #f6c23e; }
                .bg-danger { background-color: #e74a3b; }
                .bg-secondary { background-color: #858796; }
            </style>
        </head>
        <body>
            <h1>Reporte de Tickets</h1>
            <p><strong>Fecha:</strong> ' . date('d/m/Y H:i:s') . '</p>
            <p><strong>Total de tickets:</strong> ' . count($tickets) . '</p>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Título</th>
                        <th>Usuario</th>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th>Prioridad</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($tickets as $ticket) {
            // Determinar clase de estado
            $clase_estado = '';
            switch ($ticket['estado']) {
                case 'Abierto': $clase_estado = 'bg-primary'; break;
                case 'En progreso': $clase_estado = 'bg-warning'; break;
                case 'Resuelto': $clase_estado = 'bg-success'; break;
                case 'Cerrado': $clase_estado = 'bg-secondary'; break;
                default: $clase_estado = 'bg-light';
            }

            // Determinar clase de prioridad
            $clase_prioridad = '';
            switch ($ticket['prioridad']) {
                case 'Alta': $clase_prioridad = 'bg-danger'; break;
                case 'Media': $clase_prioridad = 'bg-warning'; break;
                case 'Baja': $clase_prioridad = 'bg-success'; break;
                default: $clase_prioridad = 'bg-light';
            }

            echo '<tr>
                <td>#' . $ticket['id_ticket'] . '</td>
                <td>' . htmlspecialchars($ticket['titulo']) . '</td>
                <td>' . htmlspecialchars($ticket['usuario']) . '</td>
                <td>' . htmlspecialchars($ticket['categoria']) . '</td>
                <td><span class="badge ' . $clase_estado . '">' . $ticket['estado'] . '</span></td>
                <td><span class="badge ' . $clase_prioridad . '">' . $ticket['prioridad'] . '</span></td>
                <td>' . $ticket['fecha_formateada'] . '</td>
            </tr>';
        }

        echo '</tbody></table></body></html>';
    } else {
        // Exportar a Excel (CSV)
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=tickets_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // Encabezados
        fputcsv($output, [
            'ID', 'Título', 'Usuario', 'Email', 'Categoría', 'Estado', 'Prioridad', 'Fecha Creación', 'Fecha Actualización'
        ]);
        
        // Datos
        foreach ($tickets as $ticket) {
            fputcsv($output, [
                $ticket['id_ticket'],
                $ticket['titulo'],
                $ticket['usuario'],
                $ticket['email'],
                $ticket['categoria'],
                $ticket['estado'],
                $ticket['prioridad'],
                $ticket['fecha_creacion'],
                $ticket['fecha_actualizacion']
            ]);
        }
        
        fclose($output);
    }
    
} catch (Exception $e) {
    die('Error al generar el reporte: ' . $e->getMessage());
}
?>
