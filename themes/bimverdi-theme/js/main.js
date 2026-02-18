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
        mobileMenuButton.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
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

    // Hero rotating text
    const rotatingContainer = document.getElementById('hero-rotating');
    if (rotatingContainer) {
        const words = rotatingContainer.querySelectorAll('span');
        if (words.length > 1) {
            let current = 0;
            setInterval(function() {
                words[current].classList.remove('active');
                current = (current + 1) % words.length;
                words[current].classList.add('active');
            }, 3000);
        }
    }

})();
