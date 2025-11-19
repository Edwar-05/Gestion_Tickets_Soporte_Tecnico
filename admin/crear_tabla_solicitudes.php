<?php
require_once '../includes/config.php';

// Verificar si es administrador
if (!esAdmin()) {
    header('Location: ../login.php');
    exit;
}

$titulo_pagina = "Crear Tabla de Solicitudes de Software";
require_once 'includes/header.php';

try {
    $pdo = conectarDB();
    
    // Crear tabla de solicitudes de software
    $pdo->exec("CREATE TABLE IF NOT EXISTS solicitudes_software (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_usuario INT NOT NULL,
        nombre_software VARCHAR(255) NOT NULL,
        descripcion TEXT NOT NULL,
        ciclo_educativo VARCHAR(100) NOT NULL,
        estado ENUM('pendiente', 'en_proceso', 'completado', 'cancelado') DEFAULT 'pendiente',
        link_descarga TEXT NULL,
        observaciones_admin TEXT NULL,
        fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario) ON DELETE CASCADE
    )");
    
    // Crear tabla de archivos adjuntos para solicitudes
    $pdo->exec("CREATE TABLE IF NOT EXISTS archivos_solicitud (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_solicitud INT NOT NULL,
        nombre_archivo VARCHAR(255) NOT NULL,
        ruta_archivo VARCHAR(500) NOT NULL,
        tipo_archivo VARCHAR(50) NOT NULL,
        tamano_archivo INT NOT NULL,
        fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (id_solicitud) REFERENCES solicitudes_software(id) ON DELETE CASCADE
    )");
    
    echo "<div class='alert alert-success'>";
    echo "<h4><i class='bi bi-check-circle me-2'></i>Tablas creadas correctamente</h4>";
    echo "<p>Se han creado las tablas 'solicitudes_software' y 'archivos_solicitud' correctamente.</p>";
    echo "<a href='dashboard.php' class='btn btn-primary mt-2'>Ir al Dashboard</a>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>";
    echo "<h4><i class='bi bi-exclamation-triangle me-2'></i>Error al crear tablas</h4>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

require_once 'includes/footer.php';
?>
