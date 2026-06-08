<?php
/**
 * TaskFlow - Eliminar una tarea (solo por POST + CSRF).
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('tasks/index.php'));
    exit;
}

verify_csrf();

$id  = (int) ($_POST['id'] ?? 0);
$uid = current_user_id();

// El WHERE con user_id garantiza que solo se borren tareas propias.
$stmt = db()->prepare('DELETE FROM tasks WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $uid]);

if ($stmt->rowCount() > 0) {
    flash('success', 'Tarea eliminada correctamente.');
} else {
    flash('warning', 'No se encontró la tarea a eliminar.');
}

header('Location: ' . base_url('tasks/index.php'));
exit;
