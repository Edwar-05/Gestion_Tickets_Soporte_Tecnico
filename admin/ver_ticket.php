<?php
// Iniciar el buffer de salida
ob_start();

$titulo_pagina = "Ver Ticket";
require_once 'includes/header.php'; // debe inicializar sesión y conexión PDO ($pdo)

// Verificar ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID de ticket no válido.";
    header("Location: tickets.php");
    exit();
}

$ticket_id = (int)$_GET['id'];
$usuario_id = $_SESSION['usuario_id'] ?? null;

// Verificar que el usuario esté logueado
if (!$usuario_id) {
    $_SESSION['error'] = 'Debe iniciar sesión para ver este ticket';
    header('Location: ../login.php');
    exit();
}

// =============================
//  Obtener información del ticket
// =============================
$sql = "
    SELECT 
        t.id_ticket,
        t.titulo,
        t.descripcion,
        t.estado,
        t.prioridad,
        t.fecha_creacion,
        t.fecha_actualizacion,
        u.nombre AS usuario_nombre,
        c.nombre_categoria AS categoria_nombre
    FROM tickets t
    JOIN usuarios u ON t.id_usuario = u.id_usuario
    JOIN categorias c ON t.id_categoria = c.id_categoria
    WHERE t.id_ticket = :id
    LIMIT 1
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $ticket_id]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$ticket) {
    $_SESSION['error'] = "Ticket no encontrado.";
    header("Location: tickets.php");
    exit();
}

// =============================
//  Obtener archivos adjuntos (archivos_tickets)
// =============================
// Aseguramos que $archivos siempre sea un array
$archivos = [];

try {
    $sqlArch = "SELECT id, id_ticket, nombre_archivo, ruta_archivo, fecha_subida FROM archivos_tickets WHERE id_ticket = :id ORDER BY fecha_subida ASC";
    $stmtArch = $pdo->prepare($sqlArch);
    $stmtArch->execute([':id' => $ticket_id]);
    $archivos = $stmtArch->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Exception $e) {
    // No detener la página por un fallo en archivos; loguear si es necesario
    // error_log("Error obteniendo archivos: " . $e->getMessage());
    $archivos = [];
}

// =============================
//  Obtener respuestas (tabla respuestas)
// =============================
$sqlRes = "
    SELECT r.*, u.nombre AS autor_nombre
    FROM respuestas r
    JOIN usuarios u ON r.id_usuario = u.id_usuario
    WHERE r.id_ticket = :id
    ORDER BY r.fecha_creacion ASC
";
$stmt = $pdo->prepare($sqlRes);
$stmt->execute([':id' => $ticket_id]);
$respuestas = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

