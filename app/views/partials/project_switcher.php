<?php
/**
 * Shared project context switcher for task views.
 *
 * @var array $projects
 * @var array|null $selectedProject
 * @var bool $projectSwitcherAllowAll
 * @var string $projectSwitcherMode
 * @var string|null $projectSwitcherAllUrl
 * @var string|null $projectSwitcherTitle
 * @var int|null $projectSwitcherTaskCount
 * @var string|null $projectSwitcherEyebrow
 * @var string|null $projectSwitcherAllTitle
 * @var string|null $projectSwitcherAllMeta
 * @var string|null $projectSwitcherAllIcon
 * @var string|null $projectSwitcherSideLinkLabel
 * @var string|null $projectSwitcherCountLabel
 */

$projects = $projects ?? [];
$selectedProject = $selectedProject ?? null;
$projectSwitcherAllowAll = $projectSwitcherAllowAll ?? false;
$projectSwitcherMode = $projectSwitcherMode ?? 'list';
$projectSwitcherAllUrl = $projectSwitcherAllUrl ?? URLROOT . '/tasks';
$projectSwitcherTaskCount = $projectSwitcherTaskCount ?? null;
$projectSwitcherEyebrow = $projectSwitcherEyebrow ?? 'Dự án';
$projectSwitcherAllTitle = $projectSwitcherAllTitle ?? 'Tất cả dự án';
$projectSwitcherAllMeta = $projectSwitcherAllMeta ?? 'Xem toàn bộ công việc';
$projectSwitcherAllIcon = $projectSwitcherAllIcon ?? 'layers-3';
$projectSwitcherSideLinkLabel = $projectSwitcherSideLinkLabel ?? 'Xem tất cả công việc dạng list';
$projectSwitcherCountLabel = $projectSwitcherCountLabel ?? 'công việc';
$projectSwitcherTitle = $projectSwitcherTitle
    ?? (!empty($selectedProject['name']) ? (string) $selectedProject['name'] : 'Tất cả công việc');

$normalizeSearchText = static function (string $text): string {
    return function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
};

$projectHref = static function (array $project) use ($projectSwitcherMode): string {
    $projectId = (int) ($project['id'] ?? 0);

    if ($projectSwitcherMode === 'kanban') {
        return URLROOT . "/tasks/{$projectId}/kanban";
    }

    if ($projectSwitcherMode === 'settings_task') {
        return URLROOT . "/settings/task?project_id={$projectId}";
    }

    return URLROOT . "/tasks?project_id={$projectId}";
};
?>

