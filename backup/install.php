<?php
// install.php - Script de instalación automática del laboratorio
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Lab9</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 40px auto; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .code { background: #f4f4f4; padding: 10px; border-radius: 5px; font-family: monospace; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>🚀 Instalación del Laboratorio PHP - Cookies y Sesiones</h1>

<?php
// Configuración de la base de datos (modifica según sea necesario)
$db_config = [
    'host' => 'localhost',
    'name' => 'lab9',
    'user' => 'root',
    'pass' => ''
];

echo "<h2>1. Verificando configuración...</h2>";

// Verificar extensiones PHP necesarias
$required_extensions = ['pdo', 'pdo_mysql', 'session'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<p class='success'>✓ Extensión $ext disponible</p>";
    } else {
        echo "<p class='error'>✗ Extensión $ext no disponible</p>";
    }
}

try {
    echo "<h2>2. Conectando a la base de datos...</h2>";
    
    // Intentar conexión
    $pdo = new PDO(
        "mysql:host={$db_config['host']};charset=utf8mb4", 
        $db_config['user'], 
        $db_config['pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "<p class='success'>✓ Conexión al servidor MySQL exitosa</p>";
    
    // Crear base de datos si no existe
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_config['name']}`");
    echo "<p class='success'>✓ Base de datos '{$db_config['name']}' creada/verificada</p>";
    
    // Usar la base de datos
    $pdo->exec("USE `{$db_config['name']}`");
    
    echo "<h2>3. Creando estructura de tablas...</h2>";
    
    // Crear tabla usuarios
    $sql_usuarios = "
    CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre_usuario VARCHAR(50) UNIQUE NOT NULL,
        contraseña VARCHAR(255) NOT NULL,
        email VARCHAR(100) NULL,
        tema_preferido VARCHAR(10) DEFAULT 'claro',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql_usuarios);
    echo "<p class='success'>✓ Tabla 'usuarios' creada</p>";
    
    // Crear tabla tokens_recordar
    $sql_tokens = "
    CREATE TABLE IF NOT EXISTS tokens_recordar (
        id INT AUTO_INCREMENT PRIMARY KEY,
        usuario_id INT NOT NULL,
        token VARCHAR(64) NOT NULL,
        expira_en DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
        UNIQUE KEY unique_token (token)
    )";
    $pdo->exec($sql_tokens);
    echo "<p class='success'>✓ Tabla 'tokens_recordar' creada</p>";
    
    echo "<h2>4. Insertando usuarios de prueba...</h2>";
    
    // Verificar si ya existen usuarios
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $count = $stmt->fetch()['total'];
    
    if ($count == 0) {
        // Crear usuarios de prueba
        $password_hash = password_hash('password', PASSWORD_DEFAULT);
        
        $usuarios = [
            ['admin', $password_hash, 'admin@lab9.com', 'claro'],
            ['usuario1', $password_hash, 'usuario1@lab9.com', 'claro'],
            ['test', $password_hash, 'test@lab9.com', 'oscuro']
        ];
        
        foreach ($usuarios as $usuario) {
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_usuario, contraseña, email, tema_preferido) VALUES (?, ?, ?, ?)");
            $stmt->execute($usuario);
            echo "<p class='success'>✓ Usuario '{$usuario[0]}' creado</p>";
        }
    } else {
        echo "<p class='warning'>⚠ Ya existen $count usuarios en la base de datos</p>";
    }
    
    echo "<h2>5. Verificando instalación...</h2>";
    
    // Mostrar usuarios
    $stmt = $pdo->query("SELECT id, nombre_usuario, tema_preferido FROM usuarios");
    $usuarios = $stmt->fetchAll();
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Usuario</th><th>Tema</th><th>Login Test</th></tr>";
    
    foreach ($usuarios as $usuario) {
        echo "<tr>";
        echo "<td>{$usuario['id']}</td>";
        echo "<td>{$usuario['nombre_usuario']}</td>";
        echo "<td>{$usuario['tema_preferido']}</td>";
        
        // Test de login
        $stmt_test = $pdo->prepare("SELECT contraseña FROM usuarios WHERE id = ?");
        $stmt_test->execute([$usuario['id']]);
        $hash = $stmt_test->fetch()['contraseña'];
        
        if (password_verify('password', $hash)) {
            echo "<td class='success'>✓ OK</td>";
        } else {
            echo "<td class='error'>✗ Error</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>6. Configuración del archivo config.php</h2>";
    
    $config_content = "<?php
// config.php - Configuración de la base de datos

define('DB_HOST', '{$db_config['host']}');
define('DB_NAME', '{$db_config['name']}');
define('DB_USER', '{$db_config['user']}');
define('DB_PASS', '{$db_config['pass']}');

try {
    \$pdo = new PDO(
        \"mysql:host=\" . DB_HOST . \";dbname=\" . DB_NAME . \";charset=utf8mb4\", 
        DB_USER, 
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException \$e) {
    die(\"Error de conexión: \" . \$e->getMessage());
}

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>";

    if (file_put_contents('config.php', $config_content)) {
        echo "<p class='success'>✓ Archivo config.php actualizado</p>";
    } else {
        echo "<p class='error'>✗ Error al escribir config.php. Por favor, créalo manualmente:</p>";
        echo "<div class='code'>" . htmlspecialchars($config_content) . "</div>";
    }
    
    echo "<h2>🎉 ¡Instalación Completada!</h2>";
    echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>Usuarios de prueba creados:</h3>";
    echo "<ul>";
    echo "<li><strong>admin</strong> | Contraseña: <strong>password</strong></li>";
    echo "<li><strong>usuario1</strong> | Contraseña: <strong>password</strong></li>";
    echo "<li><strong>test</strong> | Contraseña: <strong>password</strong></li>";
    echo "</ul>";
    echo "<p><strong>Próximos pasos:</strong></p>";
    echo "<ol>";
    echo "<li><a href='login.php'>Ir al formulario de login</a></li>";
    echo "<li>Probar el inicio de sesión con cualquiera de los usuarios</li>";
    echo "<li>Verificar el cambio de temas</li>";
    echo "<li>Probar la función 'Recuérdame'</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2 class='error'>❌ Error durante la instalación</h2>";
    echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
    echo "<h3>Posibles soluciones:</h3>";
    echo "<ul>";
    echo "<li>Verificar que MySQL esté ejecutándose</li>";
    echo "<li>Comprobar credenciales de base de datos</li>";
    echo "<li>Asegurar permisos de escritura en el directorio</li>";
    echo "<li>Verificar extensiones PHP (PDO, PDO_MySQL)</li>";
    echo "</ul>";
}

// Iniciar sesión para limpiar cualquier sesión anterior
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
session_destroy();
?>

<hr>
<p><small>Script de instalación automática - Lab9 PHP Cookies y Sesiones</small></p>

</body>
</html>