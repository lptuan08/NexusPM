    <!-- Bootstrap Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- File JS chính của ứng dụng -->
    <script src="<?= URLROOT; ?>/assets/js/app.js"></script>
    <?php if (isset($extra_js)): ?>
        <?php foreach ((array) $extra_js as $jsFile): ?>
            <script src="<?= URLROOT; ?>/assets/js/<?= htmlspecialchars($jsFile, ENT_QUOTES, 'UTF-8') ?>.js"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    
</body>
</html>
