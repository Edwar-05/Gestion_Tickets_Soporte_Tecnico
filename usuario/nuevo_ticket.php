<?php
$titulo_pagina = "Nuevo Ticket";
require_once 'includes/header.php';

// Obtener categorías activas
$categorias = $pdo->query("SELECT * FROM categorias WHERE activa = 1 ORDER BY nombre_categoria")->fetchAll();

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $categoria = (int)($_POST['categoria'] ?? 0);
    $prioridad = $_POST['prioridad'] ?? 'Media';
    $descripcion = trim($_POST['descripcion'] ?? '');
    
    // Validaciones
    $errores = [];
    
    if (empty($titulo)) {
        $errores[] = 'El título es obligatorio';
    } elseif (strlen($titulo) > 200) {
        $errores[] = 'El título no puede tener más de 200 caracteres';
    }
    
    if (!in_array($prioridad, ['Baja', 'Media', 'Alta'])) {
        $errores[] = 'Prioridad no válida';
    }
    
    if (empty($descripcion)) {
        $errores[] = 'La descripción es obligatoria';
    }
    
    // Verificar que la categoría exista y esté activa
    $categoria_valida = false;
    foreach ($categorias as $cat) {
        if ($cat['id_categoria'] == $categoria) {
            $categoria_valida = true;
            break;
        }
    }
    
    if (!$categoria_valida) {
        $errores[] = 'Categoría no válida';
    }
    
    // Si no hay errores, guardar el ticket
    if (empty($errores)) {
        try {
            $pdo->beginTransaction();
            
            // Insertar el ticket
            $stmt = $pdo->prepare("INSERT INTO tickets (titulo, descripcion, id_usuario, id_categoria, prioridad) 
                                 VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $titulo,
                $descripcion,
                $usuario_id,
                $categoria,
                $prioridad
            ]);
            
            $ticket_id = $pdo->lastInsertId();
            
            // File upload functionality has been removed
            
            $pdo->commit();
            
            // Redirigir al ticket creado
            $_SESSION['success'] = 'Ticket creado correctamente';
            $redirect_url = 'ver_ticket.php?id=' . $ticket_id;
            if (headers_sent()) {
                echo "<script>window.location.href='$redirect_url';</script>";
            } else {
                header("Location: $redirect_url");
            }
            exit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $errores[] = 'Error al crear el ticket: ' . $e->getMessage();
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h2 class="h4 mb-0">Nuevo Ticket de Soporte</h2>
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
                
                <form method="POST">
                    <div class="mb-3">
                        <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="titulo" name="titulo" 
                               value="<?php echo isset($_POST['titulo']) ? htmlspecialchars($_POST['titulo']) : ''; ?>" 
                               required maxlength="200">
                        <div class="form-text">Describe brevemente el problema o solicitud</div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="categoria" class="form-label">Categoría <span class="text-danger">*</span></label>
                                <select class="form-select" id="categoria" name="categoria" required>
                                    <option value="">Seleccione una categoría</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo $categoria['id_categoria']; ?>" 
                                                <?php echo (isset($_POST['categoria']) && $_POST['categoria'] == $categoria['id_categoria']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoria['nombre_categoria']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="prioridad" class="form-label">Prioridad <span class="text-danger">*</span></label>
                                <select class="form-select" id="prioridad" name="prioridad" required>
                                    <option value="Baja" <?php echo (isset($_POST['prioridad']) && $_POST['prioridad'] === 'Baja') ? 'selected' : ''; ?>>Baja</option>
                                    <option value="Media" <?php echo (!isset($_POST['prioridad']) || (isset($_POST['prioridad']) && $_POST['prioridad'] === 'Media')) ? 'selected' : ''; ?>>Media</option>
                                    <option value="Alta" <?php echo (isset($_POST['prioridad']) && $_POST['prioridad'] === 'Alta') ? 'selected' : ''; ?>>Alta</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción detallada <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="6" required><?php 
                            echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; 
                        ?></textarea>
                        <div class="form-text">Describe el problema o solicitud con el mayor detalle posible</div>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="tickets.php" class="btn btn-outline-secondary me-md-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send"></i> Enviar ticket
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header bg-light">
                <h3 class="h6 mb-0">Consejos para un mejor soporte</h3>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>Proporciona un título claro y descriptivo</li>
                    <li>Incluye todos los detalles relevantes para reproducir el problema</li>
                    <li>Menciona cualquier mensaje de error que hayas recibido</li>
                    <li>Si es un problema técnico, indica los pasos que ya intentaste para resolverlo</li>
                    <li>Adjunta capturas de pantalla o archivos que puedan ayudar a entender mejor el problema</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
