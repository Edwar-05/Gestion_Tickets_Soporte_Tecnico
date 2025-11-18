<?php
require_once '../includes/config.php';

// Verificar si el usuario está logueado
if (!estaLogueado()) {
    header('Location: ../login.php');
    exit;
}

$titulo_pagina = "Mis Solicitudes de Software";
require_once 'includes/header.php';

// Obtener las solicitudes del usuario
try {
    $pdo = conectarDB();
    $stmt = $pdo->prepare("
        SELECT s.*, u.nombre as nombre_usuario 
        FROM solicitudes_software s 
        LEFT JOIN usuarios u ON s.id_usuario = u.id_usuario 
        WHERE s.id_usuario = ? 
        ORDER BY s.fecha_solicitud DESC
    ");
    $stmt->execute([$_SESSION['usuario_id']]);
    $solicitudes = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "Error al cargar las solicitudes: " . $e->getMessage();
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="bi bi-download me-2"></i>Mis Solicitudes de Software</h2>
    <a href="solicitar_software.php" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>Nueva Solicitud
    </a>
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

<?php if (empty($solicitudes)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox display-1 text-muted"></i>
            <h4 class="mt-3">No tienes solicitudes de software</h4>
            <p class="text-muted">Aún no has solicitado ningún software. ¡Crea tu primera solicitud ahora!</p>
            <a href="solicitar_software.php" class="btn btn-primary">
                <i class="bi bi-plus-circle me-2"></i>Hacer una Solicitud
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Software</th>
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
                                    <small class="text-muted"><?php echo htmlspecialchars(substr($solicitud['descripcion'], 0, 100)) . '...'; ?></small>
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
                                    $estado_icon = [
                                        'pendiente' => 'bi-clock',
                                        'en_proceso' => 'bi-arrow-repeat',
                                        'completado' => 'bi-check-circle',
                                        'cancelado' => 'bi-x-circle'
                                    ];
                                    ?>
                                    <span class="badge <?php echo $estado_class[$solicitud['estado']]; ?>">
                                        <i class="bi <?php echo $estado_icon[$solicitud['estado']]; ?> me-1"></i>
                                        <?php echo ucfirst(str_replace('_', ' ', $solicitud['estado'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])); ?></small>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#detalleModal<?php echo $solicitud['id']; ?>">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <?php if ($solicitud['estado'] === 'completado' && !empty($solicitud['link_descarga'])): ?>
                                        <a href="<?php echo htmlspecialchars($solicitud['link_descarga']); ?>" target="_blank" class="btn btn-sm btn-success ms-1">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            
                            <!-- Modal de detalles -->
                            <div class="modal fade" id="detalleModal<?php echo $solicitud['id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Detalles de la Solicitud</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <strong>Software:</strong><br>
                                                    <?php echo htmlspecialchars($solicitud['nombre_software']); ?>
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Ciclo Educativo:</strong><br>
                                                    <?php echo htmlspecialchars($solicitud['ciclo_educativo']); ?>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <strong>Descripción:</strong><br>
                                                <?php echo nl2br(htmlspecialchars($solicitud['descripcion'])); ?>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <strong>Estado:</strong><br>
                                                    <span class="badge <?php echo $estado_class[$solicitud['estado']]; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $solicitud['estado'])); ?>
                                                    </span>
                                                </div>
                                                <div class="col-md-6">
                                                    <strong>Fecha de Solicitud:</strong><br>
                                                    <?php echo date('d/m/Y H:i', strtotime($solicitud['fecha_solicitud'])); ?>
                                                </div>
                                            </div>
                                            
                                            <?php if (!empty($solicitud['observaciones_admin'])): ?>
                                                <div class="mb-3">
                                                    <strong>Observaciones del Administrador:</strong><br>
                                                    <div class="alert alert-info">
                                                        <?php echo nl2br(htmlspecialchars($solicitud['observaciones_admin'])); ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($solicitud['estado'] === 'completado' && !empty($solicitud['link_descarga'])): ?>
                                                <div class="mb-3">
                                                    <strong>Link de Descarga:</strong><br>
                                                    <a href="<?php echo htmlspecialchars($solicitud['link_descarga']); ?>" target="_blank" class="btn btn-success">
                                                        <i class="bi bi-download me-2"></i>Descargar Software
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
