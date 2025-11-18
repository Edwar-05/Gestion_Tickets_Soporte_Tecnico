<?php
session_start();
require_once 'includes/config.php';

// Si el usuario ya está logueado, redirigir según su rol
if (isset($_SESSION['usuario_id'])) {
    if ($_SESSION['rol'] === 'Administrador') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: usuario/dashboard.php');
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .login-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            max-width: 420px;
            width: 100%;
            transition: all 0.3s ease;
        }
        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        .login-header {
            background-color: #F54927;
            color: white;
            text-align: center;
            padding: 30px 20px;
        }
        .login-header i {
            font-size: 2.5rem;
            margin-bottom: 10px;
            display: block;
        }
        .login-body {
            padding: 30px;
            background: white;
        }
        .form-control {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
        }
        .form-control:focus {
            border-color: #F54927;
            box-shadow: 0 0 0 0.25rem rgba(245, 73, 39, 0.25);
        }
        .btn-login {
            background-color: #F54927;
            border: none;
            padding: 12px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .btn-login:hover {
            background-color: #e03f1f;
            transform: translateY(-2px);
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
        }
        .password-input-group {
            position: relative;
        }
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="bi bi-shield-lock"></i>
                <h2 class="h4 mb-0">Iniciar Sesión</h2>
                <p class="mb-0">Ingresa tus credenciales para continuar</p>
            </div>
            <div class="login-body">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <form action="login.php" method="POST" class="needs-validation" novalidate>
                    <div class="mb-4">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="tu@ejemplo.com" required>
                        </div>
                        <div class="invalid-feedback">
                            Por favor ingresa un correo electrónico válido.
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="contrasena" class="form-label">Contraseña</label>
                        <div class="password-input-group">
                            <input type="password" class="form-control" id="contrasena" name="contrasena" placeholder="••••••••" required>
                            <button type="button" class="password-toggle" id="togglePassword">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="invalid-feedback">
                            Por favor ingresa tu contraseña.
                        </div>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-login">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Iniciar Sesión
                        </button>
                    </div>
                    
                    <div class="text-center mt-3">
                        <a href="#" class="text-decoration-none text-muted small">¿Olvidaste tu contraseña?</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mostrar/ocultar contraseña
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('contrasena');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        });

        // Validación de formulario
        (function () {
            'use strict'
            
            // Obtener todos los formularios a los que queremos aplicar estilos de validación de Bootstrap personalizados
            var forms = document.querySelectorAll('.needs-validation')
            
            // Bucle sobre ellos y evitar el envío
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>
