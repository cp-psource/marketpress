document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.psource-tooltip').forEach(function(el) {
        el.addEventListener('touchstart', function() {
            var tip = el.querySelector('.psource-tooltip-text');
            if (tip) tip.style.visibility = 'visible';
        });
        el.addEventListener('touchend', function() {
            var tip = el.querySelector('.psource-tooltip-text');
            if (tip) tip.style.visibility = 'hidden';
        });
    });
});