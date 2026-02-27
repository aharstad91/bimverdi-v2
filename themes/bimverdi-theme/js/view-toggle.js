/**
 * View Toggle (Grid/List)
 *
 * Toggles between grid and list containers, persists choice in localStorage,
 * and forces grid on mobile viewports.
 */
(function () {
    'use strict';

    function init() {
        var toggles = document.querySelectorAll('.bv-view-toggle');
        if (!toggles.length) return;

        toggles.forEach(function (toggle) {
            var key     = toggle.dataset.storageKey;
            var gridId  = toggle.dataset.gridId;
            var listId  = toggle.dataset.listId;
            var gridEl  = document.getElementById(gridId);
            var listEl  = document.getElementById(listId);
            var btnGrid = toggle.querySelector('.bv-view-toggle__btn--grid');
            var btnList = toggle.querySelector('.bv-view-toggle__btn--list');

            if (!gridEl || !listEl || !btnGrid || !btnList) return;

            function setView(mode) {
                var isGrid = (mode === 'grid');
                gridEl.style.display = isGrid ? '' : 'none';
                listEl.style.display = isGrid ? 'none' : '';

                btnGrid.setAttribute('aria-pressed', isGrid ? 'true' : 'false');
                btnList.setAttribute('aria-pressed', isGrid ? 'false' : 'true');

                try { localStorage.setItem(key, mode); } catch (e) { /* quota */ }
            }

            btnGrid.addEventListener('click', function () { setView('grid'); });
            btnList.addEventListener('click', function () { setView('list'); });

            // Restore saved preference (grid on mobile regardless)
            var isMobile = window.innerWidth < 768;
            var saved = null;
            try { saved = localStorage.getItem(key); } catch (e) { /* private */ }
            setView(isMobile ? 'grid' : (saved || 'grid'));

            // Force grid when resizing to mobile
            var mql = window.matchMedia('(max-width: 767px)');
            mql.addEventListener('change', function (e) {
                if (e.matches) setView('grid');
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
