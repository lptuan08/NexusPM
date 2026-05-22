function refreshIcons() {
    if (window.lucide) {
        lucide.createIcons();
    }
}

refreshIcons();

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar-container');
    if (!sidebar) return;
    sidebar.classList.toggle('collapsed');
}

function setActiveSidebarLink() {
    const currentPath = window.location.pathname.replace(/\/+$/, '') || '/';
    document.querySelectorAll('.nav-link-custom[href]').forEach(link => {
        const linkPath = new URL(link.href, window.location.origin).pathname.replace(/\/+$/, '') || '/';
        const isActive = linkPath === '/'
            ? currentPath === '/'
            : currentPath === linkPath || currentPath.startsWith(`${linkPath}/`);

        link.classList.toggle('active', isActive);
    });
}

function clearFormValidation(form) {
    if (!form) return;

    form.querySelectorAll('.is-invalid').forEach(element => {
        element.classList.remove('is-invalid');
    });

    form.querySelectorAll('.border-danger').forEach(element => {
        element.classList.remove('border-danger');
    });

    form.querySelectorAll('.invalid-feedback, .form-error-message').forEach(element => {
        element.textContent = '';
        element.classList.add('d-none');
    });
}

window.NexusPM = Object.assign(window.NexusPM || {}, {
    clearFormValidation
});

// Xử lý chọn tất cả checkbox trong bảng
function initProjectSwitchers() {
    document.querySelectorAll('[data-project-switcher]').forEach(switcher => {
        const searchInput = switcher.querySelector('[data-project-switcher-search]');
        const items = Array.from(switcher.querySelectorAll('[data-project-switcher-item]'));
        const emptyState = switcher.querySelector('[data-project-switcher-empty]');

        if (!searchInput || searchInput.dataset.bound === 'true') return;
        searchInput.dataset.bound = 'true';

        searchInput.addEventListener('click', event => {
            event.stopPropagation();
        });

        searchInput.addEventListener('keydown', event => {
            event.stopPropagation();
        });

        searchInput.addEventListener('input', () => {
            const keyword = searchInput.value.trim().toLocaleLowerCase('vi-VN');
            let visibleCount = 0;

            items.forEach(item => {
                const haystack = item.dataset.projectSearch || '';
                const isVisible = keyword === '' || haystack.includes(keyword);
                item.classList.toggle('d-none', !isVisible);
                if (isVisible) visibleCount++;
            });

            if (emptyState) {
                emptyState.classList.toggle('d-none', visibleCount > 0);
            }
        });
    });
}

const selectAll = document.getElementById('selectAll');
if (selectAll) {
    selectAll.addEventListener('change', function () {
        const checkboxes = document.querySelectorAll('tbody .form-check-input');
        checkboxes.forEach(checkbox => checkbox.checked = this.checked);
    });
}

/**
 * Hàm hiển thị Modal xác nhận xóa dùng chung
 * @param {string} url - Đường dẫn thực hiện hành động xóa
 * @param {string} message - Lời nhắn hiển thị trên Modal
 */
function showDeleteModal(url, message) {
    const modalElement = document.getElementById('deleteConfirmModal');
    if (!modalElement) return;

    const modal = new bootstrap.Modal(modalElement);
    const messageElement = document.getElementById('deleteConfirmMessage');
    if (messageElement) {
        messageElement.innerText = message;
    }

    const deleteForm = document.getElementById('deleteForm');
    if (deleteForm) {
        deleteForm.setAttribute('action', url);
    }
    modal.show();
}

document.addEventListener('DOMContentLoaded', function () {
    setActiveSidebarLink();
    initProjectSwitchers();
    refreshIcons();
});
