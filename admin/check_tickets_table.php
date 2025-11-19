<?php
require_once '../includes/config.php';

try {
    $pdo = conectarDB();
    
    // Check if the tickets table exists and get its structure
    $stmt = $pdo->query("SHOW COLUMNS FROM tickets");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Estructura de la tabla 'tickets':</h2>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Valor por defecto</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . ($column['Default'] !== null ? htmlspecialchars($column['Default']) : 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Check if asignado_a column exists
    $asignado_a_exists = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'asignado_a') {
            $asignado_a_exists = true;
            break;
        }
    }
    
    if (!$asignado_a_exists) {
        echo "<h3>La columna 'asignado_a' no existe en la tabla 'tickets'.</h3>";
        echo "<p>Se requiere agregar esta columna para la funcionalidad de asignación de tickets.</p>";
    }
    
    // Check foreign key constraint for asignado_a
    $stmt = $pdo->query("
        SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_SCHEMA = 'sistema_tickets_itca' 
        AND TABLE_NAME = 'tickets' 
        AND COLUMN_NAME = 'asignado_a'");
    $foreignKey = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$foreignKey) {
        echo "<h3>No existe una restricción de clave foránea para 'asignado_a'.</h3>";
    } else {
        echo "<h3>Restricción de clave foránea para 'asignado_a':</h3>";
        echo "<pre>" . print_r($foreignKey, true) . "</pre>";
    }
    
    // Show SQL to fix the issue
    echo "<h3>Para solucionar el problema, ejecuta el siguiente SQL:</h3>";
    echo "<pre>";
    echo "-- Agregar la columna asignado_a si no existe\n";
    echo "ALTER TABLE tickets ADD COLUMN IF NOT EXISTS asignado_a INT NULL AFTER id_usuario;\n\n";
    echo "-- Agregar la restricción de clave foránea\n";
    echo "ALTER TABLE tickets ADD CONSTRAINT fk_tickets_asignado_a\n";
    echo "FOREIGN KEY (asignado_a) REFERENCES usuarios(id_usuario) ON DELETE SET NULL;\n";
    echo "</pre>";
    
} catch (PDOException $e) {
    echo "<h2>Error al conectar a la base de datos:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
