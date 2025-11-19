<?php
require_once 'includes/header.php';

// Verificar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit('Método no permitido');
}

// Validar y obtener los datos del formulario
$ticket_id = isset($_POST['id_ticket']) ? (int)$_POST['id_ticket'] : 0;
$nuevo_estado = isset($_POST['nuevo_estado']) ? trim($_POST['nuevo_estado']) : '';

// Validar los datos
if ($ticket_id <= 0 || empty($nuevo_estado)) {
    $_SESSION['error'] = 'Datos de solicitud no válidos';
    header('Location: tickets.php');
    exit();
}

try {
    // Verificar si el ticket existe y obtener su estado actual
    $stmt = $pdo->prepare("SELECT id_ticket, estado FROM tickets WHERE id_ticket = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        throw new Exception('Ticket no encontrado');
    }

    // Validar la transición de estado
    $estados_permitidos = ['Abierto', 'En Progreso', 'Resuelto', 'Cerrado'];
    if (!in_array($nuevo_estado, $estados_permitidos)) {
        throw new Exception('Estado no válido');
    }

    // Actualizar el estado del ticket
    $stmt = $pdo->prepare("UPDATE tickets SET estado = ?, fecha_actualizacion = NOW() WHERE id_ticket = ?");
    $stmt->execute([$nuevo_estado, $ticket_id]);

    // Registrar el cambio en el historial
    $stmt = $pdo->prepare("INSERT INTO historial_tickets (id_ticket, id_usuario, accion, descripcion) VALUES (?, ?, 'Cambio de estado', ?)");
    $descripcion = "El ticket ha sido cambiado de estado de '{$ticket['estado']}' a '$nuevo_estado'";
    $stmt->execute([$ticket_id, $usuario_id, $descripcion]);

    $_SESSION['success'] = 'El estado del ticket ha sido actualizado correctamente';

} catch (Exception $e) {
    $_SESSION['error'] = 'Error al actualizar el estado del ticket: ' . $e->getMessage();
}

// Redirigir de vuelta a la página de tickets
header('Location: ' . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'tickets.php'));
exit();
?>
