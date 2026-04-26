<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= !empty($pageTitle) ? $pageTitle : "" ?></title>
    <?php if (isset($extra_css)): ?>
        <link rel="stylesheet" href="<?= URLROOT; ?>/assets/css/<?= $extra_css ?>.css">
    <?php endif; ?>


    <!-- Font Roboto (Google Standard) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="<?= URLROOT; ?>/assets/css/app.css">

    <!-- toast -->
    <link rel="stylesheet" href="<?= Helper::asset('assets/css/toast.css') ?>">
    <!-- ... -->
    <script src="<?= Helper::asset('assets/js/toast.js') ?>"></script>

</head>

<body class="d-flex vh-100 overflow-hidden">
    