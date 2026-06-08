<?php
/**
 * TaskFlow - Funciones de apoyo: sesión, autenticación, CSRF y helpers.
 */

require_once __DIR__ . '/../config/database.php';

// Inicia la sesión una sola vez.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ------------------------------------------------------------------ */
/*  Escape de salida                                                  */
/* ------------------------------------------------------------------ */

/** Escapa una cadena para imprimirla de forma segura en HTML. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/* ------------------------------------------------------------------ */
/*  Autenticación                                                     */
/* ------------------------------------------------------------------ */

/** ¿Hay un usuario con sesión iniciada? */
function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

/** Devuelve el id del usuario actual (o null). */
function current_user_id(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

/** Devuelve el nombre del usuario actual (o cadena vacía). */
function current_user_name(): string
{
    return $_SESSION['user_name'] ?? '';
}

/** Obliga a que exista sesión; si no, redirige al login. */
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . base_url('auth/login.php'));
        exit;
    }
}

/* ------------------------------------------------------------------ */
/*  Protección CSRF                                                   */
/* ------------------------------------------------------------------ */

/** Genera (si hace falta) y devuelve el token CSRF de la sesión. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Campo oculto listo para insertar en un formulario. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/** Verifica el token CSRF de una petición POST. Aborta si no coincide. */
function verify_csrf(): void
{
    $sent = $_POST['csrf_token'] ?? '';
    if (!hash_equals(csrf_token(), (string) $sent)) {
        http_response_code(419);
        exit('Token de seguridad inválido. Recarga la página e inténtalo de nuevo.');
    }
}

/* ------------------------------------------------------------------ */
/*  Mensajes flash (una sola lectura)                                 */
/* ------------------------------------------------------------------ */

/** Guarda un mensaje flash. $type: success | danger | warning | info */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Devuelve y limpia todos los mensajes flash. */
function take_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

/* ------------------------------------------------------------------ */
/*  URLs                                                              */
/* ------------------------------------------------------------------ */

/**
 * Construye una URL relativa a la raíz del proyecto, detectando
 * automáticamente la subcarpeta donde está instalado.
 */
function base_url(string $path = ''): string
{
    // Carpeta base del proyecto a partir de la ubicación de este script.
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    // includes/, tasks/, auth/ están un nivel por debajo de la raíz.
    $base = preg_replace('#/(auth|tasks|includes|config)$#', '', $scriptDir);
    $base = rtrim($base, '/');
    return $base . '/' . ltrim($path, '/');
}

/* ------------------------------------------------------------------ */
/*  Catálogo de estados                                               */
/* ------------------------------------------------------------------ */

/** Estados válidos de una tarea => etiqueta legible. */
function task_statuses(): array
{
    return [
        'pendiente'   => 'Pendiente',
        'en_progreso' => 'En progreso',
        'completada'  => 'Completada',
    ];
}

/** Clase de color Bootstrap (badge) para cada estado. */
function status_badge_class(string $status): string
{
    return [
        'pendiente'   => 'bg-secondary',
        'en_progreso' => 'bg-warning text-dark',
        'completada'  => 'bg-success',
    ][$status] ?? 'bg-light text-dark';
}
