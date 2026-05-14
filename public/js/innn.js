document.addEventListener('DOMContentLoaded', function () {

    // Fade-cycle animation for unread inbox messages
    document.querySelectorAll('.new-inbox-message').forEach(function (el, index) {
        el.style.setProperty('--fade-delay', (index * 0.25) + 's');
        el.classList.add('fade-cycling');
    });

    // Message archive/delete buttons
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.message-options a.btn');
        if (!btn) return;
        e.preventDefault();

        var siblings = btn.closest('.message-options').querySelectorAll('a.btn');
        siblings.forEach(function (s) { s.classList.add('disabled'); });

        fetch(btn.getAttribute('href'), {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            var optionsId   = btn.closest('.message-options').getAttribute('id');
            var messageDomId = optionsId ? optionsId.replace('-options', '') : null;
            if (data.result && (data.status === 'archived' || data.status === 'deleted')) {
                var row = messageDomId ? document.getElementById(messageDomId) : null;
                if (row) row.style.display = 'none';
            } else if (data.result) {
                siblings.forEach(function (s) { s.classList.add('disabled'); });
                var span = document.createElement('span');
                span.className = 'btn';
                span.innerHTML = btn.innerHTML;
                btn.replaceWith(span);
                var opts = document.getElementById(messageDomId + ' .message-options a.btn:last-child');
                if (opts) opts.classList.remove('hidden', 'disabled');
            }
            var flash = document.getElementById('flashMessages');
            if (flash) {
                var alert = document.createElement('div');
                alert.className = 'alert alert-success';
                alert.textContent = 'ok';
                flash.appendChild(alert);
            }
        })
        .catch(function () {
            var flash = document.getElementById('flashMessages');
            if (flash) {
                var alert = document.createElement('div');
                alert.className = 'alert alert-danger';
                alert.textContent = 'Ein Fehler ist aufgetreten';
                flash.appendChild(alert);
            }
        });
    });
});
