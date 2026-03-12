<?php
/**
 * BIMVerdi Item Component (shadcn-inspired)
 *
 * Versatile list item with media, title, description, and actions.
 *
 * Usage:
 *
 * // Icon + action button
 * <?php bimverdi_item([
 *     'icon'        => 'shield-alert',
 *     'title'       => 'Security Alert',
 *     'description' => 'New login detected from unknown device.',
 *     'action'      => ['text' => 'Review', 'variant' => 'outline'],
 *     'variant'     => 'outline',
 * ]); ?>
 *
 * // Avatar + meta
 * <?php bimverdi_item([
 *     'avatar'      => 'AH',
 *     'title'       => 'Andreas Harstad',
 *     'description' => 'Hovedkontakt',
 *     'meta'        => 'Partner',
 * ]); ?>
 *
 * // Clickable link
 * <?php bimverdi_item([
 *     'icon'        => 'wrench',
 *     'title'       => 'Solibri Office',
 *     'description' => 'Modellsjekk og kvalitetskontroll',
 *     'href'        => '/verktoy/solibri/',
 * ]); ?>
 *
 * @package BimVerdi_Theme
 */

if (!defined('ABSPATH')) exit;

/**
 * Render an item component
 *
 * @param array $args Item configuration
 *   - icon (string) Lucide icon name for media area
 *   - avatar (string) Initials for avatar circle
 *   - avatar_color (string) Avatar background color (default #18181B)
 *   - title (string) Primary text
 *   - description (string) Secondary text
 *   - meta (string) Right-side meta text
 *   - action (array) CTA button args — passed to bimverdi_button()
 *   - badge (array) Badge args — passed to bimverdi_badge()
 *   - href (string) Makes entire item a link (adds chevron-right)
 *   - variant (string) 'default' | 'outline' | 'muted'
 *   - size (string) 'default' | 'sm'
 *   - class (string) Additional CSS classes
 * @return void
 */
function bimverdi_item($args = []) {
    $defaults = [
        'icon'         => '',
        'avatar'       => '',
        'avatar_src'   => '',
        'avatar_color' => '#18181B',
        'avatar_badge' => '',
        'title'        => '',
        'description'  => '',
        'meta'         => '',
        'action'       => [],
        'badge'        => [],
        'href'         => '',
        'variant'      => 'default',
        'size'         => 'default',
        'class'        => '',
    ];

    $args = wp_parse_args($args, $defaults);

    $wrapper_class = 'bv-item';
    $wrapper_class .= ' bv-item--' . $args['variant'];
    if ($args['size'] === 'sm') $wrapper_class .= ' bv-item--sm';
    if ($args['href']) $wrapper_class .= ' bv-item--link';
    if ($args['class']) $wrapper_class .= ' ' . $args['class'];

    $tag = $args['href'] ? 'a' : 'div';
    $href_attr = $args['href'] ? ' href="' . esc_url($args['href']) . '"' : '';

    ?>
    <<?php echo $tag; ?> class="<?php echo esc_attr($wrapper_class); ?>"<?php echo $href_attr; ?>>
        <?php if ($args['icon']): ?>
            <div class="bv-item__media bv-item__media--icon">
                <?php echo bimverdi_icon($args['icon'], $args['size'] === 'sm' ? 16 : 18); ?>
            </div>
        <?php elseif ($args['avatar'] || $args['avatar_src']): ?>
            <?php
            bimverdi_avatar([
                'src'      => $args['avatar_src'],
                'initials' => $args['avatar'],
                'color'    => $args['avatar_color'],
                'badge'    => $args['avatar_badge'],
                'size'     => $args['size'] === 'sm' ? 'sm' : 'default',
                'alt'      => $args['title'],
            ]);
            ?>
        <?php endif; ?>

        <div class="bv-item__content">
            <?php if ($args['title']): ?>
                <div class="bv-item__title"><?php echo esc_html($args['title']); ?></div>
            <?php endif; ?>
            <?php if ($args['description']): ?>
                <div class="bv-item__description"><?php echo esc_html($args['description']); ?></div>
            <?php endif; ?>
        </div>

        <?php if (!empty($args['badge'])): ?>
            <?php bimverdi_badge($args['badge']); ?>
        <?php endif; ?>

        <?php if ($args['meta']): ?>
            <div class="bv-item__meta"><?php echo esc_html($args['meta']); ?></div>
        <?php endif; ?>

        <?php if (!empty($args['action'])): ?>
            <div class="bv-item__actions">
                <?php bimverdi_button($args['action']); ?>
            </div>
        <?php endif; ?>

        <?php if ($args['href']): ?>
            <div class="bv-item__chevron">
                <?php echo bimverdi_icon('chevron-right', 16); ?>
            </div>
        <?php endif; ?>
    </<?php echo $tag; ?>>
    <?php
}

/**
 * Open an item group (adds separators between items)
 *
 * @param string $variant 'default' | 'outline' | 'muted'
 */
function bimverdi_item_group($variant = 'default') {
    $class = 'bv-item-group';
    if ($variant === 'outline') $class .= ' bv-item-group--outline';
    if ($variant === 'muted') $class .= ' bv-item-group--muted';
    echo '<div class="' . esc_attr($class) . '">';
}

function bimverdi_item_group_end() {
    echo '</div>';
}
