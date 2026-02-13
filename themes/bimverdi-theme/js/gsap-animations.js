/**
 * BIM Verdi — GSAP Page-Load Animations
 *
 * Usage:  Add data-animate="<type>" to any HTML element.
 *
 *   Types:
 *     fade-up      — Fade in + slide up 30px   (default)
 *     fade-in      — Fade in (no movement)
 *     fade-left    — Fade in + slide from left
 *     fade-right   — Fade in + slide from right
 *     fade-down    — Fade in + slide down
 *     scale-in     — Fade in + scale from 0.95
 *
 *   Optional attributes:
 *     data-animate-delay="0.2"      — Delay in seconds
 *     data-animate-duration="0.6"   — Duration in seconds
 *     data-animate-stagger="0.1"    — Stagger children (on parent)
 *
 *   Stagger children:
 *     Add data-animate-stagger on a parent to auto-stagger
 *     all direct children that have data-animate.
 *
 * @package BIMVerdi
 * @version 1.0.0
 */

(function () {
    'use strict';

    // Skip animations if user prefers reduced motion
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.querySelectorAll('[data-animate]').forEach(function (el) {
            el.style.opacity = '1';
        });
        return;
    }

    // Animation presets — { from-values }
    var presets = {
        'fade-up':    { y: 30 },
        'fade-down':  { y: -30 },
        'fade-left':  { x: -30 },
        'fade-right': { x: 30 },
        'fade-in':    {},
        'scale-in':   { scale: 0.95 },
    };

    var defaultDuration = 0.7;
    var defaultEase = 'power2.out';

    /**
     * Animate a single element using fromTo (explicit start and end)
     */
    function animateElement(el, extraDelay) {
        var type = el.getAttribute('data-animate') || 'fade-up';
        var preset = presets[type] || presets['fade-up'];
        var delay = parseFloat(el.getAttribute('data-animate-delay') || 0) + (extraDelay || 0);
        var duration = parseFloat(el.getAttribute('data-animate-duration') || defaultDuration);

        // Build "from" values: preset movement + opacity 0
        var fromVars = Object.assign({ opacity: 0 }, preset);

        // Build "to" values: natural position + opacity 1
        var toVars = { opacity: 1, x: 0, y: 0, scale: 1, duration: duration, delay: delay, ease: defaultEase };

        gsap.fromTo(el, fromVars, toVars);
    }

    /**
     * Init: find all [data-animate] elements and animate them
     */
    function init() {
        // 1. Handle stagger groups first
        var staggerParents = document.querySelectorAll('[data-animate-stagger]');
        var staggeredEls = new Set();

        staggerParents.forEach(function (parent) {
            var stagger = parseFloat(parent.getAttribute('data-animate-stagger') || 0.1);
            var children = parent.querySelectorAll(':scope > [data-animate]');

            children.forEach(function (child, i) {
                staggeredEls.add(child);
                animateElement(child, i * stagger);
            });
        });

        // 2. Animate remaining standalone elements
        document.querySelectorAll('[data-animate]').forEach(function (el) {
            if (!staggeredEls.has(el)) {
                animateElement(el);
            }
        });
    }

    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

})();
