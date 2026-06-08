<?php
/**
 * TaskFlow - Registro de usuarios.
 */
require_once __DIR__ . '/../includes/functions.php';

// Si ya hay sesión, no tiene sentido registrarse.
if (is_logged_in()) {
    header('Location: ' . base_url('index.php'));
    exit;
}

$errors = [];
$name   = '';
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['password_confirm'] ?? '';

    if ($name === '') {
        $errors[] = 'El nombre es obligatorio.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Introduce un correo electrónico válido.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'La contraseña debe tener al menos 6 caracteres.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Las contraseñas no coinciden.';
    }

    // Comprobar que el correo no esté ya registrado.
    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Ya existe una cuenta con ese correo electrónico.';
        }
    }

    if (!$errors) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = db()->prepare(
            'INSERT INTO users (name, email, password) VALUES (?, ?, ?)'
        );
        $stmt->execute([$name, $email, $hash]);

        flash('success', 'Cuenta creada con éxito. Ya puedes iniciar sesión.');
        header('Location: ' . base_url('auth/login.php'));
        exit;
    }
}

$pageTitle = 'Crear cuenta';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-12 col-sm-10 col-md-7 col-lg-5">
        <div class="card shadow-sm border-0 mt-4">
            <div class="card-body p-4">
                <h1 class="h4 text-center mb-1">
                    <i class="bi bi-person-plus text-primary"></i> Crear cuenta
                </h1>
                <p class="text-center text-muted small mb-4">Regístrate para empezar a gestionar tus tareas</p>

                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="name" class="form-control"
                               value="<?= e($name) ?>" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= e($email) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Contraseña</label>
                        <input type="password" name="password" class="form-control"
                               minlength="6" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Repetir contraseña</label>
                        <input type="password" name="password_confirm" class="form-control"
                               minlength="6" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check2"></i> Registrarme
                    </button>
                </form>

                <p class="text-center mt-3 mb-0 small">
                    ¿Ya tienes cuenta?
                    <a href="<?= e(base_url('auth/login.php')) ?>">Inicia sesión</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
