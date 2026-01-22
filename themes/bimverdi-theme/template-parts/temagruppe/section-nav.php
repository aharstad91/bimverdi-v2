<?php
/**
 * Temagruppe Section Navigation
 *
 * Sticky horizontal navigation with anchor links to page sections.
 * Only shows links to sections that have content.
 *
 * @package BimVerdi_Theme
 *
 * @param array $args {
 *     @type array $sections Array of visible sections ['id' => 'label']
 * }
 */

if (!defined('ABSPATH')) exit;

$sections = $args['sections'] ?? [];

// Don't render if less than 2 sections
if (count($sections) < 2) {
    return;
}
?>

<nav class="sticky top-0 z-40 bg-white/95 backdrop-blur-sm border-b border-[#E5E0D8] -mx-4 px-4 mb-8" aria-label="Sidenavigasjon">
    <div class="max-w-[1280px] mx-auto">
        <ul class="flex items-center gap-1 overflow-x-auto py-3 -mb-px scrollbar-hide" role="tablist">
            <?php foreach ($sections as $id => $label) : ?>
            <li>
                <a
                    href="#<?php echo esc_attr($id); ?>"
                    class="section-nav-link inline-flex items-center px-4 py-2 text-sm font-medium text-[#5A5A5A] hover:text-[#1A1A1A] hover:bg-[#F7F5EF] rounded-lg transition-colors whitespace-nowrap"
                    data-section="<?php echo esc_attr($id); ?>"
                >
                    <?php echo esc_html($label); ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</nav>

<style>
/* Hide scrollbar but keep functionality */
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

/* Active state for section nav */
.section-nav-link.active {
    color: #1A1A1A;
    background-color: #F7F5EF;
}

/* Smooth scroll for the page */
html {
    scroll-behavior: smooth;
}

/* Offset for sticky header */
[id^="section-"] {
    scroll-margin-top: 80px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const navLinks = document.querySelectorAll('.section-nav-link');
    const sections = document.querySelectorAll('[id^="section-"]');

    // Intersection Observer for active state
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Remove active from all
                navLinks.forEach(link => link.classList.remove('active'));
                // Add active to matching link
                const activeLink = document.querySelector(`.section-nav-link[data-section="${entry.target.id}"]`);
                if (activeLink) {
                    activeLink.classList.add('active');
                    // Scroll nav to show active link
                    activeLink.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                }
            }
        });
    }, {
        rootMargin: '-100px 0px -50% 0px',
        threshold: 0
    });

    sections.forEach(section => observer.observe(section));
});
</script>
