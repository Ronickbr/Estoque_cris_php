</main>
<footer class="bg-light text-center text-muted py-3 mt-5">
    <div class="container">
        <p class="mb-0">&copy; <?php echo date('Y'); ?> Sistema de Estoque</p>
    </div>
</footer>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- Custom Scripts -->
<script src="/estoque/assets/js/script.js"></script>
<?php if (ob_get_level() > 0) ob_end_flush(); ?>
</body>
</html>