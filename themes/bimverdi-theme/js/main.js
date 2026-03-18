/**
 * BIM Verdi Theme - Main JavaScript
 * 
 * @package BIMVerdi
 * @version 2.0.0
 */

(function() {
    'use strict';
    
    // Mobile menu toggle
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const mobileMenu = document.getElementById('mobile-menu');

    if (mobileMenuButton && mobileMenu) {
        const iconOpen = mobileMenuButton.querySelector('.bv-mobile-icon-open');
        const iconClose = mobileMenuButton.querySelector('.bv-mobile-icon-close');

        function toggleMobileMenu(show) {
            const isOpen = typeof show === 'boolean' ? show : mobileMenu.classList.contains('hidden');
            mobileMenu.classList.toggle('hidden', !isOpen);
            document.body.classList.toggle('overflow-hidden', isOpen);
            mobileMenuButton.setAttribute('aria-expanded', isOpen);
            if (iconOpen) iconOpen.classList.toggle('hidden', isOpen);
            if (iconClose) iconClose.classList.toggle('hidden', !isOpen);
        }

        mobileMenuButton.addEventListener('click', function() { toggleMobileMenu(); });

        // Close on Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !mobileMenu.classList.contains('hidden')) {
                toggleMobileMenu(false);
            }
        });

        // Close on link click inside menu
        mobileMenu.querySelectorAll('a').forEach(function(link) {
            link.addEventListener('click', function() { toggleMobileMenu(false); });
        });

        // Auto-close on resize above md (768px)
        window.addEventListener('resize', function() {
            if (window.innerWidth >= 768 && !mobileMenu.classList.contains('hidden')) {
                toggleMobileMenu(false);
            }
        });
    }
    
    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });

    // Hero rotating slides (title + description + dots)
    const heroTitles = document.getElementById('hero-rotating');
    const heroDescs = document.getElementById('hero-desc');
    const heroDots = document.getElementById('hero-dots');
    const heroCounter = document.getElementById('hero-counter');

    if (heroTitles && heroDescs) {
        const titles = heroTitles.querySelectorAll(':scope > span');
        const descs = heroDescs.querySelectorAll(':scope > span');
        const dots = heroDots ? heroDots.querySelectorAll('.bv-hero-dot') : [];
        const total = titles.length;
        let current = 0;
        let timer;

        function goToSlide(index) {
            titles[current].classList.remove('active');
            descs[current].classList.remove('active');
            if (dots[current]) dots[current].classList.remove('active');

            current = index % total;

            titles[current].classList.add('active');
            descs[current].classList.add('active');
            if (dots[current]) dots[current].classList.add('active');
            if (heroCounter) heroCounter.textContent = (current + 1) + ' / ' + total;
        }

        function startAutoplay() {
            timer = setInterval(function() {
                goToSlide(current + 1);
            }, 4000);
        }

        // Dot click handlers
        dots.forEach(function(dot) {
            dot.addEventListener('click', function() {
                clearInterval(timer);
                goToSlide(parseInt(this.dataset.index));
                startAutoplay();
            });
        });

        if (total > 1) startAutoplay();
    }

})();
