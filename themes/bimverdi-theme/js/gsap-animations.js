/**
 * BIM Verdi — GSAP Page Transitions
 *
 * Subtle fade-in on page load, fade-out on navigation.
 *
 * @package BIMVerdi
 * @version 1.0.0
 */

(function () {
    'use strict';

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    var duration = 0.35;
    var ease = 'power1.out';

    // Fade in on page load
    gsap.fromTo('body', { opacity: 0 }, { opacity: 1, duration: duration, ease: ease });

    // Fade out on internal link clicks
    document.addEventListener('click', function (e) {
        var link = e.target.closest('a[href]');
        if (!link) return;

        var href = link.getAttribute('href');

        // Skip non-navigating links
        if (!href
            || href.startsWith('#')
            || href.startsWith('javascript:')
            || href.startsWith('mailto:')
            || href.startsWith('tel:')
            || link.target === '_blank'
            || link.hasAttribute('download')
            || e.ctrlKey || e.metaKey || e.shiftKey
        ) return;

        // Skip external links
        try {
            var url = new URL(href, window.location.origin);
            if (url.origin !== window.location.origin) return;
        } catch (_) {
            return;
        }

        // Skip WP admin links
        if (href.indexOf('/wp-admin') !== -1 || href.indexOf('wp-login') !== -1) return;

        e.preventDefault();
        gsap.to('body', {
            opacity: 0,
            duration: duration,
            ease: 'power1.in',
            onComplete: function () {
                window.location.href = href;
            },
        });
    });

})();
