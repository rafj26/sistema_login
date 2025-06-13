<?php
require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['usuario_id'])) {
    if (isset($_COOKIE['remember_token'])) {
        $token = $_COOKIE['remember_token'];
        $stmt = $pdo->prepare("
            SELECT u.id, u.nombre_usuario, u.tema_preferido 
            FROM tokens_recordar t 
            JOIN usuarios u ON t.usuario_id = u.id 
            WHERE t.token = ? AND t.expira_en > NOW()
        ");
        $stmt->execute([$token]);
        $usuario = $stmt->fetch();
        
        if ($usuario) {
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nombre_usuario'] = $usuario['nombre_usuario'];
            $_SESSION['tema_preferido'] = $usuario['tema_preferido'];
        } else {
            setcookie('remember_token', '', time() - 3600, '/');
            header('Location: login.php');
            exit;
        }
    } else {
        header('Location: login.php');
        exit;
    }
}

$tema_actual = $_SESSION['tema_preferido'] ?? 'claro';
$mensaje = '';
$error = '';

// Obtener información completa del usuario
$stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch();

// Manejar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Cambiar contraseña
    if (isset($_POST['cambiar_password'])) {
        $password_actual = $_POST['password_actual'] ?? '';
        $password_nuevo = $_POST['password_nuevo'] ?? '';
        $password_confirmar = $_POST['password_confirmar'] ?? '';
        
        if (empty($password_actual) || empty($password_nuevo) || empty($password_confirmar)) {
            $error = 'Todos los campos de contraseña son requeridos.';
        } elseif (!password_verify($password_actual, $usuario['contraseña'])) {
            $error = 'La contraseña actual es incorrecta.';
        } elseif ($password_nuevo !== $password_confirmar) {
            $error = 'Las contraseñas nuevas no coinciden.';
        } elseif (strlen($password_nuevo) < 6) {
            $error = 'La contraseña debe tener al menos 6 caracteres.';
        } else {
            try {
                $nuevo_hash = password_hash($password_nuevo, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE usuarios SET contraseña = ? WHERE id = ?");
                $stmt->execute([$nuevo_hash, $_SESSION['usuario_id']]);
                
                // Eliminar todos los tokens de "Recuérdame" por seguridad
                $stmt = $pdo->prepare("DELETE FROM tokens_recordar WHERE usuario_id = ?");
                $stmt->execute([$_SESSION['usuario_id']]);
                
                $mensaje = 'Contraseña actualizada correctamente. Se han eliminado todas las sesiones "Recuérdame" por seguridad.';
                
                // Actualizar datos del usuario
                $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
                $stmt->execute([$_SESSION['usuario_id']]);
                $usuario = $stmt->fetch();
                
            } catch (Exception $e) {
                $error = 'Error al actualizar la contraseña.';
            }
        }
    }
    
    // Actualizar información personal
    if (isset($_POST['actualizar_info'])) {
        $nuevo_usuario = trim($_POST['nombre_usuario'] ?? '');
        $nuevo_email = trim($_POST['email'] ?? '');
        
        if (empty($nuevo_usuario)) {
            $error = 'El nombre de usuario es requerido.';
        } else {
            try {
                // Verificar si el nombre de usuario ya existe (excepto el actual)
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE nombre_usuario = ? AND id != ?");
                $stmt->execute([$nuevo_usuario, $_SESSION['usuario_id']]);
                
                if ($stmt->fetch()) {
                    $error = 'El nombre de usuario ya existe.';
                } else {
                    $stmt = $pdo->prepare("UPDATE usuarios SET nombre_usuario = ?, email = ? WHERE id = ?");
                    $stmt->execute([$nuevo_usuario, $nuevo_email, $_SESSION['usuario_id']]);
                    
                    $_SESSION['nombre_usuario'] = $nuevo_usuario;
                    $mensaje = 'Información personal actualizada correctamente.';
                    
                    // Actualizar datos del usuario
                    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
                    $stmt->execute([$_SESSION['usuario_id']]);
                    $usuario = $stmt->fetch();
                }
            } catch (Exception $e) {
                $error = 'Error al actualizar la información.';
            }
        }
    }
    
    // Eliminar todas las sesiones "Recuérdame"
    if (isset($_POST['eliminar_sesiones'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM tokens_recordar WHERE usuario_id = ?");
            $stmt->execute([$_SESSION['usuario_id']]);
            
            // Eliminar cookie actual también
            if (isset($_COOKIE['remember_token'])) {
                setcookie('remember_token', '', time() - 3600, '/');
            }
            
            $mensaje = 'Todas las sesiones "Recuérdame" han sido eliminadas.';
        } catch (Exception $e) {
            $error = 'Error al eliminar las sesiones.';
        }
    }
}

// Obtener tokens activos
$stmt = $pdo->prepare("SELECT token, expira_en, created_at FROM tokens_recordar WHERE usuario_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['usuario_id']]);
$tokens_activos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil - Lab9</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body.tema-claro {
            --bg-color: #f8f9fa;
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --container-bg: white;
            --text-color: #333;
            --text-muted: #666;
            --border-color: #ddd;
            --shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            --card-bg: white;
            --input-bg: white;
        }
        
        body.tema-oscuro {
            --bg-color: #1a1a1a;
            --bg-gradient: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            --container-bg: #2d3748;
            --text-color: #e2e8f0;
            --text-muted: #a0aec0;
            --border-color: #4a5568;
            --shadow: 0 15px 35px rgba(0, 0, 0, 0.3);
            --card-bg: #2d3748;
            --input-bg: #4a5568;
        }
        
        :root {
            --bg-color: #f8f9fa;
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --container-bg: white;
            --text-color: #333;
            --text-muted: #666;
            --border-color: #ddd;
            --shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            --card-bg: white;
            --input-bg: white;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-color);
            color: var(--text-color);
            min-height: 100vh;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        .header {
            background: var(--bg-gradient);
            color: white;
            padding: 1rem 0;
            box-shadow: var(--shadow);
        }
        
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .nav {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .nav a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background-color 0.3s ease;
        }
        
        .nav a:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .nav a.active {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .main-content {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .profile-header {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            text-align: center;
            transition: background-color 0.3s ease;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: var(--bg-gradient);
            border-radius: 50%;
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
        }
        
        .profile-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }
        
        .card {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            transition: background-color 0.3s ease;
        }
        
        .card h3 {
            color: var(--text-color);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--border-color);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 5px;
            background: var(--input-bg);
            color: var(--text-color);
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-secondary {
            background: var(--border-color);
            color: var(--text-color);
        }
        
        .btn-secondary:hover {
            background: var(--text-muted);
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #28a745;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1.5rem;
            border-left: 4px solid #dc3545;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--text-color);
        }
        
        .info-value {
            color: var(--text-muted);
        }
        
        .token-list {
            max-height: 200px;
            overflow-y: auto;
        }
        
        .token-item {
            background: var(--input-bg);
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 0.5rem;
            border-left: 4px solid #667eea;
        }
        
        .token-meta {
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            text-align: center;
            transition: background-color 0.3s ease;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            display: block;
        }
        
        .stat-label {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav {
                flex-direction: column;
            }
            
            .profile-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body class="tema-<?php echo $tema_actual; ?>">
    <header class="header">
        <div class="header-content">
            <div class="logo">Lab9 - PHP Cookies & Sesiones</div>
            <nav class="nav">
                <a href="inicio.php">🏠 Inicio</a>
                <a href="perfil.php" class="active">👤 Mi Perfil</a>
                <a href="logout.php">🚪 Cerrar Sesión</a>
            </nav>
        </div>
    </header>
    
    <main class="main-content">
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($_SESSION['nombre_usuario'], 0, 2)); ?>
            </div>
            <h1>¡Hola, <?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?>!</h1>
            <p style="color: var(--text-muted);">Gestiona tu perfil y configuraciones de seguridad</p>
        </div>
        
        <?php if ($mensaje): ?>
            <div class="success"><?php echo htmlspecialchars($mensaje); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?php echo count($tokens_activos); ?></span>
                <span class="stat-label">Sesiones "Recuérdame" Activas</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo ucfirst($tema_actual); ?></span>
                <span class="stat-label">Tema Preferido</span>
            </div>
            <div class="stat-card">
                <span class="stat-number"><?php echo date('d/m/Y', strtotime($usuario['created_at'])); ?></span>
                <span class="stat-label">Miembro Desde</span>
            </div>
        </div>
        
        <div class="profile-cards">
            <!-- Información Personal -->
            <div class="card">
                <h3>📝 Información Personal</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="nombre_usuario">Nombre de Usuario:</label>
                        <input 
                            type="text" 
                            id="nombre_usuario" 
                            name="nombre_usuario" 
                            value="<?php echo htmlspecialchars($usuario['nombre_usuario']); ?>"
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email (Opcional):</label>
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            value="<?php echo htmlspecialchars($usuario['email'] ?? ''); ?>"
                            placeholder="tu@email.com"
                        >
                    </div>
                    
                    <button type="submit" name="actualizar_info" class="btn btn-primary">
                        💾 Actualizar Información
                    </button>
                </form>
            </div>
            
            <!-- Cambiar Contraseña -->
            <div class="card">
                <h3>🔐 Cambiar Contraseña</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="password_actual">Contraseña Actual:</label>
                        <input 
                            type="password" 
                            id="password_actual" 
                            name="password_actual" 
                            required
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="password_nuevo">Nueva Contraseña:</label>
                        <input 
                            type="password" 
                            id="password_nuevo" 
                            name="password_nuevo" 
                            required
                            minlength="6"
                        >
                    </div>
                    
                    <div class="form-group">
                        <label for="password_confirmar">Confirmar Nueva Contraseña:</label>
                        <input 
                            type="password" 
                            id="password_confirmar" 
                            name="password_confirmar" 
                            required
                            minlength="6"
                        >
                    </div>
                    
                    <button type="submit" name="cambiar_password" class="btn btn-primary">
                        🔑 Cambiar Contraseña
                    </button>
                </form>
            </div>
            
            <!-- Información de Cuenta -->
            <div class="card">
                <h3>ℹ️ Información de Cuenta</h3>
                <div class="info-item">
                    <span class="info-label">ID de Usuario:</span>
                    <span class="info-value">#<?php echo $usuario['id']; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Cuenta Creada:</span>
                    <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($usuario['created_at'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tema Actual:</span>
                    <span class="info-value"><?php echo ucfirst($tema_actual); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Sesión ID:</span>
                    <span class="info-value"><?php echo substr(session_id(), 0, 8) . '...'; ?></span>
                </div>
            </div>
            
            <!-- Gestión de Sesiones -->
            <div class="card">
                <h3>🛡️ Gestión de Seguridad</h3>
                <p style="color: var(--text-muted); margin-bottom: 1.5rem;">
                    Gestiona las sesiones "Recuérdame" activas en diferentes dispositivos.
                </p>
                
                <?php if (count($tokens_activos) > 0): ?>
                    <div class="token-list">
                        <?php foreach ($tokens_activos as $token): ?>
                            <div class="token-item">
                                <div><strong>Token:</strong> <?php echo substr($token['token'], 0, 16) . '...'; ?></div>
                                <div class="token-meta">
                                    Creado: <?php echo date('d/m/Y H:i', strtotime($token['created_at'])); ?> | 
                                    Expira: <?php echo date('d/m/Y H:i', strtotime($token['expira_en'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-muted);">No tienes sesiones "Recuérdame" activas.</p>
                <?php endif; ?>
                
                <form method="POST" action="" style="margin-top: 1.5rem;">
                    <button 
                        type="submit" 
                        name="eliminar_sesiones" 
                        class="btn btn-danger"
                        onclick="return confirm('¿Estás seguro de eliminar todas las sesiones? Tendrás que iniciar sesión de nuevo en todos los dispositivos.')"
                    >
                        🗑️ Eliminar Todas las Sesiones
                    </button>
                </form>
            </div>
        </div>
    </main>
    
    <script>
        // Aplicar tema correcto al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const temaActual = '<?php echo $tema_actual; ?>';
            document.body.className = 'tema-' + temaActual;
        });
        
        // Validación de contraseñas en tiempo real
        document.getElementById('password_nuevo').addEventListener('input', function() {
            const password = this.value;
            const confirm = document.getElementById('password_confirmar');
            
            if (password.length < 6) {
                this.setCustomValidity('La contraseña debe tener al menos 6 caracteres');
            } else {
                this.setCustomValidity('');
            }
        });
        
        document.getElementById('password_confirmar').addEventListener('input', function() {
            const password = document.getElementById('password_nuevo').value;
            const confirm = this.value;
            
            if (password !== confirm) {
                this.setCustomValidity('Las contraseñas no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>