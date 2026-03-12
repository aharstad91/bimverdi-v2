<?php
/**
 * BIMVerdi Card Component (shadcn-inspired)
 *
 * Composable card with header, content, and footer sections.
 *
 * Usage:
 *
 * <?php bimverdi_card_start(); ?>
 *     <?php bimverdi_card_header([
 *         'title'       => 'Kort-tittel',
 *         'description' => 'Beskrivelse av kortet.',
 *     ]); ?>
 *     <?php bimverdi_card_content(); ?>
 *         <p>Innhold her.</p>
 *     <?php bimverdi_card_content_end(); ?>
 *     <?php bimverdi_card_footer(); ?>
 *         <?php bimverdi_button(['text' => 'Lagre']); ?>
 *     <?php bimverdi_card_footer_end(); ?>
 * <?php bimverdi_card_end(); ?>
 *
 * // Or the simple all-in-one helper:
 * <?php bimverdi_card([
 *     'title'       => 'Tittel',
 *     'description' => 'Beskrivelse',
 *     'content'     => '<p>Innhold</p>',
 *     'footer'      => '<button>OK</button>',
 * ]); ?>
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

/**
 * Open a card wrapper
 *
 * @param array $args Card configuration
 *   - size (string) 'default' | 'sm'
 *   - class (string) Additional CSS classes
 *   - href (string) Makes the entire card a link
 * @return void
 */
function bimverdi_card_start($args = []) {
    $defaults = [
        'size'  => 'default',
        'class' => '',
        'href'  => '',
    ];
    $args = wp_parse_args($args, $defaults);

    $class = 'bv-card2';
    if ($args['size'] === 'sm') $class .= ' bv-card2--sm';
    if ($args['href']) $class .= ' bv-card2--link';
    if ($args['class']) $class .= ' ' . $args['class'];

    $tag = $args['href'] ? 'a' : 'div';
    $href_attr = $args['href'] ? ' href="' . esc_url($args['href']) . '"' : '';

    echo '<' . $tag . ' class="' . esc_attr($class) . '"' . $href_attr . '>';
}

function bimverdi_card_end() {
    echo '</div>';
}

/**
 * Render card header with title, description, and optional action
 *
 * @param array $args Header configuration
 *   - title (string)
 *   - description (string)
 *   - action (string) Raw HTML for header action area (e.g. a button or link)
 * @return void
 */
function bimverdi_card_header($args = []) {
    $defaults = [
        'title'       => '',
        'description' => '',
        'action'      => '',
    ];
    $args = wp_parse_args($args, $defaults);

    ?>
    <div class="bv-card2__header">
        <div class="bv-card2__header-text">
            <?php if ($args['title']): ?>
                <div class="bv-card2__title"><?php echo esc_html($args['title']); ?></div>
            <?php endif; ?>
            <?php if ($args['description']): ?>
                <div class="bv-card2__description"><?php echo esc_html($args['description']); ?></div>
            <?php endif; ?>
        </div>
        <?php if ($args['action']): ?>
            <div class="bv-card2__action"><?php echo wp_kses_post($args['action']); ?></div>
        <?php endif; ?>
    </div>
    <?php
}

function bimverdi_card_content() {
    echo '<div class="bv-card2__content">';
}

function bimverdi_card_content_end() {
    echo '</div>';
}

function bimverdi_card_footer($class = '') {
    $cls = 'bv-card2__footer';
    if ($class) $cls .= ' ' . $class;
    echo '<div class="' . esc_attr($cls) . '">';
}

function bimverdi_card_footer_end() {
    echo '</div>';
}

/**
 * Render a card image (typically placed before the header)
 *
 * @param array $args Image configuration
 *   - src (string) Image URL
 *   - alt (string) Alt text
 *   - class (string) Additional CSS classes
 * @return void
 */
function bimverdi_card_image($args = []) {
    $defaults = ['src' => '', 'alt' => '', 'class' => ''];
    $args = wp_parse_args($args, $defaults);
    if (!$args['src']) return;

    $class = 'bv-card2__image';
    if ($args['class']) $class .= ' ' . $args['class'];
    ?>
    <div class="<?php echo esc_attr($class); ?>">
        <img src="<?php echo esc_url($args['src']); ?>" alt="<?php echo esc_attr($args['alt']); ?>" />
    </div>
    <?php
}

/**
 * All-in-one card helper for simple cases
 *
 * @param array $args
 *   - title (string)
 *   - description (string)
 *   - content (string) HTML content
 *   - footer (string) HTML footer
 *   - image (array) ['src' => '', 'alt' => '']
 *   - action (string) Header action HTML
 *   - size (string) 'default' | 'sm'
 *   - href (string) Makes card a link
 *   - class (string)
 * @return void
 */
function bimverdi_card($args = []) {
    $defaults = [
        'title'       => '',
        'description' => '',
        'content'     => '',
        'footer'      => '',
        'image'       => [],
        'action'      => '',
        'size'        => 'default',
        'href'        => '',
        'class'       => '',
    ];
    $args = wp_parse_args($args, $defaults);

    bimverdi_card_start([
        'size'  => $args['size'],
        'href'  => $args['href'],
        'class' => $args['class'],
    ]);

    if (!empty($args['image'])) {
        bimverdi_card_image($args['image']);
    }

    if ($args['title'] || $args['description'] || $args['action']) {
        bimverdi_card_header([
            'title'       => $args['title'],
            'description' => $args['description'],
            'action'      => $args['action'],
        ]);
    }

    if ($args['content']) {
        bimverdi_card_content();
        echo wp_kses_post($args['content']);
        bimverdi_card_content_end();
    }

    if ($args['footer']) {
        bimverdi_card_footer();
        echo wp_kses_post($args['footer']);
        bimverdi_card_footer_end();
    }

    bimverdi_card_end();
}
