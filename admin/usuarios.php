<?php
// admin/usuarios.php

// Buffer (opcional, si tu header.php imprime antes de redirects)
ob_start();

// Asegurar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper: conecta a la BD (usa tu función existente)
require_once 'includes/header.php'; // si aquí obtienes $pdo o la función conectarDB()
if (!function_exists('conectarDB') && !isset($pdo)) {
    // intenta cargar tu config/database o el archivo que exponga conectarDB()
    require_once __DIR__ . '/../includes/config.php'; // ajusta si tu ruta es diferente
}

// Obtener $pdo (intenta varias formas)
if (!isset($pdo)) {
    if (function_exists('conectarDB')) {
        $pdo = conectarDB();
    } else {
        // Si tu header.php ya provee $pdo, esto no hará nada.
        // Si no, intenta conectarte por defecto (ajusta credenciales si hace falta)
        try {
            $pdo = new PDO("mysql:host=localhost;dbname=sistema_tickets_itca;charset=utf8mb4", "root", "");
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (Exception $e) {
            die("Error de conexión: " . $e->getMessage());
        }
    }
}

// --- NORMALIZAR SESIÓN (buscar varios nombres posibles)
$sessionUserId = $_SESSION['usuario_id'] ?? $_SESSION['user_id'] ?? $_SESSION['id_usuario'] ?? $_SESSION['id'] ?? null;
$sessionRole   = $_SESSION['rol'] ?? $_SESSION['role'] ?? $_SESSION['user_role'] ?? $_SESSION['user_rol'] ?? null;

// Normalizar rol en minúsculas para comparación segura
$sessionRoleLower = is_string($sessionRole) ? strtolower($sessionRole) : '';

// Permiso: usuario administrador (acepta 'administrador' o 'admin')
if ($sessionRoleLower !== 'administrador' && $sessionRoleLower !== 'admin') {
    $_SESSION['error'] = 'No tiene permisos para acceder a esta sección';
    header('Location: dashboard.php');
    exit();
}

// Helper: comprobar si una columna existe en la tabla usuarios
function columnExists(PDO $pdo, string $col): bool {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM usuarios LIKE :col");
    $stmt->execute([':col' => $col]);
    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

// Detectar nombres de columnas reales y mapear a alias
$pk_col     = columnExists($pdo, 'id_usuario') ? 'id_usuario' : (columnExists($pdo, 'id') ? 'id' : 'id_usuario');
$name_col   = columnExists($pdo, 'nombre') ? 'nombre' : (columnExists($pdo, 'nombre_completo') ? 'nombre_completo' : 'nombre');
$email_col  = columnExists($pdo, 'email') ? 'email' : (columnExists($pdo, 'correo') ? 'correo' : 'email');
$role_col   = columnExists($pdo, 'rol') ? 'rol' : (columnExists($pdo, 'role') ? 'role' : null);
$active_col = columnExists($pdo, 'activo') ? 'activo' : (columnExists($pdo, 'active') ? 'active' : null);

// Construir SELECT con ALIAS constantes para usar en el template
$selectFields = [
    "$pk_col AS id_user",
    "$name_col AS nombre_user",
    "$email_col AS email_user",
];

if ($role_col) {
    $selectFields[] = "$role_col AS rol_user";
} else {
    // si no existe rol lo ponemos como literal 'Usuario' para evitar NULLs
    $selectFields[] = "'Usuario' AS rol_user";
}

if ($active_col) {
    $selectFields[] = "$active_col AS activo_user";
} else {
    $selectFields[] = "1 AS activo_user"; // por defecto activo
}

$selectSql = "SELECT " . implode(", ", $selectFields) . " FROM usuarios";

// Procesar eliminación de usuario (segura)
if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    try {
        $id_eliminar = (int)$_GET['eliminar'];
        // Proteger: no permitir eliminar a sí mismo (usar sessionUserId normalizado)
        if ($sessionUserId !== null && (int)$sessionUserId === $id_eliminar) {
            $_SESSION['error'] = 'No puede eliminar su propio usuario';
            header('Location: usuarios.php');
            exit();
        }

        $del = $pdo->prepare("DELETE FROM usuarios WHERE $pk_col = :id");
        $del->execute([':id' => $id_eliminar]);

        $_SESSION['success'] = 'Usuario eliminado correctamente';
        header('Location: usuarios.php');
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error al eliminar el usuario: ' . $e->getMessage();
        header('Location: usuarios.php');
        exit();
    }
}

// Obtener lista de usuarios (excluyendo al usuario actual si existe)
$whereExcl = '';
$paramsExcl = [];
if ($sessionUserId !== null) {
    $whereExcl = " WHERE $pk_col != :me ";
    $paramsExcl[':me'] = (int)$sessionUserId;
}
$orderBy = " ORDER BY nombre_user ASC ";
$stmt = $pdo->prepare($selectSql . $whereExcl . $orderBy);
$stmt->execute($paramsExcl);
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$titulo_pagina = "Gestión de Usuarios";
?>

<!-- HTML -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Gestión de Usuarios</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoUsuarioModal">
            <i class="bi bi-person-plus"></i> Nuevo Usuario
        </button>
    </div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($usuarios) > 0): ?>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?= (int)$usuario['id_user']; ?></td>
                                <td><?= htmlspecialchars($usuario['nombre_user']); ?></td>
                                <td><?= htmlspecialchars($usuario['email_user']); ?></td>
                                <td>
                                    <?php $rolMostrar = $usuario['rol_user'] ?? 'Usuario'; ?>
                                    <span class="badge bg-<?= (strtolower($rolMostrar) === 'administrador' || strtolower($rolMostrar) === 'admin') ? 'primary' : 'secondary'; ?>">
                                        <?= htmlspecialchars($rolMostrar); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#editarUsuarioModal" 
                                            data-id="<?= (int)$usuario['id_user']; ?>"
                                            data-nombre="<?= htmlspecialchars($usuario['nombre_user']); ?>"
                                            data-email="<?= htmlspecialchars($usuario['email_user']); ?>"
                                            data-rol="<?= htmlspecialchars($usuario['rol_user']); ?>"
                                            data-activo="<?= $activo; ?>">
                                        <i class="bi bi-pencil"></i> Editar
                                    </button>
                                    <a href="usuarios.php?eliminar=<?= (int)$usuario['id_user']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('¿Está seguro de eliminar este usuario? Esta acción no se puede deshacer.')">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center">No hay usuarios registrados</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nuevo Usuario -->
