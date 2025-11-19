<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir configuración
require_once 'includes/header.php';

// Verificar que el usuario sea administrador
if ($_SESSION['rol'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Obtener parámetros de filtrado
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'mes';
$limite = $filtro === 'mes' ? 30 : 7; // 30 días o 7 días

// Obtener datos para los gráficos
$fecha_limite = date('Y-m-d', strtotime("-$limite days"));

// 1. Tickets por categoría (últimos 30 días o 7 días)
$query = "SELECT c.nombre_categoria, COUNT(t.id_ticket) as total 
          FROM categorias c
          LEFT JOIN tickets t ON c.id_categoria = t.id_categoria 
              AND t.fecha_creacion >= ?
          WHERE c.activa = 1
          GROUP BY c.id_categoria, c.nombre_categoria
          ORDER BY total DESC";
$stmt = $pdo->prepare($query);
$stmt->execute([$fecha_limite]);
$tickets_por_categoria = $stmt->fetchAll();

// 2. Tickets por estado (últimos 30 días o 7 días)
$query = "SELECT estado, COUNT(*) as total 
          FROM tickets 
          WHERE fecha_creacion >= ? 
          GROUP BY estado";
$stmt = $pdo->prepare($query);
$stmt->execute([$fecha_limite]);
$tickets_por_estado = $stmt->fetchAll();

// 3. Tickets por día/semana (últimos 30 días o 7 días)
if ($filtro === 'mes') {
    $query = "SELECT 
                DATE_FORMAT(fecha_creacion, '%Y-%m-%d') as fecha, 
                COUNT(*) as total
              FROM tickets 
              WHERE fecha_creacion >= ? 
              GROUP BY DATE(fecha_creacion)
              ORDER BY fecha";
} else {
    $query = "SELECT 
                CONCAT('Semana ', WEEK(fecha_creacion, 1), ' (', 
                       DATE_FORMAT(MIN(DATE(fecha_creacion)), '%d/%m'), ' - ', 
                       DATE_FORMAT(MAX(DATE(fecha_creacion)), '%d/%m'), ')') as semana,
                COUNT(*) as total
              FROM tickets 
              WHERE fecha_creacion >= ? 
              GROUP BY YEARWEEK(fecha_creacion, 1)
              ORDER BY MIN(fecha_creacion)";
}
$stmt = $pdo->prepare($query);
$stmt->execute([$fecha_limite]);
$tickets_por_tiempo = $stmt->fetchAll();
if ($_SESSION['rol'] !== 'Administrador') {
    $_SESSION['error'] = 'No tiene permisos para acceder a esta sección';
    header('Location: dashboard.php');
    exit();
}

// Obtener parámetros de fecha
$tipo_reporte = $_GET['tipo'] ?? 'diario';
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-d');
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');

// Validar y formatear fechas
$fecha_inicio_valida = $fecha_inicio;
$fecha_fin_valida = $fecha_fin;

// Configurar fechas según el tipo de reporte
if ($tipo_reporte === 'semanal') {
    $fecha_inicio_valida = date('Y-m-d', strtotime('monday this week', strtotime($fecha_inicio)));
    $fecha_fin_valida = date('Y-m-d', strtotime('sunday this week', strtotime($fecha_fin)));
} elseif ($tipo_reporte === 'mensual') {
    $fecha_inicio_valida = date('Y-m-01', strtotime($fecha_inicio));
    $fecha_fin_valida = date('Y-m-t', strtotime($fecha_fin));
}

// Obtener estadísticas de tickets
$pdo = conectarDB();
$estadisticas = [
    'total' => 0,
    'abiertos' => 0,
    'en_progreso' => 0,
    'cerrados' => 0,
    'por_categoria' => []
];

try {
    // Consulta para obtener estadísticas generales
    $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'abierto' THEN 1 ELSE 0 END) as abiertos,
                SUM(CASE WHEN estado = 'en_progreso' THEN 1 ELSE 0 END) as en_progreso,
                SUM(CASE WHEN estado = 'cerrado' THEN 1 ELSE 0 END) as cerrados
            FROM tickets 
            WHERE DATE(fecha_creacion) BETWEEN :fecha_inicio AND :fecha_fin";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':fecha_inicio' => $fecha_inicio_valida,
        ':fecha_fin' => $fecha_fin_valida
    ]);
    
    $estadisticas = array_merge($estadisticas, $stmt->fetch(PDO::FETCH_ASSOC));
    
    // Consulta para obtener estadísticas por categoría
    $sql_categorias = "SELECT 
                        c.nombre_categoria as categoria,
                        COUNT(t.id_ticket) as total,
                        SUM(CASE WHEN t.estado = 'abierto' THEN 1 ELSE 0 END) as abiertos,
                        SUM(CASE WHEN t.estado = 'en_progreso' THEN 1 ELSE 0 END) as en_progreso,
                        SUM(CASE WHEN t.estado = 'cerrado' THEN 1 ELSE 0 END) as cerrados
                    FROM categorias c
                    LEFT JOIN tickets t ON c.id_categoria = t.id_categoria 
                        AND DATE(t.fecha_creacion) BETWEEN :fecha_inicio AND :fecha_fin
                    GROUP BY c.id_categoria, c.nombre_categoria
                    ORDER BY total DESC";
    
    $stmt_cat = $pdo->prepare($sql_categorias);
    $stmt_cat->execute([
        ':fecha_inicio' => $fecha_inicio_valida,
        ':fecha_fin' => $fecha_fin_valida
    ]);
    $estadisticas['por_categoria'] = $stmt_cat->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $_SESSION['error'] = 'Error al generar el reporte: ' . $e->getMessage();
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Reportes</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="?filtro=mes" class="btn btn-sm btn-outline-secondary <?php echo $filtro === 'mes' ? 'active' : ''; ?>">
                    Últimos 30 días
                </a>
                <a href="?filtro=semana" class="btn btn-sm btn-outline-secondary <?php echo $filtro === 'semana' ? 'active' : ''; ?>">
                    Últimas 4 semanas
                </a>
            </div>
            <div class="btn-group me-2">
                <a href="?tipo=diario" class="btn btn-sm btn-outline-secondary <?= $tipo_reporte === 'diario' ? 'active' : '' ?>">
                    Diario
                </a>
                <a href="?tipo=semanal" class="btn btn-sm btn-outline-secondary <?= $tipo_reporte === 'semanal' ? 'active' : '' ?>">
                    Semanal
                </a>
                <a href="?tipo=mensual" class="btn btn-sm btn-outline-secondary <?= $tipo_reporte === 'mensual' ? 'active' : '' ?>">
                    Mensual
                </a>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                <i class="bi bi-printer"></i> Imprimir
            </button>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-funnel"></i> Filtros
        </div>
        <div class="card-body">
            <form method="get" class="row g-3">
                <input type="hidden" name="tipo" value="<?= $tipo_reporte ?>">
                
                <div class="col-md-5">
                    <label for="fecha_inicio" class="form-label">Fecha de inicio</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                           value="<?= $fecha_inicio ?>" required>
                </div>
                
                <div class="col-md-5">
                    <label for="fecha_fin" class="form-label">Fecha de fin</label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                           value="<?= $fecha_fin ?>" required>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-funnel"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumen -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Total de Tickets</h6>
                            <h2 class="mb-0"><?= $estadisticas['total'] ?? 0 ?></h2>
                        </div>
                        <i class="bi bi-ticket-detailed fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-warning h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Abiertos</h6>
                            <h2 class="mb-0"><?= $estadisticas['abiertos'] ?? 0 ?></h2>
                        </div>
                        <i class="bi bi-hourglass-split fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-info h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">En Progreso</h6>
                            <h2 class="mb-0"><?= $estadisticas['en_progreso'] ?? 0 ?></h2>
                        </div>
                        <i class="bi bi-gear-wide-connected fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card text-white bg-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title">Cerrados</h6>
                            <h2 class="mb-0"><?= $estadisticas['cerrados'] ?? 0 ?></h2>
                        </div>
                        <i class="bi bi-check-circle fs-1 opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico de barras -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="bi bi-bar-chart"></i> Distribución de Tickets por Estado
        </div>
        <div class="card-body">
            <canvas id="estadosChart" height="100"></canvas>
        </div>
    </div>

    <!-- Tabla por categorías -->
    <div class="card">
        <div class="card-header">
            <i class="bi bi-list-ul"></i> Tickets por Categoría
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th>Categoría</th>
                            <th class="text-center">Total</th>
                            <th class="text-center">Abiertos</th>
                            <th class="text-center">En Progreso</th>
                            <th class="text-center">Cerrados</th>
                            <th class="text-center">% Completado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($estadisticas['por_categoria'] as $categoria): ?>
                            <?php if ($categoria['total'] > 0): ?>
                                <?php 
                                $porcentaje = $categoria['total'] > 0 
                                    ? round(($categoria['cerrados'] / $categoria['total']) * 100, 1) 
                                    : 0;
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($categoria['categoria']) ?></td>
                                    <td class="text-center"><?= $categoria['total'] ?></td>
                                    <td class="text-center"><?= $categoria['abiertos'] ?></td>
                                    <td class="text-center"><?= $categoria['en_progreso'] ?></td>
                                    <td class="text-center"><?= $categoria['cerrados'] ?></td>
                                    <td>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" role="progressbar" 
                                                 style="width: <?= $porcentaje ?>%" 
                                                 aria-valuenow="<?= $porcentaje ?>" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                <?= $porcentaje ?>%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <?php if (empty($estadisticas['por_categoria']) || array_sum(array_column($estadisticas['por_categoria'], 'total')) === 0): ?>
                            <tr>
                                <td colspan="6" class="text-center">No hay datos para mostrar en el período seleccionado</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row mt-4">
    <!-- Gráfico de tickets por categoría -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-tags me-1"></i>
                Tickets por Categoría (<?php echo $filtro === 'mes' ? 'Últimos 30 días' : 'Últimas 4 semanas'; ?>)
            </div>
            <div class="card-body">
                <canvas id="categoriaChart" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Gráfico de tickets por estado -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-clipboard-check me-1"></i>
                Tickets por Estado (<?php echo $filtro === 'mes' ? 'Últimos 30 días' : 'Últimas 4 semanas'; ?>)
            </div>
            <div class="card-body">
                <canvas id="estadoChart" height="300"></canvas>
            </div>
        </div>
    </div>

    <!-- Gráfico de tendencia de tickets -->
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-graph-up me-1"></i>
                Tendencias de Tickets (<?php echo $filtro === 'mes' ? 'Últimos 30 días' : 'Últimas 4 semanas'; ?>)
            </div>
            <div class="card-body">
                <canvas id="tendenciaChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Colores para los gráficos
