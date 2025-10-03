<?php
// Cerrar sesión de forma segura y redirigir a la portada
session_start();

// Vaciar variables de sesión
$_SESSION = [];

// Invalidar cookie de sesión si existe
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}

// Destruir la sesión
session_destroy();

// Redirigir a inicio
header('Location: index.php');
exit;
