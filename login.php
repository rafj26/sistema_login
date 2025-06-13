<?php
require_once 'config.php';

// Si ya está logueado, redirigir al inicio
if (isset($_SESSION['usuario_id'])) {
    header('Location: inicio.php');
    exit;
}

$error = '';
$mensaje = '';

// Mostrar mensaje de logout exitoso
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $mensaje = 'Sesión cerrada correctamente. ¡Hasta pronto!';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_usuario = trim($_POST['nombre_usuario'] ?? '');
    $contraseña = $_POST['contraseña'] ?? '';
    $recordarme = isset($_POST['recordarme']);
    
    if (empty($nombre_usuario) || empty($contraseña)) {
        $error = 'Por favor, completa todos los campos.';
    } else {
        try {
            // Validar credenciales
            $stmt = $pdo->prepare("SELECT id, nombre_usuario, contraseña, tema_preferido FROM usuarios WHERE nombre_usuario = ?");
            $stmt->execute([$nombre_usuario]);
            $usuario = $stmt->fetch();
            
            if ($usuario && password_verify($contraseña, $usuario['contraseña'])) {
                // Login exitoso
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
                $_SESSION['tema_preferido'] = $usuario['tema_preferido'];
                
                // Si marcó "Recuérdame", crear token simple
                if ($recordarme) {
                    $token = bin2hex(random_bytes(32));
                    $expira_en = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60));
                    
                    // Limpiar tokens antiguos del usuario
                    $stmt = $pdo->prepare("DELETE FROM tokens_recordar WHERE usuario_id = ?");
                    $stmt->execute([$usuario['id']]);
                    
                    // Insertar nuevo token
                    $stmt = $pdo->prepare("INSERT INTO tokens_recordar (usuario_id, token, expira_en) VALUES (?, ?, ?)");
                    $stmt->execute([$usuario['id'], $token, $expira_en]);
                    
                    // Establecer cookie
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/');
                }
                
                header('Location: inicio.php');
                exit;
            } else {
                $error = 'Usuario o contraseña incorrectos.';
            }
        } catch (Exception $e) {
            $error = 'Error de conexión. Inténtalo de nuevo.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Lab9</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: #666;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input[type="text"]:focus,
        .form-group input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            transform: scale(1.2);
        }
        
        .checkbox-group label {
            color: #666;
            cursor: pointer;
        }
        
        .btn-login {
            width: 100%;
            padding: 0.75rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border-left: 4px solid #c33;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border-left: 4px solid #28a745;
        }
        
        .demo-info {
            margin-top: 2rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 5px;
            border-left: 4px solid #28a745;
        }
        
        .demo-info h4 {
            color: #155724;
            margin-bottom: 0.5rem;
        }
        
        .demo-info p {
            color: #155724;
            font-size: 0.9rem;
            margin: 0.25rem 0;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Iniciar Sesión</h1>
            <p>Laboratorio PHP - Cookies y Sesiones</p>
        </div>
        
        <?php if ($mensaje): ?>
            <div class="success"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="nombre_usuario">Nombre de Usuario:</label>
                <input 
                    type="text" 
                    id="nombre_usuario" 
                    name="nombre_usuario" 
                    value="<?php echo htmlspecialchars($_POST['nombre_usuario'] ?? ''); ?>"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="contraseña">Contraseña:</label>
                <input 
                    type="password" 
                    id="contraseña" 
                    name="contraseña" 
                    required
                >
            </div>
            
            <div class="checkbox-group">
                <input 
                    type="checkbox" 
                    id="recordarme" 
                    name="recordarme"
                    <?php echo isset($_POST['recordarme']) ? 'checked' : ''; ?>
                >
                <label for="recordarme">Recuérdame (30 días)</label>
            </div>
            
            <button type="submit" class="btn-login">Iniciar Sesión</button>
        </form>
        
        <div class="demo-info">
            <h4>Usuarios de prueba:</h4>
            <p><strong>Usuario:</strong> admin | <strong>Contraseña:</strong> password</p>
            <p><strong>Usuario:</strong> usuario1 | <strong>Contraseña:</strong> password</p>
            <p><strong>Usuario:</strong> test | <strong>Contraseña:</strong> password</p>
        </div>
    </div>
</body>
</html>