// Nouron global JS — Bootstrap 5 tooltip init (remaining vanilla initializers)
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[rel="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el);
    });
});
