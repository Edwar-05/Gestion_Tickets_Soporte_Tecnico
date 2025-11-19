<?php
require_once '../includes/config.php';

try {
    $pdo = conectarDB();
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // 1. Agregar la columna asignado_a si no existe
    $pdo->exec("ALTER TABLE tickets ADD COLUMN IF NOT EXISTS asignado_a INT NULL AFTER id_usuario");
    
    // 2. Verificar si ya existe la restricción de clave foránea
    $stmt = $pdo->query("
        SELECT COUNT(*) as existe 
        FROM information_schema.TABLE_CONSTRAINTS 
        WHERE CONSTRAINT_SCHEMA = 'sistema_tickets_itca' 
        AND TABLE_NAME = 'tickets' 
        AND CONSTRAINT_NAME = 'fk_tickets_asignado_a'");
    $existe_fk = $stmt->fetch(PDO::FETCH_ASSOC)['existe'] > 0;
    
    // 3. Agregar la restricción de clave foránea si no existe
    if (!$existe_fk) {
        // Primero, asegurarse de que no haya valores en asignado_a que no existan en usuarios.id_usuario
        $pdo->exec("UPDATE tickets SET asignado_a = NULL WHERE asignado_a IS NOT NULL AND asignado_a NOT IN (SELECT id_usuario FROM usuarios)");
        
        // Luego crear la restricción
        $pdo->exec("
            ALTER TABLE tickets 
            ADD CONSTRAINT fk_tickets_asignado_a 
            FOREIGN KEY (asignado_a) 
            REFERENCES usuarios(id_usuario) 
            ON DELETE SET NULL");
    }
    
    // Confirmar los cambios
    $pdo->commit();
    
    echo "<div style='padding: 20px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px;'>";
    echo "<h3>¡Base de datos actualizada correctamente!</h3>";
    echo "<p>Se ha agregado la columna 'asignado_a' a la tabla 'tickets' y configurado la restricción de clave foránea.</p>";
    echo "<p><a href='dashboard.php' class='btn btn-success'>Ir al Panel de Administración</a></p>";
    echo "</div>";
    
} catch (PDOException $e) {
    // Revertir cambios en caso de error
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    echo "<div style='padding: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;'>";
    echo "<h3>Error al actualizar la base de datos:</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    
    // Mostrar información detallada del error
    echo "<h4>Detalles del error:</h4>";
    echo "<pre>" . print_r([
        'code' => $e->getCode(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ], true) . "</pre>";
    
    echo "<p>Por favor, asegúrate de que tienes permisos para modificar la estructura de la base de datos.</p>";
    echo "</div>";
}

// Mostrar el estado actual de la tabla tickets
try {
    echo "<div style='margin: 20px; padding: 15px; background-color: #e2e3e5; border-radius: 5px;'>";
    echo "<h4>Estado actual de la tabla 'tickets':</h4>";
    
    $stmt = $pdo->query("SHOW COLUMNS FROM tickets");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background-color: #6c757d; color: white;'><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Valor por defecto</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr style='background-color: " . ($column['Field'] === 'asignado_a' ? '#d4edda' : 'white') . "'>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . ($column['Default'] !== null ? htmlspecialchars($column['Default']) : 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<p>No se pudo obtener la estructura de la tabla: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
