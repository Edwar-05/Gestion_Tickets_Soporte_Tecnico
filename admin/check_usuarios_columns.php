<?php
require_once '../includes/config.php';

try {
    $pdo = conectarDB();
    
    // Obtener la estructura de la tabla usuarios
    $stmt = $pdo->query("SHOW COLUMNS FROM usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Estructura de la tabla 'usuarios'</h2>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Clave</th><th>Valor por defecto</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (PDOException $e) {
    die("Error al conectar a la base de datos: " . $e->getMessage());
}
?>
