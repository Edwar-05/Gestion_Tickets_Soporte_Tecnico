<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir configuración
require_once '../includes/config.php';

// Verificar que sea una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = 'Acceso no autorizado';
    header('Location: usuarios.php');
    exit();
}

// Verificar que el usuario sea administrador
$sessionRole = $_SESSION['rol'] ?? null;
if ($sessionRole !== 'Administrador') {
    $_SESSION['error'] = 'No tiene permisos para realizar esta acción';
    header('Location: dashboard.php');
    exit();
}

// Obtener conexión a la base de datos
$pdo = conectarDB();

// Obtener y validar datos del formulario
$accion = $_POST['accion'] ?? '';
$id_usuario = isset($_POST['id_usuario']) ? (int)$_POST['id_usuario'] : 0;
$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? '');
$contrasena = trim($_POST['contrasena'] ?? '');

// Mapear el rol al ID correspondiente
$roles = [
    'Administrador' => 1,
    'Usuario' => 2,
    'admin' => 1,
    'usuario' => 2
];
    
$rol = $_POST['rol'] ?? 'Usuario';
$id_rol = $roles[$rol] ?? 2; // Por defecto Usuario (ID 2)
$activo = 1; // Siempre activo por defecto

// Validaciones básicas
$errores = [];

if (empty($nombre)) {
    $errores[] = 'El nombre es obligatorio';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errores[] = 'El correo electrónico no es válido';
}

// Validar contraseña solo para nuevo usuario o si se está cambiando
if (($accion === 'crear' && empty($contrasena)) || 
    ($accion === 'editar' && !empty($contrasena) && strlen($contrasena) < 6)) {
    $errores[] = 'La contraseña debe tener al menos 6 caracteres';
}

// Verificar si el correo ya existe
$stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ?");
$stmt->execute([$email, $id_usuario]);
if ($stmt->fetch()) {
    $errores[] = 'El correo electrónico ya está registrado';
}

// Si hay errores, redirigir de vuelta
if (!empty($errores)) {
    $_SESSION['error'] = implode('<br>', $errores);
    $_SESSION['form_data'] = $_POST;
    header('Location: usuarios.php');
    exit();
}

try {
    $pdo->beginTransaction();
    
    if ($accion === 'crear') {
        // Verificar si el correo ya existe
        $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            throw new Exception('El correo electrónico ya está registrado');
        }

        // Insertar nuevo usuario
        $stmt = $pdo->prepare("INSERT INTO usuarios (nombre, email, contrasena, id_rol, activo, fecha_creacion) 
                             VALUES (?, ?, ?, ?, ?, NOW())");
        $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);
        $stmt->execute([$nombre, $email, $contrasena_hash, $id_rol, $activo]);
        $mensaje = 'Usuario creado correctamente';
    } else {
        // Actualizar usuario existente
        $id = (int)$_POST['id_usuario'];
        
        // Verificar si el correo ya existe (excluyendo el usuario actual)
        $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ? AND id_usuario != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->rowCount() > 0) {
            throw new Exception('El correo electrónico ya está registrado por otro usuario');
        }
        
        if (!empty($contrasena)) {
            // Si se proporcionó una nueva contraseña
            $stmt = $pdo->prepare("UPDATE usuarios 
                                 SET nombre = ?, email = ?, contrasena = ?, id_rol = ?, activo = ? 
                                 WHERE id_usuario = ?");
            $contrasena_hash = password_hash($contrasena, PASSWORD_DEFAULT);
            $stmt->execute([$nombre, $email, $contrasena_hash, $id_rol, $activo, $id]);
        } else {
            // Si no se cambió la contraseña
            $stmt = $pdo->prepare("UPDATE usuarios 
                                 SET nombre = ?, email = ?, id_rol = ?, activo = ? 
                                 WHERE id_usuario = ?");
            $stmt->execute([$nombre, $email, $id_rol, $activo, $id]);
        }
        $mensaje = 'Usuario actualizado correctamente';
    }
    
    $pdo->commit();
    $_SESSION['success'] = $mensaje;
} catch (PDOException $e) {
    $pdo->rollBack();
    $_SESSION['error'] = 'Error al guardar el usuario: ' . $e->getMessage();
}

header('Location: usuarios.php');
exit();
