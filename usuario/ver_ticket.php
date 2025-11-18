<?php
// Activar el buffer de salida al inicio del script
if (ob_get_level() == 0) {
    ob_start();
}

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir configuración
require_once dirname(__DIR__) . '/includes/config.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

// Conectar a la base de datos
try {
    $pdo = conectarDB();
    $usuario_id = $_SESSION['usuario_id'];
} catch (Exception $e) {
    die('Error de conexión a la base de datos');
}

// Obtener el ID del ticket de la URL
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$ticket_id) {
    $_SESSION['error'] = 'Ticket no especificado';
    header('Location: ' . SITE_URL . '/usuario/tickets.php');
    exit();
}

// =============================
//  Procesar nueva respuesta
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enviar_respuesta'])) {
    $mensaje = trim($_POST['mensaje'] ?? '');
    
    if (empty($mensaje)) {
        $_SESSION['error'] = 'El mensaje no puede estar vacío';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Insertar respuesta
            $stmt = $pdo->prepare("INSERT INTO respuestas (id_ticket, id_usuario, mensaje, fecha_creacion) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$ticket_id, $usuario_id, $mensaje]);
            
            // Actualizar fecha de actualización del ticket
            $pdo->prepare("UPDATE tickets SET fecha_actualizacion = NOW() WHERE id_ticket = ?")->execute([$ticket_id]);
            
            // Si el estado es 'Nuevo', cambiarlo a 'En Proceso'
            if ($ticket['estado'] === 'Nuevo' || $ticket['estado'] === 'Abierto') {
                $pdo->prepare("UPDATE tickets SET estado = 'En Proceso' WHERE id_ticket = ?")->execute([$ticket_id]);
                $ticket['estado'] = 'En Proceso';
            }
            
            $pdo->commit();
            
            // Redirigir para evitar reenvío del formulario
            $_SESSION['success'] = 'Respuesta enviada correctamente';
            header('Location: ' . SITE_URL . "/usuario/ver_ticket.php?id=$ticket_id");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = 'Error al enviar la respuesta: ' . $e->getMessage();
            error_log("Error al enviar respuesta: " . $e->getMessage());
            header('Location: ' . SITE_URL . "/usuario/ver_ticket.php?id=$ticket_id");
            exit();
        }
    }
}

