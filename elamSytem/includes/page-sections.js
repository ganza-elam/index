/**
 * Show one main content section at a time (from sidebar hash links).
 * Elements: .nav-page-section[data-nav-section], .nav-page-section--always stays visible.
 */
(function () {
    if (document.body.getAttribute('data-skip-page-sections') === '1') {
        return;
    }
    var sections = document.querySelectorAll('.nav-page-section[data-nav-section]');
    if (!sections.length) {
        return;
    }

    function sectionIdFromHref(href) {
        if (!href) return '';
        var hash = '';
        try {
            var u = new URL(href, window.location.href);
            hash = u.hash ? u.hash.slice(1) : '';
        } catch (e) {
            var parts = href.split('#');
            hash = parts.length > 1 ? parts[1] : '';
        }
        return hash;
    }

    function resolveActiveSectionId() {
        var hash = window.location.hash.replace(/^#/, '');
        // Filter bar is always visible; do not hide report tables when Search uses #report-filters
        if (hash === 'report-filters') {
            hash = '';
        }
        if (hash) {
            return hash;
        }
        var bodyDefault = document.body.getAttribute('data-default-nav-section');
        if (bodyDefault) {
            return bodyDefault;
        }
        var active = document.querySelector('.sidebar-dropdown-item.is-active');
        if (active) {
            var fromLink = sectionIdFromHref(active.getAttribute('href'));
            if (fromLink) {
                return fromLink;
            }
        }
        var first = sections[0];
        return first ? first.getAttribute('data-nav-section') : '';
    }

    function applySections() {
        var activeId = resolveActiveSectionId();
        var found = false;
        sections.forEach(function (el) {
            var sid = el.getAttribute('data-nav-section');
            if (sid === activeId) {
                found = true;
            }
        });
        if (!found && sections[0]) {
            activeId = sections[0].getAttribute('data-nav-section');
        }

        sections.forEach(function (el) {
            var sid = el.getAttribute('data-nav-section');
            var show = el.classList.contains('nav-page-section--always') || sid === activeId;
            el.hidden = !show;
            el.classList.toggle('is-nav-visible', show);
        });

        document.querySelectorAll('.sidebar-dropdown-item').forEach(function (link) {
            var sid = sectionIdFromHref(link.getAttribute('href'));
            link.classList.toggle('is-active', sid !== '' && sid === activeId);
        });

        window.dispatchEvent(new CustomEvent('nav-section-changed', { detail: { sectionId: activeId } }));
    }

    window.addEventListener('hashchange', applySections);
    document.addEventListener('DOMContentLoaded', function () {
        applySections();
        document.querySelectorAll('.sidebar-dropdown-item[href*="#"]').forEach(function (link) {
            link.addEventListener('click', function () {
                var sid = sectionIdFromHref(link.getAttribute('href'));
                if (sid) {
                    window.location.hash = sid;
                }
            });
        });
    });
})();
