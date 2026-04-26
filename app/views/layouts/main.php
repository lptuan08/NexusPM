<?php
require_once VIEW_PATH . '/partials/header.php';
require_once VIEW_PATH . '/partials/sidebar.php';
?>

<!-- Wrapper cho phần nội dung bên phải Sidebar -->
<div class="flex-grow-1 d-flex flex-column min-vw-0 h-100 bg-slate-100">
    <?php require_once VIEW_PATH . '/partials/topbar.php'; ?>
    <?php require_once VIEW_PATH . '/partials/toast.php'; ?>

    <main class="flex-grow-1 overflow-auto p-4">
        <!-- Nội dung động được render từ View::render sẽ hiển thị ở đây -->
        <?= $content ?? '' ?>
    </main>
</div>
<?php require_once VIEW_PATH . '/partials/footer.php'; ?>