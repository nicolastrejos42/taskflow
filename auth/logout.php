<?php
/**
 * TaskFlow - Cierre de sesión.
 */
require_once __DIR__ . '/../includes/functions.php';

// Vaciar y destruir la sesión por completo.
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

header('Location: ' . base_url('auth/login.php'));
exit;
