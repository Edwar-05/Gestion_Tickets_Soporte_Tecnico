<?php
require_once '../includes/config.php';

// Verificar si el usuario está logueado
if (!estaLogueado()) {
    header('Location: ../login.php');
    exit;
}

// Procesar formulario de solicitud
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = conectarDB();
        
        $nombre_software = trim($_POST['nombre_software']);
        $descripcion = trim($_POST['descripcion']);
        $ciclo_educativo = trim($_POST['ciclo_educativo']);
        
        if (empty($nombre_software) || empty($descripcion) || empty($ciclo_educativo)) {
            throw new Exception("Todos los campos son obligatorios");
        }
        
        $stmt = $pdo->prepare("INSERT INTO solicitudes_software (id_usuario, nombre_software, descripcion, ciclo_educativo) VALUES (?, ?, ?, ?)");
        $stmt->execute([$_SESSION['usuario_id'], $nombre_software, $descripcion, $ciclo_educativo]);
        
        $_SESSION['exito'] = 'Solicitud de software enviada correctamente. El administrador la revisará pronto.';
        header('Location: mis_solicitudes.php');
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$titulo_pagina = "Solicitar Software";
require_once 'includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-download me-2"></i>Nueva Solicitud de Software</h5>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="nombre_software" class="form-label">Nombre del Software *</label>
                        <input type="text" class="form-control" id="nombre_software" name="nombre_software" 
                               value="<?php echo isset($_POST['nombre_software']) ? htmlspecialchars($_POST['nombre_software']) : ''; ?>" 
                               placeholder="Ej: AutoCAD, Photoshop, Office 365" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="ciclo_educativo" class="form-label">Ciclo Educativo *</label>
                        <select class="form-select" id="ciclo_educativo" name="ciclo_educativo" required>
                            <option value="">Seleccionar ciclo...</option>
                            <option value="Primer Ciclo" <?php echo (isset($_POST['ciclo_educativo']) && $_POST['ciclo_educativo'] == 'Primer Ciclo') ? 'selected' : ''; ?>>Primer Ciclo</option>
                            <option value="Segundo Ciclo" <?php echo (isset($_POST['ciclo_educativo']) && $_POST['ciclo_educativo'] == 'Segundo Ciclo') ? 'selected' : ''; ?>>Segundo Ciclo</option>
                            <option value="Tercer Ciclo" <?php echo (isset($_POST['ciclo_educativo']) && $_POST['ciclo_educativo'] == 'Tercer Ciclo') ? 'selected' : ''; ?>>Tercer Ciclo</option>
                            <option value="Cuarto Ciclo" <?php echo (isset($_POST['ciclo_educativo']) && $_POST['ciclo_educativo'] == 'Cuarto Ciclo') ? 'selected' : ''; ?>>Cuarto Ciclo</option>
                            <option value="Quinto Ciclo" <?php echo (isset($_POST['ciclo_educativo']) && $_POST['ciclo_educativo'] == 'Quinto Ciclo') ? 'selected' : ''; ?>>Quinto Ciclo</option>
                            <option value="Sexto Ciclo" <?php echo (isset($_POST['ciclo_educativo']) && $_POST['ciclo_educativo'] == 'Sexto Ciclo') ? 'selected' : ''; ?>>Sexto Ciclo</option>
                            <option value="Todos los ciclos" <?php echo (isset($_POST['ciclo_educativo']) && $_POST['ciclo_educativo'] == 'Todos los ciclos') ? 'selected' : ''; ?>>Todos los ciclos</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción de la necesidad *</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="4" 
                                  placeholder="Describe para qué necesitas el software y qué actividades realizarás con él..." required><?php echo isset($_POST['descripcion']) ? htmlspecialchars($_POST['descripcion']) : ''; ?></textarea>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="mis_solicitudes.php" class="btn btn-secondary me-md-2">
                            <i class="bi bi-arrow-left me-2"></i>Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-2"></i>Enviar Solicitud
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Información</h6>
            </div>
            <div class="card-body">
                <p class="card-text">
                    <strong>Proceso de solicitud:</strong>
                </p>
                <ol class="small">
                    <li>Completa el formulario con los datos del software que necesitas</li>
                    <li>El administrador revisará tu solicitud</li>
                    <li>Recibirás una respuesta con el link de descarga si es aprobada</li>
                    <li>Puedes verificar el estado de tus solicitudes en cualquier momento</li>
                </ol>
                <hr>
                <p class="card-text small text-muted">
                    <strong>Tiempo estimado de respuesta:</strong> 24-48 horas hábiles
                </p>
            </div>
        </div>
        
        <div class="card mt-3">
            <div class="card-header bg-warning text-dark">
                <h6 class="mb-0"><i class="bi bi-lightbulb me-2"></i>Consejos</h6>
            </div>
            <div class="card-body">
                <ul class="small">
                    <li>Describe detalladamente el uso que le darás al software</li>
                    <li>Si conoces alguna versión específica, menciónalo</li>
                    <li>Verifica que el software sea para tu ciclo educativo</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
