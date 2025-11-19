<?php
$titulo_pagina = "Configuración del Sistema";
require_once 'includes/header.php';

// Verificar permisos de administrador
if ($_SESSION['rol'] !== 'Administrador') {
    $_SESSION['error'] = 'No tiene permisos para acceder a esta sección';
    header('Location: dashboard.php');
    exit();
}

$pdo = conectarDB();
$configuraciones = [];

// Obtener configuraciones actuales
try {
    $stmt = $pdo->query("SELECT * FROM configuracion");
    while ($row = $stmt->fetch()) {
        $configuraciones[$row['clave']] = $row['valor'];
    }
} catch (PDOException $e) {
    // Si la tabla no existe, la creamos
    $pdo->exec("CREATE TABLE IF NOT EXISTS configuracion (
        id INT AUTO_INCREMENT PRIMARY KEY,
        clave VARCHAR(50) NOT NULL UNIQUE,
        valor TEXT,
        descripcion TEXT,
        fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    
    // Insertar configuraciones por defecto
    $configs = [
        ['nombre_sistema', 'Sistema de Tickets', 'Nombre del sistema que se muestra en el encabezado'],
        ['items_por_pagina', '10', 'Número de ítems a mostrar por página en listados'],
        ['notificaciones_activas', '1', 'Habilitar notificaciones por correo electrónico'],
        ['email_contacto', 'soporte@ejemplo.com', 'Correo electrónico de contacto para soporte'],
        ['mantenimiento', '0', 'Modo mantenimiento (1 = activo, 0 = inactivo)']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO configuracion (clave, valor, descripcion) VALUES (?, ?, ?)");
    foreach ($configs as $config) {
        $stmt->execute($config);
    }
    
    // Volver a cargar las configuraciones
    $stmt = $pdo->query("SELECT * FROM configuracion");
    while ($row = $stmt->fetch()) {
        $configuraciones[$row['clave']] = $row['valor'];
    }
}

// Procesar el formulario de configuración
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("UPDATE configuracion SET valor = ? WHERE clave = ?");
        
        foreach ($_POST['config'] as $clave => $valor) {
            $valor = trim($valor);
            $stmt->execute([$valor, $clave]);
            $configuraciones[$clave] = $valor;
        }
        
        $pdo->commit();
        $_SESSION['success'] = "Configuración actualizada correctamente";
        
        // Actualizar la configuración en la sesión si es necesario
        if (isset($configuraciones['nombre_sistema'])) {
            $_SESSION['nombre_sistema'] = $configuraciones['nombre_sistema'];
        }
        
        header("Location: configuracion.php");
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error al actualizar la configuración: " . $e->getMessage();
    }
}
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Configuración del Sistema</h1>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i> 
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="nombre_sistema" class="form-label">Nombre del Sistema</label>
                            <input type="text" class="form-control" id="nombre_sistema" 
                                   name="config[nombre_sistema]" 
                                   value="<?php echo htmlspecialchars($configuraciones['nombre_sistema'] ?? 'Sistema de Tickets'); ?>" 
                                   required>
                            <div class="form-text">Este nombre aparecerá en el encabezado del sistema.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="items_por_pagina" class="form-label">Ítems por Página</label>
                            <select class="form-select" id="items_por_pagina" name="config[items_por_pagina]">
                                <option value="5" <?php echo ($configuraciones['items_por_pagina'] ?? '10') == '5' ? 'selected' : ''; ?>>5</option>
                                <option value="10" <?php echo ($configuraciones['items_por_pagina'] ?? '10') == '10' ? 'selected' : ''; ?>>10</option>
                                <option value="25" <?php echo ($configuraciones['items_por_pagina'] ?? '10') == '25' ? 'selected' : ''; ?>>25</option>
                                <option value="50" <?php echo ($configuraciones['items_por_pagina'] ?? '10') == '50' ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo ($configuraciones['items_por_pagina'] ?? '10') == '100' ? 'selected' : ''; ?>>100</option>
                            </select>
                            <div class="form-text">Número de elementos a mostrar en las listas.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Notificaciones por Correo</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="notificaciones_activas" 
                                       name="config[notificaciones_activas]" value="1"
                                       <?php echo ($configuraciones['notificaciones_activas'] ?? '1') == '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="notificaciones_activas">
                                    Activar notificaciones por correo electrónico
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email_contacto" class="form-label">Correo de Contacto</label>
                            <input type="email" class="form-control" id="email_contacto" 
                                   name="config[email_contacto]" 
                                   value="<?php echo htmlspecialchars($configuraciones['email_contacto'] ?? 'soporte@ejemplo.com'); ?>" 
                                   required>
                            <div class="form-text">Correo electrónico para notificaciones y contacto.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Modo Mantenimiento</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="mantenimiento" 
                                       name="config[mantenimiento]" value="1"
                                       <?php echo ($configuraciones['mantenimiento'] ?? '0') == '1' ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="mantenimiento">
                                    Activar modo mantenimiento (solo accesible para administradores)
                                </label>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Guardar Configuración
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i> Información del Sistema
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Versión PHP
                            <span class="badge bg-primary rounded-pill"><?php echo phpversion(); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Versión MySQL
                            <span class="badge bg-primary rounded-pill">
                                <?php 
                                $version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
                                echo $version ?: 'No disponible';
                                ?>
                            </span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Servidor Web
                            <span class="badge bg-primary rounded-pill"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'No disponible'; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Sistema Operativo
                            <span class="badge bg-primary rounded-pill"><?php echo php_uname('s') . ' ' . php_uname('r'); ?></span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <i class="bi bi-shield-lock"></i> Seguridad
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="backup.php" class="btn btn-outline-primary">
                            <i class="bi bi-download"></i> Crear Respaldo
                        </a>
                        <a href="logs.php" class="btn btn-outline-secondary">
                            <i class="bi bi-journal-text"></i> Ver Registros del Sistema
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