// Obtener la información del ticket
$stmt = $pdo->prepare("SELECT t.*, c.nombre_categoria, u.nombre as nombre_usuario 
                      FROM tickets t 
                      JOIN categorias c ON t.id_categoria = c.id_categoria 
                      JOIN usuarios u ON t.id_usuario = u.id_usuario 
                      WHERE t.id_ticket = ? AND t.id_usuario = ?");
$stmt->execute([$ticket_id, $usuario_id]);
$ticket = $stmt->fetch();

// Verificar si el ticket existe y pertenece al usuario
if (!$ticket) {
    $_SESSION['error'] = 'Ticket no encontrado o no tienes permiso para verlo';
    header('Location: ' . SITE_URL . '/usuario/tickets.php');
    exit();
}

// =============================
//  Obtener respuestas (tabla respuestas)
// =============================
$sqlRes = "
    SELECT r.*, u.nombre as nombre_usuario, u.id_rol
    FROM respuestas r
    JOIN usuarios u ON r.id_usuario = u.id_usuario
    WHERE r.id_ticket = :id_ticket
    ORDER BY r.fecha_creacion ASC
";
$stmt = $pdo->prepare($sqlRes);
$stmt->execute([':id_ticket' => $ticket_id]);
$respuestas = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// Actualizar el estado a "En Proceso" si está "Abierto"
if ($ticket['estado'] === 'Abierto') {
    $pdo->prepare("UPDATE tickets SET estado = 'En Proceso' WHERE id_ticket = ?")->execute([$ticket_id]);
    $ticket['estado'] = 'En Proceso';
}

// Incluir el header después de toda la lógica de procesamiento
$titulo_pagina = 'Ver Ticket #' . $ticket_id;
require_once 'includes/header.php';

?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Ticket #<?php echo $ticket['id_ticket']; ?></h2>
    <div>
        <a href="tickets.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver a la lista
        </a>
        <a href="editar_ticket.php?id=<?php echo $ticket['id_ticket']; ?>" class="btn btn-outline-primary">
            <i class="bi bi-pencil"></i> Editar
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Información del ticket -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?php echo htmlspecialchars($ticket['titulo']); ?></h5>
                <div>
                    <span class="badge bg-<?php 
                        echo $ticket['estado'] === 'Abierto' ? 'warning' : 
                            ($ticket['estado'] === 'En Proceso' ? 'info' : 
                            ($ticket['estado'] === 'Resuelto' ? 'success' : 'secondary')); 
                    ?>">
                        <?php echo $ticket['estado']; ?>
                    </span>
                    <span class="badge bg-<?php 
                        echo $ticket['prioridad'] === 'Alta' ? 'danger' : 
                            ($ticket['prioridad'] === 'Media' ? 'warning' : 'success'); 
                    ?>">
                        <?php echo $ticket['prioridad']; ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h6>Descripción:</h6>
                    <div class="p-3 bg-light rounded">
                        <?php echo nl2br(htmlspecialchars($ticket['descripcion'])); ?>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4">
                        <h6>Categoría:</h6>
                        <p><?php echo htmlspecialchars($ticket['nombre_categoria']); ?></p>
                    </div>
                    <div class="col-md-4">
                        <h6>Fecha de creación:</h6>
                        <p><?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></p>
                    </div>
                    <div class="col-md-4">
                        <h6>Última actualización:</h6>
                        <p><?php echo date('d/m/Y H:i', strtotime($ticket['fecha_actualizacion'])); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Respuestas -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Respuestas</h5>
            </div>
            <div class="card-body p-0">
                <?php if (count($respuestas) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($respuestas as $respuesta): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between mb-2">
                                    <div class="fw-bold">
                                        <?php echo htmlspecialchars($respuesta['nombre_usuario']); ?>
                                        <?php if ($respuesta['id_rol'] == 1): ?>
                                            <span class="badge bg-primary">Administrador</span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y H:i', strtotime($respuesta['fecha_creacion'])); ?>
                                    </small>
                                </div>
                                <div class="mb-2">
                                    <?php echo nl2br(htmlspecialchars($respuesta['mensaje'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-chat-square-text text-muted" style="font-size: 2rem;"></i>
                        <p class="text-muted mt-2 mb-0">No hay respuestas aún</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Formulario de respuesta -->
        <?php if ($ticket['estado'] !== 'Cerrado'): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Responder</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label for="mensaje" class="form-label">Mensaje</label>
                            <textarea class="form-control" id="mensaje" name="mensaje" rows="4" required></textarea>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" name="enviar_respuesta" class="btn btn-primary">
                                <i class="bi bi-send"></i> Enviar respuesta
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> Este ticket ha sido cerrado y no se pueden agregar más respuestas.
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
        <!-- Acciones del ticket -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Acciones</h5>
            </div>
            <div class="card-body">
                <?php if (esAdmin()): ?>
                        <form method="POST" action="acciones_ticket.php" class="mb-3">
                            <input type="hidden" name="id_ticket" value="<?php echo $ticket['id_ticket']; ?>">
                            <input type="hidden" name="accion" value="reasignar">
                            <div class="mb-2">
                                <label class="form-label">Reasignar a:</label>
                                <select name="id_usuario" class="form-select">
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <option value="<?php echo $usuario['id_usuario']; ?>" 
                                                <?php echo $usuario['id_usuario'] == $ticket['id_usuario'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($usuario['nombre']); ?> (<?php echo $usuario['email']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="bi bi-arrow-repeat"></i> Reasignar
                            </button>
                        </form>
                        
                        <form method="POST" action="acciones_ticket.php" class="mb-3">
                            <input type="hidden" name="id_ticket" value="<?php echo $ticket['id_ticket']; ?>">
                            <input type="hidden" name="accion" value="cambiar_estado">
                            <div class="mb-2">
                                <label class="form-label">Cambiar estado:</label>
                                <select name="estado" class="form-select">
                                    <option value="Abierto" <?php echo $ticket['estado'] === 'Abierto' ? 'selected' : ''; ?>>Abierto</option>
                                    <option value="En Proceso" <?php echo $ticket['estado'] === 'En Proceso' ? 'selected' : ''; ?>>En Proceso</option>
                                    <option value="En Espera" <?php echo $ticket['estado'] === 'En Espera' ? 'selected' : ''; ?>>En Espera</option>
                                    <option value="Resuelto" <?php echo $ticket['estado'] === 'Resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                                    <option value="Cerrado" <?php echo $ticket['estado'] === 'Cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="bi bi-arrow-repeat"></i> Actualizar estado
                            </button>
                        </form>
                    <?php endif; ?>
            </div>
        </div>
    </div>

<?php 
// Incluir el footer al final
require_once 'includes/footer.php'; 

// Limpiar el buffer de salida y desactivarlo
if (ob_get_level() > 0) {
    @ob_end_flush();
}
?>
