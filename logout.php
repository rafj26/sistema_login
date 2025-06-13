<?php
require_once 'config.php';

// Eliminar token de "Recuérdame" de la base de datos si existe
if (isset($_SESSION['usuario_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM tokens_recordar WHERE usuario_id = ?");
        $stmt->execute([$_SESSION['usuario_id']]);
    } catch (Exception $e) {
        // Silenciar errores
    }
}

// Eliminar cookie de "Recuérdame"
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Destruir todas las variables de sesión
$_SESSION = array();

// Eliminar cookie de sesión si está configurada
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión
session_destroy();

// Redirigir al login con mensaje de confirmación
header('Location: login.php?logout=success');
exit;
?>