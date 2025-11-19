<?php
$titulo_pagina = "Editar Ticket";
require_once 'includes/header.php';

// Verificar si se proporcionó un ID de ticket
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID de ticket no válido.";
    header('Location: tickets.php');
    exit();
}

$ticket_id = (int)$_GET['id'];

// Obtener información del ticket
$query = "SELECT t.*, u.nombre as usuario_nombre, c.nombre_categoria 
          FROM tickets t 
          JOIN usuarios u ON t.id_usuario = u.id_usuario 
          JOIN categorias c ON t.id_categoria = c.id_categoria 
          WHERE t.id_ticket = ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    $_SESSION['error'] = "Ticket no encontrado.";
    header('Location: tickets.php');
    exit();
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $estado = $_POST['estado'];
    $prioridad = $_POST['prioridad'];
    $id_categoria = (int)$_POST['id_categoria'];
    $solucion = trim($_POST['solucion'] ?? '');

    // Validaciones básicas
    if (empty($titulo) || empty($descripcion)) {
        $_SESSION['error'] = "El título y la descripción son obligatorios.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Actualizar el ticket
            $query = "UPDATE tickets SET 
                      titulo = ?, 
                      descripcion = ?, 
                      estado = ?, 
                      prioridad = ?, 
                      id_categoria = ?,
                      solucion = ?,
                      fecha_actualizacion = NOW()
                      WHERE id_ticket = ?";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([
                $titulo,
                $descripcion,
                $estado,
                $prioridad,
                $id_categoria,
                $solucion,
                $ticket_id
            ]);
            
            // Registrar la actualización en el historial
            $query = "INSERT INTO historial_tickets (id_ticket, id_usuario, accion, detalle) 
                      VALUES (?, ?, 'actualizacion', 'Ticket actualizado por el administrador')";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$ticket_id, $usuario_id]);
            
            $pdo->commit();
            
            $_SESSION['success'] = "Ticket actualizado correctamente.";
            header("Location: ver_ticket.php?id=" . $ticket_id);
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Error al actualizar el ticket: " . $e->getMessage();
        }
    }
}

// Obtener categorías para el select
$categorias = $pdo->query("SELECT * FROM categorias WHERE activa = 1 ORDER BY nombre_categoria")->fetchAll();
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Editar Ticket #<?php echo $ticket_id; ?></h4>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                    <?php endif; ?>
                    
                    <form method="post" id="formEditarTicket">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" 
                                   value="<?php echo htmlspecialchars($ticket['titulo']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" 
                                      rows="4" required><?php echo htmlspecialchars($ticket['descripcion']); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado" required>
                                    <option value="Abierto" <?php echo $ticket['estado'] === 'Abierto' ? 'selected' : ''; ?>>Abierto</option>
                                    <option value="En progreso" <?php echo $ticket['estado'] === 'En progreso' ? 'selected' : ''; ?>>En progreso</option>
                                    <option value="En espera" <?php echo $ticket['estado'] === 'En espera' ? 'selected' : ''; ?>>En espera</option>
                                    <option value="Resuelto" <?php echo $ticket['estado'] === 'Resuelto' ? 'selected' : ''; ?>>Resuelto</option>
                                    <option value="Cerrado" <?php echo $ticket['estado'] === 'Cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="prioridad" class="form-label">Prioridad</label>
                                <select class="form-select" id="prioridad" name="prioridad" required>
                                    <option value="Baja" <?php echo $ticket['prioridad'] === 'Baja' ? 'selected' : ''; ?>>Baja</option>
                                    <option value="Media" <?php echo $ticket['prioridad'] === 'Media' ? 'selected' : ''; ?>>Media</option>
                                    <option value="Alta" <?php echo $ticket['prioridad'] === 'Alta' ? 'selected' : ''; ?>>Alta</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="id_categoria" class="form-label">Categoría</label>
                            <select class="form-select" id="id_categoria" name="id_categoria" required>
                                <?php foreach ($categorias as $categoria): ?>
                                    <option value="<?php echo $categoria['id_categoria']; ?>" 
                                        <?php echo $categoria['id_categoria'] == $ticket['id_categoria'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($categoria['nombre_categoria']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="solucion" class="form-label">Solución (opcional)</label>
                            <textarea class="form-control" id="solucion" name="solucion" 
                                     rows="3"><?php echo htmlspecialchars($ticket['solucion'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="tickets.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Validación del formulario
    document.getElementById('formEditarTicket').addEventListener('submit', function(e) {
        const titulo = document.getElementById('titulo').value.trim();
        const descripcion = document.getElementById('descripcion').value.trim();
        
        if (!titulo || !descripcion) {
            e.preventDefault();
            alert('Por favor complete todos los campos obligatorios.');
        }
    });
});
</script>
