<?php
/**
 * TaskFlow - Listado de tareas con búsqueda por nombre y filtro por estado.
 */
require_once __DIR__ . '/../includes/functions.php';
require_login();

$uid      = current_user_id();
$statuses = task_statuses();

// Parámetros de búsqueda/filtro.
$search = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
if (!array_key_exists($status, $statuses)) {
    $status = '';                       // valor no válido => sin filtro
}

// Construcción dinámica y segura de la consulta.
$sql    = 'SELECT * FROM tasks WHERE user_id = ?';
$params = [$uid];

if ($search !== '') {
    $sql      .= ' AND title LIKE ?';
    $params[]  = '%' . $search . '%';
}
if ($status !== '') {
    $sql      .= ' AND status = ?';
    $params[]  = $status;
}
// Orden portable (MySQL y SQLite): primero por estado, luego por fecha límite.
$sql .= " ORDER BY
            CASE status
                WHEN 'pendiente'   THEN 0
                WHEN 'en_progreso' THEN 1
                WHEN 'completada'  THEN 2
                ELSE 3
            END,
            (due_date IS NULL), due_date ASC,
            created_at DESC";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

$pageTitle = 'Tareas';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><i class="bi bi-list-task"></i> Mis tareas</h1>
    <a href="<?= e(base_url('tasks/create.php')) ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Nueva
    </a>
</div>

<!-- Búsqueda y filtros -->
<form method="get" class="card card-body shadow-sm border-0 mb-4">
    <div class="row g-2 align-items-end">
        <div class="col-12 col-md-6">
            <label class="form-label small mb-1">Buscar por nombre</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="q" class="form-control"
                       placeholder="Escribe el título…" value="<?= e($search) ?>">
            </div>
        </div>
        <div class="col-8 col-md-4">
            <label class="form-label small mb-1">Estado</label>
            <select name="status" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($statuses as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $status === $key ? 'selected' : '' ?>>
                        <?= e($label) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-4 col-md-2 d-grid">
            <button type="submit" class="btn btn-outline-primary">
                <i class="bi bi-funnel"></i> Filtrar
            </button>
        </div>
    </div>
    <?php if ($search !== '' || $status !== ''): ?>
        <div class="mt-2">
            <a href="<?= e(base_url('tasks/index.php')) ?>" class="small text-decoration-none">
                <i class="bi bi-x-circle"></i> Limpiar filtros
            </a>
        </div>
    <?php endif; ?>
</form>

<!-- Resultados -->
<?php if (!$tasks): ?>
    <div class="card shadow-sm border-0">
        <div class="card-body text-center text-muted py-5">
            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
            No se encontraron tareas con esos criterios.
        </div>
    </div>
<?php else: ?>
    <!-- Vista en tabla (pantallas medianas en adelante) -->
    <div class="card shadow-sm border-0 d-none d-md-block">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Título</th>
                        <th>Estado</th>
                        <th>Fecha límite</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $t): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e($t['title']) ?></div>
                                <?php if (!empty($t['description'])): ?>
                                    <div class="small text-muted text-truncate" style="max-width: 360px;">
                                        <?= e($t['description']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= e(status_badge_class($t['status'])) ?>">
                                    <?= e($statuses[$t['status']]) ?>
                                </span>
                            </td>
                            <td>
                                <?= $t['due_date'] ? e(date('d/m/Y', strtotime($t['due_date']))) : '<span class="text-muted">—</span>' ?>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="<?= e(base_url('tasks/edit.php?id=' . $t['id'])) ?>"
                                   class="btn btn-sm btn-outline-secondary" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="post" action="<?= e(base_url('tasks/delete.php')) ?>"
                                      class="d-inline" data-confirm="¿Eliminar esta tarea?">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Vista en tarjetas (móviles) -->
    <div class="d-md-none">
        <?php foreach ($tasks as $t): ?>
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <h2 class="h6 mb-1"><?= e($t['title']) ?></h2>
                        <span class="badge <?= e(status_badge_class($t['status'])) ?> ms-2">
                            <?= e($statuses[$t['status']]) ?>
                        </span>
                    </div>
                    <?php if (!empty($t['description'])): ?>
                        <p class="small text-muted mb-2"><?= e($t['description']) ?></p>
                    <?php endif; ?>
                    <div class="small text-muted mb-2">
                        <i class="bi bi-calendar-event"></i>
                        <?= $t['due_date'] ? e(date('d/m/Y', strtotime($t['due_date']))) : 'Sin fecha' ?>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= e(base_url('tasks/edit.php?id=' . $t['id'])) ?>"
                           class="btn btn-sm btn-outline-secondary flex-fill">
                            <i class="bi bi-pencil"></i> Editar
                        </a>
                        <form method="post" action="<?= e(base_url('tasks/delete.php')) ?>"
                              class="flex-fill" data-confirm="¿Eliminar esta tarea?">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">
                                <i class="bi bi-trash"></i> Eliminar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