<div class="modal fade" id="nuevoUsuarioModal" tabindex="-1" aria-labelledby="nuevoUsuarioModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="guardar_usuario.php" method="post" id="formNuevoUsuario">
                <div class="modal-header">
                    <h5 class="modal-title" id="nuevoUsuarioModalLabel">Nuevo Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="crear">
                    
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="contrasena" class="form-label">Contraseña</label>
                        <input type="password" class="form-control" id="contrasena" name="contrasena" required minlength="6">
                        <div class="form-text">Mínimo 6 caracteres</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="rol" class="form-label">Rol</label>
                        <select class="form-select" id="rol" name="rol" required>
                            <option value="Usuario">Usuario</option>
                            <option value="Administrador">Administrador</option>
                        </select>
                    </div>
                    
                    <input type="hidden" name="activo" value="1">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Usuario -->
<div class="modal fade" id="editarUsuarioModal" tabindex="-1" aria-labelledby="editarUsuarioModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="guardar_usuario.php" method="post" id="formEditarUsuario">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarUsuarioModalLabel">Editar Usuario</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="accion" value="editar">
                    <input type="hidden" name="id_usuario" id="editar_id">
                    
                    <div class="mb-3">
                        <label for="editar_nombre" class="form-label">Nombre Completo</label>
                        <input type="text" class="form-control" id="editar_nombre" name="nombre" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editar_email" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="editar_email" name="email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editar_contrasena" class="form-label">Nueva Contraseña</label>
                        <input type="password" class="form-control" id="editar_contrasena" name="contrasena" placeholder="Dejar en blanco para no cambiar">
                        <div class="form-text">Dejar en blanco para no cambiar. Mínimo 6 caracteres.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="editar_rol" class="form-label">Rol</label>
                        <select class="form-select" id="editar_rol" name="rol" required>
                            <option value="Usuario">Usuario</option>
                            <option value="Administrador">Administrador</option>
                        </select>
                    </div>
                    
                    <input type="hidden" name="activo" value="1">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Script para manejar la edición de usuarios (se mantiene igual)
var editarUsuarioModal = document.getElementById('editarUsuarioModal');
if (editarUsuarioModal) {
    editarUsuarioModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var nombre = button.getAttribute('data-nombre');
        var email = button.getAttribute('data-email');
        var rol = button.getAttribute('data-rol');

        document.getElementById('editar_id').value = id;
        document.getElementById('editar_nombre').value = nombre;
        document.getElementById('editar_email').value = email;
        document.getElementById('editar_rol').value = rol;
    });
}
</script>

<?php
// Limpiar buffer
ob_end_flush();
require_once 'includes/footer.php';
