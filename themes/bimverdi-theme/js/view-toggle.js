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
                gridEl.classList.toggle('hidden', !isGrid);
                listEl.classList.toggle('hidden', isGrid);

                // Active button styling
                btnGrid.className = 'bv-view-toggle__btn bv-view-toggle__btn--grid px-2.5 py-2 rounded-md transition-all ' +
                    (isGrid ? 'bg-white text-[#111827] shadow-sm' : 'text-[#57534E]');
                btnList.className = 'bv-view-toggle__btn bv-view-toggle__btn--list px-2.5 py-2 rounded-md transition-all ' +
                    (isGrid ? 'text-[#57534E]' : 'bg-white text-[#111827] shadow-sm');

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
