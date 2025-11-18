    </div>

    <footer class="footer mt-auto py-3 bg-light">
        <div class="container text-center">
            <span class="text-muted"><?php echo SITE_NAME; ?> &copy; <?php echo date('Y'); ?></span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Activar tooltips de Bootstrap
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Confirmación para acciones importantes
        document.addEventListener('DOMContentLoaded', function() {
            var deleteButtons = document.querySelectorAll('.confirm-delete');
            deleteButtons.forEach(function(button) {
                button.addEventListener('click', function(e) {
                    if (!confirm('¿Estás seguro de que deseas realizar esta acción?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>