// =============================
//  Procesar nueva respuesta
// =============================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['responder'])) {
    $mensaje = trim($_POST['respuesta'] ?? '');
    $nuevo_estado = $_POST['estado'] ?? $ticket['estado'];

    if (empty($mensaje)) {
        $error = "El mensaje no puede estar vacío.";
    } else {
        try {
            $pdo->beginTransaction();

            // Insertar respuesta
            $stmt = $pdo->prepare("
                INSERT INTO respuestas (id_ticket, id_usuario, mensaje, fecha_creacion)
                VALUES (:id_ticket, :id_usuario, :mensaje, NOW())
            ");
            $stmt->execute([
                ':id_ticket' => $ticket_id,
                ':id_usuario' => $usuario_id,
                ':mensaje' => $mensaje
            ]);

            // Actualizar estado del ticket
            $stmt = $pdo->prepare("
                UPDATE tickets
                SET estado = :estado, fecha_actualizacion = NOW()
                WHERE id_ticket = :id
            ");
            $stmt->execute([
                ':estado' => $nuevo_estado,
                ':id' => $ticket_id
            ]);

            $pdo->commit();
            $_SESSION['success'] = "Respuesta enviada correctamente.";
            header("Location: ver_ticket.php?id=$ticket_id");
            exit();

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error al enviar la respuesta: " . $e->getMessage();
        }
    }
}

// =============================
//  Función para mostrar badge por estado
// =============================
function badgeEstado(string $estado): string {
    switch ($estado) {
        case 'Abierto': return 'warning';
        case 'En Proceso': return 'info';
        case 'En Espera': return 'secondary';
        case 'Resuelto': return 'success';
        case 'Cerrado': return 'dark';
        default: return 'light';
    }
}
?>
<div class="container">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4>Ticket #<?= $ticket['id_ticket']; ?>: <?= htmlspecialchars($ticket['titulo']); ?></h4>
            <span class="badge bg-<?= badgeEstado($ticket['estado']); ?>">
                <?= htmlspecialchars($ticket['estado']); ?>
            </span>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <p><strong>Solicitante:</strong> <?= htmlspecialchars($ticket['usuario_nombre']); ?></p>
                    <p><strong>Categoría:</strong> <?= htmlspecialchars($ticket['categoria_nombre']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Fecha de creación:</strong> <?= date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></p>
                    <p><strong>Última actualización:</strong> <?= date('d/m/Y H:i', strtotime($ticket['fecha_actualizacion'])); ?></p>
                </div>
            </div>
            <div class="border p-3 rounded bg-light">
                <?= nl2br(htmlspecialchars($ticket['descripcion'])); ?>
            </div>

            <!-- Sección de archivos adjuntos -->
            <div class="mt-3">
                <h6>Archivos adjuntos</h6>
                <?php if (is_countable($archivos) && count($archivos) > 0): ?>
                    <ul class="list-group">
                        <?php foreach ($archivos as $a): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?= htmlspecialchars($a['nombre_archivo']); ?></strong>
                                    <div class="small text-muted">Subido: <?= date('d/m/Y H:i', strtotime($a['fecha_subida'])); ?></div>
                                </div>
                                <div>
                                    <?php
                                    // Enlace de descarga — ajusta la ruta si las rutas se guardaron relativas o absolutas
                                    $ruta = htmlspecialchars($a['ruta_archivo']);
                                    ?>
                                    <a href="<?= $ruta; ?>" class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener noreferrer" download>
                                        Descargar
                                    </a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="alert alert-secondary mb-0">No hay archivos adjuntos.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <h4 class="mb-3">Respuestas</h4>
    <div class="mb-4">
        <?php if (is_countable($respuestas) && count($respuestas) > 0): ?>
            <?php foreach ($respuestas as $r): ?>
                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between">
                        <div>
                            <strong><?= htmlspecialchars($r['autor_nombre']); ?></strong>
                        </div>
                        <small class="text-muted">
                            <?= date('d/m/Y H:i', strtotime($r['fecha_creacion'])); ?>
                        </small>
                    </div>
                    <div class="card-body">
                        <?= nl2br(htmlspecialchars($r['mensaje'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">No hay respuestas aún.</div>
        <?php endif; ?>
    </div>

    <div class="card mb-5">
        <div class="card-header">
            <h5 class="mb-0">Responder al ticket</h5>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label for="respuesta" class="form-label">Mensaje</label>
                    <textarea class="form-control" id="respuesta" name="respuesta" rows="4" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="estado" class="form-label">Cambiar estado a:</label>
                    <select class="form-select" id="estado" name="estado">
                        <option value="Abierto" <?= $ticket['estado'] === 'Abierto' ? 'selected' : ''; ?>>Abierto</option>
                        <option value="En Proceso" <?= $ticket['estado'] === 'En Proceso' ? 'selected' : ''; ?>>En Proceso</option>
                        <option value="En Espera" <?= $ticket['estado'] === 'En Espera' ? 'selected' : ''; ?>>En Espera</option>
                        <option value="Resuelto" <?= $ticket['estado'] === 'Resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                        <option value="Cerrado" <?= $ticket['estado'] === 'Cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                    </select>
                </div>
                <button type="submit" name="responder" class="btn btn-primary">
                    <i class="bi bi-send"></i> Enviar respuesta
                </button>
                <a href="tickets.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Volver a la lista
                </a>
            </form>
        </div>
    </div>
</div>

<?php 
// Limpiar el buffer de salida y mostrarlo
ob_end_flush();
require_once 'includes/footer.php'; 
?>
