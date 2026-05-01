<!-- Toast Notification System -->
<div class="toast-container position-fixed top-0 end-0 p-3">
    <?php
    $flashTypes = [
        'success' => ['icon' => 'check-circle', 'class' => 'toast-success'],
        'error'   => ['icon' => 'alert-circle', 'class' => 'toast-error']
    ];

    foreach ($flashTypes as $type => $config):
        if ($msg = \App\helpers\Helper::getFlash($type)): ?>
            <div class="toast toast-custom <?= $config['class'] ?>" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex p-3">
                    <div class="toast-icon-wrapper me-3">
                        <i data-lucide="<?= $config['icon'] ?>" size="18"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold small text-slate-800">Thông báo</div>
                        <div class="small text-slate-500"><?= htmlspecialchars($msg) ?></div>
                    </div>
                    <button type="button" class="btn-close small" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
    <?php endif;
    endforeach; ?>
</div>