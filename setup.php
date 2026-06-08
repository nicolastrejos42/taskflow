<?php
/**
 * TaskFlow - Script de instalación de ayuda.
 *
 * Crea la base de datos y las tablas ejecutando sql/schema.sql, sin tener
 * que abrir la consola de MySQL ni phpMyAdmin.
 *
 *  IMPORTANTE: por seguridad, ELIMINA o renombra este archivo después de
 *  usarlo una vez.
 *
 * Uso: abre  http://localhost/TaskFlow/setup.php  en el navegador.
 */

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    // Conexión SIN seleccionar base de datos (para poder crearla).
    $dsn = sprintf('mysql:host=%s;charset=%s', DB_HOST, DB_CHARSET);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $sql = file_get_contents(__DIR__ . '/sql/schema.sql');
    if ($sql === false) {
        throw new RuntimeException('No se pudo leer sql/schema.sql');
    }

    // Ejecuta el script completo (varias sentencias).
    $pdo->exec($sql);

    echo "✔ Base de datos 'taskflow' y tablas creadas correctamente.\n\n";
    echo "Siguiente paso:\n";
    echo "  1. Abre  auth/register.php  y crea tu cuenta.\n";
    echo "  2. ELIMINA este archivo (setup.php) por seguridad.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "✗ Error durante la instalación:\n";
    echo $e->getMessage() . "\n\n";
    echo "Revisa las credenciales en config/database.php y que MySQL esté activo.\n";
}
