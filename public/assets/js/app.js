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