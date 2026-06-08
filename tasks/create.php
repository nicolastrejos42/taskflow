<?php
/**
 * TaskFlow - Crear una nueva tarea.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

$uid      = current_user_id();
$statuses = task_statuses();

$errors = [];
$title       = '';
$description = '';
$due_date    = '';
$status      = 'pendiente';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $due_date    = trim($_POST['due_date'] ?? '');
    $status      = $_POST['status'] ?? 'pendiente';

    if ($title === '') {
        $errors[] = 'El título es obligatorio.';
    }
    if (!array_key_exists($status, $statuses)) {
        $errors[] = 'El estado seleccionado no es válido.';
    }
    if ($due_date !== '' && !DateTime::createFromFormat('Y-m-d', $due_date)) {
        $errors[] = 'La fecha no tiene un formato válido.';
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'INSERT INTO tasks (user_id, title, description, due_date, status)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $uid,
            $title,
            $description !== '' ? $description : null,
            $due_date !== '' ? $due_date : null,
            $status,
        ]);

        flash('success', 'Tarea creada correctamente.');
        header('Location: ' . base_url('tasks/index.php'));
        exit;
    }
}

$pageTitle = 'Nueva tarea';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <h1 class="h3 mb-4"><i class="bi bi-plus-circle"></i> Nueva tarea</h1>

        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Título <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control"
                               value="<?= e($title) ?>" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Descripción</label>
                        <textarea name="description" class="form-control" rows="4"><?= e($description) ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-12 col-sm-6 mb-3">
                            <label class="form-label">Fecha límite</label>
                            <input type="date" name="due_date" class="form-control"
                                   value="<?= e($due_date) ?>">
                        </div>
                        <div class="col-12 col-sm-6 mb-3">
                            <label class="form-label">Estado</label>
                            <select name="status" class="form-select">
                                <?php foreach ($statuses as $key => $label): ?>
                                    <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>>
                                        <?= e($label) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check2"></i> Guardar tarea
                        </button>
                        <a href="<?= e(base_url('tasks/index.php')) ?>" class="btn btn-outline-secondary">
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
