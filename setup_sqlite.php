<?php
/**
 * TaskFlow - Instalador de la base de datos SQLite con datos de muestra.
 *
 * Crea el archivo  database/taskflow.sqlite , aplica el esquema y carga
 * datos de ejemplo (usuarios y tareas en todos los estados) para poder
 * probar la aplicación completa sin necesidad de MySQL.
 *
 * Uso:
 *   - Navegador:  http://localhost/TaskFlow/setup_sqlite.php
 *   - Consola:    php setup_sqlite.php
 *   - Recargar datos desde cero:  añade  ?fresh=1  (o el argumento  fresh).
 *
 * IMPORTANTE: por seguridad, elimina o renombra este archivo en producción.
 *
 * Credenciales de las cuentas de muestra (contraseña para todas: "password123"):
 *   - ana@taskflow.test
 *   - carlos@taskflow.test
 */

require_once __DIR__ . '/config/database.php';

$isCli = (PHP_SAPI === 'cli');
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
}
$fresh = isset($_GET['fresh']) || in_array('fresh', $argv ?? [], true);

/** Imprime una línea de salida. */
function out(string $line): void { echo $line . "\n"; }

try {
    // 1) Asegurar la carpeta del archivo SQLite.
    $dir = dirname(DB_SQLITE_PATH);
    if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
        throw new RuntimeException("No se pudo crear la carpeta: $dir");
    }

    // 2) Conexión directa (no usamos db() para poder reconstruir desde cero).
    $pdo = new PDO('sqlite:' . DB_SQLITE_PATH, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA foreign_keys = ON');

    // 3) Reconstrucción opcional desde cero.
    if ($fresh) {
        $pdo->exec('DROP TRIGGER IF EXISTS trg_tasks_updated_at');
        $pdo->exec('DROP TABLE IF EXISTS tasks');
        $pdo->exec('DROP TABLE IF EXISTS users');
        out('• Tablas anteriores eliminadas (modo fresh).');
    }

    // 4) Aplicar el esquema, sentencia por sentencia (PDO::exec en SQLite
    //    ejecuta una sola sentencia por llamada).
    $statements = [
        'CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL UNIQUE,
            password TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP)
        )',
        'CREATE TABLE IF NOT EXISTS tasks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            title TEXT NOT NULL,
            description TEXT,
            due_date TEXT,
            status TEXT NOT NULL DEFAULT \'pendiente\'
                   CHECK (status IN (\'pendiente\',\'en_progreso\',\'completada\')),
            created_at TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP),
            updated_at TEXT NOT NULL DEFAULT (CURRENT_TIMESTAMP),
            FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        )',
        'CREATE INDEX IF NOT EXISTS idx_tasks_user   ON tasks (user_id)',
        'CREATE INDEX IF NOT EXISTS idx_tasks_status ON tasks (status)',
        'CREATE TRIGGER IF NOT EXISTS trg_tasks_updated_at
            AFTER UPDATE ON tasks FOR EACH ROW
            BEGIN
                UPDATE tasks SET updated_at = CURRENT_TIMESTAMP WHERE id = OLD.id;
            END',
    ];
    foreach ($statements as $sql) {
        $pdo->exec($sql);
    }
    out('✔ Esquema SQLite aplicado en: ' . DB_SQLITE_PATH);

    // 5) Sembrar datos solo si no hay usuarios (evita duplicados al recargar).
    $hasUsers = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0;
    if ($hasUsers) {
        out('• Ya existen datos; no se vuelven a insertar. Usa ?fresh=1 para recargar.');
    } else {
        seed($pdo);
        out('✔ Datos de muestra insertados.');
    }

    // 6) Resumen.
    $u = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $t = $pdo->query('SELECT COUNT(*) FROM tasks')->fetchColumn();
    out('');
    out("Resumen: $u usuario(s), $t tarea(s).");
    out('Cuentas de muestra (contraseña: password123):');
    out('  - ana@taskflow.test');
    out('  - carlos@taskflow.test');
    out('');
    out('Siguiente paso: abre  http://localhost/TaskFlow/  e inicia sesión.');
    out('Recuerda eliminar setup_sqlite.php (y setup.php) en producción.');
} catch (Throwable $e) {
    if (!$isCli) http_response_code(500);
    out('✗ Error durante la instalación:');
    out($e->getMessage());
}

/**
 * Inserta usuarios y tareas de ejemplo con fechas relativas a hoy,
 * cubriendo los tres estados y casos con/sin descripción y fecha límite.
 */
function seed(PDO $pdo): void
{
    $hash = password_hash('password123', PASSWORD_DEFAULT);

    $insUser = $pdo->prepare(
        'INSERT INTO users (name, email, password) VALUES (?, ?, ?)'
    );
    $insUser->execute(['Ana Torres',  'ana@taskflow.test',    $hash]);
    $anaId = (int) $pdo->lastInsertId();
    $insUser->execute(['Carlos Ruiz', 'carlos@taskflow.test', $hash]);
    $carlosId = (int) $pdo->lastInsertId();

    // Helpers de fecha relativos a hoy.
    $in = static fn(int $d) => date('Y-m-d', strtotime("+$d days"));
    $ago = static fn(int $d) => date('Y-m-d', strtotime("-$d days"));

    // [title, description|null, due_date|null, status]
    $tasksByUser = [
        $anaId => [
            ['Preparar informe mensual de ventas', 'Consolidar cifras de mayo y generar el PDF para dirección.', $in(3),  'en_progreso'],
            ['Enviar facturas pendientes a clientes', 'Revisar las facturas vencidas antes de enviarlas.',        $ago(2), 'pendiente'],
            ['Reunión con el equipo de diseño', 'Definir la nueva paleta de colores del producto.',              $ago(5), 'completada'],
            ['Actualizar la documentación de la API', null,                                                       null,    'pendiente'],
            ['Revisar los pull requests del repositorio', 'Quedan 4 PRs por revisar y aprobar.',                  $in(1),  'en_progreso'],
            ['Planificar el próximo sprint', 'Preparar el backlog y estimar las historias de usuario.',           $in(7),  'pendiente'],
            ['Hacer copia de seguridad de la base de datos', null,                                                $ago(10),'completada'],
        ],
        $carlosId => [
            ['Configurar el servidor de pruebas', 'Instalar PHP 8.2 y configurar el virtual host.',              $ago(1), 'completada'],
            ['Migrar la base de datos a producción', 'Coordinar ventana de mantenimiento con el equipo.',         $in(2),  'en_progreso'],
            ['Escribir tests unitarios del módulo de tareas', null,                                               $in(5),  'pendiente'],
            ['Optimizar las consultas del dashboard', 'Añadir índices y revisar los planes de ejecución.',        null,    'pendiente'],
            ['Desplegar la versión 1.2.0', 'Incluye soporte para SQLite y mejoras de seguridad.',                 $in(4),  'en_progreso'],
            ['Documentar el proceso de despliegue', null,                                                         $ago(3), 'completada'],
        ],
    ];

    $insTask = $pdo->prepare(
        'INSERT INTO tasks (user_id, title, description, due_date, status)
         VALUES (?, ?, ?, ?, ?)'
    );
    foreach ($tasksByUser as $userId => $tasks) {
        foreach ($tasks as [$title, $desc, $due, $status]) {
            $insTask->execute([$userId, $title, $desc, $due, $status]);
        }
    }
}
