<?php
require_once 'includes/header.php';

header('Content-Type: application/json');

// Verificar si la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit();
}

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit();
}

// Obtener y validar los datos del formulario
$ticket_id = isset($_POST['id_ticket']) ? (int)$_POST['id_ticket'] : 0;
$nuevo_estado = isset($_POST['nuevo_estado']) ? trim($_POST['nuevo_estado']) : '';

// Validar que el estado sea uno de los permitidos
$estados_permitidos = ['Abierto', 'En progreso', 'En espera', 'Resuelto', 'Cerrado'];
if (!in_array($nuevo_estado, $estados_permitidos)) {
    echo json_encode(['success' => false, 'error' => 'Estado no válido']);
    exit();
}

try {
    // Iniciar transacción
    $pdo->beginTransaction();
    
    // 1. Obtener el estado actual del ticket
    $stmt = $pdo->prepare("SELECT estado FROM tickets WHERE id_ticket = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();
    
    if (!$ticket) {
        throw new Exception('Ticket no encontrado');
    }
    
    $estado_anterior = $ticket['estado'];
    
    // 2. Actualizar el estado del ticket
    $stmt = $pdo->prepare("UPDATE tickets SET estado = ?, fecha_actualizacion = NOW() WHERE id_ticket = ?");
    $stmt->execute([$nuevo_estado, $ticket_id]);
    
    // 3. Registrar el cambio en el historial
    $accion = 'cambio_estado';
    $detalle = "Cambio de estado: " . strtolower($estado_anterior) . " → " . strtolower($nuevo_estado);
    
    $stmt = $pdo->prepare("INSERT INTO historial_tickets (id_ticket, id_usuario, accion, detalle) VALUES (?, ?, ?, ?)");
    $stmt->execute([$ticket_id, $usuario_id, $accion, $detalle]);
    
    // 4. Si se marca como resuelto, registrar quién lo resolvió
    if ($nuevo_estado === 'Resuelto') {
        $stmt = $pdo->prepare("UPDATE tickets SET resuelto_por = ?, fecha_resolucion = NOW() WHERE id_ticket = ?");
        $stmt->execute([$usuario_id, $ticket_id]);
        
        // Registrar en el historial
        $stmt = $pdo->prepare("INSERT INTO historial_tickets (id_ticket, id_usuario, accion, detalle) VALUES (?, ?, 'resolucion', 'Ticket marcado como resuelto')");
        $stmt->execute([$ticket_id, $usuario_id]);
    }
    
    // 5. Si se cierra el ticket, registrar la fecha de cierre
    if ($nuevo_estado === 'Cerrado') {
        $stmt = $pdo->prepare("UPDATE tickets SET cerrado_por = ?, fecha_cierre = NOW() WHERE id_ticket = ?");
        $stmt->execute([$usuario_id, $ticket_id]);
        
        // Registrar en el historial
        $stmt = $pdo->prepare("INSERT INTO historial_tickets (id_ticket, id_usuario, accion, detalle) VALUES (?, ?, 'cierre', 'Ticket cerrado')");
        $stmt->execute([$ticket_id, $usuario_id]);
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log('Error al cambiar estado del ticket: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Error al actualizar el estado del ticket']);
}
