<?php
// verificar_usuarios.php - Script para verificar y corregir usuarios
require_once 'config.php';

echo "<h2>Verificación de Usuarios en la Base de Datos</h2>";

try {
    // Verificar si la tabla usuarios existe y tiene datos
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $result = $stmt->fetch();
    
    echo "<p>Total de usuarios en la base de datos: " . $result['total'] . "</p>";
    
    if ($result['total'] == 0) {
        echo "<p style='color: orange;'>No hay usuarios en la base de datos. Creando usuarios de prueba...</p>";
        
        // Crear usuarios con contraseñas hasheadas correctamente
        $password_hash = password_hash('password', PASSWORD_DEFAULT);
        
        $usuarios = [
            ['admin', $password_hash, 'admin@lab9.com'],
            ['usuario1', $password_hash, 'usuario1@lab9.com'],
            ['test', $password_hash, 'test@lab9.com']
        ];
        
        foreach ($usuarios as $usuario) {
            $stmt = $pdo->prepare("INSERT INTO usuarios (nombre_usuario, contraseña, email, tema_preferido) VALUES (?, ?, ?, 'claro')");
            $stmt->execute($usuario);
            echo "<p style='color: green;'>Usuario '{$usuario[0]}' creado exitosamente.</p>";
        }
    } else {
        // Mostrar usuarios existentes
        $stmt = $pdo->query("SELECT id, nombre_usuario, email, tema_preferido FROM usuarios");
        $usuarios = $stmt->fetchAll();
        
        echo "<h3>Usuarios existentes:</h3>";
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Usuario</th><th>Email</th><th>Tema</th><th>Test Login</th></tr>";
        
        foreach ($usuarios as $usuario) {
            echo "<tr>";
            echo "<td>" . $usuario['id'] . "</td>";
            echo "<td>" . $usuario['nombre_usuario'] . "</td>";
            echo "<td>" . ($usuario['email'] ?? 'Sin email') . "</td>";
            echo "<td>" . $usuario['tema_preferido'] . "</td>";
            
            // Verificar si la contraseña 'password' funciona
            $stmt_verify = $pdo->prepare("SELECT contraseña FROM usuarios WHERE id = ?");
            $stmt_verify->execute([$usuario['id']]);
            $hash = $stmt_verify->fetch()['contraseña'];
            
            if (password_verify('password', $hash)) {
                echo "<td style='color: green;'>✓ OK</td>";
            } else {
                echo "<td style='color: red;'>✗ Error</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Verificar tabla de tokens
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM tokens_recordar");
    $tokens = $stmt->fetch();
    echo "<p>Tokens de 'Recuérdame' activos: " . $tokens['total'] . "</p>";
    
    echo "<h3>Información de conexión:</h3>";
    echo "<p>Host: " . DB_HOST . "</p>";
    echo "<p>Base de datos: " . DB_NAME . "</p>";
    echo "<p>Usuario: " . DB_USER . "</p>";
    echo "<p style='color: green;'>✓ Conexión a la base de datos exitosa</p>";
    
    echo "<hr>";
    echo "<p><strong>Usuarios de prueba:</strong></p>";
    echo "<ul>";
    echo "<li>Usuario: <strong>admin</strong> | Contraseña: <strong>password</strong></li>";
    echo "<li>Usuario: <strong>usuario1</strong> | Contraseña: <strong>password</strong></li>";
    echo "<li>Usuario: <strong>test</strong> | Contraseña: <strong>password</strong></li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Verifica que:</p>";
    echo "<ul>";
    echo "<li>La base de datos 'lab9' exista</li>";
    echo "<li>Las credenciales en config.php sean correctas</li>";
    echo "<li>El servidor MySQL esté funcionando</li>";
    echo "<li>Las tablas hayan sido creadas con el script SQL</li>";
    echo "</ul>";
}
?>