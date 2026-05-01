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

// Xử lý chọn tất cả checkbox trong bảng
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
    refreshIcons();
});
