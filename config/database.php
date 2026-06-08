<?php
/**
 * TaskFlow - Configuración y conexión a la base de datos.
 *
 * Soporta dos motores intercambiables mediante DB_DRIVER:
 *   - 'sqlite' (por defecto): no requiere servidor; usa un único archivo.
 *   - 'mysql' : XAMPP/Laragon/MAMP (host "localhost", usuario "root", sin contraseña).
 *
 * Puedes forzar el motor con la variable de entorno TASKFLOW_DB=mysql|sqlite,
 * o cambiando el valor por defecto de DB_DRIVER aquí abajo.
 */

define('DB_DRIVER', getenv('TASKFLOW_DB') ?: 'sqlite');

/* ---- Credenciales MySQL ------------------------------------------- */
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'taskflow');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/* ---- Ruta del archivo SQLite -------------------------------------- */
define('DB_SQLITE_PATH', __DIR__ . '/../database/taskflow.sqlite');

/**
 * Devuelve una conexión PDO única (singleton) a la base de datos,
 * según el motor configurado en DB_DRIVER.
 *
 * @return PDO
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            if (DB_DRIVER === 'sqlite') {
                $pdo = new PDO('sqlite:' . DB_SQLITE_PATH, null, null, $options);
                // SQLite no aplica las claves foráneas por defecto.
                $pdo->exec('PRAGMA foreign_keys = ON');
            } else {
                $dsn = sprintf(
                    'mysql:host=%s;dbname=%s;charset=%s',
                    DB_HOST,
                    DB_NAME,
                    DB_CHARSET
                );
                $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            }
        } catch (PDOException $e) {
            http_response_code(500);
            exit(
                'Error de conexión a la base de datos. '
                . (DB_DRIVER === 'sqlite'
                    ? 'Ejecuta setup_sqlite.php para crear el archivo SQLite.'
                    : 'Revisa config/database.php y que el servidor MySQL esté activo.')
            );
        }
    }

    return $pdo;
}
