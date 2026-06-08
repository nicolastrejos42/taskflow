/* TaskFlow - JavaScript básico del lado del cliente */

document.addEventListener('DOMContentLoaded', function () {
    // Confirmación antes de enviar formularios marcados con data-confirm
    // (por ejemplo, la eliminación de tareas).
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var message = form.getAttribute('data-confirm') || '¿Estás seguro?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    // Auto-cierre de las alertas flash tras 4 segundos.
    document.querySelectorAll('.alert-dismissible').forEach(function (alert) {
        setTimeout(function () {
            if (window.bootstrap && bootstrap.Alert) {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            }
        }, 4000);
    });
});
