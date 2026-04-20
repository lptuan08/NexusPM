<?php
require_once APPROOT . '/app/views/partials/header.php';
require_once APPROOT . '/app/views/partials/sidebar.php';
?>

<!-- Wrapper cho phần nội dung bên phải Sidebar -->
<div class="flex-grow-1 d-flex flex-column min-vw-0 h-100 bg-slate-100">
    <?php require_once APPROOT . '/app/views/partials/topbar.php'; ?>

    <main class="flex-grow-1 overflow-auto p-4">
        <?php 
            // Ở đây sau này sẽ là biến $content từ Controller
            require_once APPROOT . '/app/views/projects/index.php'; 
        ?>
    </main>
</div>

<?php require_once APPROOT . '/app/views/partials/footer.php'; ?>
