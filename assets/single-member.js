/* Related Batchmates Tab Switching */
document.addEventListener('DOMContentLoaded', function () {
    var tabs  = document.querySelectorAll('.mp-rtab');
    var panes = document.querySelectorAll('.mp-related-pane');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var target = this.dataset.tab;
            tabs.forEach(function (t) { t.classList.remove('mp-rtab-active'); });
            panes.forEach(function (p) { p.classList.remove('mp-rpane-active'); });
            this.classList.add('mp-rtab-active');
            var pane = document.querySelector('.mp-related-pane[data-pane="' + target + '"]');
            if (pane) pane.classList.add('mp-rpane-active');
        });
    });

    /* Carousel Arrow Buttons */
    function initCarouselArrows(wrap) {
        var grid = wrap.querySelector('[data-carousel]');
        var prev = wrap.querySelector('.mp-car-prev');
        var next = wrap.querySelector('.mp-car-next');
        if (!grid || !prev || !next) return;

        function updateArrows() {
            prev.disabled = grid.scrollLeft <= 2;
            next.disabled = grid.scrollLeft + grid.clientWidth >= grid.scrollWidth - 2;
        }

        prev.addEventListener('click', function () {
            grid.scrollBy({ left: -220, behavior: 'smooth' });
        });
        next.addEventListener('click', function () {
            grid.scrollBy({ left: 220, behavior: 'smooth' });
        });
        grid.addEventListener('scroll', updateArrows);
        updateArrows();
    }

    document.querySelectorAll('.mp-carousel-wrap').forEach(initCarouselArrows);

    /* Sidebar Scroll Spy */
    var sectionIds = ['section-overview', 'section-academic', 'section-professional-full', 'section-leadership', 'section-personal', 'section-skills'];
    var navLinks = document.querySelectorAll('.mp-sidebar-nav a[href^="#"]');

    function setActiveNav(id) {
        navLinks.forEach(function(link) {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + id) {
                link.classList.add('active');
            }
        });
    }

    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                setActiveNav(entry.target.id);
            }
        });
    }, {
        rootMargin: '-20% 0px -70% 0px',
        threshold: 0
    });

    sectionIds.forEach(function(id) {
        var el = document.getElementById(id);
        if (el) observer.observe(el);
    });
});