const chartColors = {
    primary: '#4e73df',
    success: '#1cc88a',
    info: '#36b9cc',
    warning: '#f6c23e',
    danger: '#e74a3b',
    secondary: '#858796',
    light: '#f8f9fc',
    dark: '#5a5c69',
};

// Configuración común para los gráficos
const chartOptions = {
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: true,
            position: 'bottom',
        },
        tooltip: {
            backgroundColor: 'rgb(255,255,255)',
            bodyColor: '#858796',
            titleMarginBottom: 10,
            titleFontSize: 14,
            titleFontColor: '#6e707e',
            borderColor: '#dddfeb',
            borderWidth: 1,
            xPadding: 15,
            yPadding: 15,
            displayColors: false,
            caretPadding: 10,
        },
    },
    scales: {
        x: {
            grid: {
                display: false,
                drawBorder: false
            },
            ticks: {
                maxTicksLimit: 10
            }
        },
        y: {
            beginAtZero: true,
            ticks: {
                precision: 0
            },
            grid: {
                color: 'rgb(234, 236, 244)',
                drawBorder: false,
                borderDash: [2],
                zeroLineColor: 'rgb(234, 236, 244)',
                zeroLineBorderDash: [2],
                drawTicks: false
            }
        }
    }
};
// Gráfico de estados
const ctx = document.getElementById('estadosChart').getContext('2d');
const estadosChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Abiertos', 'En Progreso', 'Cerrados'],
        datasets: [{
            label: 'Tickets',
            data: [
                <?= $estadisticas['abiertos'] ?? 0 ?>, 
                <?= $estadisticas['en_progreso'] ?? 0 ?>, 
                <?= $estadisticas['cerrados'] ?? 0 ?>
            ],
            backgroundColor: [
                'rgba(255, 193, 7, 0.8)',
                'rgba(23, 162, 184, 0.8)',
                'rgba(40, 167, 69, 0.8)'
            ],
            borderColor: [
                'rgba(255, 193, 7, 1)',
                'rgba(23, 162, 184, 1)',
                'rgba(40, 167, 69, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: false
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Configurar fechas por defecto según el tipo de reporte
document.addEventListener('DOMContentLoaded', function() {
    const tipoReporte = '<?= $tipo_reporte ?>';
    const fechaInicio = document.getElementById('fecha_inicio');
    const fechaFin = document.getElementById('fecha_fin');
    
    // Si es un reporte semanal, ajustar las fechas al inicio y fin de semana
    if (tipoReporte === 'semanal') {
        const hoy = new Date();
        const primerDiaSemana = new Date(hoy.setDate(hoy.getDate() - hoy.getDay() + (hoy.getDay() === 0 ? -6 : 1)));
        const ultimoDiaSemana = new Date(primerDiaSemana);
        ultimoDiaSemana.setDate(primerDiaSemana.getDate() + 6);
        
        fechaInicio.valueAsDate = primerDiaSemana;
        fechaFin.valueAsDate = ultimoDiaSemana;
    } 
    // Si es un reporte mensual, ajustar al primer y último día del mes
    else if (tipoReporte === 'mensual') {
        const hoy = new Date();
        const primerDiaMes = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        const ultimoDiaMes = new Date(hoy.getFullYear(), hoy.getMonth() + 1, 0);
        
        fechaInicio.valueAsDate = primerDiaMes;
        fechaFin.valueAsDate = ultimoDiaMes;
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
