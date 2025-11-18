<?php
// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Cambiar por tu usuario de MySQL
define('DB_PASS', '');     // Cambiar por tu contraseña de MySQL
define('DB_NAME', 'sistema_tickets_itca');

// Configuración de la aplicación
define('SITE_NAME', 'Sistema de Tickets - ITCA FEPADE');
define('SITE_URL', 'http://' . $_SERVER['HTTP_HOST'] . '/NOSEEEE');
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . '/NOSEEEE');

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Función para conectar a la base de datos
function conectarDB() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Error de conexión: " . $e->getMessage());
    }
}

// Función para verificar si el usuario está logueado
function estaLogueado() {
    return isset($_SESSION['usuario_id']);
}

// Función para verificar si el usuario es administrador
function esAdmin() {
    return isset($_SESSION['rol']) && $_SESSION['rol'] === 'Administrador';
}

// Función para redireccionar
function redireccionar($ruta) {
    header('Location: ' . SITE_URL . '/' . $ruta);
    exit();
}

// Función para mostrar mensajes
function mostrarMensaje($tipo, $mensaje) {
    $_SESSION[$tipo] = $mensaje;
}

// Función para obtener mensajes
function obtenerMensaje($tipo) {
    if (isset($_SESSION[$tipo])) {
        $mensaje = $_SESSION[$tipo];
        unset($_SESSION[$tipo]);
        return $mensaje;
    }
    return '';
}
