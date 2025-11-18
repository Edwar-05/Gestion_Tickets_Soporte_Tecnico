<?php
require_once 'includes/header.php';

// Verificar si se proporcionó un ID de archivo
$archivo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$archivo_id) {
    $_SESSION['error'] = 'Archivo no especificado';
    header('Location: tickets.php');
    exit();
}

try {
    // Obtener información del archivo
    $stmt = $pdo->prepare("SELECT a.*, t.id_usuario 
                          FROM archivos_tickets a 
                          JOIN tickets t ON a.id_ticket = t.id_ticket 
                          WHERE a.id = ? AND t.id_usuario = ?");
    $stmt->execute([$archivo_id, $usuario_id]);
    $archivo = $stmt->fetch();

    // Verificar si el archivo existe y pertenece al usuario
    if (!$archivo) {
        throw new Exception('Archivo no encontrado o no tienes permiso para eliminarlo');
    }

    // Ruta completa del archivo
    $ruta_completa = ROOT_PATH . '/' . $archivo['ruta_archivo'];
    
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // Eliminar el registro de la base de datos
    $stmt = $pdo->prepare("DELETE FROM archivos_tickets WHERE id = ?");
    $stmt->execute([$archivo_id]);
    
    // Eliminar el archivo físico
    if (file_exists($ruta_completa)) {
        unlink($ruta_completa);
    }
    
    $pdo->commit();
    
    $_SESSION['success'] = 'Archivo eliminado correctamente';
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'Error al eliminar el archivo: ' . $e->getMessage();
}

// Redirigir de vuelta al ticket
header('Location: ' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'tickets.php'));
exit();
