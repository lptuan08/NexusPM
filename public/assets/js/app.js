lucide.createIcons();

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar-container');
    sidebar.classList.toggle('collapsed');
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
    const modal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));
    document.getElementById('deleteConfirmMessage').innerText = message;
    const deleteForm = document.getElementById('deleteForm');
    if (deleteForm) {
        deleteForm.setAttribute('action', url);
    }
    modal.show();
}