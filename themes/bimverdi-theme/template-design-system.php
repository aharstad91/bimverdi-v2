<?php
/**
 * Template Name: Design System
 * Description: Living design system page — dev-only reference
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) {
    exit;
}

// Dev-only gate — redirect to home if WP_DEBUG is off
if (!defined('WP_DEBUG') || !WP_DEBUG) {
    wp_redirect(home_url('/'));
    exit;
}

get_header();
?>

<style>
/* Design System page layout */
.ds-dev-banner {
    background: #FEF3C7;
    border-bottom: 1px solid #FDE68A;
    padding: 8px 16px;
    text-align: center;
    font-size: 13px;
    color: #92400E;
}

.ds-page {
    display: flex;
    min-height: 100vh;
    background: #fff;
}

.ds-sidebar {
    width: 220px;
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
    padding: 32px 16px;
    border-right: 1px solid #E7E5E4;
    background: #FAFAF9;
    flex-shrink: 0;
}

.ds-sidebar__title {
    font-size: 14px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #888;
    margin-bottom: 16px;
}

.ds-sidebar__link {
    display: block;
    padding: 6px 12px;
    font-size: 13px;
    color: #5A5A5A;
    text-decoration: none;
    border-radius: 6px;
    margin-bottom: 2px;
    transition: background 0.15s, color 0.15s;
}

.ds-sidebar__link:hover {
    background: #F5F5F4;
    color: #1A1A1A;
}

.ds-sidebar__link--active {
    background: #FFF3ED;
    color: #FF8B5E;
    font-weight: 500;
}

.ds-content {
    flex: 1;
    padding: 48px 64px;
}

.ds-section {
    padding: 48px 0;
    border-bottom: 1px solid #E7E5E4;
}

.ds-section:first-child {
    padding-top: 0;
}

.ds-section__title {
    font-size: 1.5rem;
    font-weight: 300;
    letter-spacing: -0.02em;
    color: #1A1A1A;
    margin-bottom: 8px;
}

.ds-section__desc {
    color: #5A5A5A;
    font-size: 0.9375rem;
    margin-bottom: 32px;
}
</style>

<div class="ds-dev-banner">
    Denne siden er kun synlig i utviklingsmiljøet (WP_DEBUG = true)
</div>

<div class="ds-page">

    <!-- Sticky sidebar navigation -->
    <nav class="ds-sidebar" role="navigation" aria-label="Design system navigation">
        <div class="ds-sidebar__title">Design System</div>

        <?php
        $ds_sections = [
            'farger'            => 'Farger',
            'typografi'         => 'Typografi',
            'spacing'           => 'Spacing',
            'borders-shadows'   => 'Borders & Shadows',
            'knapper'           => 'Knapper',
            'breadcrumb'        => 'Breadcrumb',
            'field'             => 'Field',
            'badges'            => 'Badges',
            'switch'            => 'Switch',
            'tabs'              => 'Tabs',
            'navigation-menu'   => 'Navigation Menu',
            'table'             => 'Table',
            'pagination'        => 'Pagination',
            'avatar'            => 'Avatar',
            'accordion'         => 'Accordion',
            'card'              => 'Card',
            'alert'             => 'Alert',
            'ikoner'            => 'Ikoner',
            'skjema'            => 'Skjema',
            'kort'              => 'Kort',
            'section-headers'   => 'Section Headers',
            'list-items'        => 'Item',
            'empty-states'      => 'Empty States',
            'layout'            => 'Layout',
        ];

        foreach ($ds_sections as $id => $label) :
        ?>
            <a href="#<?php echo esc_attr($id); ?>"
               class="ds-sidebar__link"
               data-ds-nav="<?php echo esc_attr($id); ?>">
                <?php echo esc_html($label); ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Main content area -->
    <main class="ds-content">

        <section id="farger" class="ds-section">
            <?php get_template_part('parts/design-system/tokens-colors'); ?>
        </section>

        <section id="typografi" class="ds-section">
            <?php get_template_part('parts/design-system/tokens-typography'); ?>
        </section>

        <section id="spacing" class="ds-section">
            <?php get_template_part('parts/design-system/tokens-spacing'); ?>
        </section>

        <section id="borders-shadows" class="ds-section">
            <?php get_template_part('parts/design-system/tokens-borders-shadows'); ?>
        </section>

        <section id="knapper" class="ds-section">
            <?php get_template_part('parts/design-system/components-buttons'); ?>
        </section>

        <section id="breadcrumb" class="ds-section">
            <?php get_template_part('parts/design-system/components-breadcrumb'); ?>
        </section>

        <section id="field" class="ds-section">
            <?php get_template_part('parts/design-system/components-field'); ?>
        </section>

        <section id="badges" class="ds-section">
            <?php get_template_part('parts/design-system/components-badges'); ?>
        </section>

        <section id="switch" class="ds-section">
            <?php get_template_part('parts/design-system/components-switch'); ?>
        </section>

        <section id="tabs" class="ds-section">
            <?php get_template_part('parts/design-system/components-tabs'); ?>
        </section>

        <section id="navigation-menu" class="ds-section">
            <?php get_template_part('parts/design-system/components-navigation-menu'); ?>
        </section>

        <section id="table" class="ds-section">
            <?php get_template_part('parts/design-system/components-table'); ?>
        </section>

        <section id="pagination" class="ds-section">
            <?php get_template_part('parts/design-system/components-pagination'); ?>
        </section>

        <section id="avatar" class="ds-section">
            <?php get_template_part('parts/design-system/components-avatar'); ?>
        </section>

        <section id="accordion" class="ds-section">
            <?php get_template_part('parts/design-system/components-accordion'); ?>
        </section>

        <section id="card" class="ds-section">
            <?php get_template_part('parts/design-system/components-card'); ?>
        </section>

        <section id="alert" class="ds-section">
            <?php get_template_part('parts/design-system/components-alert'); ?>
        </section>

        <section id="ikoner" class="ds-section">
            <?php get_template_part('parts/design-system/components-icons'); ?>
        </section>

        <section id="skjema" class="ds-section">
            <?php get_template_part('parts/design-system/components-forms'); ?>
        </section>

        <section id="kort" class="ds-section">
            <?php get_template_part('parts/design-system/compound-cards'); ?>
        </section>

        <section id="section-headers" class="ds-section">
            <?php get_template_part('parts/design-system/compound-section-headers'); ?>
        </section>

        <section id="list-items" class="ds-section">
            <?php get_template_part('parts/design-system/compound-list-items'); ?>
        </section>

        <section id="empty-states" class="ds-section">
            <?php get_template_part('parts/design-system/compound-empty-states'); ?>
        </section>

        <section id="layout" class="ds-section">
            <?php get_template_part('parts/design-system/layout-patterns'); ?>
        </section>

    </main>

</div>

<script>
(function() {
    // IntersectionObserver to highlight active nav link
    const sections = document.querySelectorAll('.ds-section');
    const navLinks = document.querySelectorAll('.ds-sidebar__link');

    if (!sections.length || !navLinks.length) return;

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                const id = entry.target.getAttribute('id');
                navLinks.forEach(function(link) {
                    if (link.getAttribute('data-ds-nav') === id) {
                        link.classList.add('ds-sidebar__link--active');
                    } else {
                        link.classList.remove('ds-sidebar__link--active');
                    }
                });
            }
        });
    }, {
        rootMargin: '-20% 0px -70% 0px',
        threshold: 0
    });

    sections.forEach(function(section) {
        observer.observe(section);
    });
})();
</script>

<?php get_footer(); ?>
