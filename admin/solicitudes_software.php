<?php
require_once '../includes/config.php';

// Verificar si es administrador
if (!esAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = conectarDB();
        
        if (isset($_POST['actualizar_estado'])) {
            $id_solicitud = $_POST['id_solicitud'];
            $estado = $_POST['estado'];
            $link_descarga = $_POST['link_descarga'] ?? null;
            $observaciones = $_POST['observaciones'] ?? null;
            
            if ($estado === 'completado' && empty($link_descarga)) {
                throw new Exception("Para marcar como completado, debes proporcionar un link de descarga");
            }
            
            $stmt = $pdo->prepare("
                UPDATE solicitudes_software 
                SET estado = ?, link_descarga = ?, observaciones_admin = ?, fecha_actualizacion = CURRENT_TIMESTAMP 
                WHERE id = ?
            ");
            $stmt->execute([$estado, $link_descarga, $observaciones, $id_solicitud]);
            
            $_SESSION['exito'] = 'Solicitud actualizada correctamente';
            header('Location: solicitudes_software.php');
            exit;
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$titulo_pagina = "Gestión de Solicitudes de Software";
require_once 'includes/header.php';

// Obtener todas las solicitudes
try {
    $pdo = conectarDB();
    $stmt = $pdo->prepare("
        SELECT s.*, u.nombre as nombre_usuario, u.email 
        FROM solicitudes_software s 
        LEFT JOIN usuarios u ON s.id_usuario = u.id_usuario 
        ORDER BY s.fecha_solicitud DESC
    ");
    $stmt->execute();
    $solicitudes = $stmt->fetchAll();
    
    // Contar solicitudes por estado
    $stmt = $pdo->query("SELECT estado, COUNT(*) as total FROM solicitudes_software GROUP BY estado");
    $estadisticas = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch (Exception $e) {
    $error = "Error al cargar las solicitudes: " . $e->getMessage();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-download me-2"></i>Gestión de Solicitudes de Software</h2>
    <div>
        <a href="crear_tabla_solicitudes.php" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-database me-2"></i>Verificar Tablas
        </a>
    </div>
</div>

<?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['exito'])): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($_SESSION['exito']); unset($_SESSION['exito']); ?>
    </div>
<?php endif; ?>

<!-- Estadísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-warning text-dark">
            <div class="card-body">
                <h5 class="card-title">Pendientes</h5>
                <h3><?php echo $estadisticas['pendiente'] ?? 0; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <h5 class="card-title">En Proceso</h5>
                <h3><?php echo $estadisticas['en_proceso'] ?? 0; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <h5 class="card-title">Completados</h5>
                <h3><?php echo $estadisticas['completado'] ?? 0; ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-danger text-white">
            <div class="card-body">
                <h5 class="card-title">Cancelados</h5>
                <h3><?php echo $estadisticas['cancelado'] ?? 0; ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- Lista de solicitudes -->
<div class="card">
    <div class="card-body">
        <?php if (empty($solicitudes)): ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox display-1 text-muted"></i>
                <h4 class="mt-3">No hay solicitudes de software</h4>
                <p class="text-muted">Los usuarios aún no han solicitado ningún software.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Software</th>
                            <th>Solicitante</th>
                            <th>Ciclo</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($solicitudes as $solicitud): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($solicitud['nombre_software']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars(substr($solicitud['descripcion'], 0, 80)) . '...'; ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($solicitud['nombre_usuario']); ?>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($solicitud['email']); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($solicitud['ciclo_educativo']); ?></span>
                                </td>
                                <td>
                                    <?php
                                    $estado_class = [
                                        'pendiente' => 'bg-warning',
                                        'en_proceso' => 'bg-info',
                                        'completado' => 'bg-success',
                                        'cancelado' => 'bg-danger'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $estado_class[$solicitud['estado']]; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $solicitud['estado'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])); ?></small>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#gestionModal<?php echo $solicitud['id']; ?>">
                                        <i class="bi bi-gear"></i> Gestionar
                                    </button>
                                </td>
                            </tr>
                            
                            <!-- Modal de gestión -->
                            <div class="modal fade" id="gestionModal<?php echo $solicitud['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <form method="POST" action="">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Gestionar Solicitud</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="id_solicitud" value="<?php echo $solicitud['id']; ?>">
                                                
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <strong>Software:</strong><br>
                                                        <?php echo htmlspecialchars($solicitud['nombre_software']); ?>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>Solicitante:</strong><br>
                                                        <?php echo htmlspecialchars($solicitud['nombre_usuario']); ?> (<?php echo htmlspecialchars($solicitud['email']); ?>)
                                                    </div>
                                                </div>
                                                
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <strong>Ciclo Educativo:</strong><br>
                                                        <?php echo htmlspecialchars($solicitud['ciclo_educativo']); ?>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <strong>Fecha de Solicitud:</strong><br>
                                                        <?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])); ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <strong>Descripción:</strong><br>
                                                    <div class="alert alert-light">
                                                        <?php echo nl2br(htmlspecialchars($solicitud['descripcion'])); ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="estado" class="form-label">Estado *</label>
                                                    <select class="form-select" id="estado" name="estado" required>
                                                        <option value="pendiente" <?php echo $solicitud['estado'] === 'pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                                                        <option value="en_proceso" <?php echo $solicitud['estado'] === 'en_proceso' ? 'selected' : ''; ?>>En Proceso</option>
                                                        <option value="completado" <?php echo $solicitud['estado'] === 'completado' ? 'selected' : ''; ?>>Completado</option>
                                                        <option value="cancelado" <?php echo $solicitud['estado'] === 'cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                                                    </select>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="link_descarga" class="form-label">Link de Descarga</label>
                                                    <input type="url" class="form-control" id="link_descarga" name="link_descarga" 
                                                           value="<?php echo htmlspecialchars($solicitud['link_descarga'] ?? ''); ?>" 
                                                           placeholder="https://ejemplo.com/descarga/software">
                                                    <div class="form-text">Obligatorio solo si el estado es "Completado"</div>
                                                </div>
                                                
                                                <div class="mb-3">
                                                    <label for="observaciones" class="form-label">Observaciones para el Usuario</label>
                                                    <textarea class="form-control" id="observaciones" name="observaciones" rows="3" 
                                                              placeholder="Información adicional para el usuario sobre su solicitud..."><?php echo htmlspecialchars($solicitud['observaciones_admin'] ?? ''); ?></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" name="actualizar_estado" class="btn btn-primary">
                                                    <i class="bi bi-check-circle me-2"></i>Actualizar Solicitud
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
