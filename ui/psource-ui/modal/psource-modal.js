document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[data-psource-modal-open]').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var targetId = btn.getAttribute('data-psource-modal-open');
            var target = document.getElementById(targetId);
            if (target) {
                // Generisch: Wenn das Modal ein iframe enthält, setze das src-Attribut
                var iframe = target.querySelector('iframe');
                if (iframe && btn.hasAttribute('href')) {
                    iframe.src = btn.getAttribute('href');
                }
                target.showModal();
            }
        });
    });
    document.querySelectorAll('.psource-modal-close').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            var dialog = btn.closest('dialog');
            // Generisch: iframe src zurücksetzen, wenn vorhanden
            if (dialog) {
                var iframe = dialog.querySelector('iframe');
                if (iframe) iframe.src = '';
                dialog.close();
            }
        });
    });
});