<div class="project-context d-flex align-items-center gap-3 min-vw-0">
    <div class="dropdown tasks-project-dropdown project-switcher" data-project-switcher>
        <button class="btn btn-link project-switcher-trigger text-decoration-none shadow-none border-0" type="button" data-bs-toggle="dropdown" data-bs-offset="0,8" aria-expanded="false">
            <span class="project-switcher-icon">
                <i data-lucide="folder-kanban"></i>
            </span>
            <span class="project-switcher-text">
                <span class="project-switcher-eyebrow"><?= htmlspecialchars($projectSwitcherEyebrow, ENT_QUOTES, 'UTF-8') ?></span>
                <span class="project-switcher-title"><?= htmlspecialchars($projectSwitcherTitle, ENT_QUOTES, 'UTF-8') ?></span>
            </span>
            <i data-lucide="chevron-down" class="project-switcher-chevron"></i>
        </button>

        <ul class="dropdown-menu dropdown-menu-start shadow-xl border-0">
            <li class="project-switcher-search px-3 py-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white text-slate-400">
                        <i data-lucide="search" size="16"></i>
                    </span>
                    <input type="search" class="form-control border-start-0" placeholder="Tìm dự án..." data-project-switcher-search>
                </div>
            </li>

            <?php if ($projectSwitcherAllowAll): ?>
                <li>
                    <a class="dropdown-item project-switcher-item <?= empty($selectedProject) ? 'active' : '' ?>" href="<?= htmlspecialchars($projectSwitcherAllUrl, ENT_QUOTES, 'UTF-8') ?>">
                        <span class="project-switcher-item-icon">
                            <i data-lucide="<?= htmlspecialchars($projectSwitcherAllIcon, ENT_QUOTES, 'UTF-8') ?>"></i>
                        </span>
                        <span class="project-switcher-item-main">
                            <span class="project-switcher-item-title"><?= htmlspecialchars($projectSwitcherAllTitle, ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="project-switcher-item-meta"><?= htmlspecialchars($projectSwitcherAllMeta, ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                        <?php if (empty($selectedProject)): ?>
                            <i data-lucide="check" class="project-switcher-check"></i>
                        <?php endif; ?>
                    </a>
                </li>
                <li><hr class="dropdown-divider opacity-50 my-1"></li>
            <?php endif; ?>

            <li class="px-0 py-0">
                <div class="project-dropdown-scroll">
                    <?php foreach ($projects as $project): ?>
                        <?php
                        $isCurrentProject = (string) ($selectedProject['id'] ?? '') === (string) ($project['id'] ?? '');
                        $projectName = (string) ($project['name'] ?? 'Dự án');
                        $projectCode = (string) ($project['project_code'] ?? '');
                        $statusName = (string) ($project['status_name'] ?? '');
                        $searchText = $normalizeSearchText(trim($projectName . ' ' . $projectCode . ' ' . $statusName));
                        ?>
                        <a class="dropdown-item project-switcher-item <?= $isCurrentProject ? 'active' : '' ?>"
                            href="<?= htmlspecialchars($projectHref($project), ENT_QUOTES, 'UTF-8') ?>"
                            data-project-switcher-item
                            data-project-search="<?= htmlspecialchars($searchText, ENT_QUOTES, 'UTF-8') ?>">
                            <span class="project-switcher-item-icon">
                                <i data-lucide="folder"></i>
                            </span>
                            <span class="project-switcher-item-main">
                                <span class="project-switcher-item-title"><?= htmlspecialchars($projectName, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="project-switcher-item-meta">
                                    <?= htmlspecialchars($projectCode !== '' ? $projectCode : 'Chưa có mã', ENT_QUOTES, 'UTF-8') ?>
                                    <?php if ($statusName !== ''): ?>
                                        <span class="project-switcher-dot">&middot;</span>
                                        <?= htmlspecialchars($statusName, ENT_QUOTES, 'UTF-8') ?>
                                    <?php endif; ?>
                                </span>
                            </span>
                            <?php if ($isCurrentProject): ?>
                                <i data-lucide="check" class="project-switcher-check"></i>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>

                    <div class="project-switcher-empty d-none" data-project-switcher-empty>
                        Không tìm thấy dự án phù hợp.
                    </div>
                </div>
            </li>

            <?php if (!$projectSwitcherAllowAll): ?>
                <li><hr class="dropdown-divider opacity-50 my-1"></li>
                <li>
                    <a class="dropdown-item project-switcher-side-link" href="<?= htmlspecialchars($projectSwitcherAllUrl, ENT_QUOTES, 'UTF-8') ?>">
                        <i data-lucide="list"></i>
                        <span><?= htmlspecialchars($projectSwitcherSideLinkLabel, ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <?php if (!empty($selectedProject)): ?>
        <?php $projectStatusColor = $selectedProject['status_color'] ?? '#64748b'; ?>
        <div class="project-context-meta d-flex align-items-center gap-2 border-start border-slate-200">
            <?php if (!empty($selectedProject['project_code'])): ?>
                <span class="text-slate-500 small fw-medium"><?= htmlspecialchars((string) $selectedProject['project_code'], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>

            <?php if (!empty($selectedProject['status_name'])): ?>
                <span class="status-pill py-0 px-2" style="font-size: 11px; background-color: <?= htmlspecialchars((string) $projectStatusColor, ENT_QUOTES, 'UTF-8') ?>20; color: <?= htmlspecialchars((string) $projectStatusColor, ENT_QUOTES, 'UTF-8') ?>;">
                    <?= htmlspecialchars((string) $selectedProject['status_name'], ENT_QUOTES, 'UTF-8') ?>
                </span>
            <?php endif; ?>

            <?php if ($projectSwitcherTaskCount !== null): ?>
                <span class="ui-badge status-muted py-0 px-2" style="font-size: 11px;">
                    <?= (int) $projectSwitcherTaskCount ?> <?= htmlspecialchars($projectSwitcherCountLabel, ENT_QUOTES, 'UTF-8') ?>
                </span>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
