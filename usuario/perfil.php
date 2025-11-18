<?php
$titulo_pagina = "Mi Perfil";
require_once 'includes/header.php';

// Obtener datos del usuario actual
$usuario_id = $_SESSION['usuario_id'];
$pdo = conectarDB();

// Procesar actualización del perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contrasena_actual = $_POST['contrasena_actual'] ?? '';
    $nueva_contrasena = $_POST['nueva_contrasena'] ?? '';
    $confirmar_contrasena = $_POST['confirmar_contrasena'] ?? '';
    
    $errores = [];
    
    // Validaciones
    if (empty($nombre)) {
        $errores[] = "El nombre es obligatorio";
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = "El correo electrónico no es válido";
    }
    
    // Verificar si el correo ya existe
    $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ?");
    $stmt->execute([$email, $usuario_id]);
    if ($stmt->fetch()) {
        $errores[] = "El correo electrónico ya está en uso";
    }
    
    // Si se está cambiando la contraseña
    if (!empty($nueva_contrasena)) {
        // Obtener la contraseña actual del usuario
        $stmt = $pdo->prepare("SELECT contrasena FROM usuarios WHERE id_usuario = ?");
        $stmt->execute([$usuario_id]);
        $usuario = $stmt->fetch();
        
        if (!password_verify($contrasena_actual, $usuario['contrasena'])) {
            $errores[] = "La contraseña actual es incorrecta";
        } elseif (strlen($nueva_contrasena) < 6) {
            $errores[] = "La nueva contraseña debe tener al menos 6 caracteres";
        } elseif ($nueva_contrasena !== $confirmar_contrasena) {
            $errores[] = "Las contraseñas no coinciden";
        }
    }
    
    if (empty($errores)) {
        try {
            $pdo->beginTransaction();
            
            if (!empty($nueva_contrasena)) {
                // Actualizar con nueva contraseña
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, contrasena = ? WHERE id_usuario = ?");
                $contrasena_hash = password_hash($nueva_contrasena, PASSWORD_DEFAULT);
                $stmt->execute([$nombre, $email, $contrasena_hash, $usuario_id]);
                
                // Cerrar todas las sesiones excepto la actual
                session_regenerate_id(true);
            } else {
                // Actualizar sin cambiar la contraseña
                $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ? WHERE id_usuario = ?");
                $stmt->execute([$nombre, $email, $usuario_id]);
            }
            
            $pdo->commit();
            
            // Actualizar datos de sesión
            $_SESSION['nombre'] = $nombre;
            $_SESSION['email'] = $email;
            
            $_SESSION['success'] = "Perfil actualizado correctamente";
            header("Location: perfil.php");
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $errores[] = "Error al actualizar el perfil: " . $e->getMessage();
        }
    }
}

// Obtener datos actuales del usuario
$stmt = $pdo->prepare("SELECT nombre, email FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$usuario_id]);
$usuario = $stmt->fetch();
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Mi Perfil</h4>
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
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre Completo</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   value="<?php echo htmlspecialchars($usuario['nombre']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo Electrónico</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
                        </div>
                        
                        <hr class="my-4">
                        <h5 class="mb-3">Cambiar Contraseña</h5>
                        <p class="text-muted small">Deja estos campos en blanco si no deseas cambiar la contraseña.</p>
                        
                        <div class="mb-3">
                            <label for="contrasena_actual" class="form-label">Contraseña Actual</label>
                            <input type="password" class="form-control" id="contrasena_actual" name="contrasena_actual">
                        </div>
                        
                        <div class="mb-3">
                            <label for="nueva_contrasena" class="form-label">Nueva Contraseña</label>
                            <input type="password" class="form-control" id="nueva_contrasena" name="nueva_contrasena">
                            <div class="form-text">Mínimo 6 caracteres</div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirmar_contrasena" class="form-label">Confirmar Nueva Contraseña</label>
                            <input type="password" class="form-control" id="confirmar_contrasena" name="confirmar_contrasena">
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Volver al Panel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save me-1"></i> Guardar Cambios
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
