    <?php /* cierre del <main> abierto en header.php */ ?>
</main>

<footer class="text-center text-muted py-4 small">
    TaskFlow &copy; <?= date('Y') ?> · Sistema Web de Gestión de Tareas
</footer>

<!-- Bootstrap 5 JS (bundle con Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- JS propio -->
<script src="<?= e(base_url('assets/js/app.js')) ?>"></script>
</body>
</html>
