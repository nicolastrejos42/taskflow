<?php
/**
 * TaskFlow - Dashboard (panel principal).
 * Muestra el resumen de tareas del usuario y las más recientes.
 */
require_once __DIR__ . '/includes/functions.php';
require_login();

$uid = current_user_id();

// Conteos por estado en una sola consulta.
$stmt = db()->prepare(
    'SELECT status, COUNT(*) AS total FROM tasks WHERE user_id = ? GROUP BY status'
);
$stmt->execute([$uid]);

$counts = ['pendiente' => 0, 'en_progreso' => 0, 'completada' => 0];
foreach ($stmt->fetchAll() as $row) {
    $counts[$row['status']] = (int) $row['total'];
}
$total = array_sum($counts);

// Últimas 5 tareas.
$stmt = db()->prepare(
    'SELECT * FROM tasks WHERE user_id = ? ORDER BY created_at DESC LIMIT 5'
);
$stmt->execute([$uid]);
$recent = $stmt->fetchAll();

$statuses = task_statuses();

$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Hola, <?= e(current_user_name()) ?> 👋</h1>
    <a href="<?= e(base_url('tasks/create.php')) ?>" class="btn btn-primary">
        <i class="bi bi-plus-circle"></i> Nueva tarea
    </a>
</div>

<!-- Tarjetas de resumen -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card text-bg-primary shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="display-5 fw-bold"><?= $total ?></div>
                        <div class="small">Total de tareas</div>
                    </div>
                    <i class="bi bi-list-task fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-bg-secondary shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="display-5 fw-bold"><?= $counts['pendiente'] ?></div>
                        <div class="small">Pendientes</div>
                    </div>
                    <i class="bi bi-hourglass-split fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-bg-warning shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="display-5 fw-bold"><?= $counts['en_progreso'] ?></div>
                        <div class="small">En progreso</div>
                    </div>
                    <i class="bi bi-arrow-repeat fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-bg-success shadow-sm border-0 h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="display-5 fw-bold"><?= $counts['completada'] ?></div>
                        <div class="small">Completadas</div>
                    </div>
                    <i class="bi bi-check2-circle fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Barra de progreso de completadas -->
<?php if ($total > 0): ?>
    <?php $pct = (int) round($counts['completada'] / $total * 100); ?>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="d-flex justify-content-between mb-1">
                <span class="fw-semibold">Progreso general</span>
                <span class="text-muted"><?= $pct ?>% completado</span>
            </div>
            <div class="progress" role="progressbar" aria-valuenow="<?= $pct ?>"
                 aria-valuemin="0" aria-valuemax="100" style="height: 12px;">
                <div class="progress-bar bg-success" style="width: <?= $pct ?>%"></div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Tareas recientes -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-clock-history"></i> Tareas recientes</span>
        <a href="<?= e(base_url('tasks/index.php')) ?>" class="small">Ver todas</a>
    </div>
    <div class="card-body p-0">
        <?php if (!$recent): ?>
            <div class="text-center text-muted py-5">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                Aún no tienes tareas. ¡Crea la primera!
            </div>
        <?php else: ?>
            <div class="list-group list-group-flush">
                <?php foreach ($recent as $t): ?>
                    <a href="<?= e(base_url('tasks/edit.php?id=' . $t['id'])) ?>"
                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        <span class="text-truncate me-2"><?= e($t['title']) ?></span>
                        <span class="badge <?= e(status_badge_class($t['status'])) ?>">
                            <?= e($statuses[$t['status']]) ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
