document.addEventListener('DOMContentLoaded', function () {
    const toastElements = document.querySelectorAll('.toast');
    toastElements.forEach(el => {
        const toast = new bootstrap.Toast(el, {
            autohide: true,
            delay: 5000
        });
        toast.show();
    });
});