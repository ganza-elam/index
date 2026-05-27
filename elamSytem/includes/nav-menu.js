(function () {
    function closeAllDropdowns(except) {
        document.querySelectorAll('.sidebar-dropdown.is-open').forEach(function (d) {
            if (except && d === except) {
                return;
            }
            d.classList.remove('is-open');
            var t = d.querySelector('.sidebar-dropdown-toggle');
            if (t) {
                t.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function openDropdown(wrap) {
        if (!wrap) return;
        closeAllDropdowns(wrap);
        wrap.classList.add('is-open');
        var btn = wrap.querySelector('.sidebar-dropdown-toggle');
        if (btn) {
            btn.setAttribute('aria-expanded', 'true');
        }
    }

    document.querySelectorAll('.sidebar-dropdown-toggle').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var wrap = btn.closest('.sidebar-dropdown');
            if (!wrap) return;
            var isOpen = wrap.classList.contains('is-open');
            closeAllDropdowns();
            if (!isOpen) {
                openDropdown(wrap);
            }
        });
    });

    document.querySelectorAll('.sidebar-dropdown-item').forEach(function (link) {
        link.addEventListener('click', function () {
            var wrap = link.closest('.sidebar-dropdown');
            closeAllDropdowns(wrap);
            if (wrap) {
                openDropdown(wrap);
            }
        });
    });

    document.querySelectorAll('.sidebar-dropdown-item.is-active').forEach(function (item) {
        openDropdown(item.closest('.sidebar-dropdown'));
    });

    if (window.location.hash) {
        var id = window.location.hash.slice(1);
        var el = document.getElementById(id);
        if (el) {
            setTimeout(function () {
                el.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 150);
        }
    }
})();
