<?php
// Start session and include config at the very top
session_start();
require_once __DIR__ . '/../includes/config.php';

// Enable output buffering at the very beginning
if (ob_get_level() == 0) {
    ob_start();
}

// Check if user is logged in
if (!isset($_SESSION['usuario_id'])) {
    // ruta absoluta desde la raíz de tu proyecto
    header('Location: /NOSEEEE/usuario/login.php');
    exit();
}


// Obtener el ID del ticket de la URL
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$ticket_id) {
    $_SESSION['error'] = 'Ticket no especificado';
    header('Location: tickets.php');
    exit();
}

// Conectar a la base de datos
$pdo = conectarDB();

// Obtener la información del ticket
$usuario_id = $_SESSION['usuario_id']; // Asegurarse de que el ID del usuario esté definido
$stmt = $pdo->prepare("SELECT t.*, c.nombre_categoria 
                      FROM tickets t 
                      JOIN categorias c ON t.id_categoria = c.id_categoria 
                      WHERE t.id_ticket = ? AND t.id_usuario = ?");
$stmt->execute([$ticket_id, $usuario_id]);
$ticket = $stmt->fetch();

// Verificar si el ticket existe y pertenece al usuario
if (!$ticket) {
    $_SESSION['error'] = 'Ticket no encontrado o no tienes permiso para editarlo';
    header('Location: tickets.php');
    exit();
}

// Procesar el formulario cuando se envía
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errores = [];
    $titulo = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $id_categoria = (int)($_POST['id_categoria'] ?? 0);
    $estado = trim($_POST['estado'] ?? '');
    
    // Validaciones
    if (empty($titulo)) {
        $errores[] = 'El título es obligatorio';
    }
    
    if (empty($descripcion)) {
        $errores[] = 'La descripción es obligatoria';
    }
    
    if ($id_categoria <= 0) {
        $errores[] = 'Debes seleccionar una categoría válida';
    }
    
    // Obtener archivos adjuntos existentes
    $archivos = $pdo->prepare("SELECT * FROM archivos_tickets WHERE id_ticket = ? ORDER BY fecha_subida DESC");
    $archivos->execute([$ticket_id]);
    $archivos = $archivos->fetchAll();

    // Directorio para guardar archivos
    $upload_dir = ROOT_PATH . '/uploads/tickets/' . $ticket_id . '/';

    // Crear directorio si no existe
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (empty($errores)) {
        try {
            $pdo->beginTransaction();
            
            // Actualizar el ticket
            $stmt = $pdo->prepare("UPDATE tickets SET titulo = ?, descripcion = ?, id_categoria = ?, estado = ?, fecha_actualizacion = NOW() WHERE id_ticket = ?");
            $stmt->execute([$titulo, $descripcion, $id_categoria, $estado, $ticket_id]);
            
            $pdo->commit();
            
            // Clear output buffer before redirect
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            
            $_SESSION['success'] = 'Ticket actualizado correctamente';
            header("Location: ver_ticket.php?id=$ticket_id");
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errores[] = "Error al actualizar el ticket: " . $e->getMessage();
        }
    }
}

// Obtener categorías para el select
$categorias = $pdo->query("SELECT * FROM categorias WHERE activa = 1 ORDER BY nombre_categoria")->fetchAll();

require_once 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Editar Ticket #<?php echo $ticket['id_ticket']; ?></h4>
                        <a href="ver_ticket.php?id=<?php echo $ticket_id; ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Volver al ticket
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($errores)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errores as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post">
                        <div class="mb-3">
                            <label for="titulo" class="form-label">Título</label>
                            <input type="text" class="form-control" id="titulo" name="titulo" 
                                   value="<?php echo htmlspecialchars($ticket['titulo']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" 
                                      rows="5" required><?php echo htmlspecialchars($ticket['descripcion']); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="id_categoria" class="form-label">Categoría</label>
                                    <select class="form-select" id="id_categoria" name="id_categoria" required>
                                        <option value="">Seleccione una categoría</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                            <option value="<?php echo $categoria['id_categoria']; ?>" 
                                                <?php echo $categoria['id_categoria'] == $ticket['id_categoria'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($categoria['nombre_categoria']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="estado" class="form-label">Estado</label>
                                    <select class="form-select" id="estado" name="estado" required>
                                        <option value="Abierto" <?php echo $ticket['estado'] === 'Abierto' ? 'selected' : ''; ?>>Abierto</option>
                                        <option value="En Proceso" <?php echo $ticket['estado'] === 'En Proceso' ? 'selected' : ''; ?>>En Proceso</option>
                                        <option value="Cerrado" <?php echo $ticket['estado'] === 'Cerrado' ? 'selected' : ''; ?>>Cerrado</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="ver_ticket.php?id=<?php echo $ticket_id; ?>" class="btn btn-outline-secondary me-md-2">
                                <i class="bi bi-x-circle"></i> Cancelar
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
