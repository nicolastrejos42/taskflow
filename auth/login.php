<?php
/**
 * TaskFlow - Inicio de sesión.
 */
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    header('Location: ' . base_url('index.php'));
    exit;
}

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Introduce tu correo y contraseña.';
    } else {
        $stmt = db()->prepare('SELECT id, name, password FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerar id de sesión para prevenir fijación de sesión.
            session_regenerate_id(true);
            $_SESSION['user_id']   = (int) $user['id'];
            $_SESSION['user_name'] = $user['name'];

            header('Location: ' . base_url('index.php'));
            exit;
        }

        $errors[] = 'Credenciales incorrectas. Verifica tu correo y contraseña.';
    }
}

$pageTitle = 'Iniciar sesión';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-sm-10 col-md-7 col-lg-5">
        <div class="text-center mt-4 mb-3">
            <h1 class="display-6 fw-bold text-primary">
                <i class="bi bi-check2-square"></i> TaskFlow
            </h1>
            <p class="text-muted">Sistema Web de Gestión de Tareas</p>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h2 class="h5 mb-4 text-center">Iniciar sesión</h2>

                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $err): ?>
                            <div><?= e($err) ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= e($email) ?>" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right"></i> Entrar
                    </button>
                </form>

                <p class="text-center mt-3 mb-0 small">
                    ¿No tienes cuenta?
                    <a href="<?= e(base_url('auth/register.php')) ?>">Regístrate</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
