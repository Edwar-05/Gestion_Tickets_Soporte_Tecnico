<?php
session_start();
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    if (empty($email) || empty($contrasena)) {
        $_SESSION['error'] = 'Por favor ingrese su correo y contraseña';
        header('Location: index.php');
        exit();
    }

    try {
        $pdo = conectarDB();
        $stmt = $pdo->prepare(
            "SELECT u.*, r.nombre_rol as rol 
             FROM usuarios u 
             INNER JOIN roles r ON u.id_rol = r.id_rol 
             WHERE email = ? 
             LIMIT 1"
        );
        $stmt->execute([$email]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$usuario) {
            $_SESSION['error'] = 'Credenciales incorrectas';
            header('Location: index.php');
            exit();
        }

        $hashEnBD = $usuario['contrasena'];

        $isHashed = is_string($hashEnBD) && (strpos($hashEnBD, '$2y$') === 0 || strpos($hashEnBD, '$2a$') === 0);

        if ($isHashed) {
            // Comprobar con password_verify
            if (!password_verify($contrasena, $hashEnBD)) {
                $_SESSION['error'] = 'Credenciales incorrectas';
                header('Location: index.php');
                exit();
            }

            // Opcional: rehash si el algoritmo/params cambiaron
            if (password_needs_rehash($hashEnBD, PASSWORD_DEFAULT)) {
                $nuevoHash = password_hash($contrasena, PASSWORD_DEFAULT);
                $upd = $pdo->prepare("UPDATE usuarios SET contrasena = :h WHERE id_usuario = :id");
                $upd->execute([':h' => $nuevoHash, ':id' => $usuario['id_usuario']]);
            }
        } else {
            // Compatibilidad: si la BD tiene texto plano (no recomendado)
            if ($contrasena !== $hashEnBD) {
                $_SESSION['error'] = 'Credenciales incorrectas';
                header('Location: index.php');
                exit();
            }
            // Re-hashear la contraseña plana para mejorar seguridad
            $nuevoHash = password_hash($contrasena, PASSWORD_DEFAULT);
            $upd = $pdo->prepare("UPDATE usuarios SET contrasena = :h WHERE id_usuario = :id");
            $upd->execute([':h' => $nuevoHash, ':id' => $usuario['id_usuario']]);
        }

        // Si llegamos aquí, login exitoso
        $_SESSION['usuario_id'] = $usuario['id_usuario'];
        $_SESSION['nombre'] = $usuario['nombre'];
        $_SESSION['email'] = $usuario['email'];
        $_SESSION['rol'] = $usuario['rol'];

        if ($usuario['rol'] === 'Administrador') {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: usuario/dashboard.php');
        }
        exit();

    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error al iniciar sesión';
        header('Location: index.php');
        exit();
    }
} else {
    header('Location: index.php');
    exit();
}
