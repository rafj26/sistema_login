<?php
require_once 'config.php';

// Verificar autenticación básica
if (!isset($_SESSION['usuario_id'])) {
    // Intentar autenticación con cookie simple
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
            // Token inválido, eliminar cookie
            setcookie('remember_token', '', time() - 3600, '/');
            header('Location: login.php');
            exit;
        }
    } else {
        header('Location: login.php');
        exit;
    }
}

// Obtener tema actual
$tema_actual = $_SESSION['tema_preferido'] ?? 'claro';

// Manejar cambio de tema
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_tema'])) {
    $nuevo_tema = ($_POST['tema'] === 'oscuro') ? 'oscuro' : 'claro';
    
    try {
        // Actualizar tema en base de datos
        $stmt = $pdo->prepare("UPDATE usuarios SET tema_preferido = ? WHERE id = ?");
        $resultado = $stmt->execute([$nuevo_tema, $_SESSION['usuario_id']]);
        
        if ($resultado) {
            // Actualizar sesión
            $_SESSION['tema_preferido'] = $nuevo_tema;
            $tema_actual = $nuevo_tema; // Actualizar variable local también
        }
    } catch (Exception $e) {
        // Silenciar errores para evitar problemas
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - Lab9</title>
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
        }
        
        /* Valores por defecto */
        :root {
            --bg-color: #f8f9fa;
            --bg-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --container-bg: white;
            --text-color: #333;
            --text-muted: #666;
            --border-color: #ddd;
            --shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            --card-bg: white;
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
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }
        
        .btn-logout {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        
        .btn-logout:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        .main-content {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .welcome-card,
        .feature-card,
        .theme-section,
        .session-info {
            background: var(--card-bg);
            transition: background-color 0.3s ease, color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .welcome-card {
            padding: 2rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
        }
        
        .welcome-card h1 {
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }
        
        .welcome-card p {
            color: var(--text-muted);
            font-size: 1.1rem;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .feature-card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            border-left: 4px solid #667eea;
        }
        
        .feature-card h3 {
            color: var(--text-color);
            margin-bottom: 1rem;
        }
        
        .feature-card p {
            color: var(--text-muted);
            line-height: 1.6;
        }
        
        .theme-section {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }
        
        .theme-section h2 {
            color: var(--text-color);
            margin-bottom: 1rem;
        }
        
        .theme-selector {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .theme-option {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .theme-option:hover {
            border-color: #667eea;
        }
        
        .theme-option.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .theme-option input[type="radio"] {
            margin: 0;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
            margin-top: 1rem;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
        }
        
        .session-info {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-top: 2rem;
        }
        
        .session-info h3 {
            color: var(--text-color);
            margin-bottom: 1rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .info-label {
            font-weight: 600;
            color: var(--text-color);
        }
        
        .info-value {
            color: var(--text-muted);
        }
        
        .mensaje-exito {
            background: #d4edda;
            color: #155724;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border-left: 4px solid #28a745;
        }
        
        .mensaje-error {
            background: #f8d7da;
            color: #721c24;
            padding: 0.75rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            border-left: 4px solid #dc3545;
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .user-info {
                flex-direction: column;
            }
            
            .theme-selector {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body class="tema-<?php echo $tema_actual; ?>">
    <header class="header">
        <div class="header-content">
            <div class="logo">Lab9 - PHP Cookies & Sesiones</div>
            <div class="user-info">
                <span>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?>!</span>
                <a href="perfil.php" class="btn btn-logout">👤 Mi Perfil</a>
                <a href="logout.php" class="btn btn-logout">Cerrar Sesión</a>
            </div>
        </div>
    </header>
    
    <main class="main-content">
        <div class="welcome-card">
            <h1>¡Bienvenido al Laboratorio!</h1>
            <p>Sistema de autenticación con cookies y sesiones implementado exitosamente.</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <h3>🔐 Autenticación Segura</h3>
                <p>Sistema de login con validación de credenciales usando hash de contraseñas y tokens seguros para la función "Recuérdame".</p>
            </div>
            
            <div class="feature-card">
                <h3>🍪 Gestión de Cookies</h3>
                <p>Implementación de cookies seguras con tokens de larga duración (30 días) para mantener la sesión activa de forma segura.</p>
            </div>
            
            <div class="feature-card">
                <h3>🎨 Personalización</h3>
                <p>Sistema de temas personalizables que se guardan en la base de datos y se mantienen entre sesiones.</p>
            </div>
            
            <div class="feature-card">
                <h3>📱 Responsive Design</h3>
                <p>Diseño adaptable que funciona perfectamente en dispositivos móviles y de escritorio.</p>
            </div>
        </div>
        
        <div class="theme-section">
            <h2>Personalizar Tema</h2>
            <p style="color: var(--text-muted); margin-bottom: 1rem;">Selecciona tu tema preferido. Los cambios se guardarán automáticamente.</p>
            
            <form method="POST" action="" id="theme-form">
                <div class="theme-selector">
                    <label class="theme-option <?php echo $tema_actual === 'claro' ? 'active' : ''; ?>">
                        <input type="radio" name="tema" value="claro" <?php echo $tema_actual === 'claro' ? 'checked' : ''; ?>>
                        <span>🌞 Tema Claro</span>
                    </label>
                    
                    <label class="theme-option <?php echo $tema_actual === 'oscuro' ? 'active' : ''; ?>">
                        <input type="radio" name="tema" value="oscuro" <?php echo $tema_actual === 'oscuro' ? 'checked' : ''; ?>>
                        <span>🌙 Tema Oscuro</span>
                    </label>
                </div>
                
                <input type="hidden" name="cambiar_tema" value="1">
            </form>
        </div>
        
        <div class="session-info">
            <h3>Información de Sesión</h3>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Usuario:</span>
                    <span class="info-value"><?php echo htmlspecialchars($_SESSION['nombre_usuario']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">ID de Sesión:</span>
                    <span class="info-value"><?php echo substr(session_id(), 0, 8) . '...'; ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tema Actual:</span>
                    <span class="info-value"><?php echo ucfirst($tema_actual); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Cookie "Recuérdame":</span>
                    <span class="info-value"><?php echo isset($_COOKIE['remember_token']) ? 'Activa' : 'Inactiva'; ?></span>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        // Cambiar tema inmediatamente al hacer clic
        document.querySelectorAll('input[name="tema"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // Cambiar tema visualmente de inmediato
                document.body.className = 'tema-' + this.value;
                
                // Enviar formulario para guardar en BD
                this.form.submit();
            });
        });
        
        // Asegurar que el tema correcto esté aplicado al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const temaActual = '<?php echo $tema_actual; ?>';
            document.body.className = 'tema-' + temaActual;
            
            // Marcar el radio button correcto
            const radioCorrect = document.querySelector(`input[name="tema"][value="${temaActual}"]`);
            if (radioCorrect) {
                radioCorrect.checked = true;
            }
            
            // Actualizar clases visuales
            document.querySelectorAll('.theme-option').forEach(option => {
                const radio = option.querySelector('input[type="radio"]');
                if (radio.value === temaActual) {
                    option.classList.add('active');
                } else {
                    option.classList.remove('active');
                }
            });
        });
    </script>
</body>
</html>