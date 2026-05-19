document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-low="1"]').forEach(function (row) {
        row.classList.add('low-stock');
    });
});