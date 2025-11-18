<?php
// Habilitar el buffer de salida
if (ob_get_level() == 0) {
    ob_start();
}

require_once 'includes/header.php';

// Verificar si se recibió una acción
if (!isset($_POST['accion']) || !isset($_POST['id_ticket'])) {
    $_SESSION['error'] = 'Acción no válida';
    header('Location: tickets.php');
    exit();
}

$accion = $_POST['accion'];
$ticket_id = (int)$_POST['id_ticket'];

// Obtener información del ticket
$stmt = $pdo->prepare("SELECT *, TIMESTAMPDIFF(MINUTE, fecha_creacion, NOW()) as minutos_transcurridos 
                      FROM tickets WHERE id_ticket = ? AND id_usuario = ?");
$stmt->execute([$ticket_id, $usuario_id]);
$ticket = $stmt->fetch();

// Verificar si el ticket existe y pertenece al usuario
if (!$ticket) {
    $_SESSION['error'] = 'Ticket no encontrado o no tienes permiso para realizar esta acción';
    header('Location: tickets.php');
    exit();
}

// Procesar la acción solicitada
switch ($accion) {
    case 'eliminar':
        // Solo se pueden eliminar tickets cancelados
        if ($ticket['estado'] === 'Cancelado') {
            try {
                $pdo->beginTransaction();
                
                // Eliminar respuestas asociadas
                $stmt = $pdo->prepare("DELETE FROM respuestas WHERE id_ticket = ?");
                $stmt->execute([$ticket_id]);
                
                // Eliminar archivos asociados
                $stmt = $pdo->prepare("DELETE FROM archivos_tickets WHERE id_ticket = ?");
                $stmt->execute([$ticket_id]);
                
                // Eliminar el ticket
                $stmt = $pdo->prepare("DELETE FROM tickets WHERE id_ticket = ?");
                $stmt->execute([$ticket_id]);
                
                $pdo->commit();
                
                $_SESSION['success'] = 'Ticket eliminado correctamente';
                header("Location: tickets.php");
                exit();
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['error'] = 'Error al eliminar el ticket: ' . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = 'Solo se pueden eliminar tickets cancelados';
        }
        break;
        
    case 'reabrir':
        // Solo el administrador puede reabrir tickets
        if (esAdmin()) {
            $stmt = $pdo->prepare("UPDATE tickets SET estado = 'Abierto', fecha_actualizacion = NOW() WHERE id_ticket = ?");
            $stmt->execute([$ticket_id]);
            
            // Registrar la acción en el historial
            $stmt = $pdo->prepare("INSERT INTO historial_tickets (id_ticket, id_usuario, accion, descripcion) 
                                 VALUES (?, ?, 'Reapertura', 'El ticket fue reabierto')");
            $stmt->execute([$ticket_id, $usuario_id]);
            
            $_SESSION['success'] = 'Ticket reabierto correctamente';
        } else {
            $_SESSION['error'] = 'No tienes permiso para realizar esta acción';
        }
        break;
        
    case 'cambiar_estado':
        // Solo para administradores
        if (esAdmin() && isset($_POST['estado'])) {
            $nuevo_estado = $_POST['estado'];
            $estados_permitidos = ['Abierto', 'En Proceso', 'En Espera', 'Resuelto', 'Cerrado'];
            
            if (in_array($nuevo_estado, $estados_permitidos)) {
                $stmt = $pdo->prepare("UPDATE tickets SET estado = ?, fecha_actualizacion = NOW() WHERE id_ticket = ?");
                $stmt->execute([$nuevo_estado, $ticket_id]);
                
                // Registrar la acción en el historial
                $stmt = $pdo->prepare("INSERT INTO historial_tickets (id_ticket, id_usuario, accion, descripcion) 
                                     VALUES (?, ?, 'Cambio de estado', ?)");
                $stmt->execute([$ticket_id, $usuario_id, "Estado cambiado a: $nuevo_estado"]);
                
                $_SESSION['success'] = 'Estado del ticket actualizado correctamente';
            } else {
                $_SESSION['error'] = 'Estado no válido';
            }
        } else {
            $_SESSION['error'] = 'No tienes permiso para realizar esta acción';
        }
        break;
        
    case 'reasignar':
        // Solo para administradores
        if (esAdmin() && isset($_POST['id_usuario'])) {
            $nuevo_usuario = (int)$_POST['id_usuario'];
            
            // Verificar que el usuario de reasignación existe
            $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE id_usuario = ?");
            $stmt->execute([$nuevo_usuario]);
            
            if ($stmt->fetch()) {
                $stmt = $pdo->prepare("UPDATE tickets SET id_usuario = ?, fecha_actualizacion = NOW() WHERE id_ticket = ?");
                $stmt->execute([$nuevo_usuario, $ticket_id]);
                
                // Registrar la acción en el historial
                $stmt = $pdo->prepare("SELECT nombre FROM usuarios WHERE id_usuario = ?");
                $stmt->execute([$nuevo_usuario]);
                $nuevo_usuario_nombre = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare("INSERT INTO historial_tickets (id_ticket, id_usuario, accion, descripcion) 
                                     VALUES (?, ?, 'Reasignación', ?)");
                $stmt->execute([$ticket_id, $usuario_id, "Ticket reasignado a: $nuevo_usuario_nombre"]);
                
                $_SESSION['success'] = 'Ticket reasignado correctamente';
            } else {
                $_SESSION['error'] = 'Usuario de reasignación no válido';
            }
        } else {
            $_SESSION['error'] = 'No tienes permiso para realizar esta acción';
        }
        break;
        
    default:
        $_SESSION['error'] = 'Acción no reconocida';
}

// Redirigir de vuelta al ticket
header("Location: ver_ticket.php?id=$ticket_id");
exit();
