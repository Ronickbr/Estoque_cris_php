</main>
<footer class="bg-white border-top text-center text-muted py-3 mt-auto">
    <div class="container-fluid">
        <p class="mb-0 small text-muted">&copy; <?php echo date('Y'); ?> Sistema de Estoque Pro. Todos os direitos reservados.</p>
    </div>
</footer>
<?php if (estaLogado()): ?>
</div> <!-- #mainWrapper -->
<?php endif; ?>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<!-- Custom Scripts -->
<script src="<?php echo function_exists('url') ? url('/assets/js/script.js') : '/assets/js/script.js'; ?>?v=<?php echo @filemtime(__DIR__ . '/../assets/js/script.js') ?: '2.1'; ?>"></script>
<?php if (ob_get_level() > 0) ob_end_flush(); ?>
</body>
</html>